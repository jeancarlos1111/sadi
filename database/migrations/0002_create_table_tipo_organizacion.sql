-- Migración: tipo_organizacion
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS tipo_organizacion (
    id_tipo_organizacion SERIAL PRIMARY KEY,
    nombre_tipo_organizacion TEXT,
    eliminado BOOLEAN DEFAULT false
);
