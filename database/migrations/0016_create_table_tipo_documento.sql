-- Migración: tipo_documento
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS tipo_documento (
    id_tipo_documento SERIAL PRIMARY KEY,
    denominacion_tipo_documento TEXT,
    afecta_presupuesto_tipo_documento BOOLEAN,
    siglas_tipo_documento TEXT,
    eliminado BOOLEAN DEFAULT false
);
