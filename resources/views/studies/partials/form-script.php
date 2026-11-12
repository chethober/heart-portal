<?php

use App\Models\EchoStudy;

/**
 * Behaviour for the echo study form (studies/form.blade.php). Kept as plain PHP rather than
 * Blade so the script's braces and @ signs are never treated as Blade syntax.
 *
 * @var EchoStudy|null $study  Set when editing a saved study.
 */
$study ??= null;
?>
  <script>
    const ECHO_ROUTES = <?= json_encode([
        'initialPatient' => $initialPatient ?? null,
        // Saving closes the study's taskbar tab.
        'store' => route('studies.store', array_filter(['context' => request('context')])),
        'preview' => route('studies.preview'),
        'storageScope' => auth()->user()->hospital_id.':'.auth()->id().':'.($patientContext['patient_id'] ?? 'legacy'),
        'edit' => $study ? [
            'url' => route('studies.update', $study),
            'data' => $study->toFormArray(),
        ] : null,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;
  </script>
  <script>
    // -- Persian Custom Alert Handler
    function showCustomAlert(type, title, message, buttonText, onClose) {
      const modal = document.getElementById('customAlertModal');
      const icon = document.getElementById('modalIcon');
      const titleEl = document.getElementById('modalTitle');
      const messageEl = document.getElementById('modalMessage');
      const closeBtn = document.getElementById('modalCloseBtn');
      
      // Set content
      titleEl.textContent = title;
      messageEl.innerHTML = message;
      closeBtn.textContent = buttonText || 'تایید';
      
      // Set type-specific classes
      icon.className = 'modal-icon ' + type;
      closeBtn.className = 'modal-btn ' + type + '-btn';
      
      // Show dialog
      modal.showModal();
      
      // Animate open
      requestAnimationFrame(() => {
        modal.style.opacity = '1';
        modal.style.transform = 'scale(1)';
      });
      
      const closeHandler = () => {
        // Animate close
        modal.style.opacity = '0';
        modal.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
          modal.close();
          if (onClose) onClose();
        }, 200);
        
        closeBtn.removeEventListener('click', closeHandler);
        modal.removeEventListener('cancel', cancelHandler);
      };
      
      const cancelHandler = (e) => {
        e.preventDefault();
        closeHandler();
      };
      
      closeBtn.addEventListener('click', closeHandler);
      modal.addEventListener('cancel', cancelHandler);
      
      // Close on backdrop click
      const backdropClickHandler = (e) => {
        if (e.target === modal) {
          closeHandler();
          modal.removeEventListener('click', backdropClickHandler);
        }
      };
      modal.addEventListener('click', backdropClickHandler);
    }
    // -- helper for determining if any field in a section has been filled
    function isSectionFilled(section) {
      const inputs = section.querySelectorAll('input, select, textarea');
      for (let input of inputs) {
        if (input.type === 'button' || input.type === 'submit' || input.type === 'reset') continue;

        if (input.type === 'checkbox' || input.type === 'radio') {
          if (input.checked) return true;
        } else {
          const val = input.value.trim();
          if (val !== '' && val.toLowerCase() !== 'none' && val.toLowerCase() !== 'auto' && val !== '-1') {
            return true;
          }
        }
      }
      return false;
    }

    // -- helper for updating navigation button states
    function updateNavButtonStates() {
      const buttons = document.querySelectorAll('.section-nav-btn');
      buttons.forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const section = document.getElementById(targetId);
        if (!section) return;

        btn.classList.remove('status-unfilled', 'status-filled');

        // Note: 'active' class (blue) is styled via CSS to override unfilled/filled colors.
        // So we can compute and apply the status classes regardless, and CSS handles active precedence.
        const filled = isSectionFilled(section);
        if (filled) {
          btn.classList.add('status-filled');
        } else {
          btn.classList.add('status-unfilled');
        }
      });
    }

    // -- helper for switching active form sections via button navigation
    function initSectionNav() {
      const navButtons = document.querySelectorAll('.section-nav-btn');
      const sections = document.querySelectorAll('.form-section');

      navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.getAttribute('data-target');

          // Update active button state
          navButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');

          // Update active section state
          sections.forEach(sec => {
            if (sec.id === targetId) {
              sec.classList.add('active');
            } else {
              sec.classList.remove('active');
            }
          });

          // Refresh status colors when tab is changed
          updateNavButtonStates();
        });
      });

      // Initial update
      updateNavButtonStates();
    }

    // -- helper for show/hide linked fields
    const linkMap = [
      { src: 'Echo_indication', val: 'Other', target: 'other_Echo_indication_wrap' },
      { src: 'Rhythm', val: 'Other', target: 'other_Rhythm_wrap' },
      { src: 'Echo_type', val: 'Other', target: 'other_Echo_type_wrap' },
      { src: 'lv_Hypertrophy', val: 'Others', target: 'lv_others_wrap' },
      { src: 'lv_Hypertrophy', val: 'Asymmetric LVH with SAM and LVOT obstruction', target: 'PPG_wrap' },
      { src: 'lv_clot', val: 'Other', target: 'other_clot_wrap_lv' },
      { src: 'la_clot', val: '1', target: 'other_clot_wrap_la' },
      { src: 'rv_Hypertrophy', val: 'Other', target: 'rv_other_hyper_wrap' },
    ];

    function applyLinks() {
      linkMap.forEach(m => {
        const src = document.getElementById(m.src);
        const target = document.getElementById(m.target);
        if (!src || !target) return;

        // Check if values match
        const show = String(src.value) === String(m.val);
        target.classList.toggle('hidden', !show);
      });
    }

    // attach listeners
    linkMap.forEach(m => {
      const s = document.getElementById(m.src);
      if (s) s.addEventListener('change', applyLinks);
    });

    // multi-patient auto-save: save to localStorage per patient tab
    const form = document.getElementById('echoForm');
    const STORAGE_PATIENTS_KEY = 'echoForm_patients_v2:' + ECHO_ROUTES.storageScope;
    const STORAGE_ACTIVE_KEY = 'echoForm_active_patient_v2:' + ECHO_ROUTES.storageScope;

    function getPatientsStore() {
      try {
        const raw = localStorage.getItem(STORAGE_PATIENTS_KEY);
        if (!raw) return {};
        return JSON.parse(raw) || {};
      } catch (e) {
        return {};
      }
    }

    function savePatientsStore(store) {
      try {
        localStorage.setItem(STORAGE_PATIENTS_KEY, JSON.stringify(store));
      } catch (e) {}
    }

    function getActivePatientId() {
      return localStorage.getItem(STORAGE_ACTIVE_KEY) || 'p_default';
    }

    function setActivePatientId(id) {
      localStorage.setItem(STORAGE_ACTIVE_KEY, id);
    }

    function generatePatientId() {
      return 'p_' + Date.now() + '_' + Math.random().toString(36).substr(2, 4);
    }

    function initMultiPatientStore() {
      let store = getPatientsStore();
      // Migration from old single-patient autosave if exists
      const oldRaw = localStorage.getItem('echoForm_autosave_v1');
      if (!ECHO_ROUTES.initialPatient && oldRaw && Object.keys(store).length === 0) {
        try {
          const oldData = JSON.parse(oldRaw);
          const defaultId = 'p_default';
          store[defaultId] = {
            id: defaultId,
            title: (oldData.first_name || oldData.last_name) ? `${oldData.first_name || ''} ${oldData.last_name || ''}`.trim() : 'Patient 1',
            data: oldData,
            updatedAt: Date.now()
          };
          savePatientsStore(store);
          setActivePatientId(defaultId);
        } catch (e) {}
      }

      if (Object.keys(store).length === 0) {
        const defaultId = 'p_default';
        store[defaultId] = {
          id: defaultId,
          title: 'Patient 1',
          data: {},
          updatedAt: Date.now()
        };
        savePatientsStore(store);
        setActivePatientId(defaultId);
      }

      if (!store[getActivePatientId()]) {
        setActivePatientId(Object.keys(store)[0]);
      }

      renderPatientTabs();
      loadPatientData(getActivePatientId());
    }

    function renderPatientTabs() {
      const container = document.getElementById('patientTabsContainer');
      if (!container) return;

      const store = getPatientsStore();
      const activeId = getActivePatientId();
      const keys = Object.keys(store);

      container.innerHTML = '';

      keys.forEach((pid, index) => {
        const item = store[pid];
        const fname = item.data ? (item.data.first_name || '') : '';
        const lname = item.data ? (item.data.last_name || '') : '';
        let displayName = (fname || lname) ? `${fname} ${lname}`.trim() : (item.title || `Patient ${index + 1}`);

        const tabBtn = document.createElement('div');
        tabBtn.className = 'patient-tab' + (pid === activeId ? ' active' : '');
        
        const titleSpan = document.createElement('span');
        titleSpan.className = 'patient-tab-title';
        titleSpan.textContent = displayName;
        titleSpan.addEventListener('click', () => switchPatient(pid));

        tabBtn.appendChild(titleSpan);

        if (keys.length > 1) {
          const closeBtn = document.createElement('button');
          closeBtn.type = 'button';
          closeBtn.className = 'patient-tab-close';
          closeBtn.title = 'Remove patient draft';
          closeBtn.innerHTML = '&times;';
          closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            removePatient(pid);
          });
          tabBtn.appendChild(closeBtn);
        }

        container.appendChild(tabBtn);
      });
    }

    function switchPatient(targetId) {
      if (targetId === getActivePatientId()) return;
      saveToLocal(true); // silent save current tab before switching
      setActivePatientId(targetId);
      renderPatientTabs();
      loadPatientData(targetId);
    }

    function addNewPatientTab() {
      saveToLocal(true);
      const store = getPatientsStore();
      const newId = generatePatientId();
      const count = Object.keys(store).length + 1;
      store[newId] = {
        id: newId,
        title: `Patient ${count}`,
        data: {},
        updatedAt: Date.now()
      };
      savePatientsStore(store);
      setActivePatientId(newId);
      renderPatientTabs();
      form.reset();
      loadPatientData(newId);
    }

    function removePatient(pid) {
      const store = getPatientsStore();
      const keys = Object.keys(store);
      if (keys.length <= 1) return;

      delete store[pid];
      savePatientsStore(store);

      if (getActivePatientId() === pid) {
        const nextId = Object.keys(store)[0];
        setActivePatientId(nextId);
        loadPatientData(nextId);
      }
      renderPatientTabs();
    }

    function saveToLocal(silent = false) {
      if (ECHO_ROUTES.edit) return;
      const activeId = getActivePatientId();
      const store = getPatientsStore();
      const data = {};
      new FormData(form).forEach((v, k) => {
        data[k] = v;
      });

      const fname = data.first_name || '';
      const lname = data.last_name || '';
      const title = (fname || lname) ? `${fname} ${lname}`.trim() : (store[activeId]?.title || 'Patient');

      store[activeId] = {
        id: activeId,
        title: title,
        data: data,
        updatedAt: Date.now()
      };

      savePatientsStore(store);
      renderPatientTabs();
      if (!silent) flashSaved();
    }

    function flashSaved() {
      const b = document.getElementById('saveBtn');
      if (!b) return;
      b.textContent = 'Saved';
      setTimeout(() => b.textContent = 'Save Locally', 900);
    }

    // load saved patient data
    function loadPatientData(pid) {
      const store = getPatientsStore();
      const patient = store[pid];
      form.reset();
      const qtyInitEl = document.getElementById('cp_quantity');
      const spapInitEl = document.getElementById('cp_SPAP');
      if (qtyInitEl) { delete qtyInitEl.dataset.userLocked; delete qtyInitEl.dataset.autoCalculated; }
      if (spapInitEl) { delete spapInitEl.dataset.autoCalculated; }

      if (!patient || !patient.data) {
        applyLinks();
        return;
      }

      const obj = patient.data;
      Object.entries(obj).forEach(([k, v]) => {
        const el = form.elements[k];
        if (!el) return;
        try {
          if (el.type === 'checkbox' || el.type === 'radio') { el.checked = !!v; }
          else el.value = v;
        } catch (e) { }
      });
      if (qtyInitEl && obj['cp_quantity'] && obj['cp_quantity'] !== 'Auto') {
        qtyInitEl.dataset.userLocked = 'true';
      }
      applyLinks();
      updateNavButtonStates();
      queueLivePreviewUpdate();
      if (form.elements.admission_id) form.elements.admission_id.dataset.selected = obj.admission_id || '';
      window.patientLookup?.check(false, obj.admission_id);
    }

    function loadSaved() {
      if (ECHO_ROUTES.edit) {
        loadEditData(ECHO_ROUTES.edit.data);
        return;
      }
      initMultiPatientStore();
      if (ECHO_ROUTES.initialPatient) {
        loadEditData({ ...Object.fromEntries(new FormData(form)), ...ECHO_ROUTES.initialPatient });
        saveToLocal();
      }
    }

    // Fill the form from a saved study. Values that are not one of a select's
    // options were typed in its "Manual" box, so restore them there.
    function loadEditData(obj) {
      form.reset();
      const manualSelects = { lv_size: 'lv_size_manual', rv_size: 'rv_size_manual', av_aorta: 'av_aorta_manual' };
      Object.entries(obj).forEach(([k, v]) => {
        const el = form.elements[k];
        if (!el) return;
        if (el.type === 'checkbox' || el.type === 'radio') { el.checked = v === '1'; return; }
        if (el.tagName === 'SELECT' && v !== '' && !Array.from(el.options).some((o) => o.value === v)) {
          if (manualSelects[k] && form.elements[manualSelects[k]]) {
            el.value = 'Manual';
            el.dispatchEvent(new Event('change'));
            form.elements[manualSelects[k]].value = v;
          } else {
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = v;
            el.appendChild(opt);
            el.value = v;
          }
          return;
        }
        el.value = v;
      });
      const qtyInitEl = document.getElementById('cp_quantity');
      if (qtyInitEl && obj['cp_quantity'] && obj['cp_quantity'] !== 'Auto') {
        qtyInitEl.dataset.userLocked = 'true';
      }
      applyLinks();
      updateNavButtonStates();
      queueLivePreviewUpdate();
      if (form.elements.admission_id) form.elements.admission_id.dataset.selected = obj.admission_id || '';
      window.patientLookup?.check(false, obj.admission_id);
    }

    // export JSON
    function exportJSON() {
      const data = {};
      new FormData(form).forEach((v, k) => {
        data[k] = v;
      });
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = 'echo-export.json'; document.body.appendChild(a); a.click(); a.remove();
      URL.revokeObjectURL(url);
    }

    // Handle form submission
    async function submitForm() {
      const form = document.getElementById('echoForm');
      
      // Perform validation check for required fields
      const requiredFields = [
        { id: 'first_name', label: 'نام' },
        { id: 'last_name', label: 'نام خانوادگی' },
        { id: 'nationalID', label: 'کد ملی' }
      ];

      const missingFields = [];
      requiredFields.forEach(field => {
        const el = document.getElementById(field.id);
        if (el) {
          if (!el.value || el.value.trim() === '') {
            missingFields.push(field.label);
          }
        }
      });

      if (missingFields.length > 0) {
        const message = 'لطفاً فیلدهای الزامی زیر را تکمیل کنید:<br><ul>' + 
                        missingFields.map(f => `<li>${f}</li>`).join('') + 
                        '</ul>';
        showCustomAlert('warning', 'اطلاعات ناقص', message, 'تایید');
        return;
      }

      const formData = new FormData(form);
      const data = {};

      // Get all select elements to identify which fields should be treated as text
      const selectFields = Array.from(document.querySelectorAll('select')).map(select => select.name);

      // Convert FormData to object with proper field names
      for (let [key, value] of formData.entries()) {
        // Skip empty values for optional fields
        if (value === '' || key === '_token') continue;

        // Skip the temporary manual field so it isn't submitted as a database column
        if (key === 'lv_size_manual' || key === 'rv_size_manual' || key === 'av_aorta_manual') continue;

        // Handle manual override for select fields
        if (key === 'lv_size' && value === 'Manual') {
          const manualVal = formData.get('lv_size_manual');
          if (manualVal) {
            value = manualVal;
          }
        }
        if (key === 'rv_size' && value === 'Manual') {
          const manualVal = formData.get('rv_size_manual');
          if (manualVal) {
            value = manualVal;
          }
        }
        if (key === 'av_aorta' && value === 'Manual') {
          const manualVal = formData.get('av_aorta_manual');
          if (manualVal) {
            value = manualVal;
          }
        }

        // Check if this is a select field - if so, always save as text
        if (selectFields.includes(key)) {
          data[key] = value;
          continue;
        }

        // Handle numeric fields for non-select inputs
        if (key.startsWith('lv_') || key.startsWith('rv_') || key.startsWith('la_') ||
          key.startsWith('ra_') || key.startsWith('mv_') || key.startsWith('av_') ||
          key.startsWith('pv_') || key.startsWith('tv_') || key.startsWith('cp_') ||
          key.startsWith('pre_')) {

          // List of fields that MUST be numbers.
          const numericFields = [
            'LVEDV', 'LVESV', 'LVEDD', 'LVESD', 'IVS_thickness', 'Posterior',
            'EF', 'GLS', 'LVOT', 'LVOT_diameter', 'septale', 'lateral',
            'septala', 'septal_Sm', 'PPG',
            'rv_RVOT', 'rv_RVOT_diameter',
            'la_area', 'la_volume', 'la_diameter', 'ra_area', 'ra_volume',
            'mv_planimetry', 'mv_MPG', 'mv_PHT', 'mv_PE_Velocity', 'mv_PA_Velocity', 'mv_DT',
            'mv_MV_area', 'av_peakvelocity', 'av_AVA_CE', 'av_DVI', 'av_PHT', 'av_planimetry',
            'av_MPG', 'av_ACCT', 'av_Velocity', 'av_PPG', 'a_AV_annulus', 'a_Sinus_valsalva',
            'a_ST_junction', 'a_ascending_aorta', 'av_annulus', 'av_sinus_valsalva',
            'av_st_junction', 'av_proximal_ascending_aorta', 'pv_Velocity', 'pv_ACCT', 'pv_MPG', 'pv_PPG',
            'tv_MPG', 'tv_PHT', 'tv_PE_Velocity', 'tv_PA_Velocity', 'tv_TRG', 'cp_ivc_size',
            'cp_SPAP'
          ];

          if (numericFields.some(field => key.endsWith(field))) {
            // Convert to number
            data[key] = value.includes('.') ? parseFloat(value) : parseInt(value, 10);
          } else {
            // Keep as string (Text)
            data[key] = value;
          }
        } else {
          // Non-prefixed fields
          data[key] = value;
        }
      }

      if (!data['dob']) data['dob'] = '';
      if (!data['first_name']) data['first_name'] = '';
      if (!data['last_name']) data['last_name'] = '';

      if (!data['echo_date']) {
        const d = new Date();
        const pDate = new Intl.DateTimeFormat('en-US-u-ca-persian', {year: 'numeric', month: '2-digit', day: '2-digit'}).format(d);
        const parts = pDate.split('/');
        const year = parts[2].replace(/\D/g, '');
        const month = parts[0].replace(/\D/g, '');
        const day = parts[1].replace(/\D/g, '');
        data['echo_date'] = year + '/' + month + '/' + day;
      }

      try {
        const response = await fetch(ECHO_ROUTES.edit ? ECHO_ROUTES.edit.url : ECHO_ROUTES.store, {
          method: ECHO_ROUTES.edit ? 'PUT' : 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify(data)
        });

        if (response.status === 419 || response.status === 401) {
          showCustomAlert('error', 'نشست منقضی شده', 'لطفاً صفحه را دوباره بارگذاری کرده و وارد شوید. اطلاعات فرم در مرورگر ذخیره شده است.', 'متوجه شدم');
          return;
        }

        const result = await response.json();

        if (response.status === 422 && result.errors) {
          const escapeHtml = (t) => String(t).replace(/[&<>"']/g, (c) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
          const items = Object.values(result.errors).flat().map((m) => `<li>${escapeHtml(m)}</li>`).join('');
          showCustomAlert('warning', 'اطلاعات نامعتبر', `<ul>${items}</ul>`, 'تایید');
          return;
        }

        if (response.ok && result.success && ECHO_ROUTES.edit) {
          window.location.href = result.url;
          return;
        }

        if (response.ok && result.success) {
          showCustomAlert('success', 'ثبت موفقیت‌آمیز', 'اطلاعات اکو با موفقیت ذخیره شد.', 'تایید');
        } else {
          throw new Error(result.message || 'Failed to save data');
        }
      } catch (error) {
        console.error('Error saving data:', error);
        showCustomAlert('error', 'خطا در ثبت اطلاعات', 'خطایی در ذخیره اطلاعات رخ داده است. لطفاً برای اطلاعات بیشتر لاگ‌های سیستم را بررسی کنید.', 'متوجه شدم');
      }
    }

    // Add input validation for numeric fields
    function validateNumericInput(event) {
      if (event.target.type === 'number') {
        const value = parseFloat(event.target.value);
        const min = parseFloat(event.target.min);
        const max = parseFloat(event.target.max);

        if (!isNaN(min) && value < min) {
          event.target.value = min;
        } else if (!isNaN(max) && value > max) {
          event.target.value = max;
        }
      }
    }

    // Add input event listeners for validation
    document.querySelectorAll('input[type="number"]').forEach(input => {
      input.addEventListener('blur', validateNumericInput);
    });

    // Initialize Persian Datepicker
    $(document).ready(function () {
      $("#dob").persianDatepicker({
        observer: true,
        format: 'YYYY/MM/DD',
        altField: '#dob-display',
        altFormat: 'YYYY/MM/DD',
        initialValue: false
      });
      $("#echo_date").persianDatepicker({
        observer: true,
        format: 'YYYY/MM/DD',
        altField: '#echo_date-display',
        altFormat: 'YYYY/MM/DD',
        initialValue: true
      });
    });

    const RWMA_MOTION_OPTIONS = [
      ['none', 'None'],
      ['Normal', 'Normal'],
      ['Mild hypokinesia', 'Mild hypokinesia'],
      ['Severe hypokinesia', 'Severe hypokinesia'],
      ['Akinesia', 'Akinesia'],
      ['Dyskinesia', 'Dyskinesia'],
      ['Aneurysm', 'Aneurysm'],
    ];

    const RWMA_TERRITORIES = [
      {
        title: 'Basal',
        segments: [
          { name: 'lv_Basal_Anterior', label: 'Basal Anterior (LAD)' },
          { name: 'lv_Basal_anteroseptum', label: 'Basal Anteroseptal (LAD)' },
          { name: 'lv_Basal_inferoseptum', label: 'Basal Inferoseptal (RCA)' },
          { name: 'lv_Basal_inferior', label: 'Basal Inferior (RCA)' },
          { name: 'lv_Basal_inferolateral', label: 'Basal Inferolateral (LCX)' },
          { name: 'lv_Basal_anterolateral', label: 'Basal Anterolateral (LCX)' },
        ],
      },
      {
        title: 'Mid',
        segments: [
          { name: 'lv_Mid_Anterior', label: 'Mid Anterior (LAD)' },
          { name: 'lv_Mid_anteroseptum', label: 'Mid Anteroseptal (LAD)' },
          { name: 'lv_Mid_inferoseptum', label: 'Mid Inferoseptal (RCA)' },
          { name: 'lv_Mid_inferior', label: 'Mid Inferior (RCA)' },
          { name: 'lv_Mid_inferolateral', label: 'Mid Inferolateral (LCX)' },
          { name: 'lv_Mid_anterolateral', label: 'Mid Anterolateral (LCX)' },
        ],
      },
      {
        title: 'Apical',
        segments: [
          { name: 'lv_Apical_anterior', label: 'Apical Anterior (LAD)' },
          { name: 'lv_Apical_septum', label: 'Apical Septal (LAD)' },
          { name: 'lv_Apical_inferior', label: 'Apical Inferior (LAD)' },
          { name: 'lv_Apical_lateral', label: 'Apical Lateral (LCX)' },
          { name: 'lv_Apical_cap', label: 'Apical Cap (LAD)' },
        ],
      },
    ];

    function buildRwmaFields() {
      const container = document.getElementById('rwma-segments');
      if (!container || container.dataset.built === '1') return;
      container.dataset.built = '1';

      RWMA_TERRITORIES.forEach((group) => {
        const heading = document.createElement('div');
        heading.className = 'small';
        heading.style.cssText = 'font-weight:600;margin-top:12px;margin-bottom:4px';
        heading.textContent = group.title;
        container.appendChild(heading);

        const row = document.createElement('div');
        row.className = 'row';
        row.style.marginTop = '8px';

        group.segments.forEach((seg) => {
          const field = document.createElement('div');
          field.className = 'field';
          const label = document.createElement('label');
          label.htmlFor = seg.name;
          label.textContent = seg.label;
          const select = document.createElement('select');
          select.id = seg.name;
          select.name = seg.name;
          RWMA_MOTION_OPTIONS.forEach(([text, val]) => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = text;
            select.appendChild(opt);
          });
          select.addEventListener('change', () => {
            const val = select.value;
            if (val && val.toLowerCase() !== 'normal' && val.toLowerCase() !== 'none') {
              const rwmaCb = document.getElementById('lv_rwma_checked');
              if (rwmaCb && !rwmaCb.checked) {
                rwmaCb.checked = true;
              }
            }
          });
          field.appendChild(label);
          field.appendChild(select);
          row.appendChild(field);
        });

        container.appendChild(row);
      });
    }

    // Function to handle valve abnormality description visibility
    function setupValveAbnormalityToggle(selectId, containerId) {
      const select = document.getElementById(selectId);
      const container = document.getElementById(containerId);

      if (!select || !container) return;

      // Add event listener
      select.addEventListener('change', function () {
        container.style.display = (this.value === 'Abnormal') ? 'flex' : 'none';
      });
    }

    function updateValveAbnormalityToggles() {
      [
        { select: 'mv_mvls', container: 'mv_abnormality_container' },
        { select: 'av_Aortic_valve', container: 'av_abnormality_container' },
        { select: 'pv_Pulmonary_valve', container: 'pv_abnormality_container' },
        { select: 'tv_Tricuspid_valve', container: 'tv_abnormality_container' }
      ].forEach(cfg => {
        const select = document.getElementById(cfg.select);
        const container = document.getElementById(cfg.container);
        if (select && container) {
          container.style.display = (select.value === 'Abnormal') ? 'flex' : 'none';
        }
      });
    }

    // Set up event listeners for all valve sections
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(function () {
        applyLinks(); // Ensure links apply on load

        // Set up valve abnormality toggles
        setupValveAbnormalityToggle('mv_mvls', 'mv_abnormality_container');
        setupValveAbnormalityToggle('av_Aortic_valve', 'av_abnormality_container');
        setupValveAbnormalityToggle('pv_Pulmonary_valve', 'pv_abnormality_container');
        setupValveAbnormalityToggle('tv_Tricuspid_valve', 'tv_abnormality_container');

        updateValveAbnormalityToggles();
        setupAutoSpapAndQuantityListeners();
      }, 100);
    });

    // Auto-calculate SPAP from TR Gradient, IVC size and Respiratory variation, and PAP Quantity
    function calculateAutoSpapAndQuantity(isDirectSpapEdit = false) {
      const trgInput = document.getElementById('tv_TRG');
      const ivcSizeInput = document.getElementById('cp_ivc_size');
      const respVarSelect = document.getElementById('cp_Respiratory_variation');
      const spapInput = document.getElementById('cp_SPAP');
      const qtySelect = document.getElementById('cp_quantity');

      if (!spapInput || !qtySelect) return;

      const trgVal = parseFloat(trgInput ? trgInput.value : '');
      const ivcSizeVal = parseFloat(ivcSizeInput ? ivcSizeInput.value : '');
      const respVarVal = (respVarSelect ? respVarSelect.value : '').toLowerCase().trim();

      // 1. Calculate SPAP if inputs are valid and user is not typing SPAP directly
      if (!isDirectSpapEdit) {
        if (!isNaN(trgVal) && trgVal > 0 && !isNaN(ivcSizeVal) && respVarVal && respVarVal !== 'none') {
          const isCollapseOver50 = respVarVal.includes('>50%') || respVarVal.includes('> 50%');
          let rap = 0;
          if (ivcSizeVal <= 21) {
            rap = isCollapseOver50 ? 3 : 8;
          } else {
            rap = isCollapseOver50 ? 8 : 15;
          }
          const calculatedSpap = Math.round((trgVal + rap) * 10) / 10;
          spapInput.value = calculatedSpap;
          spapInput.dataset.autoCalculated = 'true';
        } else if (spapInput.dataset.autoCalculated === 'true') {
          if (isNaN(trgVal) || trgVal <= 0) {
            spapInput.value = '';
            delete spapInput.dataset.autoCalculated;
          }
        }
      }

      // 2. Calculate Quantity based on SPAP range: <35: normal, 35-44: Mild PH, 45-59: Moderate PH, >=60: severe
      const spapVal = parseFloat(spapInput.value);
      if (!isNaN(spapVal)) {
        let autoQty = '';
        if (spapVal < 35) {
          autoQty = 'Normal PAP';
        } else if (spapVal <= 44) {
          autoQty = 'Mild PH';
        } else if (spapVal <= 59) {
          autoQty = 'Moderate PH';
        } else {
          autoQty = 'Severe PH';
        }

        if (qtySelect.value === 'Auto' || qtySelect.value === '' || qtySelect.dataset.autoCalculated === 'true' || !qtySelect.dataset.userLocked) {
          qtySelect.value = autoQty;
          qtySelect.dataset.autoCalculated = 'true';
        }
      } else {
        if (qtySelect.dataset.autoCalculated === 'true') {
          qtySelect.value = 'Auto';
          delete qtySelect.dataset.autoCalculated;
        }
      }
    }

    function setupAutoSpapAndQuantityListeners() {
      const trgEl = document.getElementById('tv_TRG');
      const ivcSizeEl = document.getElementById('cp_ivc_size');
      const respVarEl = document.getElementById('cp_Respiratory_variation');
      const spapEl = document.getElementById('cp_SPAP');
      const qtyEl = document.getElementById('cp_quantity');

      if (trgEl) {
        trgEl.addEventListener('input', () => { calculateAutoSpapAndQuantity(false); });
        trgEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (ivcSizeEl) {
        ivcSizeEl.addEventListener('input', () => { calculateAutoSpapAndQuantity(false); });
        ivcSizeEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (respVarEl) {
        respVarEl.addEventListener('change', () => { calculateAutoSpapAndQuantity(false); });
      }
      if (spapEl) {
        spapEl.addEventListener('input', () => {
          delete spapEl.dataset.autoCalculated;
          calculateAutoSpapAndQuantity(true);
        });
        spapEl.addEventListener('change', () => {
          delete spapEl.dataset.autoCalculated;
          calculateAutoSpapAndQuantity(true);
        });
      }
      if (qtyEl) {
        qtyEl.addEventListener('change', () => {
          if (qtyEl.value === 'Auto') {
            delete qtyEl.dataset.userLocked;
            delete qtyEl.dataset.autoCalculated;
            calculateAutoSpapAndQuantity(false);
          } else {
            qtyEl.dataset.userLocked = 'true';
            delete qtyEl.dataset.autoCalculated;
          }
        });
      }
    }

    // wire events
    form.addEventListener('change', () => {
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    });
    form.addEventListener('input', () => {
      updateNavButtonStates();
      queueLivePreviewUpdate();
    });

    document.getElementById('saveBtn').addEventListener('click', saveToLocal);
    document.getElementById('submitBtn').addEventListener('click', submitForm);

    // Live Preview Logic
    const previewToggleBtn = document.getElementById('previewToggleBtn');
    const previewContainer = document.getElementById('preview-container');
    const previewResizer = document.getElementById('preview-resizer');
    const previewCloseBtn = document.getElementById('previewCloseBtn');
    const previewRefreshBtn = document.getElementById('previewRefreshBtn');
    const previewNewTabBtn = document.getElementById('previewNewTabBtn');
    const appLayout = document.getElementById('app-layout');
    const formContainer = document.getElementById('form-container');

    function toggleLivePreview(forceState) {
      const isCurrentlyActive = appLayout.classList.contains('preview-active');
      const shouldActivate = forceState !== undefined ? forceState : !isCurrentlyActive;

      if (shouldActivate) {
        appLayout.classList.add('preview-active');
        previewContainer.classList.remove('hidden');
        if (previewResizer) previewResizer.classList.remove('hidden');
        previewToggleBtn.classList.add('active');
        previewToggleBtn.setAttribute('aria-pressed', 'true');
        localStorage.setItem('echoForm_live_preview', 'true');
        
        // Restore saved split width if exists
        const savedWidth = localStorage.getItem('echoForm_preview_split_width');
        if (savedWidth && window.innerWidth > 1024) {
          formContainer.style.flex = `0 0 ${savedWidth}%`;
          previewContainer.style.flex = `0 0 ${100 - savedWidth}%`;
        } else if (window.innerWidth > 1024) {
          formContainer.style.flex = `0 0 50%`;
          previewContainer.style.flex = `0 0 50%`;
        }
        
        updateLivePreview();
      } else {
        appLayout.classList.remove('preview-active');
        previewContainer.classList.add('hidden');
        if (previewResizer) previewResizer.classList.add('hidden');
        previewToggleBtn.classList.remove('active');
        previewToggleBtn.setAttribute('aria-pressed', 'false');
        localStorage.setItem('echoForm_live_preview', 'false');
        
        // Reset flex styles
        formContainer.style.flex = '';
        previewContainer.style.flex = '';
      }
    }

    // Draggable Resizer Logic
    let isDragging = false;
    if (previewResizer) {
      previewResizer.addEventListener('mousedown', (e) => {
        isDragging = true;
        document.body.classList.add('dragging');
        previewResizer.classList.add('dragging');
        e.preventDefault();
      });

      document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const containerWidth = appLayout.clientWidth;
        if (containerWidth <= 0) return;
        
        // The layout sits inside the dashboard card, so measure from its own left edge.
        let newFormWidth = ((e.clientX - appLayout.getBoundingClientRect().left) / containerWidth) * 100;
        // Constrain form width percentage between 20% and 80%
        if (newFormWidth < 20) newFormWidth = 20;
        if (newFormWidth > 80) newFormWidth = 80;
        
        formContainer.style.flex = `0 0 ${newFormWidth}%`;
        previewContainer.style.flex = `0 0 ${100 - newFormWidth}%`;
      });

      document.addEventListener('mouseup', () => {
        if (isDragging) {
          isDragging = false;
          document.body.classList.remove('dragging');
          previewResizer.classList.remove('dragging');
          
          // Save width preference if not in responsive stacked layout
          if (window.innerWidth > 1024) {
            const match = formContainer.style.flex.match(/0\s+0\s+([\d.]+)%/);
            if (match && match[1]) {
              localStorage.setItem('echoForm_preview_split_width', match[1]);
            }
          }
        }
      });
    }

    if (previewToggleBtn) {
      previewToggleBtn.addEventListener('click', () => toggleLivePreview());
    }
    if (previewCloseBtn) {
      previewCloseBtn.addEventListener('click', () => toggleLivePreview(false));
    }
    if (previewRefreshBtn) {
      previewRefreshBtn.addEventListener('click', () => updateLivePreview());
    }
    if (previewNewTabBtn) {
      previewNewTabBtn.addEventListener('click', () => {
        const originalAction = form.action;
        const originalTarget = form.target;
        const originalMethod = form.method;
        
        form.action = ECHO_ROUTES.preview;
        form.target = '_blank';
        form.method = 'POST';
        form.submit();
        
        form.action = originalAction;
        form.target = originalTarget;
        form.method = originalMethod;
      });
    }

    let previewTimeout = null;
    function updateLivePreview() {
      if (!appLayout.classList.contains('preview-active')) return;
      
      const originalAction = form.action;
      const originalTarget = form.target;
      const originalMethod = form.method;
      
      form.action = ECHO_ROUTES.preview;
      form.target = 'preview-iframe';
      form.method = 'POST';
      form.submit();
      
      form.action = originalAction;
      form.target = originalTarget;
      form.method = originalMethod;
    }

    function queueLivePreviewUpdate() {
      if (!appLayout.classList.contains('preview-active')) return;
      if (previewTimeout) clearTimeout(previewTimeout);
      previewTimeout = setTimeout(() => {
        updateLivePreview();
      }, 400);
    }

    function setupManualToggle(selectId, inputId, rowId) {
      const select = document.getElementById(selectId);
      const manualInput = document.getElementById(inputId);
      const row = document.getElementById(rowId);
      if (!select || !manualInput || !row) return;

      function updateVisibility() {
        if (select.value === 'Manual') {
          row.style.display = 'flex';
        } else {
          row.style.display = 'none';
          manualInput.value = '';
        }
      }

      select.addEventListener('change', updateVisibility);
      updateVisibility();
    }

    // Auto Summary Generator
    function getSummaryFieldVal(id) {
      const el = document.getElementById(id);
      if (!el) return '';
      if (el.type === 'checkbox') return el.checked;
      let value = (el.value || '').trim();
      // Remove "there is", "there are", "there was", "there were" prefixes
      value = value.replace(/^(there is|there are|there was|there were)\s+/i, '');
      return value;
    }

    function isCleanSummaryVal(v) {
      if (!v) return false;
      const lower = String(v).toLowerCase();
      return lower !== 'none' && lower !== '-1' && lower !== '' && lower !== 'auto';
    }

    // Resolves field values respecting user manual selections while auto-calculating
    // empty or "Auto" fields using the exact same formulas as view_report.php
    function getResolvedSummaryValues() {
      const gender = (getSummaryFieldVal('gender') || '').trim();
      const isMale = gender.toLowerCase() === 'male' || gender === '1';
      const isFemale = gender.toLowerCase() === 'female' || gender === '2';

      const height = parseFloat(getSummaryFieldVal('Height')) || 0;
      const weight = parseFloat(getSummaryFieldVal('Weight')) || 0;
      let bsa = 0;
      if (height > 0 && weight > 0) {
        bsa = Math.sqrt((height * weight) / 3600);
      }

      // 1. LV Size
      const lvSizeRaw = getSummaryFieldVal('lv_size');
      let lvSize = '';
      if (lvSizeRaw === 'Manual') {
        lvSize = getSummaryFieldVal('lv_size_manual');
      } else if (isCleanSummaryVal(lvSizeRaw)) {
        lvSize = lvSizeRaw;
      } else {
        const lvedv = parseFloat(getSummaryFieldVal('lv_LVEDV')) || 0;
        const lvedvi = (lvedv > 0 && bsa > 0) ? (lvedv / bsa) : 0;
        const lvdd = parseFloat(getSummaryFieldVal('lv_LVEDD')) || 0;
        const lveddi = (lvdd > 0 && bsa > 0) ? (lvdd / bsa) : 0;

        let autoLvSize = null;
        if (isMale || isFemale) {
          if (lvedvi > 0) {
            if (isMale) {
              if (lvedvi <= 74) autoLvSize = 'Normal LV size';
              else if (lvedvi <= 89) autoLvSize = 'Mild LV enlargement';
              else if (lvedvi <= 100) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvedvi <= 61) autoLvSize = 'Normal LV size';
              else if (lvedvi <= 70) autoLvSize = 'Mild LV enlargement';
              else if (lvedvi <= 80) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lvedv > 0) {
            if (isMale) {
              if (lvedv <= 150) autoLvSize = 'Normal LV size';
              else if (lvedv <= 174) autoLvSize = 'Mild LV enlargement';
              else if (lvedv <= 200) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvedv <= 106) autoLvSize = 'Normal LV size';
              else if (lvedv <= 120) autoLvSize = 'Mild LV enlargement';
              else if (lvedv <= 130) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lveddi > 0) {
            if (isMale) {
              if (lveddi <= 30) autoLvSize = 'Normal LV size';
              else if (lveddi <= 33) autoLvSize = 'Mild LV enlargement';
              else if (lveddi <= 36) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lveddi <= 31) autoLvSize = 'Normal LV size';
              else if (lveddi <= 34) autoLvSize = 'Mild LV enlargement';
              else if (lveddi <= 37) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          } else if (lvdd > 0) {
            if (isMale) {
              if (lvdd <= 58) autoLvSize = 'Normal LV size';
              else if (lvdd <= 63) autoLvSize = 'Mild LV enlargement';
              else if (lvdd <= 68) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            } else {
              if (lvdd <= 52) autoLvSize = 'Normal LV size';
              else if (lvdd <= 56) autoLvSize = 'Mild LV enlargement';
              else if (lvdd <= 61) autoLvSize = 'Moderate LV enlargement';
              else autoLvSize = 'Severe LV enlargement';
            }
          }
        }
        lvSize = autoLvSize || 'Normal LV size';
      }

      // 2. LV Systolic
      const lvSysRaw = getSummaryFieldVal('lv_Systolic');
      let lvSys = '';
      const ef = parseFloat(getSummaryFieldVal('lv_EF')) || 0;
      if (isCleanSummaryVal(lvSysRaw)) {
        lvSys = lvSysRaw;
      } else {
        let autoSys = null;
        if (ef > 0 && (isMale || isFemale)) {
          if (isMale) {
            if (ef < 30) autoSys = 'With severe systolic dysfunction';
            else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
            else if (ef < 52) autoSys = 'With mild systolic dysfunction';
            else autoSys = 'With normal systolic function';
          } else {
            if (ef < 30) autoSys = 'With severe systolic dysfunction';
            else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
            else if (ef < 54) autoSys = 'With mild systolic dysfunction';
            else autoSys = 'With normal systolic function';
          }
        } else if (ef > 0) {
          if (ef < 30) autoSys = 'With severe systolic dysfunction';
          else if (ef < 41) autoSys = 'With moderate systolic dysfunction';
          else if (ef < 52) autoSys = 'With mild systolic dysfunction';
          else autoSys = 'With normal systolic function';
        }
        lvSys = autoSys || 'With normal systolic function';
      }

      // 3. LV Hypertrophy
      const lvHypRaw = getSummaryFieldVal('lv_Hypertrophy');
      let lvHyp = '';
      if (isCleanSummaryVal(lvHypRaw)) {
        lvHyp = lvHypRaw;
      } else {
        const ivs = parseFloat(getSummaryFieldVal('lv_IVS_thickness')) || 0;
        let autoHyp = null;
        if (ivs > 0 && (isMale || isFemale)) {
          if (isMale) {
            if (ivs < 11) autoHyp = 'No LVH';
            else if (ivs < 14) autoHyp = 'Mild LVH';
            else if (ivs <= 16) autoHyp = 'Moderate LVH';
            else autoHyp = 'Severe LVH';
          } else {
            if (ivs < 10) autoHyp = 'No LVH';
            else if (ivs < 13) autoHyp = 'Mild LVH';
            else if (ivs <= 15) autoHyp = 'Moderate LVH';
            else autoHyp = 'Severe LVH';
          }
        } else if (ivs > 0) {
          if (ivs < 11) autoHyp = 'No LVH';
          else if (ivs < 14) autoHyp = 'Mild LVH';
          else if (ivs <= 16) autoHyp = 'Moderate LVH';
          else autoHyp = 'Severe LVH';
        }
        lvHyp = autoHyp || 'No LVH';
      }

      // 4. LV Diastolic
      const lvDiaRaw = getSummaryFieldVal('lv_Diastolic');
      let lvDia = '';
      if (isCleanSummaryVal(lvDiaRaw)) {
        lvDia = lvDiaRaw;
      } else {
        const septale = parseFloat(getSummaryFieldVal('lv_septale')) || 0;
        const lateral = parseFloat(getSummaryFieldVal('lv_lateral')) || 0;
        const E = parseFloat(getSummaryFieldVal('mv_PE_Velocity')) || 0;
        const A = parseFloat(getSummaryFieldVal('mv_PA_Velocity')) || 0;
        const trg = parseFloat(getSummaryFieldVal('tv_TRG')) || 0;
        let pasp = parseFloat(getSummaryFieldVal('cp_SPAP')) || 0;
        if (pasp <= 0 && trg > 0) {
          const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
          const respVar = (getSummaryFieldVal('cp_Respiratory_variation') || '').toLowerCase().trim();
          if (!isNaN(ivcSize) && respVar && respVar !== 'none') {
            const isCollapseOver50 = respVar.includes('>50%') || respVar.includes('> 50%');
            const rap = (ivcSize <= 21) ? (isCollapseOver50 ? 3 : 8) : (isCollapseOver50 ? 8 : 15);
            pasp = Math.round((trg + rap) * 10) / 10;
          }
        }

        const hasAnyDiastolic = (septale > 0 || lateral > 0 || E > 0 || A > 0 || trg > 0 || pasp > 0);
        if (hasAnyDiastolic) {
          let hasReducedE = false;
          if (septale > 0 && septale <= 6) hasReducedE = true;
          if (lateral > 0 && lateral <= 7) hasReducedE = true;
          if (septale > 0 && lateral > 0 && ((septale + lateral) / 2) <= 6.5) hasReducedE = true;

          let hasIncreasedEe = false;
          if (E > 0) {
            if (septale > 0 && (E * 100 / septale) >= 15) hasIncreasedEe = true;
            if (lateral > 0 && (E * 100 / lateral) >= 13) hasIncreasedEe = true;
            if (septale > 0 && lateral > 0 && (E * 100 / ((septale + lateral) / 2)) >= 14) hasIncreasedEe = true;
          }

          const trVel = trg > 0 ? Math.sqrt(trg / 4) : 0;
          const hasIncreasedTrPasp = (trVel >= 2.8 || pasp >= 35);
          const eaRatio = (E > 0 && A > 0) ? (E / A) : null;

          let abnormalCount = 0;
          if (hasReducedE) abnormalCount++;
          if (hasIncreasedEe) abnormalCount++;
          if (hasIncreasedTrPasp) abnormalCount++;

          if (hasReducedE && !hasIncreasedEe && !hasIncreasedTrPasp) {
            if (eaRatio !== null && eaRatio > 0.8) {
              lvDia = 'Indeterminate diastolic function';
            } else {
              lvDia = 'Grade I diastolic dysfunction';
            }
          } else if (hasReducedE && hasIncreasedEe && hasIncreasedTrPasp) {
            if (eaRatio !== null && eaRatio >= 2.0) {
              lvDia = 'Grade III diastolic dysfunction';
            } else {
              lvDia = 'Grade II diastolic dysfunction';
            }
          } else if (abnormalCount > 0) {
            lvDia = 'Indeterminate diastolic function';
          } else {
            lvDia = 'Normal diastolic function';
          }
        } else {
          lvDia = 'Normal diastolic function';
        }
      }

      // 5. LV Clot
      const lvClotRaw = getSummaryFieldVal('lv_clot');
      let lvClot = '';
      if (isCleanSummaryVal(lvClotRaw)) {
        lvClot = lvClotRaw;
      } else {
        lvClot = 'No LV clot';
      }

      // 6. RV Size
      const rvSizeRaw = getSummaryFieldVal('rv_size');
      let rvSize = '';
      if (rvSizeRaw === 'Manual') {
        rvSize = getSummaryFieldVal('rv_size_manual');
      } else if (isCleanSummaryVal(rvSizeRaw)) {
        rvSize = rvSizeRaw;
      } else {
        const rvBasal = parseFloat(getSummaryFieldVal('rv_RV_basal_diameter')) || 0;
        const rvMid = parseFloat(getSummaryFieldVal('rv_RV_mid_diameter')) || 0;
        let autoRvSize = null;
        if (rvBasal > 0) {
          if (rvBasal <= 40) autoRvSize = 'Normal RV size';
          else if (rvBasal <= 44) autoRvSize = 'Mild RV enlargement';
          else if (rvBasal <= 49) autoRvSize = 'Moderate RV enlargement';
          else autoRvSize = 'Severe RV enlargement';
        } else if (rvMid > 0) {
          if (rvMid <= 35) autoRvSize = 'Normal RV size';
          else if (rvMid <= 38) autoRvSize = 'Mild RV enlargement';
          else if (rvMid <= 43) autoRvSize = 'Moderate RV enlargement';
          else autoRvSize = 'Severe RV enlargement';
        }
        rvSize = autoRvSize || 'Normal RV size';
      }

      // 7. RV Systolic
      const rvSysRaw = getSummaryFieldVal('rv_Systolic');
      let rvSys = '';
      const tapse = parseFloat(getSummaryFieldVal('rv_TAPSE')) || 0;
      if (isCleanSummaryVal(rvSysRaw)) {
        rvSys = rvSysRaw;
      } else {
        let autoRvSys = null;
        if (tapse > 0) {
          if (tapse > 17) autoRvSys = 'With normal systolic function';
          else if (tapse >= 14) autoRvSys = 'With mild systolic dysfunction';
          else if (tapse >= 10) autoRvSys = 'With moderate systolic dysfunction';
          else autoRvSys = 'With severe systolic dysfunction';
        }
        rvSys = autoRvSys || 'With normal systolic function';
      }

      // 8. RV Hypertrophy
      const rvHypRaw = getSummaryFieldVal('rv_Hypertrophy');
      let rvHyp = '';
      if (isCleanSummaryVal(rvHypRaw)) {
        rvHyp = rvHypRaw;
      } else {
        rvHyp = 'No RVH';
      }

      // 9. LA Size
      const laSizeRaw = getSummaryFieldVal('la_size');
      let laSize = '';
      if (isCleanSummaryVal(laSizeRaw)) {
        laSize = laSizeRaw;
      } else {
        const laVol = parseFloat(getSummaryFieldVal('la_volume')) || 0;
        const laArea = parseFloat(getSummaryFieldVal('la_area')) || 0;
        const laDiam = parseFloat(getSummaryFieldVal('la_diameter')) || 0;
        let autoLaSize = null;
        if (laVol > 0 && bsa > 0) {
          const lavi = laVol / bsa;
          if (lavi <= 34) autoLaSize = 'Normal LA size';
          else if (lavi <= 41) autoLaSize = 'Mild LA enlargement';
          else if (lavi <= 48) autoLaSize = 'Moderate LA enlargement';
          else autoLaSize = 'Severe LA enlargement';
        } else if (laArea > 0) {
          if (laArea <= 20) autoLaSize = 'Normal LA size';
          else if (laArea <= 30) autoLaSize = 'Mild LA enlargement';
          else if (laArea <= 41) autoLaSize = 'Moderate LA enlargement';
          else autoLaSize = 'Severe LA enlargement';
        } else if (laDiam > 0 && (isMale || isFemale)) {
          if (bsa > 0) {
            const ladi = laDiam / bsa;
            if (ladi <= 23) autoLaSize = 'Normal LA size';
            else autoLaSize = 'There is LA enlargement';
          } else {
            if (isMale) {
              if (laDiam <= 40) autoLaSize = 'Normal LA size';
              else autoLaSize = 'There is LA enlargement';
            } else {
              if (laDiam <= 38) autoLaSize = 'Normal LA size';
              else autoLaSize = 'There is LA enlargement';
            }
          }
        }
        laSize = autoLaSize || 'Normal LA size';
      }

      // 10. RA Size
      const raSizeRaw = getSummaryFieldVal('ra_size');
      let raSize = '';
      if (isCleanSummaryVal(raSizeRaw)) {
        raSize = raSizeRaw;
      } else {
        const raArea = parseFloat(getSummaryFieldVal('ra_area')) || 0;
        let autoRaSize = null;
        if (raArea > 0) {
          if (raArea < 18) autoRaSize = 'Normal RA size';
          else autoRaSize = 'RA enlargement';
        }
        raSize = autoRaSize || 'Normal RA size';
      }

      // Aorta Size Auto-calculation
      const avAortaRaw = getSummaryFieldVal('av_aorta');
      const annulus = parseFloat(getSummaryFieldVal('av_annulus')) || 0;
      const sinusValsalva = parseFloat(getSummaryFieldVal('av_sinus_valsalva')) || 0;
      const stJunction = parseFloat(getSummaryFieldVal('av_st_junction')) || 0;
      const proxAscAorta = parseFloat(getSummaryFieldVal('av_proximal_ascending_aorta')) || 0;

      let dilatedSegments = [];
      let anyAortaMeasured = (annulus > 0 || sinusValsalva > 0 || stJunction > 0 || proxAscAorta > 0);

      // Annulus: abs{male:>29, female:>25}, indexed{male:14, female:14}
      if (annulus > 0) {
        const absLimit = isFemale ? 25 : 29;
        const idxLimit = 14;
        const idxVal = (bsa > 0) ? (annulus / bsa) : 0;
        if (annulus > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('annulus');
        }
      }

      // Sinuses of Valsalva: abs{male:>37, female:>33}, indexed{male:19, female:20}
      if (sinusValsalva > 0) {
        const absLimit = isFemale ? 33 : 37;
        const idxLimit = isFemale ? 20 : 19;
        const idxVal = (bsa > 0) ? (sinusValsalva / bsa) : 0;
        if (sinusValsalva > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('sinuses of valsalva');
        }
      }

      // Sinotubular junction: abs{male:>32, female:>29}, indexed{male:17, female:17}
      if (stJunction > 0) {
        const absLimit = isFemale ? 29 : 32;
        const idxLimit = 17;
        const idxVal = (bsa > 0) ? (stJunction / bsa) : 0;
        if (stJunction > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('sinotubular junction');
        }
      }

      // Proximal ascending aorta: abs{male:>34, female:>31}, indexed{male:17, female:19}
      if (proxAscAorta > 0) {
        const absLimit = isFemale ? 31 : 34;
        const idxLimit = isFemale ? 19 : 17;
        const idxVal = (bsa > 0) ? (proxAscAorta / bsa) : 0;
        if (proxAscAorta > absLimit || (bsa > 0 && idxVal > idxLimit)) {
          dilatedSegments.push('proximal ascending aorta');
        }
      }

      let resolvedAorta = '';
      if (avAortaRaw === 'Manual') {
        resolvedAorta = getSummaryFieldVal('av_aorta_manual');
      } else if (avAortaRaw === 'None') {
        resolvedAorta = '';
      } else if (avAortaRaw === 'Normal aortic size') {
        resolvedAorta = 'There is normal aortic size';
      } else if (avAortaRaw === 'Dilated aortic size') {
        if (dilatedSegments.length > 0) {
          resolvedAorta = `There is dilated aortic size (${dilatedSegments.join(', ')})`;
        } else {
          resolvedAorta = 'There is dilated aortic size';
        }
      } else { // Auto or empty
        if (dilatedSegments.length > 0) {
          resolvedAorta = `There is dilated aortic size (${dilatedSegments.join(', ')})`;
        } else if (anyAortaMeasured) {
          resolvedAorta = 'There is normal aortic size';
        }
      }

      return {
        bsa,
        isMale,
        isFemale,
        lvSize,
        lvSys,
        lvHyp,
        lvDia,
        lvClot,
        rvSize,
        rvSys,
        rvHyp,
        laSize,
        raSize,
        resolvedAorta,
        dilatedSegments,
        avAortaRaw
      };
    }

    function generateAutoSummaryText() {
      const bullets = [];
      const resolved = getResolvedSummaryValues();

      // 1. LV size
      const lvSizeText = resolved.lvSize;
      if (lvSizeText) {
        bullets.push(`• ${lvSizeText}`);
      }

      // LVH - moved to its own bullet line (no parentheses)
      const lvHyp = resolved.lvHyp;
      const lvOtherHyp = getSummaryFieldVal('lv_other_hyper');
      const lvhText = (lvHyp === 'Others' || lvHyp === 'Other') && lvOtherHyp ? lvOtherHyp : lvHyp;
      if (lvhText && lvhText.toLowerCase() !== 'no lvh' && lvhText.toLowerCase() !== 'none') {
        bullets.push(`• ${lvhText}`);
      }

      // LV clot if present
      const lvClot = resolved.lvClot;
      const lvOtherClot = getSummaryFieldVal('lv_other_clot');
      const clotVal = (lvClot === 'Other' && lvOtherClot ? lvOtherClot : lvClot);
      if (clotVal && clotVal.toLowerCase() !== 'no lv clot' && clotVal.toLowerCase() !== 'none') {
        bullets.push(`• ${clotVal}`);
      }

      // LV other description if present
      const lvOtherDes = getSummaryFieldVal('other_des_lv');
      if (lvOtherDes) {
        bullets.push(`• ${lvOtherDes}`);
      }

      // 2. LV EF / Systolic (EF remains in parentheses per user requirement)
      const ef = getSummaryFieldVal('lv_EF');
      let lvSys = resolved.lvSys;
      if (lvSys.toLowerCase().startsWith('with ')) {
        lvSys = lvSys.substring(5).trim();
      }
      if (lvSys) {
        lvSys = lvSys.charAt(0).toUpperCase() + lvSys.slice(1);
      }

      let lvEfText = '';
      if (ef && lvSys) {
        lvEfText = `${lvSys} (EF: ${ef}%)`;
      } else if (ef) {
        lvEfText = `EF: ${ef}%`;
      } else if (lvSys) {
        lvEfText = lvSys;
      } else {
        lvEfText = 'Normal systolic function';
      }
      bullets.push(`• ${lvEfText}`);

      // 3. LV diastolic (only when abnormal: a normal finding is left out of the summary)
      if (resolved.lvDia && !/^normal\b/i.test(resolved.lvDia)) {
        bullets.push(`• ${resolved.lvDia}`);
      }

      // 4. LV Wall Motion
      const rwmaChecked = !!getSummaryFieldVal('lv_rwma_checked');
      const globalHypoChecked = !!getSummaryFieldVal('lv_global_hypokinesia');
      let rwmaFindings = [];

      // Segment findings only when RWMA is ticked (global hypokinesia alone leaves them out).
      if (rwmaChecked && typeof RWMA_TERRITORIES !== 'undefined') {
        const territoryMap = {
          'LAD': [],
          'RCA': [],
          'LCX': []
        };

        RWMA_TERRITORIES.forEach(group => {
          group.segments.forEach(seg => {
            const val = getSummaryFieldVal(seg.name);
            if (val && val.toLowerCase() !== 'none' && val.toLowerCase() !== 'normal') {
              let t = 'LAD';
              if (seg.label.includes('RCA')) t = 'RCA';
              else if (seg.label.includes('LCX')) t = 'LCX';
              
              const cleanSegLabel = seg.label.replace(/\s*\([A-Z]+\)\s*/, '').trim();
              territoryMap[t].push(`${val.toLowerCase()} of ${cleanSegLabel}`);
            }
          });
        });

        Object.keys(territoryMap).forEach(t => {
          if (territoryMap[t].length > 0) {
            rwmaFindings.push(`${territoryMap[t].join(', ')} in ${t} territory`);
          }
        });
      }

      const lvWith = getSummaryFieldVal('lv_with_preserved');
      let withText = '';
      if (lvWith === '1') withText = ', with preserved tissue thickness';
      else if (lvWith === '2') withText = ', with thinning and loss of tissue';

      if (globalHypoChecked && rwmaFindings.length > 0) {
        bullets.push(`• Global hypokinesia, with ${rwmaFindings.join(', ')}${withText}`);
      } else if (globalHypoChecked) {
        bullets.push(`• Global hypokinesia${withText}`);
      } else if (rwmaFindings.length > 0) {
        const rwmaText = rwmaFindings.join(', ');
        bullets.push(`• ${rwmaText.charAt(0).toUpperCase() + rwmaText.slice(1)}${withText}`);
      }

      if (getSummaryFieldVal('lv_septal_motion_lbbb')) {
        bullets.push('• Abnormal septal motion due to LBBB');
      }

      // 5. RV size
      const rvSize = resolved.rvSize;
      bullets.push(`• ${rvSize}`);

      const rvHyp = resolved.rvHyp;
      const rvOtherHyp = getSummaryFieldVal('rv_other_hyper');
      const rvhText = (rvHyp === 'Other' && rvOtherHyp ? rvOtherHyp : rvHyp);
      if (rvhText && rvhText.toLowerCase() !== 'no rvh' && rvhText.toLowerCase() !== 'none') {
        bullets.push(`• ${rvhText}`);
      }

      const rvOtherDes = getSummaryFieldVal('rv_other_des');
      if (rvOtherDes) {
        bullets.push(`• ${rvOtherDes}`);
      }

      // 6. RV function
      let rvSys = resolved.rvSys;
      if (rvSys.toLowerCase().startsWith('with ')) {
        rvSys = rvSys.substring(5).trim();
      }
      if (rvSys) {
        if (!rvSys.toLowerCase().includes('rv')) {
          rvSys = rvSys.replace(/systolic/i, 'RV systolic');
        }
        rvSys = rvSys.charAt(0).toUpperCase() + rvSys.slice(1);
      }

      let rvFuncText = rvSys || 'Normal RV systolic function';
      bullets.push(`• ${rvFuncText}`);

      // 7. LA and RA enlargement if present
      const laSize = resolved.laSize;
      const laArea = parseFloat(getSummaryFieldVal('la_area'));
      const isLaEnlarged = (laSize && !laSize.toLowerCase().startsWith('normal')) || (!isNaN(laArea) && laArea > 20);

      if (isLaEnlarged) {
        const laText = (laSize && !laSize.toLowerCase().startsWith('normal')) ? laSize : 'LA enlargement';
        bullets.push(`• ${laText}`);
      }

      const laSmoky = getSummaryFieldVal('la_smoky');
      const laClot = getSummaryFieldVal('la_clot');
      const laOtherDes = getSummaryFieldVal('la_other_des');
      let laOtherNotes = [];
      if (laSmoky === '1') laOtherNotes.push('Smoky appearance');
      if (laClot === '1') {
        const laOtherClot = getSummaryFieldVal('la_other_clot');
        laOtherNotes.push(laOtherClot ? `Clot: ${laOtherClot}` : 'Clot present');
      }
      if (laOtherDes) laOtherNotes.push(laOtherDes);
      if (laOtherNotes.length > 0 && !isLaEnlarged) {
        bullets.push(`• LA: ${laOtherNotes.join(', ')}`);
      }

      const raSize = resolved.raSize;
      const raArea = parseFloat(getSummaryFieldVal('ra_area'));
      const isRaEnlarged = (raSize && !raSize.toLowerCase().startsWith('normal')) || (!isNaN(raArea) && raArea >= 18);

      if (isRaEnlarged) {
        const raText = (raSize && !raSize.toLowerCase().startsWith('normal')) ? raSize : 'RA enlargement';
        bullets.push(`• ${raText}`);
      }

      const raOtherDes = getSummaryFieldVal('ra_other_des');
      if (raOtherDes && !isRaEnlarged) {
        bullets.push(`• RA: ${raOtherDes}`);
      }

      // 8. Valves abnormalities if present
      const valves = [
        { code: 'MV', title: 'Mitral valve', prefix: 'mv', name: 'mvls', sten: 'mv_stenosis1', reg: 'mv_regurgitant', abDesc: 'mv_abnormality_description', other: 'mv_other_des' },
        { code: 'AV', title: 'Aortic valve', prefix: 'av', name: 'Aortic_valve', sten: 'av_stenosis1', reg: 'av_regurgitant', abDesc: 'av_abnormality_description', other: 'av_other_des' },
        { code: 'TV', title: 'Tricuspid valve', prefix: 'tv', name: 'Tricuspid_valve', sten: 'tv_stenosis1', reg: 'tv_regurgitant', abDesc: 'tv_abnormality_description', other: 'tv_other_des' },
        { code: 'PV', title: 'Pulmonary valve', prefix: 'pv', name: 'Pulmonary_valve', sten: 'pv_stenosis1', reg: 'pv_regurgitant', abDesc: 'pv_abnormality_description', other: 'pv_other_des' }
      ];

      let valveAbs = [];
      valves.forEach(v => {
        let vDetails = [];
        const isAbnormalVal = getSummaryFieldVal(`${v.prefix}_${v.name}`) === 'Abnormal';
        const abDesc = getSummaryFieldVal(v.abDesc);
        if (isAbnormalVal && abDesc) {
          vDetails.push(abDesc);
        }

        const sten = getSummaryFieldVal(v.sten);
        if (isCleanSummaryVal(sten) && !sten.toLowerCase().startsWith('no ')) {
          vDetails.push(sten);
        }

        const reg = getSummaryFieldVal(v.reg);
        if (isCleanSummaryVal(reg) && !reg.toLowerCase().startsWith('no ')) {
          vDetails.push(reg);
        }

        const oth = getSummaryFieldVal(v.other);
        if (oth) vDetails.push(oth);

        if (vDetails.length > 0) {
          valveAbs.push(vDetails.join(', '));
        }
      });

      if (valveAbs.length > 0) {
        bullets.push(`• ${valveAbs.join('; ')}`);
      }

      // Aorta if dilated or manual
      if (resolved.resolvedAorta) {
        if (resolved.avAortaRaw === 'Manual') {
          bullets.push(`• ${resolved.resolvedAorta}`);
        } else if (resolved.resolvedAorta.toLowerCase().includes('dilated')) {
          bullets.push(`• ${resolved.resolvedAorta}`);
        }
      }

      // 9. IVC if dilated
      const ivc = getSummaryFieldVal('cp_ivc1');
      const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
      const isIvcDilated = (ivc && ivc.toLowerCase() === 'dilated') || (!isNaN(ivcSize) && ivcSize > 21);

      if (isIvcDilated) {
        bullets.push('• Dilated IVC');
      }

      // 10. Pericardium if not normal
      const periMorph = getSummaryFieldVal('pre_Morphology');
      const isPeriMorphAbnormal = isCleanSummaryVal(periMorph) && !periMorph.toLowerCase().startsWith('normal');

      const pe = getSummaryFieldVal('pre_Pericardial_effusion');
      const isPeAbnormal = isCleanSummaryVal(pe) && !pe.toLowerCase().startsWith('no ');

      const periOther = getSummaryFieldVal('pre_Pericardium_others');

      if (isPeriMorphAbnormal || isPeAbnormal || periOther) {
        let periFindings = [];
        if (isPeriMorphAbnormal) {
          periFindings.push(periMorph);
        }
        if (isPeAbnormal) {
          const peQty = getSummaryFieldVal('pre_quantity_PE');
          let peText = pe;
          if (peQty && isCleanSummaryVal(peQty)) {
            peText = `${peQty} ${pe.toLowerCase()}`;
          }
          const peHemo = getSummaryFieldVal('pre_Hemodynamic');
          if (peHemo === 'with hemodynamic effect') {
            peText += ', with hemodynamic effect';
          }
          periFindings.push(peText);
        }
        if (periOther) {
          periFindings.push(periOther);
        }
        bullets.push(`• ${periFindings.join(', ')}`);
      }

      // Additional findings or Elevated SPAP if present
      let spap = getSummaryFieldVal('cp_SPAP');
      let spapNum = parseFloat(spap);
      const trg = parseFloat(getSummaryFieldVal('tv_TRG')) || 0;
      if (isNaN(spapNum) && trg > 0) {
        const ivcSize = parseFloat(getSummaryFieldVal('cp_ivc_size'));
        const respVar = (getSummaryFieldVal('cp_Respiratory_variation') || '').toLowerCase().trim();
        if (!isNaN(ivcSize) && respVar && respVar !== 'none') {
          const isCollapseOver50 = respVar.includes('>50%') || respVar.includes('> 50%');
          const rap = (ivcSize <= 21) ? (isCollapseOver50 ? 3 : 8) : (isCollapseOver50 ? 8 : 15);
          spapNum = Math.round((trg + rap) * 10) / 10;
          spap = String(spapNum);
        }
      }

      let cpQty = getSummaryFieldVal('cp_quantity');
      if (cpQty === 'Auto' || !cpQty) {
        if (!isNaN(spapNum)) {
          if (spapNum < 35) cpQty = 'Normal PAP';
          else if (spapNum <= 44) cpQty = 'Mild PH';
          else if (spapNum <= 59) cpQty = 'Moderate PH';
          else cpQty = 'Severe PH';
        }
      }

      if (spap || (cpQty && cpQty.includes('PH'))) {
        let phText = '';
        if (cpQty && cpQty !== 'Normal PAP' && cpQty !== 'Normal' && cpQty !== 'Top normal PAP' && cpQty !== 'Auto') {
          phText = cpQty;
        } else if (!isNaN(spapNum) && spapNum >= 35) {
          if (spapNum <= 44) phText = 'Mild PH';
          else if (spapNum <= 59) phText = 'Moderate PH';
          else phText = 'Severe PH';
        }

        if (phText) {
          bullets.push(`• ${phText}`);
        } else if (spap && !isNaN(spapNum) && spapNum >= 35) {
          bullets.push('• Pulmonary hypertension');
        }
      }

      const finding = getSummaryFieldVal('finding');
      if (finding) {
        bullets.push(`• ${finding}`);
      }

      return bullets.join('\n');
    }

    function applyAutoSummary(force = true) {
      const summaryEl = document.getElementById('summary');
      if (!summaryEl) return;

      const generated = generateAutoSummaryText();
      summaryEl.value = generated;
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();

      const btn = document.getElementById('generateSummaryBtn');
      if (btn) {
        const origHtml = btn.innerHTML;
        btn.innerHTML = `<span>✓ Generated</span>`;
        btn.classList.add('is-done');
        setTimeout(() => {
          btn.innerHTML = origHtml;
          btn.classList.remove('is-done');
        }, 1200);
      }
    }

    function clearSummary() {
      const summaryEl = document.getElementById('summary');
      if (!summaryEl) return;
      summaryEl.value = '';
      window._dirty = true;
      saveToLocal();
      updateNavButtonStates();
      queueLivePreviewUpdate();
    }

    window.addEventListener('load', () => {
      initSectionNav();
      buildRwmaFields();
      loadSaved();
      setupManualToggle('lv_size', 'lv_size_manual', 'lv_manual_row');
      setupManualToggle('rv_size', 'rv_size_manual', 'rv_manual_row');
      setupManualToggle('av_aorta', 'av_aorta_manual', 'av_aorta_manual_row');
      updateValveAbnormalityToggles();
      updateNavButtonStates();

      const genBtn = document.getElementById('generateSummaryBtn');
      if (genBtn) {
        genBtn.addEventListener('click', () => applyAutoSummary(true));
      }
      const clrBtn = document.getElementById('clearSummaryBtn');
      if (clrBtn) {
        clrBtn.addEventListener('click', clearSummary);
      }

      // Initialize any date fields to today if empty
      const today = new Date().toISOString().split('T')[0];
      const dateInputs = document.querySelectorAll('input[type="date"]');
      dateInputs.forEach(input => {
        if (!input.value) {
          input.value = today;
        }
      });

      // Restore preview state on load
      const savedPreviewState = localStorage.getItem('echoForm_live_preview');
      if (savedPreviewState === 'true') {
        setTimeout(() => {
          toggleLivePreview(true);
        }, 300);
      }
    });
  </script>
