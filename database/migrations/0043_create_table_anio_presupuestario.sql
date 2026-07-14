-- Migración: anio_presupuestario
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS anio_presupuestario (
    anio INTEGER PRIMARY KEY,
    estado BOOLEAN DEFAULT true
);
