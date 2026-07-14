-- Migración: tipo_operacion_presupuesto
-- Generada: 2026-07-13 01:39:34

-- TIPOS DE SERVICIO

-- TIPOS DE OPERACIÓN PRESUPUESTARIA
CREATE TABLE IF NOT EXISTS tipo_operacion_presupuesto (
    id_tipo_operacion_presupuesto SERIAL PRIMARY KEY,
    denominacion TEXT,
    descripcion  TEXT,
    eliminado    BOOLEAN DEFAULT false
);
