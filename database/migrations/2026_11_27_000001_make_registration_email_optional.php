<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The unique index stays: MySQL allows any number of NULLs in a unique column.
    public function up(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
