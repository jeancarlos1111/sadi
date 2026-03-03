<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\AjustePresupuestario;

class AjustePresupuestarioRepository extends Repository
{
    protected function getTable(): string
    {
        return 'ajuste_presupuestario';
    }

    public function all(string $search = '', string $mes = ''): array
    {
        // En un sistema real, uniriamos varias tablas de ajustes.
        // Para SADI mapeamos a una tabla simplificada o simulamos el retorno unificado.
        $db = $this->getPdo();

        // Simulación: en el futuro esto leerá de tablas reales como credito_adicional, traspasos, etc.
        $results = [];
        $results[] = [
            'entity' => new AjustePresupuestario(
                'TRASPASO',
                date('Y-m-d'),
                'Traspaso para cubrir insuficiencia en papelería',
                50000.00,
                'APROBADO',
                1
            ),
        ];

        return $results;
    }
}
