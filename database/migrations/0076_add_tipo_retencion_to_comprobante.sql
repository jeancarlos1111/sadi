-- Migración: add_tipo_retencion_to_comprobante
-- Fase 3: Retenciones SENIAT

ALTER TABLE comprobante_retencion 
ADD COLUMN IF NOT EXISTS id_tipo_retencion INTEGER REFERENCES tipo_retencion(id_tipo_retencion);
