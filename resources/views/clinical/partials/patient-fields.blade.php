@php
    /**
     * The patient details every study starts with, entered once on the dashboard (step 1): name,
     * national ID, date of birth, phone, gender, study date and admission number. Entering a
     * registered patient's national ID fills in their details and offers their admission numbers
     * (clinical/partials/patient-lookup.blade.php). Field names match config/echo.php "patient_fields".
     *
     * @var array<string, mixed> $values  Values to show.
     */
    $values ??= [];
    $value = fn (string $name) => old($name, $values[$name] ?? null);
@endphp
<div class="field">
    <label for="first_name">{{ __('First name') }} <span class="req">*</span></label>
    <input id="first_name" name="first_name" type="text" value="{{ $value('first_name') }}" required />
    @error('first_name') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="last_name">{{ __('Last name') }} <span class="req">*</span></label>
    <input id="last_name" name="last_name" type="text" value="{{ $value('last_name') }}" required />
    @error('last_name') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="nationalID">{{ __('National ID') }} <span class="req">*</span></label>
    <input id="nationalID" name="nationalID" type="text" inputmode="numeric" value="{{ $value('nationalID') }}" required aria-describedby="nationalID-status" />
    <span id="nationalID-status" class="small" role="status" aria-live="polite"></span>
    @error('nationalID') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="dob">{{ __('Date of birth') }}</label>
    <input id="dob" name="dob" type="text" class="no-voice" value="{{ $value('dob') }}" />
    <span id="dob-display"></span>
    @error('dob') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="phone_number">{{ __('Phone number') }}</label>
    <input id="phone_number" name="phone_number" type="text" value="{{ $value('phone_number') }}" />
    @error('phone_number') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>

<div class="field">
    <label for="gender">{{ __('Gender') }}</label>
    <select id="gender" name="gender">
        <option value="">{{ __('Select gender') }}</option>
        @foreach (['Male', 'Female'] as $gender)
            <option value="{{ $gender }}" @selected($value('gender') === $gender)>{{ __($gender) }}</option>
        @endforeach
    </select>
</div>

<div class="field">
    <label for="study_date">{{ __('Date of study') }}</label>
    <input id="study_date" name="study_date" type="text" class="no-voice" value="{{ $value('study_date') }}" placeholder="1405/01/01" dir="ltr" />
    @error('study_date') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>
<div class="field">
    <label for="admission_number">{{ __('Admission number') }}</label>
    <input id="admission_number" name="admission_number" type="text" value="{{ $value('admission_number') }}" list="patient-admissions" dir="ltr" />
    <datalist id="patient-admissions"></datalist>
    @error('admission_number') <span class="small" style="color:#dc2626">{{ $message }}</span> @enderror
</div>
