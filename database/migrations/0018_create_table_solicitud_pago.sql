-- Migración: solicitud_pago
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS solicitud_pago (

    id_solicitud_pago SERIAL PRIMARY KEY,
    fecha_solicitud_pago TEXT,
    concepto_solicitud_pago TEXT,
    monto_pagar_solicitud_pago REAL,
    id_documento INTEGER,
    contabilizada BOOLEAN DEFAULT false,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_documento) REFERENCES documento(id_documento) ON DELETE SET NULL
);
