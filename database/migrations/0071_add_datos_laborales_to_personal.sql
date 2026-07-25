-- Migración: add_datos_laborales_to_personal
-- Fase 1: Expediente completo del trabajador

ALTER TABLE personal ADD COLUMN IF NOT EXISTS rif TEXT;
ALTER TABLE personal ADD COLUMN IF NOT EXISTS telefono TEXT;
ALTER TABLE personal ADD COLUMN IF NOT EXISTS direccion TEXT;
ALTER TABLE personal ADD COLUMN IF NOT EXISTS correo TEXT;
ALTER TABLE personal ADD COLUMN IF NOT EXISTS estado_civil TEXT DEFAULT 'SOLTERO';
ALTER TABLE personal ADD COLUMN IF NOT EXISTS cargas_familiares INT DEFAULT 0;
ALTER TABLE personal ADD COLUMN IF NOT EXISTS nivel_instruccion TEXT;

-- Datos laborales que van en ficha
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS tipo_relacion_laboral TEXT DEFAULT 'FIJO';
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS banco TEXT;
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS numero_cuenta TEXT;
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS tipo_cuenta TEXT DEFAULT 'CORRIENTE';
