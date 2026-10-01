<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\EchoStudy;
use App\Models\Hospital;
use App\Models\Scopes\HospitalScope;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->hospital = Hospital::factory()->create();
    $this->admin = User::factory()->for($this->hospital)->role(Role::HospitalAdmin)->create();
    $this->tech = User::factory()->for($this->hospital)->role(Role::Technician)->create();
});

function auditActions(): array
{
    return AuditLog::withoutGlobalScope(HospitalScope::class)->orderBy('id')->pluck('action')->all();
}

test('clinical actions are recorded with the actor and subject', function () {
    $this->actingAs($this->tech)->postJson('/studies', echoPayload())->assertCreated();
    $study = EchoStudy::withoutGlobalScope(HospitalScope::class)->sole();
    $this->actingAs($this->tech)->get("/studies/{$study->id}");
    $this->actingAs($this->tech)->putJson("/studies/{$study->id}", echoPayload(['lv_EF' => 40]));
    $this->actingAs($this->tech)->get('/patients?nationalID=0012345678');
    $this->actingAs($this->tech)->get('/patients?nationalID=999');

    expect(auditActions())->toBe(['patient.created', 'study.created', 'study.viewed', 'study.updated', 'patient.viewed', 'patient.search_no_match']);

    $update = AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'study.updated')->sole();
    expect($update->user_id)->toBe($this->tech->id)
        ->and($update->hospital_id)->toBe($this->hospital->id)
        ->and($update->subject_type)->toBe('echo_study')
        ->and($update->subject_id)->toBe($study->id)
        ->and($update->changes['fields']['lv_EF'])->toEqual([35, 40]);

    // Encrypted identifiers never land in the audit trail in clear text.
    expect(json_encode(AuditLog::withoutGlobalScope(HospitalScope::class)->pluck('changes')))->not->toContain('0012345678');
});

test('logins and failed logins are recorded', function () {
    $this->post('/login', ['email' => $this->tech->email, 'password' => 'wrong']);
    $this->post('/login', ['email' => $this->tech->email, 'password' => 'password']);

    expect(auditActions())->toBe(['auth.failed', 'auth.login']);
});

test('the audit log is append-only at the database level', function () {
    $this->actingAs($this->tech)->get('/patients?nationalID=1');
    $log = AuditLog::withoutGlobalScope(HospitalScope::class)->sole();

    expect(fn () => $log->update(['action' => 'x']))->toThrow(LogicException::class);
    expect(fn () => DB::table('audit_logs')->where('id', $log->id)->update(['action' => 'tampered']))
        ->toThrow(QueryException::class, 'append-only');
});

test('hospital admins see only their hospital audit log; other roles cannot', function () {
    $this->actingAs($this->tech)->get('/patients?nationalID=1');

    $other = User::factory()->role(Role::HospitalAdmin)->create();
    $this->actingAs($other)->get('/patients?nationalID=2');

    $this->actingAs($this->admin)->get('/audit')
        ->assertOk()
        ->assertSee($this->tech->name)
        ->assertDontSee($other->name);

    $this->actingAs($this->admin)->get('/audit?action=patient.search_no_match&user_id='.$this->tech->id)->assertOk()->assertSee('patient.search_no_match');

    $this->actingAs($this->tech)->get('/audit')->assertForbidden();
});

test('staff management is audited', function () {
    Notification::fake();
    $this->actingAs($this->admin)->post('/staff/invitations', ['email' => 'new@example.com', 'role' => 'doctor']);
    $this->actingAs($this->admin)->patch("/staff/{$this->tech->id}", ['role' => 'doctor', 'is_active' => 1]);

    $updated = AuditLog::withoutGlobalScope(HospitalScope::class)->where('action', 'staff.updated')->sole();
    expect(auditActions())->toContain('staff.invited')
        ->and($updated->changes['role'])->toBe(['technician', 'doctor']);
});
