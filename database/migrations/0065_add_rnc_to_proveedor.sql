-- Migración: add_rnc_to_proveedor
-- Generada: 2026-07-19 20:35:37

ALTER TABLE proveedor ADD COLUMN IF NOT EXISTS numero_rnc TEXT;
ALTER TABLE proveedor ADD COLUMN IF NOT EXISTS fecha_vencimiento_rnc DATE;
