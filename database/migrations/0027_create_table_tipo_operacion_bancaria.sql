-- Migración: tipo_operacion_bancaria
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS tipo_operacion_bancaria (
    id_tipo_operacion_bancaria SERIAL PRIMARY KEY,
    nombre_tipo_operacion_bancaria TEXT,
    acronimo_tipo_operacion_bancaria TEXT,
    eliminado BOOLEAN DEFAULT false
);
