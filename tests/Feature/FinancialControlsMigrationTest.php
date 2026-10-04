<?php

use App\Models\FinancialApproval;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('resumes financial controls migration after partially committed schema changes', function () {
    $migration = require database_path('migrations/2026_10_04_110000_add_financial_controls.php');
    $user = User::factory()->create();
    $payment = Payment::create(['user_id'=>$user->id, 'payable_type'=>'historical', 'payable_id'=>1,
        'purpose'=>'transcript', 'reference'=>'EDU-MIGRATION-RETRY', 'email'=>$user->email,
        'amount'=>3000000, 'currency'=>'NGN', 'status'=>'pending', 'initialization_attempted_at'=>null]);
    $indexesBefore = Schema::getIndexes('payments');
    Schema::drop('payment_settlements');
    $migration->up();
    $migration->up();

    expect(Schema::hasTable('payment_settlements'))->toBeTrue()
        ->and(Schema::hasColumn('payments', 'initialization_attempted_at'))->toBeTrue()
        ->and(Schema::getIndexes('payments'))->toBe($indexesBefore)
        ->and($payment->fresh()->initialization_attempted_at)->toBeNull()
        ->and($payment->fresh()->amount)->toBe(3000000)
        ->and(FinancialApproval::count())->toBe(0);
});
