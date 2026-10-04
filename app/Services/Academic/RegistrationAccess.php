<?php

namespace App\Services\Academic;

use App\Models\{AcademicSession, RegistrationSetting, TuitionInvoice, User};
use App\Services\TuitionBilling;
use Illuminate\Validation\ValidationException;

class RegistrationAccess
{
    public static function status(User $student, string $sessionName, string $semester, ?RegistrationSetting $settings = null): array
    {
        $settings ??= RegistrationSetting::current();
        if (! $settings->registration_open) {
            return ['cleared' => false, 'field' => 'course_registration', 'message' => 'Course registration is currently closed. Please wait for the administrator to reopen it.'];
        }
        if (! $settings->require_fee_clearance) {
            return ['cleared' => true, 'message' => 'Course registration is open. Fee clearance is currently not required.'];
        }
        $session = AcademicSession::where('name', $sessionName)->first();
        if (! $session) {
            return ['cleared' => true, 'message' => 'Course registration is open. No tuition session is configured.'];
        }
        $billing = app(TuitionBilling::class);
        $billing->ensureInvoices($student, $session);
        $hasInvoice = TuitionInvoice::current()->where('user_id', $student->id)
            ->where('academic_session_id', $session->id)->whereIn('period', ['Annual', $semester])->exists();
        // Unbilled sessions without a tuition policy do not create a payment requirement.
        if (! $session->tuition_enabled && ! $hasInvoice) {
            return ['cleared' => true, 'message' => 'Course registration is open. No tuition bill applies to this semester.'];
        }
        return $billing->clearance($student, $session, $semester, true) + ['field' => 'tuition'];
    }

    public static function assertAllowed(User $student, string $session, string $semester, RegistrationSetting $settings): void
    {
        $status = self::status($student, $session, $semester, $settings);
        if (! $status['cleared']) {
            throw ValidationException::withMessages([$status['field'] => $status['message']]);
        }
    }
}
