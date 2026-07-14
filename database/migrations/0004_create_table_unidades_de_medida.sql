-- Migración: unidades_de_medida
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS unidades_de_medida (
    id_unidades_de_medida SERIAL PRIMARY KEY,
    denominacion_udm TEXT,
    unidades_udm TEXT,
    observacion_udm TEXT,
    eliminado BOOLEAN DEFAULT false
);
