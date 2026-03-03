<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\ConvertidorCuenta;
use PDO;

class ConvertidorCuentaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'convertidor_cuentas';
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("
            SELECT cc.*, p.codigo_plan_unico, p.denominacion as partida, c.codigo_cuenta as codigo, c.denominacion_cuenta as cuenta
            FROM convertidor_cuentas cc
            JOIN plan_unico_cuentas p ON cc.id_codigo_plan_unico = p.id_codigo_plan_unico
            JOIN cuenta_contable c ON cc.id_cuenta = c.id_cuenta_contable
            WHERE cc.eliminado = 0
            ORDER BY p.codigo_plan_unico ASC, cc.tipo_operacion ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCuentaContableId(int $id_codigo_plan_unico, string $tipo_operacion): ?int
    {
        $row = $this->query()
                    ->select('id_cuenta')
                    ->where('id_codigo_plan_unico', '=', $id_codigo_plan_unico)
                    ->where('tipo_operacion', '=', $tipo_operacion)
                    ->where('eliminado', '=', 0)
                    ->first();

        return $row ? (int)$row['id_cuenta'] : null;
    }

    public function save(ConvertidorCuenta $item): bool
    {
        $data = [
            'id_codigo_plan_unico' => $item->id_codigo_plan_unico,
            'id_cuenta'            => $item->id_cuenta,
            'tipo_operacion'       => $item->tipo_operacion,
            'descripcion'          => $item->descripcion,
        ];

        if ($item->id_convertidor) {
            return $this->query()->where('id_convertidor', '=', $item->id_convertidor)->update($data);
        }

        $id = $this->query()->insert($data);

        return $id !== false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_convertidor', '=', $id)->update(['eliminado' => 1]);
    }
}
