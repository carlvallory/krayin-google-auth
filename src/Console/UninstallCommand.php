<?php

namespace CarlVallory\KrayinGoogleAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    protected $signature = 'google-auth:uninstall';

    protected $description = 'Revierte el esquema de KrayinGoogleAuth (rol Básico + columnas en users).';

    public function handle(): int
    {
        // 1. Reasigna usuarios del rol Básico al rol de respaldo y borra el rol.
        $basico = DB::table('roles')->where('name', 'Básico')->first();

        if ($basico) {
            $fallbackName = config('google-auth.uninstall_fallback_role', 'Administrator');
            $fallback = DB::table('roles')->where('name', $fallbackName)->first();

            if ($fallback) {
                DB::table('users')->where('role_id', $basico->id)->update(['role_id' => $fallback->id]);
            }

            DB::table('roles')->where('id', $basico->id)->delete();
            $this->info('Rol Básico eliminado.');
        }

        // 2. Quita las columnas agregadas a users.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_id')) {
                $table->dropUnique('users_google_id_unique');
                $table->dropColumn('google_id');
            }
            if (Schema::hasColumn('users', 'auth_provider')) {
                $table->dropColumn('auth_provider');
            }
        });

        $this->info('Columnas google_id y auth_provider eliminadas.');
        $this->warn('Quita el paquete de composer.json y borra GOOGLE_* del .env para completar la desinstalación.');

        return self::SUCCESS;
    }
}
