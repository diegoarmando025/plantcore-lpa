<?php

namespace App\Console\Commands;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProvisionPortfolioAccess extends Command
{
    protected $signature = 'portfolio:provision-access';
    protected $description = 'Crear acceso privado del propietario y de un administrador de demostración, sin exponer contraseñas.';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Solo se permite la provisión interactiva desde SSH.');
            return self::FAILURE;
        }

        $this->warn('Ejecutar únicamente en la instalación que deseas configurar.');
        $this->line('No compartas estas contraseñas ni las guardes en GitHub.');
        $ownerEmail = trim((string) $this->ask('Correo de tu cuenta propietaria'));
        $adminEmail = trim((string) $this->ask('Correo para la cuenta administradora demo'));
        if (! filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)
            || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)
            || strcasecmp($ownerEmail, $adminEmail) === 0
        ) {
            $this->error('Usa dos correos válidos y distintos.');
            return self::FAILURE;
        }

        $existingOwner = User::query()->where('is_portfolio_owner', true)->first();
        if ($existingOwner && strcasecmp($existingOwner->email, $ownerEmail) !== 0) {
            $this->error('Ya existe una cuenta propietaria distinta. No se sobrescribirá.');
            return self::FAILURE;
        }

        $owner = User::query()->where('email', $ownerEmail)->first();
        if ($owner && ! $owner->is_portfolio_owner
            && ! $this->confirm('Este correo ya existe. ¿Confirmas que es TU cuenta y deseas protegerla?', false)) {
            return self::FAILURE;
        }

        $admin = User::query()->where('email', $adminEmail)->first();
        if ($admin && ($admin->is_portfolio_owner
            || ! $this->confirm('El administrador demo ya existe. ¿Confirmas que deseas RESTRINGIRLO a la planta ficticia?', false))) {
            return self::FAILURE;
        }

        $plant = Plant::query()->where('code', 'DEMO-PUBLIC')->first();
        if (! $plant) {
            $this->error('No existe la planta ficticia DEMO-PUBLIC. Primero prepara los datos públicos demo existentes.');
            return self::FAILURE;
        }

        $ownerPassword = null;
        if (! $owner) {
            $ownerPassword = (string) $this->secret('Contraseña del propietario (mínimo 16 caracteres)');
            $confirmation = (string) $this->secret('Repite la contraseña del propietario');
            if (strlen($ownerPassword) < 16 || ! hash_equals($ownerPassword, $confirmation)) {
                $this->error('La contraseña del propietario no cumple la longitud o no coincide.');
                return self::FAILURE;
            }
        }

        $adminPassword = null;
        if (! $admin) {
            $adminPassword = (string) $this->secret('Contraseña del administrador demo (mínimo 16 caracteres)');
            $confirmation = (string) $this->secret('Repite la contraseña del administrador demo');
            if (strlen($adminPassword) < 16 || ! hash_equals($adminPassword, $confirmation)) {
                $this->error('La contraseña demo no cumple la longitud o no coincide.');
                return self::FAILURE;
            }
        }

        if (! $this->confirm('¿Aplicar las cuentas y permisos en ESTA base de datos?', false)) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ownerEmail, $adminEmail, $ownerPassword, $adminPassword, $plant): void {
            $owner = User::query()->where('email', $ownerEmail)->lockForUpdate()->first();
            if (! $owner) {
                $owner = User::query()->create([
                    'name' => 'Propietario PlantCore',
                    'email' => $ownerEmail,
                    'password' => $ownerPassword,
                ]);
            }
            if (User::query()->where('is_portfolio_owner', true)->whereKeyNot($owner->id)->exists()) {
                throw new RuntimeException('Otra cuenta ya está protegida como propietaria.');
            }
            $owner->is_portfolio_owner = true;
            $owner->save();

            foreach (Plant::query()->pluck('id') as $plantId) {
                DB::table('plant_user')->updateOrInsert(
                    ['plant_id' => $plantId, 'user_id' => $owner->id],
                    ['role' => 'supervisor']
                );
            }

            $admin = User::query()->where('email', $adminEmail)->lockForUpdate()->first();
            if (! $admin) {
                $admin = User::query()->create([
                    'name' => 'Administrador de evaluación',
                    'email' => $adminEmail,
                    'password' => $adminPassword,
                ]);
            }
            if ($admin->is_portfolio_owner) {
                throw new RuntimeException('No se puede convertir la cuenta propietaria en demo.');
            }
            if (User::query()->where('is_portfolio_demo_admin', true)->whereKeyNot($admin->id)->exists()) {
                throw new RuntimeException('Ya existe otra cuenta de demostración habilitada.');
            }
            $admin->is_portfolio_demo_admin = true;
            $admin->save();
            // A demo administrator must never retain access to real plants.
            DB::table('plant_user')->where('user_id', $admin->id)->delete();
            DB::table('plant_user')->insert([
                'plant_id' => $plant->id, 'user_id' => $admin->id, 'role' => 'supervisor',
            ]);
        });

        $this->info('Accesos creados. El propietario tiene rol supervisor en las plantas actuales.');
        $this->info('El administrador demo solo puede usar la planta DEMO-PUBLIC.');
        $this->warn('No hay administración de usuarios desde la web; los permisos se validan en el servidor.');
        $this->warn('Las contraseñas existentes NO se cambiaron. Guarda las nuevas en tu gestor de contraseñas.');
        return self::SUCCESS;
    }
}
