-- Migración: movimiento_contable
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS movimiento_contable (

    id_movimiento_contable SERIAL PRIMARY KEY,
    id_comprobante_diario INTEGER,
    id_cuenta_contable INTEGER,
    tipo_operacion_mc TEXT, -- D (Debe), H (Haber)
    monto_mc REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_comprobante_diario) REFERENCES comprobante_diario(id_comprobante_diario) ON DELETE SET NULL,
    FOREIGN KEY (id_cuenta_contable) REFERENCES cuenta_contable(id_cuenta_contable) ON DELETE SET NULL
);
