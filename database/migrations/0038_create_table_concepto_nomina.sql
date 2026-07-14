-- Migración: concepto_nomina
-- Generada: 2026-07-13 01:39:34

-- TABLAS TRANSACCIONALES DE NÓMINA

CREATE TABLE IF NOT EXISTS concepto_nomina (
    id_concepto SERIAL PRIMARY KEY,
    codigo TEXT,
    descripcion TEXT,
    tipo_concepto TEXT, -- 'A' Asignacion, 'D' Deduccion
    formula_valor REAL, -- Puede ser un monto fijo o un porcentaje. Para simplicidad de MVP usaremos montos fijos o un flag de porcentaje.
    es_porcentaje BOOLEAN DEFAULT false, -- Si es 1, formula_valor es % sobre sueldo basico
    eliminado BOOLEAN DEFAULT false
);
