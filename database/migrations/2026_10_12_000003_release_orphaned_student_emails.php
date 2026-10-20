<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')
            ->whereNotNull('official_email')
            ->where(function ($query): void {
                $query->whereNull('account_verification_status')
                    ->orWhereNotIn('account_verification_status', ['pending', 'approved']);
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.student_number', 'students.student_number');
            })
            ->update(['official_email' => null]);

        DB::table('student_account_activations')
            ->whereNotNull('used_at')
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.student_number', 'student_account_activations.student_number');
            })
            ->delete();
    }

    public function down(): void
    {
        // Deleted account reservations cannot be reconstructed safely.
    }
};
