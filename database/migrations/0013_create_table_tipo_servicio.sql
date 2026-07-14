-- Migración: tipo_servicio
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS tipo_servicio (
    id_tipo_servicio SERIAL PRIMARY KEY,
    denominacion     TEXT,
    descripcion      TEXT,
    eliminado        BOOLEAN DEFAULT false
);
