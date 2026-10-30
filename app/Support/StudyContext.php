<?php

namespace App\Support;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The patient step of the two-step study flow: the dashboard records who the study is for
 * (patient, admission, date) in the session under a token, and the study form opened with
 * ?context=<token> starts from it. A token only works for the member who created it.
 *
 * The started studies are also the multi-patient taskbar: one tab each, until the study is
 * saved or the tab is closed.
 */
final class StudyContext
{
    private const KEY = 'study_contexts';

    /**
     * Keep a started study and return its token. It takes the place of the tab it $replaces
     * (its patient step was resubmitted) or of the same patient's tab for the same form.
     *
     * @param  array<string, mixed>  $context
     */
    public static function put(Request $request, array $context, ?string $replaces = null): string
    {
        $token = (string) Str::uuid();
        $kept = [];
        $placed = false;
        foreach ($request->session()->get(self::KEY, []) as $key => $existing) {
            $same = $key === $replaces || ($existing['user_id'] === $context['user_id']
                && $existing['patient_id'] === $context['patient_id'] && $existing['form'] === $context['form']);
            if (! $same) {
                $kept[$key] = $existing;
            } elseif (! $placed) {
                $kept[$token] = $context;
                $placed = true;
            }
        }
        if (! $placed) {
            $kept[$token] = $context;
        }
        $request->session()->put(self::KEY, array_slice($kept, -100, null, true));

        return $token;
    }

    /** Close a started study's tab (it was saved, or closed in the taskbar). */
    public static function forget(Request $request, ?string $token): void
    {
        if (filled($token)) {
            $request->session()->forget(self::KEY.'.'.$token);
        }
    }

    /**
     * The member's started studies, oldest first, as taskbar tabs.
     *
     * @return list<array{token: string, name: string, nationalID: string, study: string, url: string}>
     */
    public static function tabs(Request $request): array
    {
        $user = $request->user();
        $contexts = array_filter($request->session()->get(self::KEY, []),
            fn (array $context) => $context['user_id'] === $user->id && $context['hospital_id'] === $user->hospital_id);
        $patients = Patient::whereIn('id', array_column($contexts, 'patient_id'))->get()->keyBy('id');
        $tabs = [];
        foreach ($contexts as $token => $context) {
            $patient = $patients->get($context['patient_id']);
            if ($patient) {
                $tabs[] = [
                    'token' => (string) $token,
                    'name' => trim($patient->first_name.' '.$patient->last_name),
                    'nationalID' => (string) $patient->national_id,
                    'study' => $context['form'] === 'echo' ? __('Echocardiography') : __(Procedures::label($context['form'])),
                    'url' => self::url((string) $token, $context['form']),
                ];
            }
        }

        return $tabs;
    }

    public static function url(string $token, string $form): string
    {
        return $form === 'echo'
            ? route('studies.create', ['context' => $token])
            : route('procedures.create', ['procedure' => $form, 'context' => $token]);
    }

    /** @return array<string, mixed>|null */
    public static function get(Request $request, ?string $form = null): ?array
    {
        if (! $request->filled('context')) {
            return null;
        }
        $context = $request->session()->get(self::KEY.'.'.$request->string('context')->toString());
        abort_unless(is_array($context)
            && $context['user_id'] === $request->user()->id
            && $context['hospital_id'] === $request->user()->hospital_id
            && ($form === null || $context['form'] === $form), 404);
        $patient = Patient::findOrFail($context['patient_id']);
        $admission = $context['admission_id'] ? Admission::where('patient_id', $patient->id)->findOrFail($context['admission_id']) : null;

        return [...$context, 'patient' => $patient, 'admission' => $admission];
    }

    /** @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function values(array $context): array
    {
        return [
            ...$context['patient']->toFormArray(),
            'admission_id' => $context['admission_id'],
            $context['form'] === 'echo' ? 'echo_date' : config('procedures.'.$context['form'].'.date_field') => $context['study_date'],
        ];
    }
}
