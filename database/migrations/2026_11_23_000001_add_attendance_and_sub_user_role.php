<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            $table->timestamp('attended_at')->nullable()->index()->after('mail_sent_at');
            $table->foreignId('attended_by')->nullable()->after('attended_at')
                ->constrained('users')->nullOnDelete();
        });

        // Volunteer role for event-day check-in (see App\Support\Roles).
        Role::findOrCreate('SUB-USER', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('walkathon_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('attended_by');
            $table->dropColumn('attended_at');
        });

        Role::where('name', 'SUB-USER')->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
