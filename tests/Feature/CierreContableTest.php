<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use App\Services\CierreContableService;
use Exception;

class CierreContableTest extends TestCase
{
    private CierreContableService $cierreService;
    private $db;
    private string $anioTest = '2026';

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->cierreService = new CierreContableService();

        // Asegurar que el rol ID 1 existe
        $this->db->exec("INSERT INTO rol (id_rol, nombre, descripcion) VALUES (1, 'Admin', 'Admin') ON CONFLICT DO NOTHING");
        // Asegurar que el usuario ID 1 existe
        $this->db->exec("INSERT INTO usuario (id_usuario, usuario, contrasenya) VALUES (1, 'admin', '123') ON CONFLICT DO NOTHING");

        // 1. Limpiar asientos previos del año de prueba
        $this->db->exec("DELETE FROM movimiento_contable WHERE id_comprobante_diario IN (SELECT id_comprobante_diario FROM comprobante_diario WHERE fecha_comprobante LIKE '{$this->anioTest}-%')");
        $this->db->exec("DELETE FROM comprobante_diario WHERE fecha_comprobante LIKE '{$this->anioTest}-%'");
        $this->db->exec("DELETE FROM cierre_ejercicio WHERE anio = {$this->anioTest}");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM movimiento_contable WHERE id_comprobante_diario IN (SELECT id_comprobante_diario FROM comprobante_diario WHERE fecha_comprobante LIKE '{$this->anioTest}-%')");
        $this->db->exec("DELETE FROM comprobante_diario WHERE fecha_comprobante LIKE '{$this->anioTest}-%'");
        $this->db->exec("DELETE FROM cierre_ejercicio WHERE anio = {$this->anioTest}");
    }

    private function getCuentaId(string $codigo): int
    {
        $stmt = $this->db->prepare("SELECT id_cuenta_contable FROM cuenta_contable WHERE codigo_cuenta = ? LIMIT 1");
        $stmt->execute([$codigo]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            throw new Exception("Cuenta {$codigo} no encontrada. Asegúrate de correr los seeders.");
        }
        return (int)$id;
    }

    private function insertarAsientoManual(string $fecha, array $movimientos): void
    {
        $stmtCd = $this->db->prepare("INSERT INTO comprobante_diario (numero_comprobante, fecha_comprobante, concepto) VALUES (?, ?, ?) RETURNING id_comprobante_diario");
        $stmtCd->execute(['CD-TEST-' . rand(1000, 9999), $fecha, 'Asiento de prueba']);
        $idCd = $stmtCd->fetchColumn();

        $stmtMc = $this->db->prepare("INSERT INTO movimiento_contable (id_comprobante_diario, id_cuenta_contable, tipo_operacion_mc, monto_mc) VALUES (?, ?, ?, ?)");
        
        foreach ($movimientos as $mov) {
            $stmtMc->execute([$idCd, $mov['id_cuenta'], $mov['tipo'], $mov['monto']]);
        }
    }

    public function testCierreContableConSuperavit()
    {
        // Ingresos > Gastos
        $idIngreso = $this->getCuentaId('4.1.1.01.01'); // Ingresos por Venta
        $idGasto = $this->getCuentaId('5.1.1.01.01'); // Sueldos y Salarios
        $idBanco = $this->getCuentaId('1.1.1.02.01'); // Bancos

        // Registrar Ingreso de 1000 (HABER en Ingreso, DEBE en Banco)
        $this->insertarAsientoManual("{$this->anioTest}-06-01", [
            ['id_cuenta' => $idBanco, 'tipo' => 'D', 'monto' => 1000.00],
            ['id_cuenta' => $idIngreso, 'tipo' => 'H', 'monto' => 1000.00]
        ]);

        // Registrar Gasto de 400 (DEBE en Gasto, HABER en Banco)
        $this->insertarAsientoManual("{$this->anioTest}-07-01", [
            ['id_cuenta' => $idGasto, 'tipo' => 'D', 'monto' => 400.00],
            ['id_cuenta' => $idBanco, 'tipo' => 'H', 'monto' => 400.00]
        ]);

        // 1. Validar Resumen (Debería haber Superávit de 600)
        $resumen = $this->cierreService->obtenerResumenCierre($this->anioTest);
        $this->assertEquals(1000.00, $resumen['total_ingresos']);
        $this->assertEquals(400.00, $resumen['total_gastos']);
        $this->assertEquals(600.00, $resumen['resultado_ejercicio']);
        $this->assertTrue($resumen['es_superavit']);

        // 2. Ejecutar Cierre
        $this->cierreService->ejecutarCierre($this->anioTest, 1);

        // 3. Validar que las cuentas quedaron saldadas
        $resumenPostCierre = $this->cierreService->obtenerResumenCierre($this->anioTest);
        
        // Dado que el servicio de resumen obtiene la suma incluyendo el asiento de cierre, 
        // el total neto debería ser 0 ahora.
        $this->assertEquals(0.00, $resumenPostCierre['total_ingresos']);
        $this->assertEquals(0.00, $resumenPostCierre['total_gastos']);

        // 4. Validar que la cuenta Resultados del Ejercicio tiene el abono (Superávit) de 600
        $idResultado = $this->getCuentaId('3.2.1.01.01');
        $stmt = $this->db->prepare("
            SELECT SUM(CASE WHEN tipo_operacion_mc = 'H' THEN monto_mc ELSE -monto_mc END) 
            FROM movimiento_contable 
            WHERE id_cuenta_contable = ? AND id_comprobante_diario IN (
                SELECT id_comprobante_diario FROM comprobante_diario WHERE fecha_comprobante LIKE ?
            )
        ");
        $stmt->execute([$idResultado, "{$this->anioTest}-%"]);
        $saldoResultado = (float)$stmt->fetchColumn();
        
        $this->assertEquals(600.00, $saldoResultado);

        // 5. Validar que intentar cerrar otra vez lanza error
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("El ejercicio fiscal {$this->anioTest} ya se encuentra cerrado");
        $this->cierreService->ejecutarCierre($this->anioTest, 1);
    }
}
