-- Migración: movimiento_bancario
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS movimiento_bancario (

    id_movimiento_bancario SERIAL PRIMARY KEY,
    id_cta_bancaria INTEGER,
    id_tipo_operacion_bancaria INTEGER,
    monto REAL DEFAULT 0,
    fecha TEXT,
    referencia TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_cta_bancaria) REFERENCES cta_bancaria(id_cta_bancaria) ON DELETE SET NULL,
    FOREIGN KEY (id_tipo_operacion_bancaria) REFERENCES tipo_operacion_bancaria(id_tipo_operacion_bancaria) ON DELETE SET NULL
);
