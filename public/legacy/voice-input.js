/**
 * voice-input.js - Reusable Native Web Speech-to-Text Module
 * Provides voice input capability for all text, number, date, and other inputs & textareas (except selections).
 */
(function (window, document) {
    'use strict';

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition || null;

    /**
     * Converts spoken numbers (in English or Persian words/digits) to a clean numeric string.
     */
    function convertSpokenToNumber(spoken) {
        if (!spoken) return '';

        // Convert Persian and Arabic digits to ASCII 0-9
        let text = spoken
            .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
            .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
            .toLowerCase()
            .trim();

        // Map common words
        const wordMap = {
            'zero': '0', 'one': '1', 'two': '2', 'three': '3', 'four': '4',
            'five': '5', 'six': '6', 'seven': '7', 'eight': '8', 'nine': '9',
            'ten': '10', 'eleven': '11', 'twelve': '12', 'thirteen': '13',
            'fourteen': '14', 'fifteen': '15', 'sixteen': '16', 'seventeen': '17',
            'eighteen': '18', 'nineteen': '19', 'twenty': '20', 'thirty': '30',
            'forty': '40', 'fifty': '50', 'sixty': '60', 'seventy': '70',
            'eighty': '80', 'ninety': '90', 'hundred': '00', 'thousand': '000',
            'point': '.', 'dot': '.', 'minus': '-', 'negative': '-',
            // Persian words
            'صفر': '0', 'یک': '1', 'دو': '2', 'سه': '3', 'چهار': '4',
            'پنج': '5', 'شش': '6', 'هفت': '7', 'هشت': '8', 'نه': '9',
            'ده': '10', 'یازده': '11', 'دوازده': '12', 'سیزده': '13',
            'چهارده': '14', 'پانزده': '15', 'شانزده': '16', 'هفده': '17',
            'هجده': '18', 'نوزده': '19', 'بیست': '20', 'سی': '30',
            'چهل': '40', 'پنجاه': '50', 'شصت': '60', 'هفتاد': '70',
            'هشتاد': '80', 'نود': '90', 'صد': '100', 'هزار': '1000',
            'ممیز': '.', 'نقطه': '.', 'منفی': '-'
        };

        // If text directly contains numeric pattern, extract it
        const directMatch = text.match(/[-+]?\d+(?:\.\d+)?/);
        if (directMatch) {
            return directMatch[0];
        }

        // Replace words
        const words = text.split(/\s+/);
        let numStr = '';
        for (const w of words) {
            if (wordMap[w] !== undefined) {
                numStr += wordMap[w];
            } else if (/^[-+]?\d+(?:\.\d+)?$/.test(w)) {
                numStr += w;
            }
        }

        const match = numStr.match(/[-+]?\d*(?:\.\d+)?/);
        return match && match[0] ? match[0] : numStr.replace(/[^\d.-]/g, '');
    }

    class VoiceInputManager {
        constructor() {
            this.isSupported = !!SpeechRecognition;
            this.recognition = null;
            this.activeElement = null;
            this.activeBtn = null;
            this.isRecording = false;
            this.userExplicitStop = false;
            
            // Default language is English (en-US) for medical reporting, with easy toggle to Persian (fa-IR)
            this.currentLang = localStorage.getItem('echo_voice_lang') || 'en-US';
            
            // Baseline text before voice transcription starts in the active session
            this.sessionPrefix = '';
            this.sessionSuffix = '';
            this.finalAccumulatedText = '';

            // Debounce timer for DOM observation
            this._debounceTimer = null;

            this.init();
        }

        init() {
            const onReady = () => {
                this.createUIElements();
                this.scanAndAttach();
                this.observeDOM();
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', onReady);
            } else {
                onReady();
            }

            window.addEventListener('load', () => {
                this.scanAndAttach();
            });

            // Global escape key listener to stop recording
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isRecording) {
                    this.stop();
                }
            });
        }

        createUIElements() {
            if (!document.body) return;

            // 1. Floating Recording Status Pill
            if (!document.getElementById('voice-status-pill')) {
                const pill = document.createElement('div');
                pill.id = 'voice-status-pill';
                pill.className = 'no-print';
                pill.innerHTML = `
                    <div class="voice-waveform">
                        <div class="voice-waveform-bar"></div>
                        <div class="voice-waveform-bar"></div>
                        <div class="voice-waveform-bar"></div>
                        <div class="voice-waveform-bar"></div>
                        <div class="voice-waveform-bar"></div>
                    </div>
                    <span id="voice-status-text">Listening...</span>
                    <button type="button" class="voice-lang-btn" id="voice-lang-toggle" title="Switch recognition language">
                        ${this.currentLang === 'fa-IR' ? '🇮🇷 FA' : '🇺🇸 EN'}
                    </button>
                    <button type="button" class="voice-stop-btn" id="voice-stop-action" title="Stop recording (Esc)">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                        Stop
                    </button>
                `;
                document.body.appendChild(pill);

                // Wire pill actions
                const langBtn = pill.querySelector('#voice-lang-toggle');
                if (langBtn) {
                    langBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        const nextLang = this.currentLang === 'en-US' ? 'fa-IR' : 'en-US';
                        this.setLanguage(nextLang);
                    });
                }

                const stopBtn = pill.querySelector('#voice-stop-action');
                if (stopBtn) {
                    stopBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        e.preventDefault();
                        this.stop();
                    });
                }
            }

            // 2. Notification Toast
            if (!document.getElementById('voice-toast')) {
                const toast = document.createElement('div');
                toast.id = 'voice-toast';
                toast.className = 'voice-toast no-print';
                document.body.appendChild(toast);
            }
        }

        showToast(message, type = 'error', duration = 4000) {
            this.createUIElements();
            const toast = document.getElementById('voice-toast');
            if (!toast) return;

            toast.className = `voice-toast no-print show ${type}`;
            toast.textContent = message;

            if (this._toastTimer) clearTimeout(this._toastTimer);
            this._toastTimer = setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }

        setLanguage(lang) {
            this.currentLang = lang;
            localStorage.setItem('echo_voice_lang', lang);

            const langBtn = document.getElementById('voice-lang-toggle');
            if (langBtn) {
                langBtn.textContent = lang === 'fa-IR' ? '🇮🇷 FA' : '🇺🇸 EN';
            }

            const langName = lang === 'fa-IR' ? 'Persian (fa-IR)' : 'English (en-US)';
            this.showToast(`Voice language set to ${langName}`, 'info', 2000);

            // If actively recording, restart recognition with the new language
            if (this.isRecording && this.activeElement && this.activeBtn) {
                const targetEl = this.activeElement;
                const targetBtn = this.activeBtn;
                this.stop();
                setTimeout(() => {
                    this.start(targetEl, targetBtn);
                }, 200);
            }
        }

        isDateField(el) {
            if (!el) return false;
            const type = (el.type || '').toLowerCase();
            const dateTypes = ['date', 'datetime-local', 'time', 'month', 'week'];
            if (dateTypes.includes(type)) {
                return true;
            }
            const id = (el.id || '').toLowerCase();
            const name = (el.name || '').toLowerCase();
            const className = (el.className || '').toLowerCase();

            // Check id or name for date/dob keywords
            if (id === 'dob' || name === 'dob' || id.includes('date') || name.includes('date') || id.includes('birth') || name.includes('birth')) {
                return true;
            }
            if (className.includes('date') || className.includes('datepicker') || className.includes('pdp')) {
                return true;
            }
            if (el.hasAttribute('pdp-id') || el.classList.contains('pdp-el')) {
                return true;
            }

            // Check associated or parent label
            if (el.labels && el.labels.length > 0) {
                for (let i = 0; i < el.labels.length; i++) {
                    const lText = (el.labels[i].textContent || '').toLowerCase();
                    if (lText.includes('date') || lText.includes('dob') || lText.includes('تاریخ') || lText.includes('تولد')) {
                        return true;
                    }
                }
            }
            const fieldParent = el.closest('.field');
            if (fieldParent) {
                const parentLabel = fieldParent.querySelector('label');
                if (parentLabel) {
                    const lText = (parentLabel.textContent || '').toLowerCase();
                    if (lText.includes('date') || lText.includes('dob') || lText.includes('تاریخ') || lText.includes('تولد')) {
                        return true;
                    }
                }
            }

            return false;
        }

        isEligible(el) {
            if (!el || el.dataset.voiceAttached === 'true') return false;
            
            // Skip if readonly, disabled, explicitly opted-out, or inside navbar
            if (el.readOnly || el.disabled || el.classList.contains('no-voice') || el.closest('.app-navbar') || el.closest('.navbar-search-inline')) return false;

            // Completely remove voice input from dates
            if (this.isDateField(el)) return false;

            const tagName = el.tagName.toLowerCase();
            
            // Textareas are eligible
            if (tagName === 'textarea') {
                return true;
            }

            // All inputs EXCEPT selections (select, checkbox, radio), buttons, hidden, and file inputs
            if (tagName === 'input') {
                const type = (el.type || 'text').toLowerCase();
                const excludedTypes = [
                    'checkbox',
                    'radio',
                    'button',
                    'submit',
                    'reset',
                    'image',
                    'hidden',
                    'file',
                    'range',
                    'color',
                    'date',
                    'datetime-local',
                    'time',
                    'month',
                    'week'
                ];
                
                return !excludedTypes.includes(type);
            }

            return false;
        }

        scanAndAttach(root = document) {
            if (!root || !root.querySelectorAll) return;
            const elements = root.querySelectorAll('input, textarea');
            elements.forEach((el) => {
                if (this.isEligible(el)) {
                    this.attach(el);
                }
            });
        }

        observeDOM() {
            if (!document.body) return;
            const observer = new MutationObserver((mutations) => {
                let shouldScan = false;
                for (const mutation of mutations) {
                    if (mutation.addedNodes.length > 0) {
                        for (const node of mutation.addedNodes) {
                            if (node.nodeType === Node.ELEMENT_NODE) {
                                if (node.matches && (node.matches('input, textarea') || node.querySelector('input, textarea'))) {
                                    shouldScan = true;
                                    break;
                                }
                            }
                        }
                    }
                    if (shouldScan) break;
                }

                if (shouldScan) {
                    if (this._debounceTimer) clearTimeout(this._debounceTimer);
                    this._debounceTimer = setTimeout(() => {
                        this.scanAndAttach();
                    }, 120);
                }
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        attach(el) {
            if (!el || el.dataset.voiceAttached === 'true') return;
            el.dataset.voiceAttached = 'true';

            const isTextarea = el.tagName.toLowerCase() === 'textarea';

            // Wrap element in .voice-input-wrapper if not already inside one
            let wrapper = el.parentElement;
            if (!wrapper || !wrapper.classList.contains('voice-input-wrapper')) {
                wrapper = document.createElement('div');
                wrapper.className = 'voice-input-wrapper no-print-wrap';
                
                // Transfer relevant layout styles if el has inline flex/width
                if (el.style.width && el.style.width !== '100%') {
                    wrapper.style.width = el.style.width;
                }
                
                el.parentNode.insertBefore(wrapper, el);
                wrapper.appendChild(el);
            }

            el.classList.add('voice-input-field');

            // Create mic button
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.tabIndex = -1; // Keep natural tab indexing
            btn.className = `voice-mic-btn no-print ${isTextarea ? 'textarea-mic' : 'input-mic'}`;
            
            if (!this.isSupported) {
                btn.classList.add('unsupported');
                btn.title = 'Voice input is not supported in this browser (Use Chrome/Edge/Safari)';
                btn.setAttribute('aria-label', 'Voice input unsupported');
            } else {
                btn.title = 'Voice input (Click to speak)';
                btn.setAttribute('aria-label', 'Voice input');
            }

            btn.innerHTML = `
                <svg viewBox="0 0 24 24">
                    <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"></path>
                    <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                    <line x1="12" y1="19" x2="12" y2="23"></line>
                    <line x1="8" y1="23" x2="16" y2="23"></line>
                </svg>
            `;

            // Track selected/focused state for element
            if (document.activeElement === el) {
                wrapper.classList.add('field-focused');
            }
            el.addEventListener('focus', () => {
                wrapper.classList.add('field-focused');
            });
            el.addEventListener('blur', () => {
                if (!this.isRecording || this.activeElement !== el) {
                    wrapper.classList.remove('field-focused');
                }
            });

            // Prevent input from losing focus when clicking the microphone button
            btn.addEventListener('mousedown', (e) => {
                e.preventDefault();
            });

            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                el.focus();
                this.toggle(el, btn);
            });

            wrapper.appendChild(btn);
        }

        toggle(el, btn) {
            if (!this.isSupported) {
                this.showToast('Voice input (Web Speech API) is not supported in this browser. Please use Chrome, Edge, or Safari.', 'warning');
                return;
            }

            if (this.isRecording && this.activeElement === el) {
                this.stop();
            } else {
                if (this.isRecording) {
                    this.stop();
                }
                this.start(el, btn);
            }
        }

        start(el, btn) {
            if (!this.isSupported || !el || !btn) return;

            try {
                this.recognition = new SpeechRecognition();
                this.recognition.continuous = true;
                this.recognition.interimResults = true;
                this.recognition.maxAlternatives = 1;
                this.recognition.lang = this.currentLang;

                this.activeElement = el;
                this.activeBtn = btn;
                this.userExplicitStop = false;
                this.finalAccumulatedText = '';

                // Calculate prefix and suffix based on current cursor position or end of text
                const currentVal = el.value || '';
                const startPos = typeof el.selectionStart === 'number' ? el.selectionStart : currentVal.length;
                const endPos = typeof el.selectionEnd === 'number' ? el.selectionEnd : currentVal.length;

                this.sessionPrefix = currentVal.substring(0, startPos);
                this.sessionSuffix = currentVal.substring(endPos);

                // Smart spacing for text inputs
                if (el.type !== 'number' && this.sessionPrefix && !/\s$/.test(this.sessionPrefix)) {
                    this.sessionPrefix += ' ';
                }

                this.recognition.onstart = () => {
                    this.isRecording = true;
                    this.activeBtn.classList.add('recording');
                    this.activeBtn.title = 'Recording voice... (Click to stop)';
                    this.activeElement.classList.add('voice-recording-field');

                    const statusPill = document.getElementById('voice-status-pill');
                    const statusText = document.getElementById('voice-status-text');
                    if (statusPill) {
                        if (statusText) {
                            const langLabel = this.currentLang === 'fa-IR' ? 'Persian' : 'English';
                            statusText.textContent = `Listening (${langLabel})...`;
                        }
                        statusPill.classList.add('visible');
                    }
                };

                this.recognition.onresult = (event) => {
                    let interimTranscript = '';
                    let finalTranscript = '';

                    for (let i = event.resultIndex; i < event.results.length; ++i) {
                        const transcript = event.results[i][0].transcript;
                        if (event.results[i].isFinal) {
                            finalTranscript += transcript;
                        } else {
                            interimTranscript += transcript;
                        }
                    }

                    if (finalTranscript) {
                        if (this.activeElement.type !== 'number' && this.finalAccumulatedText && !/\s$/.test(this.finalAccumulatedText) && !/^\s/.test(finalTranscript)) {
                            this.finalAccumulatedText += ' ';
                        }
                        this.finalAccumulatedText += finalTranscript;
                    }

                    // Combined current spoken text in this session
                    let currentSpoken = this.finalAccumulatedText;
                    if (interimTranscript) {
                        if (this.activeElement.type !== 'number' && currentSpoken && !/\s$/.test(currentSpoken) && !/^\s/.test(interimTranscript)) {
                            currentSpoken += ' ';
                        }
                        currentSpoken += interimTranscript;
                    }

                    // For number inputs, parse spoken number intelligently
                    if (this.activeElement.type === 'number') {
                        const parsedNum = convertSpokenToNumber(currentSpoken);
                        if (parsedNum !== '') {
                            this.activeElement.value = parsedNum;
                        }
                    } else {
                        // Standard text / date / time / search / textarea
                        const updatedValue = this.sessionPrefix + currentSpoken + this.sessionSuffix;
                        this.activeElement.value = updatedValue;

                        const newCursorPos = (this.sessionPrefix + currentSpoken).length;
                        if (typeof this.activeElement.setSelectionRange === 'function') {
                            try {
                                this.activeElement.setSelectionRange(newCursorPos, newCursorPos);
                            } catch (ignore) {}
                        }
                    }

                    // Auto-scroll textarea if applicable
                    if (this.activeElement.tagName.toLowerCase() === 'textarea') {
                        this.activeElement.scrollTop = this.activeElement.scrollHeight;
                    }

                    // Dispatch input and change events to trigger form autosave, summaries, and preview updates
                    this.activeElement.dispatchEvent(new Event('input', { bubbles: true }));
                };

                this.recognition.onerror = (event) => {
                    console.warn('[VoiceInput] Speech recognition error:', event.error);

                    switch (event.error) {
                        case 'not-allowed':
                        case 'service-not-allowed':
                            this.showToast('Microphone access denied. Please enable microphone permissions in your browser settings.', 'error', 5000);
                            break;
                        case 'audio-capture':
                            this.showToast('No microphone found or audio capture failed.', 'error');
                            break;
                        case 'network':
                            this.showToast('Network error during speech recognition. Please check your connection.', 'error');
                            break;
                        case 'no-speech':
                            // Quietly handled
                            break;
                        default:
                            if (event.error !== 'aborted') {
                                this.showToast(`Voice recognition error: ${event.error}`, 'warning');
                            }
                            break;
                    }

                    this.stop(true);
                };

                this.recognition.onend = () => {
                    // Cleanly end the active session and dispatch change event
                    this.cleanupSession();
                };

                this.recognition.start();
                this.activeElement.focus();

            } catch (err) {
                console.error('[VoiceInput] Failed to start recognition:', err);
                this.showToast('Could not start speech recognition. ' + (err.message || ''), 'error');
                this.cleanupSession();
            }
        }

        stop(isError = false) {
            this.userExplicitStop = true;
            if (this.recognition) {
                try {
                    this.recognition.stop();
                } catch (e) {
                    try {
                        this.recognition.abort();
                    } catch (ignore) {}
                }
            }
            this.cleanupSession();
        }

        cleanupSession() {
            this.isRecording = false;

            if (this.activeBtn) {
                this.activeBtn.classList.remove('recording');
                this.activeBtn.title = 'Voice input (Click to speak)';
            }

            if (this.activeElement) {
                this.activeElement.classList.remove('voice-recording-field');
                const activeWrapper = this.activeElement.closest('.voice-input-wrapper');
                if (activeWrapper && document.activeElement !== this.activeElement) {
                    activeWrapper.classList.remove('field-focused');
                }
                // Trigger change event to finalize autosave and updates
                this.activeElement.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const statusPill = document.getElementById('voice-status-pill');
            if (statusPill) {
                statusPill.classList.remove('visible');
            }

            this.activeElement = null;
            this.activeBtn = null;
            this.recognition = null;
        }
    }

    // Expose singleton to window
    window.VoiceInput = new VoiceInputManager();

})(window, document);
