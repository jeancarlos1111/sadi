-- Migración: create_table_tipo_retencion
-- Fase 3: Retenciones SENIAT

CREATE TABLE IF NOT EXISTS tipo_retencion (
    id_tipo_retencion SERIAL PRIMARY KEY,
    codigo TEXT UNIQUE NOT NULL,      -- Ej: ISLR_HONORARIOS_PROFESIONALES
    denominacion TEXT NOT NULL,       -- Ej: ISLR Honorarios Profesionales
    porcentaje NUMERIC(5,2) NOT NULL, -- Ej: 3.00 (3%)
    sustraendo NUMERIC(15,2) DEFAULT 0, -- Ej: Si aplica sustraendo
    aplica_a TEXT NOT NULL,           -- NATURAL | JURIDICA | AMBAS
    activo BOOLEAN DEFAULT true
);

-- Inserts iniciales (Valores comunes referenciales)
INSERT INTO tipo_retencion (codigo, denominacion, porcentaje, aplica_a) VALUES
('IVA_75', 'Retención IVA 75%', 75.00, 'AMBAS'),
('IVA_100', 'Retención IVA 100%', 100.00, 'AMBAS'),
('ISLR_HONORARIOS_PROF', 'ISLR Honorarios Profesionales (Personas Naturales)', 3.00, 'NATURAL'),
('ISLR_HONORARIOS_JURID', 'ISLR Honorarios Profesionales (Personas Jurídicas)', 5.00, 'JURIDICA'),
('ISLR_SERVICIOS', 'ISLR Contratación de Servicios', 2.00, 'AMBAS');
