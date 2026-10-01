<?php

use App\Enums\Role;
use App\Models\User;
use Symfony\Component\Finder\Finder;

/**
 * Every string passed to __() in the app and the pagination links it renders,
 * except framework group keys such as auth.failed.
 *
 * @return list<string>
 */
function translatableStrings(): array
{
    $keys = [];
    $files = Finder::create()->files()->name('*.php')->in([app_path(), resource_path('views'), base_path('routes')])
        ->append([base_path('vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php')]);

    foreach ($files as $file) {
        preg_match_all('/__\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/', $file->getContents(), $matches);
        foreach ($matches[1] as $literal) {
            $keys[stripcslashes(substr($literal, 1, -1))] = true;
        }
    }

    return array_values(array_filter(array_keys($keys), fn (string $key) => ! preg_match('/^[a-z]+\.[a-z_.]+$/', $key)));
}

dataset('translated locales', array_map(fn (string $file) => basename($file, '.json'), glob(__DIR__.'/../../lang/*.json')));

test('every selectable language has a translation file', function () {
    foreach (array_keys(config('app.available_locales')) as $locale) {
        expect($locale === 'en' || file_exists(lang_path("{$locale}.json")))->toBeTrue("Missing lang/{$locale}.json");
    }
});

test('every interface string is translated', function (string $locale) {
    $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

    expect(array_diff(translatableStrings(), array_keys($translations)))->toBe([]);
})->with('translated locales');

test('translations keep their placeholders', function (string $locale) {
    $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

    foreach ($translations as $key => $value) {
        preg_match_all('/:[a-z_]+/i', $key, $expected);
        preg_match_all('/:[a-z_]+/i', $value, $actual);
        sort($expected[0]);
        sort($actual[0]);

        expect($actual[0])->toBe($expected[0], "[{$locale}] {$key}");
    }
})->with('translated locales');

test('visitors can switch the interface language', function () {
    $this->post('/locale', ['locale' => 'fa'])
        ->assertRedirect()
        ->assertCookie('locale', 'fa');

    $this->withCookie('locale', 'fa')->get('/')
        ->assertOk()
        ->assertSee('<html lang="fa" dir="rtl"', false)
        ->assertSee('بیمارستان خود را ثبت کنید');

    $this->withCookie('locale', 'de')->get('/login')
        ->assertSee('dir="ltr"', false)
        ->assertSee('Passwort vergessen?');
});

test('unknown languages are rejected', function () {
    $this->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
});

test('the browser language is used until one is chosen', function () {
    $this->get('/', ['Accept-Language' => 'ar,en;q=0.5'])->assertSee('<html lang="ar" dir="rtl"', false);
    $this->get('/', ['Accept-Language' => 'ja'])->assertSee('<html lang="en" dir="ltr"', false);
});

test('signed-in pages are translated', function () {
    $doctor = User::factory()->role(Role::Doctor)->create();

    $this->actingAs($doctor)->withCookie('locale', 'es')->get('/dashboard')
        ->assertSee('Panel')
        ->assertSee('Estudios')
        ->assertSee('Ecos del paciente');
});
