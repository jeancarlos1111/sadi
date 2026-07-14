-- Migración: proveedor
-- Generada: 2026-07-13 01:39:34

-- SADI PostgreSQL Schema

-- We don't use schema prefixes here because SQLite will attach the same DB under different names.

CREATE TABLE IF NOT EXISTS proveedor (
    id_proveedor SERIAL PRIMARY KEY,
    rif_proveedor TEXT UNIQUE,
    nit_proveedor TEXT,
    compania_proveedor TEXT,
    id_tipo_organizacion INTEGER,
    direccion_proveedor TEXT,
    telefono_proveedor TEXT,
    id_codigo_contable INTEGER,
    eliminado BOOLEAN DEFAULT false
);
