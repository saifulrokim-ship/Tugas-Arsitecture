<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->text('salary')->nullable()->change();
        });

        // Enkripsi nilai gaji lama yang masih tersimpan sebagai angka polos.
        DB::table('resources')->orderBy('id')->cursor()->each(function ($row): void {
            $salary = $row->salary;

            if ($salary === null || $salary === '' || ! ctype_digit((string) $salary)) {
                return;
            }

            DB::table('resources')->where('id', $row->id)->update([
                'salary' => Crypt::encryptString((string) $salary),
            ]);
        });
    }

    public function down(): void
    {
        // Data terenkripsi tidak dapat dikembalikan menjadi angka; kosongkan dahulu.
        DB::table('resources')->whereNotNull('salary')->update(['salary' => null]);

        Schema::table('resources', function (Blueprint $table) {
            $table->unsignedBigInteger('salary')->nullable()->change();
        });
    }
};
