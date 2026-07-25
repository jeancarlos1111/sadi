-- Migración: add_islr_to_ficha
-- Agrega el porcentaje de retención AR-I (Decreto 1.808) a la ficha del trabajador

ALTER TABLE ficha ADD COLUMN IF NOT EXISTS porcentaje_islr REAL DEFAULT 0;
ALTER TABLE concepto_nomina ADD COLUMN IF NOT EXISTS formula_expr TEXT;
