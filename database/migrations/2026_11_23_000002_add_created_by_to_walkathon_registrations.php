<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            // Set when an admin adds the registration from the panel; null = registered on the website.
            $table->foreignId('created_by')->nullable()->after('attended_by')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
