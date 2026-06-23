<?php

namespace App\Services\Finance;

use App\Models\Finance\Account;
use App\Models\Finance\Category;
use App\Models\Finance\Person;
use App\Models\Finance\RentalContract;
use App\Models\User;

class FinanceCatalogService
{
    public function ensureForUser(User $user): void
    {
        $this->ensureAccounts($user);
        $this->ensureCategories($user);
        $this->ensurePeople($user);
    }

    private function ensureAccounts(User $user): void
    {
        $accounts = [
            ['name' => 'Efectivo', 'type' => 'cash', 'display_order' => 10],
            ['name' => 'NU', 'type' => 'card', 'display_order' => 20],
            ['name' => 'Mercado Pago', 'type' => 'card', 'display_order' => 30],
            ['name' => 'BBVA', 'type' => 'card', 'display_order' => 40],
            ['name' => 'DIDI', 'type' => 'card', 'display_order' => 50],
            ['name' => 'MPW', 'type' => 'card', 'display_order' => 60],
        ];

        foreach ($accounts as $account) {
            Account::firstOrCreate(
                ['user_id' => $user->id, 'name' => $account['name']],
                $account + ['user_id' => $user->id]
            );
        }
    }

    private function ensureCategories(User $user): void
    {
        $categories = [
            ['name' => 'Rentas San Juan', 'type' => 'income', 'group' => 'San Juan', 'color' => '#22b956', 'keywords' => 'renta,rentas,cesar,alma,wendy,lazaro,josue,oswaldo', 'is_rent' => true],
            ['name' => 'Andrea comida', 'type' => 'income', 'group' => 'Casa', 'color' => '#1bb394', 'keywords' => 'andrea,comida'],
            ['name' => 'SCIOS / FESI', 'type' => 'income', 'group' => 'Trabajo', 'color' => '#4d5761', 'keywords' => 'scios,fesi,pago'],
            ['name' => 'Rendimiento NU', 'type' => 'yield', 'group' => 'Rendimientos', 'color' => '#ffc107', 'keywords' => 'rendimiento nu,nu rendimiento'],
            ['name' => 'Rendimiento MPW', 'type' => 'yield', 'group' => 'Rendimientos', 'color' => '#ffc107', 'keywords' => 'rendimiento mpw,mpw rendimiento'],
            ['name' => 'Comida', 'type' => 'expense', 'group' => 'Diario', 'color' => '#f97316', 'keywords' => 'comida,super,mandado'],
            ['name' => 'Casa', 'type' => 'expense', 'group' => 'Diario', 'color' => '#64748b', 'keywords' => 'casa,luz,agua,internet'],
            ['name' => 'Transporte', 'type' => 'expense', 'group' => 'Diario', 'color' => '#0ea5e9', 'keywords' => 'caseta,pase,transporte'],
            ['name' => 'Saldo / Telefonia', 'type' => 'expense', 'group' => 'Diario', 'color' => '#06b6d4', 'keywords' => 'saldo,telcel,weex,recarga,telefonia,telefono'],
            ['name' => 'Gasolina', 'type' => 'expense', 'group' => 'Gasolina', 'color' => '#ef4444', 'keywords' => 'gasolina,gasolina carro,costco gasolina'],
            ['name' => 'Gasolina de moto', 'type' => 'expense', 'group' => 'Gasolina', 'color' => '#f59e0b', 'keywords' => 'gasolina moto,gasolina de moto,moto gasolina'],
            ['name' => 'Uber carro', 'type' => 'expense', 'group' => 'Transporte', 'color' => '#0ea5e9', 'keywords' => 'uber carro,uber viaje,uber taxi,uber'],
            ['name' => 'DIDI carro', 'type' => 'expense', 'group' => 'Transporte', 'color' => '#0ea5e9', 'keywords' => 'didi carro,didi viaje,didi taxi'],
            ['name' => 'Uber comida', 'type' => 'expense', 'group' => 'Comida', 'color' => '#f97316', 'keywords' => 'uber comida,uber eats,eats'],
            ['name' => 'DIDI comida', 'type' => 'expense', 'group' => 'Comida', 'color' => '#f97316', 'keywords' => 'didi comida,didi food'],
            ['name' => 'Rappi', 'type' => 'expense', 'group' => 'Comida', 'color' => '#f97316', 'keywords' => 'rappi,rappi comida'],
            ['name' => 'San Juan general', 'type' => 'expense', 'group' => 'San Juan', 'color' => '#dc3545', 'keywords' => 'snj,san juan,japam,jorge,limpieza,cloro,jabon,escoba', 'is_san_juan' => true],
            ['name' => 'JAPAM', 'type' => 'expense', 'group' => 'San Juan', 'color' => '#dc3545', 'keywords' => 'japam,agua san juan', 'is_san_juan' => true],
            ['name' => 'Limpieza Jorge', 'type' => 'expense', 'group' => 'San Juan', 'color' => '#dc3545', 'keywords' => 'jorge,limpieza', 'is_san_juan' => true],
            ['name' => 'Crédito / tarjeta', 'type' => 'expense', 'group' => 'Créditos', 'color' => '#7c3aed', 'keywords' => 'credito,tarjeta,nu,didi,mpw'],
            ['name' => 'Desconocido', 'type' => 'expense', 'group' => 'Revision', 'color' => '#111827', 'keywords' => '?,desconocido,no recuerdo'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => $category['name'], 'type' => $category['type']],
                $category + ['user_id' => $user->id]
            );
        }
    }

    private function ensurePeople(User $user): void
    {
        $people = [
            ['name' => 'Andrea', 'type' => 'family', 'is_tenant' => false, 'notes' => 'Depositos para comida u otros apoyos.'],
            ['name' => 'Jorge', 'type' => 'provider', 'is_tenant' => false, 'notes' => 'Apoyo con limpieza y pendientes de San Juan.'],
            ['name' => 'Alma', 'type' => 'tenant', 'is_tenant' => true],
            ['name' => 'Wendy', 'type' => 'tenant', 'is_tenant' => true],
            ['name' => 'Cesar', 'type' => 'tenant', 'is_tenant' => true],
            ['name' => 'Primo Josue', 'type' => 'tenant', 'is_tenant' => true],
            ['name' => 'Lazaro', 'type' => 'tenant', 'is_tenant' => true, 'notes' => 'Ingreso por internet/servicios compartidos.'],
            ['name' => 'Oswaldo', 'type' => 'tenant', 'is_tenant' => true],
        ];

        foreach ($people as $personData) {
            $person = Person::firstOrCreate(
                ['user_id' => $user->id, 'name' => $personData['name']],
                $personData + ['user_id' => $user->id]
            );

            if ($person->is_tenant) {
                RentalContract::firstOrCreate(
                    ['user_id' => $user->id, 'person_id' => $person->id],
                    [
                        'user_id' => $user->id,
                        'person_id' => $person->id,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
