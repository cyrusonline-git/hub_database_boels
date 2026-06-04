<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_table_ownership', function (Blueprint $table) {
            $table->id();
            $table->string('table_name', 100)->unique();
            $table->string('owner_slug', 50);   // bv. "core", "fleet", "sales"
            $table->string('owner_name', 150);  // bv. "Boels CORE"
            $table->text('notes')->nullable();
            $table->boolean('locked')->default(false); // true = mag NIET gewijzigd worden door anderen
            $table->timestamps();
            $table->index('owner_slug');
        });

        // Seed: alle bestaande core-tabellen markeren als eigendom van CORE
        $coreTables = [
            'users', 'roles', 'permissions', 'role_permissions', 'user_roles',
            'applications', 'departments', 'employees',
            'customers', 'contacts', 'projects', 'machines', 'machine_groups',
            'machine_subgroups', 'work_orders', 'damages', 'customer_visits',
            'leads', 'opportunities', 'tasks', 'notes', 'documents', 'attachments',
            'custom_fields', 'custom_field_values', 'field_aliases',
            'import_profiles', 'import_jobs', 'import_job_rows', 'audit_logs',
        ];

        foreach ($coreTables as $t) {
            DB::table('app_table_ownership')->insert([
                'table_name' => $t,
                'owner_slug' => 'core',
                'owner_name' => 'Boels CORE',
                'locked' => true,
                'notes' => 'Eigendom van Boels CORE. Mag NIET door child-apps worden gewijzigd.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_table_ownership');
    }
};
