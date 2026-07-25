<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Vacacion;
use App\Models\Ficha;
use DateTime;
use PDO;

class VacacionRepository extends Repository
{
    protected function getTable(): string
    {
        return 'vacaciones';
    }

    public function countAll(): int
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT COUNT(*) FROM vacaciones WHERE eliminado = false");
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return Vacacion[]
     */
    public function all(?int $limit = null, ?int $offset = null): array
    {
        $db = $this->getPdo();
        $sql = "SELECT * FROM vacaciones WHERE eliminado = false ORDER BY id_vacacion DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }
        $stmt = $db->query($sql);

        return array_map(fn ($row) => clone $this->mapRowToEntity($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $id): ?Vacacion
    {
        $row = $this->query()->where('id_vacacion', '=', $id)->where('eliminado', '=', 'false')->first();
        if (!$row) {
            return null;
        }

        return $this->mapRowToEntity($row);
    }

    public function tieneVacacionSolapada(int $codFicha, string $fechaSalida, string $fechaRetorno): bool
    {
        $db = $this->getPdo();
        // Verifica si existe alguna vacación para ese trabajador donde los periodos se solapen
        $sql = "SELECT COUNT(*) FROM vacaciones 
                WHERE cod_ficha = ? AND eliminado = false
                AND (
                    (fecha_salida <= ? AND fecha_retorno >= ?) OR
                    (fecha_salida <= ? AND fecha_retorno >= ?) OR
                    (fecha_salida >= ? AND fecha_retorno <= ?)
                )";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $codFicha,
            $fechaSalida, $fechaSalida, // inicio solapa
            $fechaRetorno, $fechaRetorno, // fin solapa
            $fechaSalida, $fechaRetorno // contiene
        ]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function save(Vacacion $item): int|bool
    {
        $data = [
            'cod_ficha' => $item->codFicha,
            'fecha_salida' => $item->fechaSalida,
            'fecha_retorno' => $item->fechaRetorno,
            'dias_disfrute' => $item->diasDisfrute,
            'dias_bono' => $item->diasBono,
            'monto_vacaciones' => $item->montoVacaciones,
            'monto_bono' => $item->montoBono,
            'monto_total' => $item->montoTotal,
            'estatus' => $item->estatus,
        ];

        if ($item->id) {
            return $this->query()->where('id_vacacion', '=', $item->id)->update($data);
        }

        $id = $this->query()->insert($data);
        return $id ? (int)$id : false;
    }

    public function delete(int $id): bool
    {
        return $this->query()->where('id_vacacion', '=', $id)->update(['eliminado' => 'true']);
    }

    private function mapRowToEntity(array $row): Vacacion
    {
        return new Vacacion(
            (int)$row['cod_ficha'],
            $row['fecha_salida'],
            $row['fecha_retorno'],
            (int)$row['dias_disfrute'],
            (int)$row['dias_bono'],
            (float)$row['monto_vacaciones'],
            (float)$row['monto_bono'],
            (float)$row['monto_total'],
            $row['estatus'],
            (bool)$row['eliminado'],
            (int)$row['id_vacacion']
        );
    }

    /**
     * Genera la simulación de vacaciones para un trabajador en una fecha dada.
     */
    public function generarSimulacion(Ficha $ficha, string $fechaSalidaStr): Vacacion
    {
        $ingreso = new DateTime($ficha->fechaIngreso);
        $salida = new DateTime($fechaSalidaStr);
        
        // Calcular antigüedad
        $antiguedad = $ingreso->diff($salida)->y;
        
        // Regla LOTTT: 15 días base + 1 día por año sucesivo (tope 30)
        // Ejemplo: 1 año = 15. 2 años = 16.
        $diasDisfrute = min(15 + max(0, $antiguedad - 1), 30);
        $diasBono = min(15 + max(0, $antiguedad - 1), 30);
        
        // Calcular fecha de retorno (saltando Sábados=6 y Domingos=0)
        $diasConsumidos = 0;
        $fechaActual = clone $salida;
        while ($diasConsumidos < $diasDisfrute) {
            $dow = (int)$fechaActual->format('w');
            if ($dow !== 0 && $dow !== 6) {
                $diasConsumidos++;
            }
            // Avanzar al siguiente día
            if ($diasConsumidos < $diasDisfrute) {
                $fechaActual->modify('+1 day');
            }
        }
        $fechaRetornoStr = $fechaActual->format('Y-m-d');

        // Salario normal (asumiendo sueldoBasico mensual / 30)
        $salarioDiario = $ficha->sueldoBasico / 30;

        $montoVacaciones = $salarioDiario * $diasDisfrute;
        $montoBono = $salarioDiario * $diasBono;
        $montoTotal = $montoVacaciones + $montoBono;

        return new Vacacion(
            $ficha->id,
            $fechaSalidaStr,
            $fechaRetornoStr,
            $diasDisfrute,
            $diasBono,
            $montoVacaciones,
            $montoBono,
            $montoTotal,
            'Generado'
        );
    }
}
