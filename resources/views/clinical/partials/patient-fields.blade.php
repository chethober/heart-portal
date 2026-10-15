@php
    /**
     * The patient details every study form starts with (echo and the cath-lab forms): name,
     * national ID, date of birth, phone, gender and the admission the study belongs to. Entering a
     * registered patient's national ID fills in their details and admissions (clinical/partials/
     * patient-lookup.blade.php). Field names match config/echo.php "patient_fields".
     *
     * @var array<string, mixed> $values  Values to show (procedure forms fill these on the server;
     *                                    the echo form fills them from its script).
     * @var \Illuminate\Support\Collection<int, \App\Models\Admission> $admissions  The known patient's admissions.
     * @var string|int|null $admission  The admission to select.
     */
    $values ??= [];
    $admissions ??= collect();
    $admission ??= null;
    $value = fn (string $name) => old($name, $values[$name] ?? null);
@endphp
<div class="field">
    <label for="first_name">First Name <span class="req">*</span></label>
    <input id="first_name" name="first_name" type="text" value="{{ $value('first_name') }}" required />
    @error('first_name') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="last_name">Last Name <span class="req">*</span></label>
    <input id="last_name" name="last_name" type="text" value="{{ $value('last_name') }}" required />
    @error('last_name') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="nationalID">National ID <span class="req">*</span></label>
    <input id="nationalID" name="nationalID" type="text" inputmode="numeric" value="{{ $value('nationalID') }}" required aria-describedby="nationalID-status" />
    <span id="nationalID-status" class="small" role="status" aria-live="polite"></span>
    @error('nationalID') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="dob">Date of Birth</label>
    <input id="dob" name="dob" type="text" class="no-voice" value="{{ $value('dob') }}" />
    <span id="dob-display"></span>
    @error('dob') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="phone_number">Phone Number</label>
    <input id="phone_number" name="phone_number" type="text" value="{{ $value('phone_number') }}" />
    @error('phone_number') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="gender">Gender</label>
    <select id="gender" name="gender">
        <option value="">Select gender</option>
        @foreach (['Male', 'Female'] as $gender)
            <option value="{{ $gender }}" @selected($value('gender') === $gender)>{{ $gender }}</option>
        @endforeach
    </select>
</div>

{{-- The patient's admissions (heart links each form to an admit record); filled in by the lookup. --}}
<div class="field" id="admission_field" @if ($admissions->isEmpty()) hidden @endif>
    <label for="admission_id">Admission</label>
    <select id="admission_id" name="admission_id" data-native data-selected="{{ $admission }}">
        <option value="">Not linked to an admission</option>
        @if ($admission && ! $admissions->contains('id', (int) $admission))
            <option value="{{ $admission }}" selected>Admission #{{ $admission }}</option>
        @endif
        @foreach ($admissions as $option)
            <option value="{{ $option->id }}" @selected((string) $admission === (string) $option->id)>{{ $option->summary() }}{{ $option->isDischarged() ? ' (discharged '.$option->discharged_on.')' : '' }}</option>
        @endforeach
    </select>
    @error('admission_id') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>
