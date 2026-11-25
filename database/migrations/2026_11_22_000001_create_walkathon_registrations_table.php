<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('walkathon_registrations', function (Blueprint $table) {
            $table->id();
            // Unguessable id used in the thank-you page URL and the QR code.
            $table->uuid('token')->unique();
            // Set right after insert from the row id: 4001, 4002 ... 4999, 41000 ...
            $table->string('bib_number', 20)->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('gender', 20);
            $table->string('email');
            $table->string('whatsapp', 10);
            $table->date('dob');
            $table->string('blood_group', 5);
            $table->text('address');
            $table->string('emergency_contact', 10);
            $table->text('medical_history');
            $table->timestamp('privacy_accepted_at');
            $table->timestamp('mail_sent_at')->nullable();
            $table->timestamps();

            // One registration per person: neither may be reused.
            $table->unique('email');
            $table->unique('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('walkathon_registrations');
    }
};
