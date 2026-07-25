<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Database\Seeder;

class CatalogosBasicosSeeder extends Seeder
{
    public function run(): void
    {
        $this->pdo->exec(<<<SQL
INSERT INTO tipo_servicio (id_tipo_servicio, denominacion) VALUES
(1, 'Servicios Profesionales'),
(2, 'Mantenimiento y Reparación'),
(3, 'Servicios de Transporte')
ON CONFLICT (id_tipo_servicio) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO tipo_organizacion (id_tipo_organizacion, nombre_tipo_organizacion) VALUES 
(1, 'Firma Personal'),
(2, 'Compania Anonima (C.A.)'),
(3, 'Sociedad Anonima (S.A.)')
ON CONFLICT (id_tipo_organizacion) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO tipo_de_articulo (id_tipo_de_articulo, denominacion_tda, tipo_tda) VALUES 
(1, 'Material de Oficina', 1),
(2, 'Equipos de Computación', 1)
ON CONFLICT (id_tipo_de_articulo) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO unidades_de_medida (id_unidades_de_medida, denominacion_udm, unidades_udm) VALUES 
(1, 'Unidad', 'UND'),
(2, 'Caja', 'CAJ'),
(3, 'Resma', 'RES')
ON CONFLICT (id_unidades_de_medida) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO tipo_documento (id_tipo_documento, denominacion_tipo_documento, afecta_presupuesto_tipo_documento, siglas_tipo_documento) VALUES 
(1, 'Factura', true, 'FAC'),
(2, 'Nota de Entrega', false, 'NE')
ON CONFLICT (id_tipo_documento) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO banco (id_banco, nombre_banco) VALUES 
(1, 'Banco de Venezuela'),
(2, 'Banco Banesco')
ON CONFLICT (id_banco) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO tipo_operacion_bancaria (id_tipo_operacion_bancaria, nombre_tipo_operacion_bancaria, acronimo_tipo_operacion_bancaria) VALUES 
(1, 'Depósito', 'DP'),
(2, 'Cheque', 'CH'),
(3, 'Transferencia', 'TR'),
(4, 'Nota de Débito', 'ND')
ON CONFLICT (id_tipo_operacion_bancaria) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO cargo (cod_cargo, nombre) VALUES 
(1, 'Director General'),
(2, 'Analista de Sistemas')
ON CONFLICT (cod_cargo) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO concepto_nomina (id_concepto, codigo, descripcion, tipo_concepto, formula_valor, es_porcentaje) VALUES 
(1, 'A001', 'Sueldo Básico', 'A', 100, true),
(2, 'A002', 'Bono Profesional', 'A', 500.00, false),
(3, 'D001', 'Seguro Social Obligatorio (SSO)', 'D', 4, true),
(4, 'D002', 'Fondo de Ahorro para la Vivienda (FAOV)', 'D', 1, true)
ON CONFLICT (id_concepto) DO NOTHING;
SQL);

        // ISLR Retencion Concept
        $this->pdo->exec(<<<SQL
INSERT INTO concepto_nomina (id_concepto, codigo, descripcion, tipo_concepto, formula_valor, es_porcentaje, formula_expr) VALUES 
(5, 'D-ISLR', 'Retención ISLR (AR-I)', 'D', 0, false, 'SUELDO * (PORCENTAJE_ISLR / 100)')
ON CONFLICT (id_concepto) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO usuario (id_usuario, usuario, contrasenya, cedula_personal) VALUES 
(1, 'ADMINISTRADOR', 'e10adc3949ba59abbe56e057f20f883e', NULL)
ON CONFLICT (id_usuario) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO anio_presupuestario (anio, estado) VALUES 
(2026, true)
ON CONFLICT (anio) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO tipo_operacion_presupuesto (id_tipo_operacion_presupuesto, denominacion) VALUES
(1, 'Gasto Ordinario'),
(2, 'Inversión'),
(3, 'Transferencias')
ON CONFLICT (id_tipo_operacion_presupuesto) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO unidad_administrativa (id_unidad_administrativa, codigo, denominacion) VALUES
(3, 'RRHH', 'Recursos Humanos')
ON CONFLICT (id_unidad_administrativa) DO NOTHING;
SQL);

        $this->pdo->exec(<<<SQL
INSERT INTO fuente_financiamiento (id_fuente_financiamiento, denominacion) VALUES
(1, 'Recursos Ordinarios'),
(2, 'Situado Constitucional'),
(3, 'Ingresos Propios'),
(4, 'FONDEN')
ON CONFLICT (id_fuente_financiamiento) DO NOTHING;
SQL);

        // ── RBAC: Usuario Administrador (bcrypt, longitud >= 8) ────────────────
        $hashAdmin = password_hash('Admin2026!', PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare("
            INSERT INTO usuario (id_usuario, usuario, contrasenya, cedula_personal)
            VALUES (1, 'ADMINISTRADOR', :hash, NULL)
            ON CONFLICT (id_usuario) DO UPDATE SET contrasenya = EXCLUDED.contrasenya
        ");
        $stmt->execute(['hash' => $hashAdmin]);
        $this->pdo->exec("SELECT setval('usuario_id_usuario_seq', (SELECT MAX(id_usuario) FROM usuario))");

        // ── RBAC: Catálogo de Permisos ─────────────────────────────────────────
        // Formato: (modulo, seccion, accion)
        // Módulos: presupuesto, compras, nomina, tesoreria, contabilidad, inventario, cxp, documental, reportes, admin
        $acciones = ['ver', 'crear', 'editar', 'eliminar'];
        $modulos  = [
            'presupuesto'  => ['gastos', 'ingresos', 'comprobantes', 'ajustes', 'estructuras', 'plan_cuentas', 'periodos', 'disponibilidad', 'proyectos', 'acciones_centralizadas', 'pac', 'fuentes', 'vinculacion_puc', 'operaciones'],
            'compras'      => ['proveedores', 'requisiciones_bienes', 'requisiciones_servicios', 'ordenes_compra', 'ordenes_servicio', 'proceso_contratacion'],
            'nomina'       => ['planillas', 'conceptos', 'prestaciones', 'personal', 'trabajadores'],
            'tesoreria'    => ['bancos', 'cuentas_bancarias', 'operaciones_bancarias', 'caja', 'cheques', 'fondo_avance'],
            'contabilidad' => ['asientos', 'apertura_cuentas', 'mayor_analitico', 'estados_financieros'],
            'inventario'   => ['articulos', 'tipos_articulos', 'servicios', 'tipos_servicios', 'unidades_medida', 'bienes', 'depreciacion', 'toma', 'despachos'],
            'cxp'          => ['documentos', 'solicitudes_pago', 'retenciones', 'deducciones', 'beneficiarios'],
            'documental'   => ['documentos', 'tipos_documentos'],
            'reportes'     => ['general'],
            'admin'        => ['usuarios', 'roles'],
        ];

        $permisoStmt = $this->pdo->prepare("
            INSERT INTO permiso (modulo, seccion, accion, descripcion)
            VALUES (:modulo, :seccion, :accion, :desc)
            ON CONFLICT (modulo, seccion, accion) DO NOTHING
        ");

        foreach ($modulos as $modulo => $secciones) {
            foreach ($secciones as $seccion) {
                foreach ($acciones as $accion) {
                    $permisoStmt->execute([
                        'modulo'  => $modulo,
                        'seccion' => $seccion,
                        'accion'  => $accion,
                        'desc'    => ucfirst($accion) . ' en ' . $modulo . '.' . $seccion,
                    ]);
                }
            }
        }

        // ── RBAC: Definición de Roles ──────────────────────────────────────────
        $roles = [
            // Rol global
            ['nombre' => 'ADMINISTRADOR',           'desc' => 'Acceso total al sistema'],
            ['nombre' => 'CONSULTA',                 'desc' => 'Solo lectura en todos los módulos'],
            // Presupuesto
            ['nombre' => 'DIRECTOR_PRESUPUESTO',     'desc' => 'Director del módulo de Presupuesto'],
            ['nombre' => 'COORDINADOR_PRESUPUESTO',  'desc' => 'Coordinador del módulo de Presupuesto'],
            ['nombre' => 'ANALISTA_PRESUPUESTO',     'desc' => 'Analista del módulo de Presupuesto'],
            // Compras
            ['nombre' => 'DIRECTOR_COMPRAS',         'desc' => 'Director del módulo de Compras'],
            ['nombre' => 'COORDINADOR_COMPRAS',      'desc' => 'Coordinador del módulo de Compras'],
            ['nombre' => 'ANALISTA_COMPRAS',         'desc' => 'Analista del módulo de Compras'],
            // Nómina
            ['nombre' => 'DIRECTOR_NOMINA',          'desc' => 'Director del módulo de Nómina'],
            ['nombre' => 'COORDINADOR_NOMINA',       'desc' => 'Coordinador del módulo de Nómina'],
            ['nombre' => 'ANALISTA_NOMINA',          'desc' => 'Analista del módulo de Nómina'],
            // Tesorería
            ['nombre' => 'DIRECTOR_TESORERIA',       'desc' => 'Director del módulo de Tesorería'],
            ['nombre' => 'COORDINADOR_TESORERIA',    'desc' => 'Coordinador del módulo de Tesorería'],
            ['nombre' => 'ANALISTA_TESORERIA',       'desc' => 'Analista del módulo de Tesorería'],
            // Contabilidad
            ['nombre' => 'DIRECTOR_CONTABILIDAD',    'desc' => 'Director del módulo de Contabilidad'],
            ['nombre' => 'COORDINADOR_CONTABILIDAD', 'desc' => 'Coordinador del módulo de Contabilidad'],
            ['nombre' => 'ANALISTA_CONTABILIDAD',    'desc' => 'Analista del módulo de Contabilidad'],
            // Inventario
            ['nombre' => 'DIRECTOR_INVENTARIO',      'desc' => 'Director del módulo de Inventario'],
            ['nombre' => 'COORDINADOR_INVENTARIO',   'desc' => 'Coordinador del módulo de Inventario'],
            ['nombre' => 'ANALISTA_INVENTARIO',      'desc' => 'Analista del módulo de Inventario'],
            // CxP
            ['nombre' => 'DIRECTOR_CXP',             'desc' => 'Director del módulo de Cuentas por Pagar'],
            ['nombre' => 'COORDINADOR_CXP',          'desc' => 'Coordinador del módulo de Cuentas por Pagar'],
            ['nombre' => 'ANALISTA_CXP',             'desc' => 'Analista del módulo de Cuentas por Pagar'],
        ];

        $rolStmt = $this->pdo->prepare("
            INSERT INTO rol (nombre, descripcion)
            VALUES (:nombre, :desc)
            ON CONFLICT (nombre) DO NOTHING
        ");
        foreach ($roles as $rol) {
            $rolStmt->execute(['nombre' => $rol['nombre'], 'desc' => $rol['desc']]);
        }

        // ── RBAC: Asignación de permisos por Rol ──────────────────────────────
        // Helper para obtener IDs de permisos
        $getPermisoId = function (string $modulo, string $seccion, string $accion): ?int {
            $s = $this->pdo->prepare("SELECT id_permiso FROM permiso WHERE modulo=? AND seccion=? AND accion=?");
            $s->execute([$modulo, $seccion, $accion]);
            $r = $s->fetchColumn();
            return $r ? (int)$r : null;
        };

        $getRolId = function (string $nombre): ?int {
            $s = $this->pdo->prepare("SELECT id_rol FROM rol WHERE nombre = ?");
            $s->execute([$nombre]);
            $r = $s->fetchColumn();
            return $r ? (int)$r : null;
        };

        $asignarPermiso = function (int $idRol, int $idPermiso): void {
            $this->pdo->prepare("
                INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?,?)
                ON CONFLICT DO NOTHING
            ")->execute([$idRol, $idPermiso]);
        };

        $asignarTodoModulo = function (int $idRol, string $modulo, array $accionesPermitidas) use ($modulos, $getPermisoId, $asignarPermiso): void {
            foreach ($modulos[$modulo] ?? [] as $seccion) {
                foreach ($accionesPermitidas as $accion) {
                    $idP = $getPermisoId($modulo, $seccion, $accion);
                    if ($idP) {
                        $asignarPermiso($idRol, $idP);
                    }
                }
            }
        };

        // ADMINISTRADOR → todos los permisos de todos los módulos
        $idAdmin = $getRolId('ADMINISTRADOR');
        if ($idAdmin) {
            foreach ($modulos as $modulo => $secciones) {
                $asignarTodoModulo($idAdmin, $modulo, $acciones);
            }
        }

        // CONSULTA → solo 'ver' en todos los módulos
        $idConsulta = $getRolId('CONSULTA');
        if ($idConsulta) {
            foreach ($modulos as $modulo => $secciones) {
                $asignarTodoModulo($idConsulta, $modulo, ['ver']);
            }
        }

        // DIRECTOR → ver + crear + editar + eliminar en SU módulo
        // COORDINADOR → ver + crear + editar en SU módulo
        // ANALISTA → ver + crear en SU módulo
        $rolesModulo = [
            'presupuesto'  => ['DIRECTOR_PRESUPUESTO', 'COORDINADOR_PRESUPUESTO', 'ANALISTA_PRESUPUESTO'],
            'compras'      => ['DIRECTOR_COMPRAS',       'COORDINADOR_COMPRAS',      'ANALISTA_COMPRAS'],
            'nomina'       => ['DIRECTOR_NOMINA',         'COORDINADOR_NOMINA',        'ANALISTA_NOMINA'],
            'tesoreria'    => ['DIRECTOR_TESORERIA',      'COORDINADOR_TESORERIA',     'ANALISTA_TESORERIA'],
            'contabilidad' => ['DIRECTOR_CONTABILIDAD',   'COORDINADOR_CONTABILIDAD',  'ANALISTA_CONTABILIDAD'],
            'inventario'   => ['DIRECTOR_INVENTARIO',     'COORDINADOR_INVENTARIO',    'ANALISTA_INVENTARIO'],
            'cxp'          => ['DIRECTOR_CXP',            'COORDINADOR_CXP',           'ANALISTA_CXP'],
        ];

        $accionesPorNivel = [
            'DIRECTOR'     => ['ver', 'crear', 'editar', 'eliminar'],
            'COORDINADOR'  => ['ver', 'crear', 'editar'],
            'ANALISTA'     => ['ver', 'crear'],
        ];

        foreach ($rolesModulo as $modulo => $rolesDelModulo) {
            foreach ($rolesDelModulo as $nombreRol) {
                $nivel = explode('_', $nombreRol)[0];
                $accionesRol = $accionesPorNivel[$nivel] ?? ['ver'];
                $idRol = $getRolId($nombreRol);
                if ($idRol) {
                    $asignarTodoModulo($idRol, $modulo, $accionesRol);
                    // Todos los roles con acceso a reportes pueden ver
                    $asignarTodoModulo($idRol, 'reportes', ['ver']);
                }
            }
        }

        // ── RBAC: Asignar rol ADMINISTRADOR al usuario ID=1 ────────────────────
        if ($idAdmin) {
            $this->pdo->prepare("
                INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (1, ?)
                ON CONFLICT DO NOTHING
            ")->execute([$idAdmin]);
        }

        // ── RBAC: Crear usuario de prueba ANALISTA_PRESUPUESTO ────────────────
        $idAnalistaP = $getRolId('ANALISTA_PRESUPUESTO');
        if ($idAnalistaP) {
            $stmt = $this->pdo->prepare("
                INSERT INTO usuario (id_usuario, usuario, contrasenya, cedula_personal)
                VALUES (2, 'ANALISTA', :hash, NULL)
                ON CONFLICT (id_usuario) DO UPDATE SET contrasenya = EXCLUDED.contrasenya
            ");
            $stmt->execute(['hash' => $hashAdmin]);
            
            $this->pdo->prepare("
                INSERT INTO usuario_rol (id_usuario, id_rol) VALUES (2, ?)
                ON CONFLICT DO NOTHING
            ")->execute([$idAnalistaP]);
        }

        // ── Sincronizar secuencia de la tabla usuario ──────────────────────────
        $this->pdo->exec("SELECT setval('usuario_id_usuario_seq', (SELECT MAX(id_usuario) FROM usuario))");
    }
}
