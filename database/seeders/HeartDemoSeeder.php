<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\AngiographyStudy;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use App\Support\Audit;
use App\Support\CurrentHospital;
use App\Support\Procedures;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Fictional patients, admissions and angiographies in the heart system's fields and codes, for
 * demos. Patients already present (by national ID) are skipped, so it is safe to run again.
 */
class HeartDemoSeeder extends Seeder
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function run(Hospital $hospital): array
    {
        $current = app(CurrentHospital::class);
        $previous = $current->id();
        $current->set($hospital->id);

        try {
            $doctor = $this->staff($hospital, Role::Doctor);
            $created = 0;

            foreach (self::cases() as $case) {
                if (Patient::whereNationalId($case['patient']['national_id'])->exists()) {
                    continue;
                }

                DB::transaction(fn () => $this->seedCase($case, $hospital, $doctor));
                $created++;
            }

            return ['created' => $created, 'skipped' => count(self::cases()) - $created];
        } finally {
            $current->set($previous);
        }
    }

    /**
     * @param  array<string, mixed>  $case
     */
    private function seedCase(array $case, Hospital $hospital, ?User $doctor): void
    {
        $patient = Patient::create($case['patient']);
        Audit::record('patient.created', $patient);

        $admission = $patient->admissions()->create([...$case['admission'], 'his_id' => 'DEMO-'.$case['patient']['national_id']]);

        $author = $this->staff($hospital, ...$case['author']);
        $study = AngiographyStudy::create([
            ...$this->checked([...self::baseline(), ...$case['study']]),
            'patient_id' => $patient->id,
            'admission_id' => $admission->id,
            'created_by' => $author?->id,
        ]);
        Audit::record('angiography.created', $study, [], $author);

        // Signing needs a doctor; without one every demo study stays a draft.
        if (! $doctor || $case['status'] === 'draft') {
            return;
        }

        $study->finalize($doctor);
        Audit::record('angiography.finalized', $study, [], $doctor);

        if ($case['status'] === 'amended') {
            [$changes, $reason] = $case['amendment'];
            $study->amend($this->checked([...$study->only(array_keys($study->fields())), ...$changes]), $doctor, $reason);
            Audit::record('angiography.amended', $study, ['reason' => $reason], $doctor);
        }
    }

    /**
     * Fails on a field, code or condition the angiography form would not accept, rather than
     * letting a typo be dropped silently.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function checked(array $values): array
    {
        $fields = Procedures::fields('angiography');

        foreach ($values as $name => $value) {
            $field = $fields[$name] ?? throw new InvalidArgumentException("Unknown angiography field {$name}.");

            if ($value === null) {
                continue;
            }
            if ($field['type'] === 'choice' && ! array_key_exists($value, $field['options'])) {
                throw new InvalidArgumentException("{$name} has no code {$value}.");
            }
            if (isset($field['show_if']) && (string) ($values[$field['show_if'][0]] ?? '') !== $field['show_if'][1]) {
                throw new InvalidArgumentException("{$name} is only recorded when {$field['show_if'][0]} = {$field['show_if'][1]}.");
            }
        }

        return $values;
    }

    private function staff(Hospital $hospital, Role ...$roles): ?User
    {
        foreach ($roles as $role) {
            $user = User::where('hospital_id', $hospital->id)->role($role->value)->orderBy('id')->first();

            if ($user) {
                return $user;
            }
        }

        return null;
    }

    /**
     * What every demo study shares: an elective transradial study with normal flow.
     *
     * @return array<string, mixed>
     */
    private static function baseline(): array
    {
        return [
            'angiography_status' => 'Elective_angiography',
            'native_coronary_angiography' => true,
            'Time_of_angiography' => '09:30',
            'contrast_agentt1' => '1',
            'contrast_volume2' => 80,
            'fluro_dose2' => 450,
            'arterial_Access_site' => '3',
            'catheter_name1' => 'Tiger 5F',
            ...self::normal('lm'),
            ...self::normal('lad'),
            ...self::normal('lcx'),
            ...self::normal('rca'),
            'lvent_lvsize' => '1',
            'lvent_lvef' => 55,
            'lvent_lvent_mr' => '1',
            'lvent_ar' => '1',
            'prc' => '0',
            'Death' => '1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function normal(string $vessel): array
    {
        return [
            "{$vessel}_Vessel_patency" => '1',
            "{$vessel}_TIMI_Flow" => '4',
            "{$vessel}_Calcification" => '1',
            "{$vessel}_Thrombus" => '1',
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function cases(): array
    {
        return [
            [
                'patient' => ['national_id' => '9990000001', 'first_name' => 'Maryam', 'last_name' => 'Ahmadi', 'dob' => '1348/03/12', 'gender' => 'Female'],
                'admission' => ['ward' => 'Cardiology', 'admitted_on' => '1405/06/02', 'discharged_on' => '1405/06/04', 'physician' => 'Dr. Demo'],
                'author' => [Role::Technician],
                'status' => 'final',
                'study' => [
                    'DateOf_Date_of_angiography' => '1405/06/03',
                    'lvent_lvef' => 60,
                    'result_legacy' => '1',
                    'recommend_fallowup' => '1',
                    'LifeStyleModificationAndFollowUp' => true,
                    'resulttt' => 'Normal coronary arteries.',
                ],
            ],
            [
                'patient' => ['national_id' => '9990000002', 'first_name' => 'Hossein', 'last_name' => 'Karimi', 'dob' => '1342/11/02', 'gender' => 'Male'],
                'admission' => ['ward' => 'CCU', 'admitted_on' => '1405/06/10', 'physician' => 'Dr. Demo'],
                'author' => [Role::Technician],
                'status' => 'final',
                'study' => [
                    'DateOf_Date_of_angiography' => '1405/06/11',
                    'lad_Vessel_patency' => '3',
                    'lad_proximal_part_segment' => '2',
                    'lad_prox_Stenotic' => '4',
                    'lad_prox_lesiontype' => 'B2',
                    'lad_Calcification' => '2',
                    'lvent_lvef' => 50,
                    'lvent_RWMA_Anteroapical' => '1',
                    'result_legacy' => '4',
                    'PCI' => '1',
                    'recommend_pci_lad' => true,
                    'resulttt' => 'Single-vessel disease: very severe proximal LAD stenosis.',
                ],
            ],
            [
                'patient' => ['national_id' => '9990000003', 'first_name' => 'Reza', 'last_name' => 'Mohammadi', 'dob' => '1338/05/20', 'gender' => 'Male'],
                'admission' => ['ward' => 'Cardiology', 'admitted_on' => '1405/06/15', 'physician' => 'Dr. Demo'],
                'author' => [Role::Technician],
                'status' => 'amended',
                'study' => [
                    'DateOf_Date_of_angiography' => '1405/06/16',
                    'lad_Vessel_patency' => '3',
                    'lad_proximal_part_segment' => '2',
                    'lad_prox_Stenotic' => '3',
                    'lcx_Vessel_patency' => '3',
                    'lcx_Mid_part_segment' => '2',
                    'lcx_mid_Stenotic' => '3',
                    'rca_Vessel_patency' => '3',
                    'rca_proximal_part' => '2',
                    'rca_prox_Stenotic' => '4',
                    'rca_Calcification' => '3',
                    'lvent_lvsize' => '2',
                    'lvent_lvef' => 40,
                    'lvent_lvent_mr' => '2',
                    'result_legacy' => '6',
                    'recommend_cabg' => '1',
                    'resulttt' => 'Three-vessel disease.',
                ],
                'amendment' => [
                    ['other_recommendation' => 'Heart team discussion: CABG preferred (diabetic, three-vessel disease).'],
                    'Added the heart team recommendation.',
                ],
            ],
            [
                'patient' => ['national_id' => '9990000004', 'first_name' => 'Ali', 'last_name' => 'Hosseini', 'dob' => '1355/01/15', 'gender' => 'Male'],
                'admission' => ['ward' => 'Emergency', 'admitted_on' => '1405/07/01', 'physician' => 'Dr. Demo'],
                'author' => [Role::Technician],
                'status' => 'draft',
                'study' => [
                    'angiography_status' => 'Emergent_angigraphy',
                    'DateOf_Date_of_angiography' => '1405/07/01',
                    'Time_of_angiography' => '02:15',
                    'arterial_Access_site' => '1',
                    'rca_Vessel_patency' => '4',
                    'rca_TIMI_Flow' => '1',
                    'rca_Thrombus' => '3',
                    'lvent_lvef' => 45,
                    'lvent_RWMA_inferior' => '1',
                    'result_legacy' => '4',
                    'PCI' => '1',
                    'recommend_pci_rca' => true,
                    'resulttt' => 'Acute total occlusion of the RCA with thrombus.',
                ],
            ],
            [
                'patient' => ['national_id' => '9990000005', 'first_name' => 'Fatemeh', 'last_name' => 'Rahimi', 'dob' => '1360/08/09', 'gender' => 'Female'],
                'admission' => ['ward' => 'Cardiology', 'admitted_on' => '1405/07/05', 'physician' => 'Dr. Demo'],
                'author' => [Role::Nurse, Role::Technician],
                'status' => 'draft',
                'study' => [
                    'DateOf_Date_of_angiography' => '1405/07/06',
                    'lad_coronary_flow' => '2',
                    'Slow_coronary_flow_pattern' => '1',
                    'result_legacy' => '1',
                    'recommend_treatment' => '1',
                    'resulttt' => 'Slow coronary flow without obstructive disease.',
                ],
            ],
            [
                'patient' => ['national_id' => '9990000006', 'first_name' => 'Zahra', 'last_name' => 'Jafari', 'dob' => '1345/02/28', 'gender' => 'Female'],
                'admission' => ['ward' => 'CCU', 'admitted_on' => '1405/07/08', 'physician' => 'Dr. Demo'],
                'author' => [Role::QualityControl, Role::Technician],
                'status' => 'draft',
                'study' => [
                    'DateOf_Date_of_angiography' => '1405/07/09',
                    'lm_Vessel_patency' => '3',
                    'lm_Body' => '2',
                    'lm_body_Stenotic1' => '3',
                    'lad_Vessel_patency' => '3',
                    'lad_proximal_part_segment' => '2',
                    'lad_prox_Stenotic' => '2',
                    'result_legacy' => '7',
                    'LMD' => '1',
                    'recommend_cabg' => '1',
                    'resulttt' => 'Significant left main disease.',
                ],
            ],
        ];
    }
}
