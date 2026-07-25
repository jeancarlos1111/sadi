-- Migración: prestacion_garantia
-- Creado para manejar las Prestaciones Sociales (LOTTT Art. 142)

-- 1. Añadir parámetros personalizables a la Ficha para Convenciones Colectivas
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS dias_utilidades INTEGER DEFAULT 30;
ALTER TABLE ficha ADD COLUMN IF NOT EXISTS dias_bono_vacacional INTEGER DEFAULT 15;

-- 2. Crear tabla de Prestaciones Garantía
CREATE TABLE IF NOT EXISTS prestacion_garantia (
    id_prestacion SERIAL PRIMARY KEY,
    cod_ficha INTEGER NOT NULL,
    periodo TEXT NOT NULL, -- Ej: '2026-Q1'
    tipo TEXT NOT NULL, -- 'TRIMESTRAL' o 'DIAS_ADICIONALES'
    dias_depositados INTEGER NOT NULL DEFAULT 15,
    salario_integral_diario REAL NOT NULL,
    monto REAL NOT NULL,
    fecha_proceso DATE NOT NULL DEFAULT CURRENT_DATE,
    eliminado BOOLEAN DEFAULT false,
    CONSTRAINT fk_prestacion_ficha FOREIGN KEY (cod_ficha) REFERENCES ficha (cod_ficha) ON DELETE RESTRICT
);
