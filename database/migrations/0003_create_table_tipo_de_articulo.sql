-- Migración: tipo_de_articulo
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS tipo_de_articulo (
    id_tipo_de_articulo SERIAL PRIMARY KEY,
    denominacion_tda TEXT,
    descripcion_tda TEXT,
    tipo_tda INTEGER,
    eliminado BOOLEAN DEFAULT false
);
