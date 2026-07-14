-- Migración: presupuesto_ingresos_ramo
-- Generada: 2026-07-13 01:39:34

-- Presupuesto de Ingresos
CREATE TABLE IF NOT EXISTS presupuesto_ingresos_ramo (
    id_ramo SERIAL PRIMARY KEY,
    codigo_ramo TEXT,
    denominacion_ramo TEXT,
    eliminado BOOLEAN DEFAULT false
);
