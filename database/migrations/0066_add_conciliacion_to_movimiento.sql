-- Migración: add_conciliacion_to_movimiento
-- Generada: 2026-07-19 20:50:45

ALTER TABLE movimiento_bancario ADD COLUMN IF NOT EXISTS conciliado BOOLEAN DEFAULT false;
ALTER TABLE movimiento_bancario ADD COLUMN IF NOT EXISTS fecha_conciliacion DATE;
