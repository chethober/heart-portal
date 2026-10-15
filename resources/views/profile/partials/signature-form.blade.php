<x-profile.section id="signature" :title="__('Signature')" :description="__('Shown on reports you sign. Draw it below with a mouse, finger or stylus.')" x-data="signaturePad">
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_13rem]">
        <div class="min-w-0">
            <form method="POST" action="{{ route('profile.signature') }}" @submit="prepare($event)">
                @csrf
                <input type="hidden" name="drawn_signature" x-ref="data">
                {{-- The canvas is always left-to-right: a signature is a drawing, not text. --}}
                <div class="relative" dir="ltr">
                    <canvas x-ref="canvas" class="block w-full h-48 touch-none cursor-crosshair rounded-lg bg-gray-50 ring-1 ring-inset ring-gray-200"
                        aria-label="{{ __('Signature drawing area') }}"
                        @pointerdown="start($event)" @pointermove="move($event)" @pointerup="end()" @pointercancel="end()"></canvas>
                    <div class="pointer-events-none absolute inset-x-6 bottom-10 border-b border-gray-300" aria-hidden="true"></div>
                    <span class="pointer-events-none absolute left-6 bottom-11 text-gray-400 text-lg" aria-hidden="true">×</span>
                    <p x-show="empty" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-gray-400">{{ __('Sign here') }}</p>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-profile.button x-bind:disabled="empty">{{ __('Save signature') }}</x-profile.button>
                    <x-profile.button type="button" variant="ghost" x-on:click="undo()" x-bind:disabled="empty">{{ __('Undo') }}</x-profile.button>
                    <x-profile.button type="button" variant="ghost" x-on:click="clear()" x-bind:disabled="empty">{{ __('Clear') }}</x-profile.button>
                </div>
            </form>

            <x-input-error :messages="$errors->signature->get('drawn_signature')" class="mt-2" />
            @if (session('status') === 'signature-updated')
                <p class="mt-2 text-sm text-green-700">{{ __('Signature saved.') }}</p>
            @endif
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-700">{{ __('Current signature') }}</h3>
            @if ($user->signature_path)
                <img src="{{ route('users.signature', $user) }}" alt="{{ __('Your signature') }}" class="mt-2 h-28 w-full object-contain rounded-lg bg-white p-2 ring-1 ring-inset ring-gray-200">
                <p class="mt-2 text-xs leading-5 text-gray-500">{{ __('Reports you have already signed keep the signature they were signed with.') }}</p>
            @else
                <p class="mt-2 rounded-lg border border-dashed border-gray-300 px-3 py-4 text-sm text-gray-500">{{ __('None yet. Reports you sign will show only your name until you add one.') }}</p>
            @endif
        </div>
    </div>
</x-profile.section>

<script>
    document.addEventListener('alpine:init', () => {
        // Strokes are kept in CSS pixels so the drawing survives a resize; the export is cropped to the ink.
        window.Alpine.data('signaturePad', () => ({
            strokes: [],
            current: null,

            get empty() {
                return this.strokes.length === 0;
            },

            init() {
                this.ctx = this.$refs.canvas.getContext('2d');
                new ResizeObserver(() => this.resize()).observe(this.$refs.canvas);
            },

            resize() {
                const canvas = this.$refs.canvas;
                const rect = canvas.getBoundingClientRect();
                if (rect.width === 0) return;
                this.dpr = Math.min(window.devicePixelRatio || 1, 3);
                canvas.width = Math.round(rect.width * this.dpr);
                canvas.height = Math.round(rect.height * this.dpr);
                this.ctx.setTransform(this.dpr, 0, 0, this.dpr, 0, 0);
                this.redraw();
            },

            point(event) {
                const rect = this.$refs.canvas.getBoundingClientRect();
                const width = event.pointerType === 'pen' && event.pressure > 0 ? 1 + event.pressure * 3 : 2.5;
                return { x: event.clientX - rect.left, y: event.clientY - rect.top, w: width };
            },

            start(event) {
                if (event.button > 0) return;
                event.preventDefault();
                this.$refs.canvas.setPointerCapture(event.pointerId);
                this.current = [this.point(event)];
                this.strokes.push(this.current);
                this.drawStroke(this.current, 0);
            },

            move(event) {
                if (!this.current) return;
                const coalesced = event.getCoalescedEvents ? event.getCoalescedEvents() : [];
                const events = coalesced.length ? coalesced : [event];
                events.forEach((e) => this.current.push(this.point(e)));
                this.drawStroke(this.current, this.current.length - events.length - 1);
            },

            end() {
                this.current = null;
            },

            undo() {
                this.strokes.pop();
                this.redraw();
            },

            clear() {
                this.strokes = [];
                this.redraw();
            },

            redraw() {
                const canvas = this.$refs.canvas;
                this.ctx.clearRect(0, 0, canvas.width, canvas.height);
                this.strokes.forEach((stroke) => this.drawStroke(stroke, 0));
            },

            // Draws the stroke from point `from` onwards; a single point is a dot.
            drawStroke(stroke, from) {
                const ctx = this.ctx;
                ctx.strokeStyle = ctx.fillStyle = '#111827';
                ctx.lineCap = ctx.lineJoin = 'round';
                if (stroke.length === 1) {
                    ctx.beginPath();
                    ctx.arc(stroke[0].x, stroke[0].y, stroke[0].w / 2, 0, Math.PI * 2);
                    ctx.fill();
                    return;
                }
                for (let i = Math.max(from, 0) + 1; i < stroke.length; i++) {
                    const a = stroke[i - 1];
                    const b = stroke[i];
                    ctx.lineWidth = (a.w + b.w) / 2;
                    ctx.beginPath();
                    ctx.moveTo(a.x, a.y);
                    ctx.lineTo(b.x, b.y);
                    ctx.stroke();
                }
            },

            // Crops to the drawn area (plus a margin) and keeps it within the server's 2000×1000 limit.
            prepare(event) {
                if (this.empty) {
                    event.preventDefault();
                    return;
                }
                const points = this.strokes.flat();
                const pad = 8;
                const canvas = this.$refs.canvas;
                const rect = canvas.getBoundingClientRect();
                const left = Math.max(Math.min(...points.map((p) => p.x)) - pad, 0);
                const top = Math.max(Math.min(...points.map((p) => p.y)) - pad, 0);
                const right = Math.min(Math.max(...points.map((p) => p.x)) + pad, rect.width);
                const bottom = Math.min(Math.max(...points.map((p) => p.y)) + pad, rect.height);
                const width = Math.max(right - left, 1) * this.dpr;
                const height = Math.max(bottom - top, 1) * this.dpr;
                const scale = Math.min(1, 2000 / width, 1000 / height);

                const out = document.createElement('canvas');
                out.width = Math.max(Math.round(width * scale), 1);
                out.height = Math.max(Math.round(height * scale), 1);
                out.getContext('2d').drawImage(canvas, left * this.dpr, top * this.dpr, width, height, 0, 0, out.width, out.height);
                this.$refs.data.value = out.toDataURL('image/png');
            },
        }));
    });
</script>
