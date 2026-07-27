<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use Exception;

class InventarioRecepcionTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        
        $this->db->exec("INSERT INTO rol (id_rol, nombre, descripcion) VALUES (1, 'Admin', 'Admin') ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO usuario (id_usuario, usuario, contrasenya) VALUES (1, 'admin_test', '123') ON CONFLICT DO NOTHING");
        
        // Limpiar
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM acta_recepcion_detalle WHERE id_acta_recepcion IN (SELECT id_acta_recepcion FROM acta_recepcion WHERE numero_acta = 'AR-TEST-001')");
        $this->db->exec("DELETE FROM acta_recepcion WHERE numero_acta = 'AR-TEST-001'");
        $this->db->exec("DELETE FROM pac WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM articulo_orden_de_compra WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9999");
        
        // Crear artículo de prueba
        $this->db->exec("INSERT INTO articulo (id_articulo, denominacion_a, id_tipo_de_articulo, id_unidades_de_medida, stock_actual) VALUES (9999, 'Articulo Recepcion Test', 1, 1, 0) ON CONFLICT DO NOTHING");
        
        // Crear proveedor y orden de compra mock
        $this->db->exec("INSERT INTO proveedor (id_proveedor, compania_proveedor, rif_proveedor, id_tipo_organizacion) VALUES (999, 'Prov Test C.A.', 'J-123', 1) ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO orden_de_compra (id_orden_de_compra, id_proveedor, monto_base_odc, fecha_odc) VALUES (1, 999, 100, CURRENT_DATE) ON CONFLICT DO NOTHING");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM acta_recepcion_detalle WHERE id_acta_recepcion IN (SELECT id_acta_recepcion FROM acta_recepcion WHERE numero_acta = 'AR-TEST-001')");
        $this->db->exec("DELETE FROM acta_recepcion WHERE numero_acta = 'AR-TEST-001'");
        $this->db->exec("DELETE FROM pac WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM articulo_orden_de_compra WHERE id_articulo = 9999");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9999");
    }

    public function testPuedeCrearActaRecepcionYGenerarMovimiento()
    {
        $repo = new \App\Repositories\ActaRecepcionRepository($this->db);
        
        $acta = new \App\Models\ActaRecepcion(
            0,
            'AR-TEST-001',
            date('Y-m-d'),
            1, // id_orden_compra
            1, // id_usuario_receptor
            true, // conformidad
            'Recepcion de prueba'
        );
        
        $detalles = [
            new \App\Models\ActaRecepcionDetalle(0, 0, 9999, 10, 'NUEVO')
        ];
        
        $idActa = $repo->saveConDetalles($acta, $detalles);
        $this->assertGreaterThan(0, $idActa);
        
        // Validar que se generó un movimiento de ENTRADA en el kardex
        $stmt = $this->db->prepare("SELECT * FROM inventario_movimiento WHERE id_acta_recepcion = ?");
        $stmt->execute([$idActa]);
        $movimientos = $stmt->fetchAll();
        
        $this->assertCount(1, $movimientos);
        $this->assertEquals('ENTRADA', $movimientos[0]['tipo_movimiento']);
        $this->assertEquals(10, $movimientos[0]['cantidad']);
        $this->assertEquals(9999, $movimientos[0]['id_articulo']);
        
        // Validar que el stock_actual del articulo se actualizó
        $stmtArt = $this->db->prepare("SELECT stock_actual FROM articulo WHERE id_articulo = 9999");
        $stmtArt->execute();
        $stock = (int)$stmtArt->fetchColumn();
        
        $this->assertEquals(10, $stock);
    }
}
