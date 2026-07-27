<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use App\Models\DespachoAlmacen;
use App\Repositories\DespachoAlmacenRepository;

class DespachoTest extends TestCase
{
    private $db;
    private $repo;

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->repo = new DespachoAlmacenRepository($this->db);

        $this->db->exec("DELETE FROM inventario_insumos WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM despacho_almacen_detalle WHERE id_despacho_almacen IN (SELECT id_despacho_almacen FROM despacho_almacen WHERE numero_despacho = 'DESP-TEST-001')");
        $this->db->exec("DELETE FROM despacho_almacen WHERE numero_despacho = 'DESP-TEST-001'");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM unidad_administrativa WHERE id_unidad_administrativa = 999");
        
        $this->db->exec("INSERT INTO rol (id_rol, nombre, descripcion) VALUES (1, 'Admin', 'Admin') ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO usuario (id_usuario, usuario, contrasenya) VALUES (1, 'admin_test', '123') ON CONFLICT DO NOTHING");

        $this->db->exec("INSERT INTO unidad_administrativa (id_unidad_administrativa, codigo, denominacion) VALUES (999, '999', 'Unidad Test') ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO articulo (id_articulo, denominacion_a, id_tipo_de_articulo, id_unidades_de_medida, stock_actual) VALUES (9998, 'Articulo Despacho Test', 1, 1, 50) ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO inventario_insumos (id_articulo, fecha_modificacion_ii, cantidad_ii, minimo_ii) VALUES (9998, CURRENT_DATE, 50, 10) ON CONFLICT DO NOTHING");
        $this->db->exec("UPDATE articulo SET stock_actual = 50 WHERE id_articulo = 9998");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM inventario_insumos WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM inventario_movimiento WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM despacho_almacen_detalle WHERE id_despacho_almacen IN (SELECT id_despacho_almacen FROM despacho_almacen WHERE numero_despacho = 'DESP-TEST-001')");
        $this->db->exec("DELETE FROM despacho_almacen WHERE numero_despacho = 'DESP-TEST-001'");
        $this->db->exec("DELETE FROM articulo WHERE id_articulo = 9998");
        $this->db->exec("DELETE FROM unidad_administrativa WHERE id_unidad_administrativa = 999");
    }

    public function testPuedeCrearDespachoYDescontarStock()
    {
        $despacho = new DespachoAlmacen(
            'DESP-TEST-001',
            date('Y-m-d'),
            999, // id_unidad_administrativa
            'Solicitante Test',
            1, // id_usuario_despacha
            'Despacho de prueba',
            'DESPACHADO',
            null,
            [
                [
                    'id_articulo' => 9998,
                    'cantidad' => 10,
                ]
            ]
        );

        $idDespacho = $this->repo->procesarDespacho($despacho);
        $this->assertGreaterThan(0, $idDespacho);

        // Validar que se generó un movimiento de SALIDA en el kardex
        $stmt = $this->db->prepare("SELECT * FROM inventario_movimiento WHERE id_articulo = ? AND tipo_movimiento = 'SALIDA'");
        $stmt->execute([9998]);
        $movimientos = $stmt->fetchAll();

        $this->assertCount(1, $movimientos);
        $this->assertEquals('SALIDA', $movimientos[0]['tipo_movimiento']);
        $this->assertEquals(10, $movimientos[0]['cantidad']);
        $this->assertEquals(9998, $movimientos[0]['id_articulo']);

        // Validar que el stock_actual del articulo se descontó (50 - 10 = 40)
        $stmtArt = $this->db->prepare("SELECT stock_actual FROM articulo WHERE id_articulo = 9998");
        $stmtArt->execute();
        $stock = (int)$stmtArt->fetchColumn();

        $this->assertEquals(40, $stock);
    }
}
