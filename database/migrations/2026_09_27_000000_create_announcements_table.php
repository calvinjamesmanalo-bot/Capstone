<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });

        // Preserve the existing announcement before retiring the single-value setting.
        $legacy = DB::table('settings')->where('key', 'general_message')->first();
        if ($legacy && trim((string) $legacy->value) !== '') {
            DB::table('announcements')->insert([
                'body' => $legacy->value,
                'created_at' => $legacy->created_at ?? now(),
                'updated_at' => $legacy->updated_at ?? now(),
            ]);
        }
        DB::table('settings')->where('key', 'general_message')->delete();
    }

    public function down(): void
    {
        // The old UI can display only one value; preserve all active text on rollback.
        $body = DB::table('announcements')->whereNull('deleted_at')->orderBy('id')->pluck('body')->implode("\n\n");
        DB::table('settings')->updateOrInsert(['key' => 'general_message'], [
            'value' => $body, 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Schema::dropIfExists('announcements');
    }
};
