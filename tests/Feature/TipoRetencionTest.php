<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Database\Connection;
use App\Models\TipoRetencion;
use App\Repositories\TipoRetencionRepository;

class TipoRetencionTest extends TestCase
{
    private TipoRetencionRepository $repo;
    private $db;

    protected function setUp(): void
    {
        $this->db = Connection::getInstance();
        $this->repo = new TipoRetencionRepository($this->db);
        
        $this->db->exec("DELETE FROM tipo_retencion WHERE codigo LIKE 'TEST_%'");
    }

    protected function tearDown(): void
    {
        $this->db->exec("DELETE FROM tipo_retencion WHERE codigo LIKE 'TEST_%'");
    }

    public function testPuedeCrearTipoRetencion()
    {
        $tipo = new TipoRetencion(
            0,
            'TEST_ISLR',
            'ISLR de Prueba',
            12.50,
            300.00,
            'NATURAL',
            true
        );

        $id = $this->repo->save($tipo);
        $this->assertGreaterThan(0, $id);

        $guardado = $this->repo->findById($id);
        $this->assertEquals('TEST_ISLR', $guardado->codigo);
        $this->assertEquals(12.50, $guardado->porcentaje);
        $this->assertEquals(300.00, $guardado->sustraendo);
        $this->assertTrue($guardado->activo);
    }

    public function testPuedeTogglearActivo()
    {
        $tipo = new TipoRetencion(
            0,
            'TEST_IVA',
            'IVA de Prueba',
            75.00,
            0,
            'AMBAS',
            true
        );
        
        $id = $this->repo->save($tipo);
        
        $this->repo->toggleActivo($id);
        
        $desactivado = $this->repo->findById($id);
        $this->assertFalse($desactivado->activo);
        
        $this->repo->toggleActivo($id);
        
        $activado = $this->repo->findById($id);
        $this->assertTrue($activado->activo);
    }
}
