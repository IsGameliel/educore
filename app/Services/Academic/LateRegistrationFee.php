<?php

namespace App\Services\Academic;

use App\Models\{AcademicSession, LateRegistrationCharge, Payment, User};

class LateRegistrationFee
{
    public static function paid(User $student, string $session, string $semester): bool
    {
        return LateRegistrationCharge::where('user_id', $student->id)->where('semester', $semester)
            ->whereHas('academicSession', fn ($q) => $q->where('name', $session))
            ->whereHas('payment', fn ($q) => $q->where('user_id', $student->id)->where('purpose', 'late_registration')
                ->where('status', 'success')->whereNotNull('verified_at')->whereNotNull('gateway_id')
                ->where('amount', Payment::FEES['late_registration'])->where('currency', 'NGN')
                ->where('gateway_reversed', false)->where('dispute_open', false)->where('gateway_deduction', 0))
            ->exists();
    }

    public static function payment(User $student, string $session, string $semester): Payment
    {
        $academicSession = AcademicSession::where('name', $session)->firstOrFail();
        $charge = LateRegistrationCharge::firstOrCreate([
            'user_id' => $student->id, 'academic_session_id' => $academicSession->id, 'semester' => $semester,
        ]);
        return app(\App\Services\PaystackPayments::class)->create($charge, $student, 'late_registration');
    }
}
