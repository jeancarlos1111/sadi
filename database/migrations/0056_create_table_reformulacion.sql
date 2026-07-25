-- Migración: reformulacion
-- Generada: 2026-07-13 01:39:34

-- REFORMULACIÓN PRESUPUESTARIA
-- Guarda el monto reformulado por estructura/cuenta (análogo a la tabla 'reformulacion' de SIGAFS)
CREATE TABLE IF NOT EXISTS reformulacion (
    id_reformulacion SERIAL PRIMARY KEY,
    id_estruc_presupuestaria INTEGER,
    id_codigo_plan_unico INTEGER,
    monto_reformulado REAL DEFAULT 0,
    fecha_registro TEXT,
    observacion TEXT,
    eliminado BOOLEAN DEFAULT false,
    UNIQUE (id_estruc_presupuestaria, id_codigo_plan_unico),
    FOREIGN KEY (id_codigo_plan_unico) REFERENCES plan_unico_cuentas(id_codigo_plan_unico) ON DELETE SET NULL,
    FOREIGN KEY (id_estruc_presupuestaria) REFERENCES estruc_presupuestaria(id_estruc_presupuestaria) ON DELETE SET NULL
);
