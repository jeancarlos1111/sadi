<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Repository;
use App\Models\Utilidad;
use App\Models\UtilidadDetalle;

class UtilidadRepository extends Repository
{
    private FichaRepository $fichaRepo;

    public function __construct()
    {
        parent::__construct();
        $this->fichaRepo = new FichaRepository();
    }

    protected function getTable(): string
    {
        return 'utilidades';
    }

    public function existeUtilidad(int $anio): bool
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT COUNT(*) FROM utilidades WHERE anio = ? AND eliminado = false");
        $stmt->execute([$anio]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function calcularMesesLaborados(string $fechaIngreso, int $anioCalculo): int
    {
        $tsIngreso = strtotime($fechaIngreso);
        $anioIngreso = (int)date('Y', $tsIngreso);
        $mesIngreso = (int)date('n', $tsIngreso);
        $diaIngreso = (int)date('j', $tsIngreso);

        if ($anioIngreso < $anioCalculo) {
            return 12;
        } elseif ($anioIngreso > $anioCalculo) {
            return 0;
        }

        // Mismo año. Si entró antes o igual al día 15, se cuenta el mes de ingreso.
        $meses = (12 - $mesIngreso) + ($diaIngreso <= 15 ? 1 : 0);
        return min(12, max(0, $meses));
    }

    /**
     * @return array{utilidad: Utilidad, detalles: UtilidadDetalle[]}
     */
    public function generarSimulacionMasiva(int $anio, int $diasBase): array
    {
        $fichas = $this->fichaRepo->allActivos();
        $detalles = [];
        $montoTotalNomina = 0.0;

        foreach ($fichas as $row) {
            // allActivos() returns Ficha model objects
            $fechaIngreso = $row->ingreso ?? $row->fechaIngreso ?? '';
            $meses = $this->calcularMesesLaborados($fechaIngreso, $anio);
            if ($meses === 0) continue;

            $salarioMensual = (float)($row->sueldoBasico ?? $row->sueldo_basico ?? 0);
            // Formula LOTTT: (Salario Mensual / 30) * DiasBase / 12 * MesesLaborados
            $montoPagar = ($salarioMensual / 30) * $diasBase / 12 * $meses;
            
            // Redondear a 2 decimales
            $montoPagar = round($montoPagar, 2);
            $montoTotalNomina += $montoPagar;

            $codFicha = (int)($row->id ?? $row->cod_ficha ?? $row->codFicha ?? 0);

            $detalles[] = new UtilidadDetalle(
                0,
                $codFicha,
                $fechaIngreso,
                $meses,
                $salarioMensual,
                $montoPagar
            );
        }

        $utilidad = new Utilidad($anio, $diasBase, $montoTotalNomina, 'Generado', false);
        return ['utilidad' => $utilidad, 'detalles' => $detalles];
    }

    public function save(Utilidad $utilidad, array $detalles = []): int|bool
    {
        $db = $this->getPdo();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare("
                INSERT INTO utilidades (anio, dias_base, monto_total_nomina, estatus, eliminado) 
                VALUES (?, ?, ?, ?, false) RETURNING id_utilidad
            ");
            $stmt->execute([
                $utilidad->anio,
                $utilidad->diasBase,
                $utilidad->montoTotalNomina,
                $utilidad->estatus
            ]);
            $idUtilidad = (int)$stmt->fetchColumn();

            $stmtDetalle = $db->prepare("
                INSERT INTO utilidades_detalle (id_utilidad, cod_ficha, fecha_ingreso_calculo, meses_laborados, salario_base, monto_pagar)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($detalles as $det) {
                $stmtDetalle->execute([
                    $idUtilidad,
                    $det->codFicha,
                    $det->fechaIngresoCalculo,
                    $det->mesesLaborados,
                    $det->salarioBase,
                    $det->montoPagar
                ]);
            }

            $db->commit();
            return $idUtilidad;
        } catch (\Exception $e) {
            $db->rollBack();
            return false;
        }
    }

    public function all(): array
    {
        $db = $this->getPdo();
        $stmt = $db->query("SELECT * FROM utilidades WHERE eliminado = false ORDER BY anio DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getDetalles(int $idUtilidad): array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("
            SELECT d.*, p.cedula, p.nombres, p.apellidos
            FROM utilidades_detalle d
            JOIN ficha f ON f.cod_ficha = d.cod_ficha
            JOIN personal p ON p.cod_personal = f.personal_cod_personal
            WHERE d.id_utilidad = ?
            ORDER BY p.nombres ASC
        ");
        $stmt->execute([$idUtilidad]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function find(int $idUtilidad): ?array
    {
        $db = $this->getPdo();
        $stmt = $db->prepare("SELECT * FROM utilidades WHERE id_utilidad = ? AND eliminado = false");
        $stmt->execute([$idUtilidad]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
