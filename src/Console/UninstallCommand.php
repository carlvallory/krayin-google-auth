<?php

namespace CarlVallory\KrayinGoogleAuth\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UninstallCommand extends Command
{
    protected $signature = 'google-auth:uninstall {--force : Omitir confirmación interactiva (para scripts automatizados)}';

    protected $description = 'Revierte el esquema de KrayinGoogleAuth (rol Básico + columnas en users).';

    public function handle(): int
    {
        // 1. Reasigna usuarios del rol Básico al rol de respaldo y borra el rol.
        $basico = DB::table('roles')->where('name', 'Básico')->first();

        if ($basico) {
            $fallbackName = config('google-auth.uninstall_fallback_role', 'Administrator');
            $fallback = DB::table('roles')->where('name', $fallbackName)->first();

            if (! $fallback) {
                $orphanCount = DB::table('users')->where('role_id', $basico->id)->count();
                if ($orphanCount > 0) {
                    $this->error("El rol de respaldo \"{$fallbackName}\" no existe y hay {$orphanCount} usuario(s) con el rol Básico. Crea ese rol antes de desinstalar.");
                    return self::FAILURE;
                }
                $this->warn("El rol de respaldo \"{$fallbackName}\" no existe, pero no hay usuarios con rol Básico. Continuando.");
            } else {
                $count = DB::table('users')->where('role_id', $basico->id)->count();

                if ($count > 0) {
                    $this->warn("$count usuario(s) del rol Básico serán reasignados al rol '{$fallbackName}' (revise sus privilegios tras la desinstalación).");

                    if (! $this->option('force') && ! $this->confirm('¿Continuar?')) {
                        $this->error('Desinstalación cancelada. No se realizaron cambios.');

                        return self::FAILURE;
                    }
                }

                DB::table('users')->where('role_id', $basico->id)->update(['role_id' => $fallback->id]);
            }

            DB::table('roles')->where('id', $basico->id)->delete();
            $this->info('Rol Básico eliminado.');
        }

        // 2. Quita las columnas agregadas a users.
        // Primero intenta eliminar el índice único (puede fallar si ya fue eliminado en una
        // ejecución parcial anterior); en ese caso emite advertencia y continúa.
        if (Schema::hasColumn('users', 'google_id')) {
            try {
                Schema::table('users', function (Blueprint $table) {
                    $table->dropUnique(['google_id']);
                });
            } catch (\Throwable $e) {
                $this->warn('Índice único de google_id no encontrado, se omite.');
            }
        }

        // Luego elimina las columnas en una llamada separada para que un fallo
        // del índice no aborte el drop de columnas.
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_id')) {
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
