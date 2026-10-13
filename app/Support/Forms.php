<?php

namespace App\Support;

use App\Models\User;

/**
 * The clinical forms whose access a hospital admin grants per staff member, like heart's per-form
 * access flags (angiography, angiography_edit, ...). Each form has the permissions
 * "{form}.view", "{form}.create", "{form}.edit" and (for signed reports) "{form}.finalize".
 * They are given to users directly, not through their role, so one user can be restricted
 * without changing everyone with the same role; Role::formCeiling() caps what can be granted.
 */
final class Forms
{
    public const ABILITIES = ['view', 'create', 'edit', 'finalize'];

    /**
     * @return list<string> 'echo', 'admission' and every procedure key.
     */
    public static function keys(): array
    {
        return ['echo', 'admission', ...array_keys(Procedures::MODELS)];
    }

    /**
     * Admissions are records, not signed reports, so they have no "finalize".
     *
     * @return list<string>
     */
    public static function abilities(string $form): array
    {
        return $form === 'admission' ? ['view', 'create', 'edit'] : self::ABILITIES;
    }

    public static function permission(string $form, string $ability): string
    {
        return "{$form}.{$ability}";
    }

    public static function label(string $form): string
    {
        return match ($form) {
            'echo' => __('Echocardiography'),
            'admission' => __('Admissions'),
            default => __(Procedures::label($form)),
        };
    }

    /**
     * Every form permission, e.g. for seeding.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        $names = [];
        foreach (self::keys() as $form) {
            foreach (self::abilities($form) as $ability) {
                $names[] = self::permission($form, $ability);
            }
        }

        return $names;
    }

    /**
     * Form permissions limited to the given abilities.
     *
     * @param  list<string>  $abilities
     * @return list<string>
     */
    public static function permissionsFor(array $abilities): array
    {
        return array_values(array_filter(self::permissions(), fn (string $name) => in_array(substr($name, strrpos($name, '.') + 1), $abilities, true)));
    }

    public static function allows(User $user, string $form, string $ability): bool
    {
        return $user->can(self::permission($form, $ability));
    }

    /**
     * Procedure keys the user may use for the ability, e.g. for menus and lists.
     *
     * @return list<string>
     */
    public static function procedures(User $user, string $ability): array
    {
        return array_values(array_filter(array_keys(Procedures::MODELS), fn (string $key) => self::allows($user, $key, $ability)));
    }

    /**
     * Whether the user may record at least one kind of study (echo or a procedure).
     */
    public static function canRecordAny(User $user): bool
    {
        return self::allows($user, 'echo', 'create') || self::procedures($user, 'create') !== [];
    }

    /**
     * Whether the user may sign at least one kind of report.
     */
    public static function canSignAny(User $user): bool
    {
        return self::allows($user, 'echo', 'finalize') || self::procedures($user, 'finalize') !== [];
    }
}
