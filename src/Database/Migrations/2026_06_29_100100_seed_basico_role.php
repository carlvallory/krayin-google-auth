<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    private array $permissions = [
        'dashboard',
        'leads',
        'leads.view',
        'contacts',
        'contacts.persons',
        'contacts.persons.view',
        'contacts.organizations',
    ];

    public function up(): void
    {
        $exists = DB::table('roles')->where('name', 'Básico')->exists();

        if (! $exists) {
            DB::table('roles')->insert([
                'name'            => 'Básico',
                'description'     => 'Rol por defecto de bajo privilegio (solo lectura) para usuarios que ingresan por Google OAuth.',
                'permission_type' => 'custom',
                'permissions'     => json_encode($this->permissions),
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ]);
        }
    }

    public function down(): void
    {
        $basico = DB::table('roles')->where('name', 'Básico')->first();

        if (! $basico) {
            return;
        }

        // Reasigna usuarios del rol Básico a un rol de respaldo antes de borrarlo (evita role_id huérfano).
        $fallbackName = config('google-auth.uninstall_fallback_role', 'Administrator');
        $fallback = DB::table('roles')->where('name', $fallbackName)->first();

        if ($fallback) {
            DB::table('users')->where('role_id', $basico->id)->update(['role_id' => $fallback->id]);
        }

        DB::table('roles')->where('id', $basico->id)->delete();
    }
};
