<?php

namespace App\Http\Controllers\Auth;

use App\Enums\HospitalStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Hospital sign-up: creates a pending hospital together with its first
 * hospital admin. A platform admin must approve the hospital before its
 * staff can use the clinical features.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'hospital_name' => ['required', 'string', 'max:255'],
            'hospital_city' => ['required', 'string', 'max:255'],
            'hospital_phone' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $hospital = Hospital::create([
                'name' => $request->hospital_name,
                'slug' => $this->uniqueSlug($request->hospital_name),
                'city' => $request->hospital_city,
                'phone' => $request->hospital_phone,
                'status' => HospitalStatus::Pending,
            ]);

            $user = User::create([
                'hospital_id' => $hospital->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
            $user->assignRole(Role::HospitalAdmin->value);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'hospital';
        $slug = $base;

        for ($i = 2; Hospital::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}
