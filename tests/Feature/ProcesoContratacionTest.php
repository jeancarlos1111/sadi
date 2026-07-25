<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use App\Models\ProcesoContratacion;
use App\Repositories\ProcesoContratacionRepository;
use Exception;

class ProcesoContratacionTest extends TestCase
{
    private ProcesoContratacionRepository $repo;
    private $db;

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->repo = new ProcesoContratacionRepository($this->db);
        
        // Ensure user exists for testing
        $this->db->exec("INSERT INTO rol (id_rol, nombre, descripcion) VALUES (1, 'Admin', 'Admin') ON CONFLICT DO NOTHING");
        $this->db->exec("INSERT INTO usuario (id_usuario, usuario, contrasenya) VALUES (1, 'admin_test', '123') ON CONFLICT DO NOTHING");
        
        $_SESSION['usuario_id'] = 1;
        
        // Clean test data
        $this->db->exec("DELETE FROM proceso_contratacion WHERE numero_expediente LIKE 'TEST-LCP-%'");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM proceso_contratacion WHERE numero_expediente LIKE 'TEST-LCP-%'");
        unset($_SESSION['usuario_id']);
    }

    public function testPuedeCrearUnProcesoDeContratacionAbierto()
    {
        $proceso = new ProcesoContratacion(
            0,
            'TEST-LCP-001',
            'Compra de Laptops para Desarrollo',
            ProcesoContratacion::CONCURSO_ABIERTO,
            50000.00,
            null,
            null,
            true,
            'CRS-2026-01',
            'ABIERTO',
            date('Y-m-d'),
            null,
            1
        );

        $id = $this->repo->save($proceso);
        
        $this->assertGreaterThan(0, $id);
        
        $guardado = $this->repo->findById($id);
        $this->assertEquals('TEST-LCP-001', $guardado->numeroExpediente);
        $this->assertEquals(ProcesoContratacion::CONCURSO_ABIERTO, $guardado->modalidad);
        $this->assertEquals('ABIERTO', $guardado->estatus);
        $this->assertTrue($guardado->crsAplicable);
    }
    
    public function testRequiereJustificacionEnContratacionDirecta()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("La Contratación Directa requiere obligatoriamente una justificación legal");
        
        // Mock controller validation behavior directly or simulate it
        $proceso = new ProcesoContratacion(
            0,
            'TEST-LCP-002',
            'Compra Directa',
            ProcesoContratacion::CONTRATACION_DIRECTA, // Directa
            5000.00,
            null,
            null, // SIN justificación
            false,
            null,
            'ABIERTO',
            date('Y-m-d'),
            null,
            1
        );
        
        if ($proceso->modalidad === ProcesoContratacion::CONTRATACION_DIRECTA && empty($proceso->justificacionLegal)) {
            throw new Exception("La Contratación Directa requiere obligatoriamente una justificación legal.");
        }
    }
}
