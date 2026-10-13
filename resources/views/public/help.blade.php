<x-public-layout title="Help center" description="How to register a hospital, invite staff, record echo and cath-lab studies, admit patients, write prescriptions, sign reports and secure your account.">
    @php($sections = [
        'getting-started' => 'Getting started',
        'roles' => 'Roles and permissions',
        'recording' => 'Recording a study',
        'reports' => 'Viewing and printing reports',
        'signing' => 'Signing and amending',
        'procedures' => 'Cath lab and discharge forms',
        'patients' => 'Finding patients',
        'admissions' => 'Admissions and wards',
        'prescriptions' => 'E-prescriptions',
        'statistics' => 'Statistics',
        'two-factor' => 'Two-factor login',
        'audit' => 'Audit log',
        'privacy' => 'Privacy and data',
        'faq' => 'FAQ',
    ])

    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 lg:grid lg:grid-cols-[14rem_1fr] lg:gap-12">
        <aside class="hidden lg:block">
            <nav class="sticky top-24 space-y-1 text-sm" aria-label="{{ __('Help topics') }}">
                @foreach ($sections as $id => $label)
                    <a href="#{{ $id }}" class="block rounded px-2 py-1 text-gray-600 hover:bg-gray-100 hover:text-gray-900">{{ __($label) }}</a>
                @endforeach
            </nav>
        </aside>

        <article class="max-w-3xl space-y-14 text-gray-700 leading-relaxed [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-gray-900 [&_h2]:scroll-mt-24 [&_h3]:font-semibold [&_h3]:text-gray-900 [&_h3]:mt-6 [&_ol]:list-decimal [&_ol]:ps-6 [&_ul]:list-disc [&_ul]:ps-6 [&_li]:mt-1 [&_a]:text-rose-700 [&_a]:underline">
            <header>
                <h1 class="text-4xl font-bold tracking-tight text-gray-900">{{ __('Help center') }}</h1>
                <p class="mt-3 text-lg text-gray-600">{{ __('Everything you need to run your echo lab and cath lab on :app.', ['app' => config('app.name')]) }}</p>
                @auth
                    <p class="mt-3 flex flex-wrap gap-x-4 text-sm">
                        @can('studies.create')
                            <a href="{{ route('studies.create') }}">{{ __('New Study') }}</a>
                        @endcan
                        @can('patients.view')
                            <a href="{{ route('patients.index') }}">{{ __('Patients') }}</a>
                        @endcan
                    </p>
                @endauth
            </header>

            <section id="getting-started">
                <h2>{{ __('Getting started') }}</h2>
                <ol class="mt-4">
                    <li><a href="{{ route('register') }}">{{ __('Register your hospital') }}</a> {{ __('with its name, city and phone number, and create the first administrator account.') }}</li>
                    <li>{{ __('Confirm your email address using the link we send you.') }}</li>
                    <li>{{ __('Wait for a platform administrator to approve the hospital. Until then you will see an "Awaiting approval" page.') }}</li>
                    <li>{{ __('Once approved, open Staff and invite your doctors, echo technicians, nurses and quality-control staff by email. Invitations expire after 7 days and can be revoked.') }}</li>
                    <li>{{ __('Each invitee sets their name, optional medical license number and password, and can start working right away.') }}</li>
                </ol>
            </section>

            <section id="roles">
                <h2>{{ __('Roles and permissions') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-sm border border-gray-200">
                        <thead class="bg-gray-50 text-start">
                            <tr><th class="p-2">{{ __('Role') }}</th><th class="p-2">{{ __('Can do') }}</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr><td class="p-2 font-medium">{{ __('Echo technician') }}</td><td class="p-2">{{ __('Record studies, edit drafts, search patients, view and print reports.') }}</td></tr>
                            <tr><td class="p-2 font-medium">{{ __('Nurse') }}</td><td class="p-2">{{ __('The same as an echo technician: record studies, edit drafts, search patients, view and print reports.') }}</td></tr>
                            <tr><td class="p-2 font-medium">{{ __('Quality control') }}</td><td class="p-2">{{ __('Everything a nurse can, plus review the audit log.') }}</td></tr>
                            <tr><td class="p-2 font-medium">{{ __('Doctor') }}</td><td class="p-2">{{ __('Everything a technician can, plus sign (finalize) reports, amend signed reports, and sign or cancel prescriptions.') }}</td></tr>
                            <tr><td class="p-2 font-medium">{{ __('Hospital admin') }}</td><td class="p-2">{{ __('Invite and manage staff, change roles, deactivate accounts, manage wards and the drug catalog, view reports and the audit log, and delete drafts.') }}</td></tr>
                            <tr><td class="p-2 font-medium">{{ __('Platform admin') }}</td><td class="p-2">{{ __('Approve, suspend and reactivate hospitals. Platform admins cannot see any patient data.') }}</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="recording">
                <h2>{{ __('Recording a study') }}</h2>
                <p class="mt-4">{{ __('Open New Study in the top navigation and choose Echocardiography. First name, last name and national ID are required; everything else is optional.') }}</p>
                <h3>{{ __('What the form covers') }}</h3>
                <ul>
                    <li>{{ __('Patient details: national ID, name, age, height, weight, BSA and blood pressure.') }}</li>
                    <li>{{ __('Left ventricle (LV) and right ventricle (RV).') }}</li>
                    <li>{{ __('Atria (LA / RA) and valves.') }}</li>
                    <li>{{ __('IVC, SPAP and pericardium, with sensible defaults filled in for you.') }}</li>
                </ul>
                <h3>{{ __('Automatic values') }}</h3>
                <p>{{ __('Leave a grading field on "Auto" (or empty) and it is calculated from your measurements: LV size from LVEDVI/LVEDV/LVEDDI/LVEDD, LV systolic function from EF, LVH from septal thickness, diastolic function from e′, E/e′, E/A and TR velocity, RV size and function, LA/RA size, SPAP from TRG plus estimated RAP, and aortic dilation by segment. Choose a value yourself to override it. On the report, auto-graded findings show how they were derived when you hover over them.') }}</p>
                <h3>{{ __('Live preview and local drafts') }}</h3>
                <p>{{ __('Turn on Live Preview to see the finished report while you type. Unsaved work is kept in your browser per patient tab, and only for your own account on that device, until you press Save to Database.') }}</p>
                <h3>{{ __('Dates') }}</h3>
                <p>{{ __('Date of birth and echo date use the Jalali (Solar Hijri) calendar, for example 1405/07/09. Persian digits are accepted.') }}</p>
            </section>

            <section id="reports">
                <h2>{{ __('Viewing and printing reports') }}</h2>
                <ul class="mt-4">
                    <li>{{ __('The report is formatted automatically and abnormal findings are underlined.') }}</li>
                    <li>{{ __('Press Print Report at the bottom of the report; it prints at a standard 120% scale.') }}</li>
                    <li>{{ __('To save a PDF, choose Save as PDF as the printer in your browser\'s print window.') }}</li>
                </ul>
            </section>

            <section id="signing">
                <h2>{{ __('Signing and amending') }}</h2>
                <ul class="mt-4">
                    <li>{{ __('Every saved study starts as a Draft and its printout is marked "DRAFT — NOT SIGNED".') }}</li>
                    <li>{{ __('A doctor reviews the report and presses Sign & Finalize. The report then shows the doctor\'s name, license number, signature image and signing time.') }}</li>
                    <li>{{ __('Signed reports are locked. To correct one, a doctor chooses Amend Report, makes the change and gives a reason; the report is re-signed and marked as amended.') }}</li>
                    <li>{{ __('Every signed version is kept and can be opened from the Signed versions list below the report.') }}</li>
                    <li>{{ __('Only drafts can be deleted, by their author or a hospital admin.') }}</li>
                </ul>
                <p class="mt-4">{{ __('Doctors upload their signature image (PNG or JPG) on their Profile page.') }}</p>
            </section>

            <section id="procedures">
                <h2>{{ __('Cath lab and discharge forms') }}</h2>
                <p class="mt-4">{{ __('New Study also lists the forms carried over from the heart system: coronary angiography, coronary angioplasty (PCI), right heart catheterization, peripheral angiography, cardiac intervention and the discharge summary.') }}</p>
                <ul>
                    <li>{{ __('Each form can be linked to one of the patient\'s admissions and is saved as a draft, then signed and amended exactly like an echo report.') }}</li>
                    <li>{{ __('Angiography, angioplasty and discharge reports are written up as a narrative from the fields you fill in; the other forms list the filled-in fields section by section.') }}</li>
                    <li>{{ __('Drafts of every form appear in the Worklist alongside echo drafts.') }}</li>
                </ul>
            </section>

            <section id="patients">
                <h2>{{ __('Finding patients') }}</h2>
                <p class="mt-4">{{ __('Use Patients or the quick search box with the patient\'s national ID to list all of their studies with status, a short summary and links to the full report.') }}</p>
                <p class="mt-4">{{ __('Open the patient record to see their demographics, contact details and admissions. Staff who record studies can edit these details.') }}</p>
            </section>

            <section id="admissions">
                <h2>{{ __('Admissions and wards') }}</h2>
                <ul class="mt-4">
                    <li>{{ __('From the patient record, choose New admission to record the ward, admission type, arrival, referring hospital and attending physician. Discharge the patient from the admission page.') }}</li>
                    <li>{{ __('The admission page lists every study recorded during that stay.') }}</li>
                    <li>{{ __('Ward patients shows everyone currently admitted, by ward, with their length of stay.') }}</li>
                    <li>{{ __('Hospital admins add, rename and deactivate wards on the Wards page. Inactive wards are no longer offered for new admissions.') }}</li>
                    <li>{{ __('Admissions can also be imported from the hospital information system; ask your operator.') }}</li>
                </ul>
            </section>

            <section id="prescriptions">
                <h2>{{ __('E-prescriptions') }}</h2>
                <ul class="mt-4">
                    <li>{{ __('Choose Write a prescription on the patient page, or Write discharge prescription on a discharge summary, and pick drugs from the catalog.') }}</li>
                    <li>{{ __('Technicians, nurses and quality control can draft a prescription; only a doctor can sign it. A signed prescription cannot be changed, only cancelled with a reason and replaced.') }}</li>
                    <li>{{ __('The printed prescription carries a QR code. Scanning it opens a public verification page that shows whether the prescription is genuine and still valid, without any patient details.') }}</li>
                    <li>{{ __('Drugs lists the shared catalog. Hospital admins can add their own drugs and withdraw ones they no longer use.') }}</li>
                </ul>
            </section>

            <section id="statistics">
                <h2>{{ __('Statistics') }}</h2>
                <p class="mt-4">{{ __('Statistics counts your hospital\'s studies and patients per form and per month, signed and draft, and charts key findings. Choose a period to narrow it down.') }}</p>
            </section>

            <section id="two-factor">
                <h2>{{ __('Two-factor login') }}</h2>
                <ol class="mt-4">
                    <li>{{ __('Install an authenticator app such as Google Authenticator, Microsoft Authenticator or Aegis.') }}</li>
                    <li>{{ __('Open Profile → Two-factor authentication, enter your password and press Set up.') }}</li>
                    <li>{{ __('Scan the QR code and enter the 6-digit code shown in the app.') }}</li>
                    <li>{{ __('Save the recovery codes somewhere safe. Each one works once if you lose your phone.') }}</li>
                </ol>
                <p class="mt-4">{{ __('Your organisation may require two-factor login; in that case you are asked to set it up before you can continue.') }}</p>
            </section>

            <section id="audit">
                <h2>{{ __('Audit log') }}</h2>
                <p class="mt-4">{{ __('Hospital admins can review who logged in, which patients were looked up, and which reports were viewed, edited, signed, amended or deleted, with the exact fields that changed. Entries can be filtered by action, user and date and can never be edited or deleted.') }}</p>
            </section>

            <section id="privacy">
                <h2>{{ __('Privacy and data') }}</h2>
                <ul class="mt-4">
                    <li>{{ __('Each hospital\'s patients, studies, staff and audit log are visible only to that hospital\'s staff.') }}</li>
                    <li>{{ __('National ID and phone number are encrypted in the database.') }}</li>
                    <li>{{ __('The searched national ID is never written to the audit log.') }}</li>
                    <li>{{ __('Hospitals are responsible for complying with the health-data regulations that apply to them; self-hosting keeps all data on your own servers.') }}</li>
                </ul>
            </section>

            <section id="faq">
                <h2>{{ __('FAQ') }}</h2>
                <h3>{{ __('How is the IVC graded automatically?') }}</h3>
                <p>{{ __('On "Auto", an IVC of 21 mm or less is reported as normal size and anything larger as dilated.') }}</p>
                <h3>{{ __('How do I find a patient\'s previous echos?') }}</h3>
                <p>{{ __('Enter the patient\'s national ID in the quick search box at the top of the page, or open Patients.') }}</p>
                <h3>{{ __('I did not receive my invitation email.') }}</h3>
                <p>{{ __('Ask your hospital admin to check the address on the Staff page and send the invitation again; re-inviting replaces the old link.') }}</p>
                <h3>{{ __('I lost my phone and my recovery codes.') }}</h3>
                <p>{{ __('Contact your hospital admin or the platform operator to have two-factor login reset after verifying your identity.') }}</p>
                <h3>{{ __('Can we run it on our own servers?') }}</h3>
                <p>{!! __('Yes. The full source is on <a href=":url">GitHub</a> with Docker instructions. Under the AGPL-3.0 you must publish any changes you make if you offer the modified version to users over a network.', ['url' => e(config('app.source_url'))]) !!}</p>
                <h3>{{ __('How do I report a security problem?') }}</h3>
                <p>{!! __('Please follow the <a href=":url">security policy</a> and report it privately rather than opening a public issue.', ['url' => e(config('app.source_url').'/blob/main/SECURITY.md')]) !!}</p>
            </section>
        </article>
    </div>
</x-public-layout>
