-- Migración: add_proceso_to_orden_compra
-- Fase 2: Módulo LCP

ALTER TABLE orden_de_compra ADD COLUMN IF NOT EXISTS id_proceso_contratacion INTEGER
    REFERENCES proceso_contratacion(id_proceso);
