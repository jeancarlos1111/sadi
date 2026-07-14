-- Migración: unidad_administrativa
-- Generada: 2026-07-13 01:39:34

-- UNIDADES ADMINISTRATIVAS
CREATE TABLE IF NOT EXISTS unidad_administrativa (
    id_unidad_administrativa SERIAL PRIMARY KEY,
    codigo       TEXT,
    denominacion TEXT,
    eliminado    BOOLEAN DEFAULT false
);
