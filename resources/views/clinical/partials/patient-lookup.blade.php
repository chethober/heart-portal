{{--
    Registered-patient check for the dashboard patient step: when a national ID is typed, asks the
    server (patients.lookup) whether it belongs to a patient of this hospital. A registered
    patient's details are filled in and their admission numbers offered; a new one is flagged as
    such, and an ID whose check digit is wrong is warned about (non-Iranian IDs may still be saved).
--}}
<script>
    window.patientLookup = (function () {
        const url = @js(route('patients.lookup'));
        const fields = ['first_name', 'last_name', 'dob', 'phone_number', 'gender'];
        const input = document.getElementById('nationalID');
        const status = document.getElementById('nationalID-status');
        const admissionList = document.getElementById('patient-admissions');
        let timer = null;
        let last = null;

        const digits = (value) => value.replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d)).replace(/[\s-]/g, '');

        function show(text, colour, link) {
            if (!status) return;
            status.textContent = text;
            status.style.color = colour;
            if (link) {
                const a = document.createElement('a');
                a.href = link; a.target = '_blank'; a.textContent = ' View record'; a.style.textDecoration = 'underline';
                status.appendChild(a);
            }
        }

        // The patient's admission numbers, offered as suggestions for the admission number field.
        function setAdmissions(admissions) {
            if (!admissionList) return;
            admissionList.replaceChildren(...admissions.filter((admission) => admission.number)
                .map((admission) => new Option(admission.label, admission.number)));
        }

        async function check(fill = true) {
            const id = digits(input.value.trim());
            if (id.length < 5) { show('', ''); setAdmissions([]); last = null; return; }
            if (id === last && fill) return;
            last = id;

            let result;
            try {
                const response = await fetch(url + '?nationalID=' + encodeURIComponent(id), { headers: { 'Accept': 'application/json' } });
                if (!response.ok) return;
                result = await response.json();
            } catch (e) { return; }
            if (digits(input.value.trim()) !== id) return; // typed on meanwhile

            if (result.patient) {
                if (fill) {
                    const form = input.form;
                    fields.forEach((name) => {
                        const el = form.elements[name];
                        const value = result.patient[name] ?? '';
                        if (el && value !== '' && el.value !== value) {
                            el.value = value;
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                }
                setAdmissions(result.patient.admissions);
                show('Registered patient: ' + result.patient.first_name + ' ' + result.patient.last_name + (fill ? ' — details filled in.' : '.'), '#15803d', result.patient.url);
            } else {
                setAdmissions([]);
                show(result.valid ? 'New patient — they will be registered when you start a study.' : 'Check the national ID: its check digit is not valid.', result.valid ? '#4b5563' : '#b45309');
            }
        }

        if (input) {
            input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => check(true), 500); });
            input.addEventListener('change', () => { clearTimeout(timer); check(true); });
            if (input.value.trim() !== '') check(false);
        }

        return { check: (fill) => input && check(fill) };
    })();
</script>
