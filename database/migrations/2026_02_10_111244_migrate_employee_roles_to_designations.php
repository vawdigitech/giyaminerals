<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Get unique roles from employees table
        $roles = DB::table('employees')
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->pluck('role');

        foreach ($roles as $role) {
            // Create designation from role
            $code = Str::upper(Str::slug($role, '_'));

            // Insert if not exists
            $existingId = DB::table('designations')
                ->where('code', $code)
                ->value('id');

            if (!$existingId) {
                $designationId = DB::table('designations')->insertGetId([
                    'name' => ucwords(str_replace(['_', '-'], ' ', $role)),
                    'code' => $code,
                    'description' => 'Migrated from employee role: ' . $role,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $designationId = $existingId;
            }

            // Update employees with this role
            DB::table('employees')
                ->where('role', $role)
                ->update(['designation_id' => $designationId]);
        }
    }

    public function down(): void
    {
        // Reset designation_id to null
        DB::table('employees')->update(['designation_id' => null]);

        // Optionally delete migrated designations
        DB::table('designations')
            ->where('description', 'like', 'Migrated from employee role:%')
            ->delete();
    }
};
