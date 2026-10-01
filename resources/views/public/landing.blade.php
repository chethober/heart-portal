<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-rose-50 to-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-16 pb-20 grid gap-12 lg:grid-cols-2 items-center">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-rose-700 ring-1 ring-rose-200">
                    {{ __('Open source · Multi-hospital · Self-hostable') }}
                </p>
                <h1 class="mt-5 text-4xl sm:text-5xl font-bold tracking-tight text-gray-900">
                    {{ __('Echo reports your cardiologists can sign with confidence.') }}
                </h1>
                <p class="mt-5 text-lg text-gray-600">
                    {{ __('Technicians enter measurements once; chamber size, systolic and diastolic function, SPAP and aortic dilation are graded automatically against guideline cut-offs. Doctors review, sign and print a bilingual report — every step audited.') }}
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-700">{{ __('Open dashboard') }}</a>
                    @else
                        <a href="{{ route('register') }}" class="rounded-md bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700">{{ __('Register your hospital') }}</a>
                        <a href="{{ route('login') }}" class="rounded-md bg-white px-5 py-3 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">{{ __('Staff log in') }}</a>
                    @endauth
                    <a href="{{ config('app.source_url') }}" class="rounded-md px-5 py-3 text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('View on GitHub') }} →</a>
                </div>
            </div>

            {{-- Report preview illustration (static, no patient data) --}}
            <div class="relative" aria-hidden="true">
                <div class="rounded-xl bg-white shadow-xl ring-1 ring-gray-200 p-6 text-sm">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div class="font-semibold">{{ __('Echocardiography report') }}</div>
                        <span class="rounded-full bg-green-50 px-2 py-0.5 text-xs font-semibold text-green-700 ring-1 ring-green-200">{{ __('Final') }}</span>
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 text-xs">
                        <div class="rounded-md bg-gray-50 p-2"><dt class="text-gray-500">LVEF</dt><dd class="font-semibold">58 %</dd></div>
                        <div class="rounded-md bg-gray-50 p-2"><dt class="text-gray-500">LVEDVI</dt><dd class="font-semibold">62 ml/m²</dd></div>
                        <div class="rounded-md bg-gray-50 p-2"><dt class="text-gray-500">SPAP</dt><dd class="font-semibold">33 mmHg</dd></div>
                    </dl>
                    <div class="mt-4 space-y-2 text-gray-700">
                        <p><span class="font-semibold">LV:</span> {{ __('Normal LV size, with normal systolic function, normal diastolic function.') }}</p>
                        <p><span class="font-semibold">RV:</span> {{ __('Normal RV size with normal systolic function (TAPSE 21 mm).') }}</p>
                        <p><span class="font-semibold">{{ __('Valves') }}:</span> <u>{{ __('Mild MR') }}</u>, {{ __('no AS.') }}</p>
                    </div>
                    <div class="mt-5 flex items-end justify-between border-t pt-3 text-xs text-gray-500">
                        <span>{{ __('Auto-graded fields are marked and explained on hover') }}</span>
                        <span class="text-right"><span class="block font-semibold text-gray-700">{{ __('Signed') }}</span>{{ __('Reporting cardiologist') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight">{{ __('Everything an echo lab needs, nothing it doesn\'t') }}</h2>
            <p class="mt-3 max-w-2xl text-gray-600">{{ __('Built with the people who write echo reports every day, from the first measurement to the signed printout.') }}</p>

            @php($features = [
                ['Structured echo form', 'Sections for LV, RV, atria, all four valves, IVC/PAP and pericardium, with regional wall motion, live report preview, voice input and a Jalali date picker.', 'M9 12h6m-6 4h6M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z'],
                ['Automatic grading', 'BSA, indexed volumes and sex-specific cut-offs grade LV/RV/LA/RA size, systolic and diastolic function, SPAP from TRG + RAP and aortic dilation — always overridable.', 'M4 19h16M7 15l3-4 3 2 4-6'],
                ['Signed, versioned reports', 'Drafts become final only when a doctor signs. Changes to a signed report are amendments with a reason, and every signed version is kept.', 'M9 12l2 2 4-4m5 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['Built for many hospitals', 'Each hospital registers, invites its own doctors and technicians, brands its reports, and only ever sees its own patients.', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6'],
                ['Audit trail', 'Logins, patient lookups, views, edits, signatures and staff changes are recorded in an append-only log that hospital admins can review.', 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                ['Open source', 'AGPL-3.0 licensed. Run it on your own servers, inspect every line, and contribute improvements back.', 'M10 20l4-16M6 16l-4-4 4-4m12 0 4 4-4 4'],
            ])
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($features as [$title, $text, $icon])
                    <div class="rounded-xl border border-gray-200 p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $icon }}" /></svg>
                        </div>
                        <h3 class="mt-4 font-semibold">{{ __($title) }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __($text) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <h2 class="text-3xl font-bold tracking-tight">{{ __('Up and running in an afternoon') }}</h2>
            <ol class="mt-12 grid gap-6 md:grid-cols-4">
                @foreach ([
                    ['Register', 'Sign up your hospital and its administrator account.'],
                    ['Get approved', 'A platform administrator verifies and activates the hospital.'],
                    ['Invite your team', 'Send email invitations to doctors and echo technicians, each with the right role.'],
                    ['Report', 'Technicians record studies; doctors review, sign and print.'],
                ] as $i => [$step, $text])
                    <li class="rounded-xl bg-white p-6 ring-1 ring-gray-200">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-rose-600 text-sm font-bold text-white">{{ $i + 1 }}</span>
                        <h3 class="mt-4 font-semibold">{{ __($step) }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ __($text) }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Security --}}
    <section id="security" class="py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 grid gap-12 lg:grid-cols-2">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">{{ __('Patient data, treated accordingly') }}</h2>
                <p class="mt-3 text-gray-600">{{ __('Security is part of the design, not an add-on. Every hospital is isolated from every other, and every access leaves a trace.') }}</p>
            </div>
            <ul class="space-y-4 text-sm">
                @foreach ([
                    'Strict per-hospital isolation enforced on every query — no data is visible without a hospital context.',
                    'National ID and phone numbers encrypted at rest; lookups use a keyed hash.',
                    'Authenticator-app two-factor login, enforceable for all staff, with one-time recovery codes.',
                    'Role-based permissions: technicians draft, doctors sign, admins manage staff and review the audit log.',
                    'Append-only audit log protected by a database trigger.',
                    'Signatures stored privately and served only to staff of the same hospital.',
                ] as $point)
                    <li class="flex gap-3">
                        <svg class="mt-0.5 h-5 w-5 flex-none text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" /></svg>
                        <span>{{ __($point) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 bg-gray-900 text-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div>
                <h2 class="text-2xl font-bold">{{ __('Bring your echo lab online') }}</h2>
                <p class="mt-2 text-gray-300">{{ __('Use the hosted platform or deploy it yourself with Docker.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('register') }}" class="rounded-md bg-rose-600 px-5 py-3 text-sm font-semibold hover:bg-rose-500">{{ __('Register your hospital') }}</a>
                <a href="{{ route('help') }}" class="rounded-md bg-white/10 px-5 py-3 text-sm font-semibold hover:bg-white/20">{{ __('Read the guide') }}</a>
            </div>
        </div>
    </section>
</x-public-layout>
