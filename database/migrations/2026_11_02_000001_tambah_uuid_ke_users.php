<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->uuid('uuid')->nullable()->unique();
        });

        DB::table('users')->whereNull('uuid')->orderBy('id')->each(
            fn ($baris) => DB::table('users')->where('id', $baris->id)->update(['uuid' => (string) Str::uuid()])
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique('users_uuid_unique');
            $t->dropColumn('uuid');
        });
    }
};
