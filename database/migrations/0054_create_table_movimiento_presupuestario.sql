-- Migración: movimiento_presupuestario
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS movimiento_presupuestario (
    id_movimiento_presupuestario SERIAL PRIMARY KEY,
    id_comprobante INTEGER NOT NULL,
    id_estruc_presupuestaria INTEGER NOT NULL,
    id_codigo_plan_unico INTEGER NOT NULL,
    id_operacion TEXT NOT NULL,        -- (ej. 'AAP', 'CA', 'CG', 'PAG', 'TR') suma o resta
    monto_mp REAL DEFAULT 0,
    descripcion_mp TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY(id_comprobante) REFERENCES comprobante_presupuestario(id_comprobante)
);
