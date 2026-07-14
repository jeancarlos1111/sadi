-- Migración: reformulacion
-- Generada: 2026-07-13 01:39:34

-- REFORMULACIÓN PRESUPUESTARIA
-- Guarda el monto reformulado por estructura/cuenta (análogo a la tabla 'reformulacion' de SIGAFS)
CREATE TABLE IF NOT EXISTS reformulacion (

    id_reformulacion SERIAL PRIMARY KEY,
    id_estructura_presupuestaria INTEGER,
    id_codigo_plan_unico INTEGER,
    monto_aumento REAL DEFAULT 0,
    monto_disminucion REAL DEFAULT 0,
    fecha_reformulacion TEXT,
    motivo_reformulacion TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_codigo_plan_unico) REFERENCES plan_unico_cuentas(id_codigo_plan_unico) ON DELETE SET NULL
);
