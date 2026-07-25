<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Gate;

use App\Repositories\InventarioInsumoRepository;
use App\Repositories\OrdenCompraRepository;
use App\Repositories\RecepcionAlmacenRepository;
use App\Repositories\DespachoAlmacenRepository;
use App\Repositories\UnidadAdministrativaRepository;
use App\Models\DespachoAlmacen;
use Exception;
use PDOException;

class InventarioController extends HomeController
{
    private InventarioInsumoRepository $insumoRepo;
    private OrdenCompraRepository $ordenRepo;
    private RecepcionAlmacenRepository $recepcionRepo;
    private DespachoAlmacenRepository $despachoRepo;
    private UnidadAdministrativaRepository $unidadRepo;

    public function __construct(
        InventarioInsumoRepository $insumoRepo,
        OrdenCompraRepository $ordenRepo,
        RecepcionAlmacenRepository $recepcionRepo,
        DespachoAlmacenRepository $despachoRepo,
        UnidadAdministrativaRepository $unidadRepo
    ) {
        $this->insumoRepo = $insumoRepo;
        $this->ordenRepo = $ordenRepo;
        $this->recepcionRepo = $recepcionRepo;
        $this->despachoRepo = $despachoRepo;
        $this->unidadRepo = $unidadRepo;

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ?route=auth/login');
            exit;
        }
    }

    public function index(): void
    {
        Gate::authorize('inventario.articulos.ver');
        $search = $_GET['search'] ?? '';

        try {
            $insumos = $this->insumoRepo->all($search);
        } catch (PDOException | \Exception $e) {
            $insumos = [];
            $error = "Error al obtener el inventario: " . $e->getMessage();
        }

        $this->renderView('inventario/existencias/index', [
            'titulo' => 'Control de Inventario (Almacén)',
            'insumos' => $insumos,
            'search' => $search,
            'error' => $error ?? null,
        ]);
    }

    public function entradas(): void
    {
        $idOrden = $_GET['id_orden'] ?? null;
        $ordenes = $this->ordenRepo->all(); // Obtener todas para selector
        $detalles = [];
        $ordenSeleccionada = null;

        if ($idOrden) {
            try {
                // Filtrar del array la orden seleccionada para mostrar cabecera
                foreach ($ordenes as $o) {
                    if ($o['entity']->id == $idOrden) {
                        $ordenSeleccionada = $o;

                        break;
                    }
                }

                // Traer artículos de la orden agrupando lo ya recibido
                $detalles = $this->recepcionRepo->getPendientesPorOrden((int)$idOrden);
            } catch (Exception $e) {
                $error = "Error al obtener la orden: " . $e->getMessage();
            }
        }

        $this->renderView('inventario/movimientos/entradas', [
            'titulo' => 'Recepción de Almacén (Compras)',
            'ordenes' => $ordenes,
            'ordenSeleccionada' => $ordenSeleccionada,
            'detalles' => $detalles,
            'error' => $error ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function procesarRecepcion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idOrden = (int)($_POST['id_orden'] ?? 0);

            $articulosRecibidos = [];
            if (isset($_POST['id_articulo']) && is_array($_POST['id_articulo'])) {
                foreach ($_POST['id_articulo'] as $index => $idArt) {
                    $cant = (float)($_POST['cantidad_recibir'][$index] ?? 0);
                    if ($cant > 0) {
                        $articulosRecibidos[] = [
                            'id_articulo' => $idArt,
                            'cantidad' => $cant,
                        ];
                    }
                }
            }

            if (empty($articulosRecibidos)) {
                header('Location: ?route=inventario/entradas&id_orden=' . $idOrden . '&error=Debe especificar al menos una cantidad a recibir superior a cero.');
                exit;
            }

            try {
                $this->recepcionRepo->recibirArticulos($idOrden, $articulosRecibidos);
                header('Location: ?route=inventario/entradas&success=Recepción procesada correctamente. Se han actualizado las existencias, generado el documento Causado en CxP e incrementado el Presupuesto Causado.');
                exit;
            } catch (Exception $e) {
                header('Location: ?route=inventario/entradas&id_orden=' . $idOrden . '&error=' . urlencode($e->getMessage()));
                exit;
            }
        }
    }

    public function despachos(): void
    {
        $search = $_GET['search'] ?? '';
        
        try {
            $despachos = $this->despachoRepo->all($search);
        } catch (Exception $e) {
            $despachos = [];
            $error = "Error al obtener los despachos: " . $e->getMessage();
        }

        $this->renderView('inventario/movimientos/despachos', [
            'titulo' => 'Salidas y Despachos',
            'despachos' => $despachos,
            'search' => $search,
            'error' => $error ?? null,
            'success' => $_GET['success'] ?? null,
        ]);
    }

    public function nuevoDespacho(): void
    {
        try {
            $unidades = $this->unidadRepo->all();
            $insumos = $this->despachoRepo->getInsumosDisponibles();
        } catch (Exception $e) {
            $error = "Error al cargar datos para despacho: " . $e->getMessage();
        }

        $this->renderView('inventario/movimientos/nuevo_despacho', [
            'titulo' => 'Nuevo Despacho (Salida de Almacén)',
            'unidades' => $unidades ?? [],
            'insumos' => $insumos ?? [],
            'error' => $error ?? null,
        ]);
    }

    public function guardarDespacho(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $detalles = [];
                if (isset($_POST['id_articulo']) && is_array($_POST['id_articulo'])) {
                    foreach ($_POST['id_articulo'] as $index => $idArt) {
                        $cant = (float)($_POST['cantidad_despachar'][$index] ?? 0);
                        if ($cant > 0) {
                            $detalles[] = [
                                'id_articulo' => $idArt,
                                'cantidad' => $cant,
                            ];
                        }
                    }
                }

                if (empty($detalles)) {
                    throw new Exception("Debe especificar al menos un artículo para despachar.");
                }

                $numeroDespacho = 'DESP-' . date('Ymd-His') . '-' . rand(100, 999);
                
                $despacho = new DespachoAlmacen(
                    $numeroDespacho,
                    date('Y-m-d'),
                    (int)$_POST['id_unidad_administrativa'],
                    trim($_POST['solicitante']),
                    (int)$_SESSION['usuario_id'],
                    trim($_POST['observaciones'] ?? ''),
                    'DESPACHADO',
                    null,
                    $detalles
                );

                $idDespacho = $this->despachoRepo->procesarDespacho($despacho);
                
                $this->audit('despacho_almacen', 'CREAR', $idDespacho, null, $despacho->toArray());

                header('Location: ?route=inventario/despachos&success=Despacho registrado correctamente.');
                exit;
            } catch (Exception $e) {
                $error = urlencode($e->getMessage());
                header("Location: ?route=inventario/nuevoDespacho&error=$error");
                exit;
            }
        }
    }

    public function imprimirDespacho(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        
        try {
            $despacho = $this->despachoRepo->findById($id);
            if (!$despacho) {
                throw new Exception("Despacho no encontrado.");
            }
            
            // Requerimos FPDF
            require_once __DIR__ . '/../../../libs/fpdf/fpdf.php';
            
            $pdf = new \FPDF('P', 'mm', 'Letter');
            $pdf->AddPage();
            
            // Header
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, utf8_decode('COMPROBANTE DE DESPACHO / SALIDA DE ALMACÉN'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell(0, 8, 'Numero: ' . $despacho['numero_despacho'], 0, 1, 'C');
            $pdf->Ln(5);
            
            // Info cabecera
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Fecha:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, date('d/m/Y', strtotime($despacho['fecha_despacho'])), 0, 1);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Unidad Solicitante:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, utf8_decode($despacho['unidad_administrativa']), 0, 1);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Entregado a:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, utf8_decode($despacho['solicitante']), 0, 1);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Despachado por:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 7, utf8_decode($despacho['usuario_despacha']), 0, 1);
            
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(40, 7, 'Observaciones:', 0, 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 7, utf8_decode($despacho['observaciones']));
            
            $pdf->Ln(5);
            
            // Tabla Detalles
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(20, 8, 'ID Art.', 1, 0, 'C', true);
            $pdf->Cell(110, 8, utf8_decode('Descripción'), 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Unidad', 1, 0, 'C', true);
            $pdf->Cell(30, 8, 'Cantidad', 1, 1, 'C', true);
            
            $pdf->SetFont('Arial', '', 10);
            foreach ($despacho['detalles'] as $det) {
                $pdf->Cell(20, 8, $det['id_articulo'], 1, 0, 'C');
                $pdf->Cell(110, 8, utf8_decode($det['denominacion_a']), 1, 0);
                $pdf->Cell(30, 8, utf8_decode($det['denominacion_udm']), 1, 0, 'C');
                $pdf->Cell(30, 8, number_format((float)$det['cantidad_despachada'], 2, ',', '.'), 1, 1, 'C');
            }
            
            $pdf->Ln(20);
            
            $y = $pdf->GetY();
            $pdf->Line(20, $y, 80, $y);
            $pdf->Line(130, $y, 190, $y);
            
            $pdf->SetXY(20, $y + 2);
            $pdf->Cell(60, 5, 'Firma Despachador', 0, 0, 'C');
            $pdf->SetXY(130, $y + 2);
            $pdf->Cell(60, 5, 'Firma Receptor', 0, 1, 'C');
            
            $pdf->Output('I', 'Despacho_' . $despacho['numero_despacho'] . '.pdf');
            exit;
            
        } catch (Exception $e) {
            echo "Error al generar PDF: " . htmlspecialchars($e->getMessage());
        }
    }
}
