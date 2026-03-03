<?php

namespace App\Repositories;

use App\Database\Repository;
use App\Models\CajaChica;
use Exception;
use PDO;

class CajaChicaRepository extends Repository
{
    protected function getTable(): string
    {
        return 'caja_chica';
    }

    /**
     * @return CajaChica[]
     */
    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM caja_chica WHERE eliminado = 0 ORDER BY denominacion");

        return array_map(fn ($r) => $this->mapRowToEntity($r), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?CajaChica
    {
        $row = $this->query()->where('id_caja_chica', '=', $id)->where('eliminado', '=', 0)->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function save(CajaChica $item): bool
    {
        $data = [
            'denominacion' => $item->denominacion,
            'responsable' => $item->responsable,
            'monto_asignado' => $item->montoAsignado,
            'monto_disponible' => $item->montoDisponible,
            'fecha_apertura' => $item->fechaApertura,
            'activa' => (int)$item->activa,
        ];

        if ($item->id) {
            return $this->query()->where('id_caja_chica', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        if ($id) {
            $item->id = (int)$id;

            return true;
        }

        return false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_caja_chica', '=', $id)->update(['eliminado' => 1]);
    }

    /** Registrar un gasto o reposición */
    public function registrarMovimiento(int $idCaja, string $tipo, string $concepto, float $monto, string $fecha, string $comprobante): bool
    {
        $db = $this->getPdo();
        $db->beginTransaction();

        try {
            // Insertar movimiento
            $stmt = $db->prepare("INSERT INTO movimiento_caja_chica (id_caja_chica,tipo,concepto,monto,fecha,comprobante) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$idCaja, $tipo, $concepto, $monto, $fecha, $comprobante]);

            // Actualizar saldo disponible
            if ($tipo === 'GASTO') {
                $db->prepare("UPDATE caja_chica SET monto_disponible = monto_disponible - ? WHERE id_caja_chica = ?")->execute([$monto, $idCaja]);
            } elseif ($tipo === 'REPOSICION' || $tipo === 'ASIGNACION') {
                $db->prepare("UPDATE caja_chica SET monto_disponible = monto_disponible + ?, monto_asignado = monto_asignado + ? WHERE id_caja_chica = ?")->execute([$monto, $monto, $idCaja]);
            }

            $db->commit();

            return true;
        } catch (Exception $e) {
            $db->rollBack();

            throw $e;
        }
    }

    public function getMovimientos(int $idCaja): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM movimiento_caja_chica WHERE id_caja_chica = ? AND eliminado = 0 ORDER BY fecha DESC, id_movimiento_cc DESC");
        $stmt->execute([$idCaja]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function mapRowToEntity(array $r): CajaChica
    {
        return new CajaChica(
            $r['denominacion'],
            $r['responsable'],
            (float)$r['monto_asignado'],
            (float)$r['monto_disponible'],
            $r['fecha_apertura'],
            (bool)$r['activa'],
            (int)$r['id_caja_chica']
        );
    }
}
