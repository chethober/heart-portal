<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A blank image as the canvas would send it (or, for the tests that expect a rejection, a JPEG).
 */
function signatureImage(int $width, int $height, string $type = 'png'): string
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    $type === 'png' ? imagepng($image) : imagejpeg($image);

    return (string) ob_get_clean();
}

/**
 * The profile page's signature form as the canvas posts it.
 *
 * @return array{drawn_signature: string}
 */
function drawnSignature(int $width = 300, int $height = 100): array
{
    return ['drawn_signature' => 'data:image/png;base64,'.base64_encode(signatureImage($width, $height))];
}

/** Submit the dashboard patient step (step 1) and return the study form URL it opens. */
function startStudyUrl(User $user, string $study = 'echo', array $overrides = []): string
{
    return test()->actingAs($user)->post(route('studies.start'), [
        'first_name' => 'Ali', 'last_name' => 'Rezaei', 'nationalID' => '0012345678',
        'dob' => '1350/01/01', 'gender' => 'Male', 'phone_number' => '09121234567',
        'study_date' => '1405/07/09', 'admission_number' => '',
        'study' => $study,
        ...$overrides,
    ])->assertSessionHasNoErrors()->assertRedirect()->headers->get('Location');
}
