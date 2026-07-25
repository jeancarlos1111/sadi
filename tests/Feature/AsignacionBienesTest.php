<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use Exception;

class AsignacionBienesTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM asignacion_bien WHERE numero_asignacion LIKE 'AS-TEST-%'");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM unidad_administrativa WHERE id_unidad_administrativa = 999");
        
        // Crear artículo con stock y unidad administrativa
        $this->db->exec("INSERT INTO articulo (id_articulo, denominacion_a, id_tipo_de_articulo, id_unidades_de_medida, stock_actual) VALUES (9998, 'Articulo Asignacion Test', 1, 1, 5) ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO unidad_administrativa (id_unidad_administrativa, denominacion) VALUES (999, 'Unidad Prueba') ON CONFLICT DO NOTHING");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM asignacion_bien WHERE numero_asignacion LIKE 'AS-TEST-%'");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM unidad_administrativa WHERE id_unidad_administrativa = 999");
    }

    public function testPuedeAsignarBienConStockSuficiente()
    {
        $repo = new \App\Repositories\AsignacionBienRepository($this->db);
        
        $asignacion = new \App\Models\AsignacionBien(
            0,
            'AS-TEST-001',
            9998,
            'V-12345678', // cedula
            999, // id_unidad
            date('Y-m-d'),
            'ACTIVA'
        );
        
        $idAsignacion = $repo->save($asignacion);
        $this->assertGreaterThan(0, $idAsignacion);
        
        // Verificar que el stock bajó a 4
        $stmtArt = $this->db->prepare("SELECT stock_actual FROM articulo WHERE id_articulo = 9998");
        $stmtArt->execute();
        $stock = (int)$stmtArt->fetchColumn();
        $this->assertEquals(4, $stock);
        
        // Verificar que hay un movimiento de SALIDA en el Kardex
        $stmtMov = $this->db->prepare("SELECT * FROM inventario_movimiento WHERE id_asignacion = ?");
        $stmtMov->execute([$idAsignacion]);
        $movs = $stmtMov->fetchAll();
        $this->assertCount(1, $movs);
        $this->assertEquals('SALIDA', $movs[0]['tipo_movimiento']);
        $this->assertEquals(1, $movs[0]['cantidad']);
    }

    public function testFallaAlAsignarSinStock()
    {
        $repo = new \App\Repositories\AsignacionBienRepository($this->db);
        
        // Forzar stock a 0
        $this->db->exec("UPDATE articulo SET stock_actual = 0 WHERE id_articulo = 9998");
        
        $asignacion = new \App\Models\AsignacionBien(
            0,
            'AS-TEST-002',
            9998,
            'V-12345678',
            999,
            date('Y-m-d'),
            'ACTIVA'
        );
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No hay stock suficiente");
        
        $repo->save($asignacion);
    }
}
