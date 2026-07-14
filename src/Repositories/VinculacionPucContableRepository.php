<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\VinculacionPucContable;
use PDO;

class VinculacionPucContableRepository extends Repository
{
    protected function getTable(): string
    {
        return 'vinculacion_puc_contable';
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("
            SELECT cc.*, p.codigo_plan_unico, p.denominacion as partida, c.codigo_cuenta as codigo, c.denominacion_cuenta as cuenta
            FROM vinculacion_puc_contable cc
            JOIN plan_unico_cuentas p ON cc.id_codigo_plan_unico = p.id_codigo_plan_unico
            JOIN cuenta_contable c ON cc.id_cuenta_contable = c.id_cuenta_contable
            WHERE cc.eliminado = false
            ORDER BY p.codigo_plan_unico ASC, cc.tipo_operacion ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCuentaContableId(int $id_codigo_plan_unico, string $tipo_operacion): ?int
    {
        $row = $this->query()
                    ->select('id_cuenta_contable')
                    ->where('id_codigo_plan_unico', '=', $id_codigo_plan_unico)
                    ->where('tipo_operacion', '=', $tipo_operacion)
                    ->where('eliminado', '=', 'false')
                    ->first();

        return $row ? (int)$row['id_cuenta_contable'] : null;
    }

    public function save(VinculacionPucContable $item): bool
    {
        $data = [
            'id_codigo_plan_unico' => $item->id_codigo_plan_unico,
            'id_cuenta_contable'            => $item->id_cuenta_contable,
            'tipo_operacion'       => $item->tipo_operacion,
            'descripcion'          => $item->descripcion,
        ];

        if ($item->id_vinculacion) {
            return $this->query()->where('id_vinculacion', '=', $item->id_vinculacion)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_vinculacion', '=', $id)->update(['eliminado' => 'true']);
    }
}
