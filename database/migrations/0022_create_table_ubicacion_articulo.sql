-- Migración: ubicacion_articulo
-- Generada: 2026-07-13 01:39:34

-- INVENTARIO (ALMACÉN)
CREATE TABLE IF NOT EXISTS ubicacion_articulo (
    id_ubicacion_articulo SERIAL PRIMARY KEY,
    denominacion_ua TEXT,
    eliminado BOOLEAN DEFAULT false
);
