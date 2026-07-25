-- Migración: Tablas RBAC (Role-Based Access Control)
-- Generada: 2026-07-14

-- Tabla de Roles
CREATE TABLE IF NOT EXISTS rol (
    id_rol      SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    eliminado   BOOLEAN DEFAULT false
);

-- Tabla de Permisos (acción granular por módulo + sección)
-- modulo: nombre del módulo principal (ej: 'presupuesto', 'compras')
-- seccion: sub-área dentro del módulo (ej: 'gastos', 'proveedores', '*' para todos)
-- accion: 'ver', 'crear', 'editar', 'eliminar'
CREATE TABLE IF NOT EXISTS permiso (
    id_permiso  SERIAL PRIMARY KEY,
    modulo      VARCHAR(100) NOT NULL,
    seccion     VARCHAR(100) NOT NULL DEFAULT '*',
    accion      VARCHAR(50)  NOT NULL,
    descripcion TEXT,
    UNIQUE(modulo, seccion, accion)
);

-- Relación Rol ↔ Permiso (muchos a muchos)
CREATE TABLE IF NOT EXISTS rol_permiso (
    id_rol     INTEGER NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    id_permiso INTEGER NOT NULL REFERENCES permiso(id_permiso) ON DELETE CASCADE,
    PRIMARY KEY (id_rol, id_permiso)
);

-- Relación Usuario ↔ Rol (muchos a muchos)
CREATE TABLE IF NOT EXISTS usuario_rol (
    id_usuario INTEGER NOT NULL REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    id_rol     INTEGER NOT NULL REFERENCES rol(id_rol) ON DELETE CASCADE,
    PRIMARY KEY (id_usuario, id_rol)
);
