<?php
$mapping = [
    'PresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'gastos'],
    'PresupuestoIngresoController' => ['modulo' => 'presupuesto', 'seccion' => 'ingresos'],
    'ComprobantePresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'comprobantes'],
    'AjustesPresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'ajustes'],
    'EstrucPresupuestariaController' => ['modulo' => 'presupuesto', 'seccion' => 'estructuras'],
    'PlanUnicoCuentasController' => ['modulo' => 'presupuesto', 'seccion' => 'plan_cuentas'],
    'PeriodosPresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'periodos'],
    'DisponibilidadPresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'disponibilidad'],
    'ProyectosController' => ['modulo' => 'presupuesto', 'seccion' => 'proyectos'],
    'AccionesCentralizadasController' => ['modulo' => 'presupuesto', 'seccion' => 'acciones_centralizadas'],
    'PACController' => ['modulo' => 'presupuesto', 'seccion' => 'pac'],
    'FuenteFinanciamientoController' => ['modulo' => 'presupuesto', 'seccion' => 'fuentes'],
    'TiposOperacionPresupuestoController' => ['modulo' => 'presupuesto', 'seccion' => 'operaciones'],
    'ProveedoresController' => ['modulo' => 'compras', 'seccion' => 'proveedores'],
    'OrdenesCompraController' => ['modulo' => 'compras', 'seccion' => 'ordenes_compra'],
    'OrdenesServicioController' => ['modulo' => 'compras', 'seccion' => 'ordenes_servicio'],
    'RequisicionesBienesController' => ['modulo' => 'compras', 'seccion' => 'requisiciones_bienes'],
    'RequisicionesServiciosController' => ['modulo' => 'compras', 'seccion' => 'requisiciones_servicios'],
    'NominaController' => ['modulo' => 'nomina', 'seccion' => 'planillas'],
    'NominaConceptosController' => ['modulo' => 'nomina', 'seccion' => 'conceptos'],
    'BancosController' => ['modulo' => 'tesoreria', 'seccion' => 'bancos'],
    'CuentasBancariasController' => ['modulo' => 'tesoreria', 'seccion' => 'cuentas_bancarias'],
    'TiposOperacionesBancariasController' => ['modulo' => 'tesoreria', 'seccion' => 'operaciones_bancarias'],
    'CajaController' => ['modulo' => 'tesoreria', 'seccion' => 'caja'],
    'ChequesController' => ['modulo' => 'tesoreria', 'seccion' => 'cheques'],
    'ContabilidadController' => ['modulo' => 'contabilidad', 'seccion' => 'asientos'],
    'AperturaCuentasController' => ['modulo' => 'contabilidad', 'seccion' => 'apertura_cuentas'],
    'MayorAnaliticoController' => ['modulo' => 'contabilidad', 'seccion' => 'mayor_analitico'],
    'EstadosFinancierosController' => ['modulo' => 'contabilidad', 'seccion' => 'estados_financieros'],
    'VinculacionPucContableController' => ['modulo' => 'presupuesto', 'seccion' => 'vinculacion_puc'],
    'InventarioController' => ['modulo' => 'inventario', 'seccion' => 'articulos'],
    'ArticulosController' => ['modulo' => 'inventario', 'seccion' => 'articulos'],
    'TiposArticulosController' => ['modulo' => 'inventario', 'seccion' => 'tipos_articulos'],
    'ServiciosController' => ['modulo' => 'inventario', 'seccion' => 'servicios'],
    'TiposServiciosController' => ['modulo' => 'inventario', 'seccion' => 'tipos_servicios'],
    'UnidadesMedidaController' => ['modulo' => 'inventario', 'seccion' => 'unidades_medida'],
    'CuentasPorPagarController' => ['modulo' => 'cxp', 'seccion' => 'documentos'],
    'DocumentosPorPagarController' => ['modulo' => 'cxp', 'seccion' => 'documentos'],
    'SolicitudesPagoController' => ['modulo' => 'cxp', 'seccion' => 'solicitudes_pago'],
    'DeduccionesCxPController' => ['modulo' => 'cxp', 'seccion' => 'deducciones'],
    'RetencionesController' => ['modulo' => 'cxp', 'seccion' => 'retenciones'],
    'BeneficiariosController' => ['modulo' => 'cxp', 'seccion' => 'beneficiarios'],
    'DocumentalController' => ['modulo' => 'documental', 'seccion' => 'documentos'],
    'TipoDocumentosController' => ['modulo' => 'documental', 'seccion' => 'tipos_documentos'],
    'ReportesController' => ['modulo' => 'reportes', 'seccion' => 'general'],
    'ReportesCxpController' => ['modulo' => 'reportes', 'seccion' => 'general'],
];

$dir = 'src/Controllers/';
$files = scandir($dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $className = str_replace('.php', '', $file);
    if (!isset($mapping[$className])) continue;

    $mod = $mapping[$className]['modulo'];
    $sec = $mapping[$className]['seccion'];
    
    $content = file_get_contents($dir . $file);
    
    // Add use App\Auth\Gate; if not present
    if (strpos($content, 'use App\Auth\Gate;') === false) {
        $content = preg_replace('/namespace App\\\\Controllers;/', "namespace App\\Controllers;\n\nuse App\\Auth\\Gate;", $content, 1);
    }
    
    // index()
    $content = preg_replace('/public function index\([^)]*\)(?:\s*:\s*void)?\s*\{/i', "$0\n        Gate::authorize('{$mod}.{$sec}.ver');", $content);
    // form()
    $content = preg_replace_callback('/public function form\([^)]*\)(?:\s*:\s*void)?\s*\{/i', function($m) use ($mod, $sec) {
        return $m[0] . "\n        \$id = \$_GET['id'] ?? null;\n        Gate::authorize(\$id ? '{$mod}.{$sec}.editar' : '{$mod}.{$sec}.crear');";
    }, $content);
    // save()
    $content = preg_replace_callback('/public function save\([^)]*\)(?:\s*:\s*void)?\s*\{/i', function($m) use ($mod, $sec) {
        return $m[0] . "\n        \$id = \$_POST['id'] ?? null;\n        Gate::authorize(\$id ? '{$mod}.{$sec}.editar' : '{$mod}.{$sec}.crear');";
    }, $content);
    // delete() or eliminar()
    $content = preg_replace('/public function (?:delete|eliminar)\([^)]*\)(?:\s*:\s*void)?\s*\{/i', "$0\n        Gate::authorize('{$mod}.{$sec}.eliminar');", $content);

    file_put_contents($dir . $file, $content);
    echo "Updated $className\n";
}
