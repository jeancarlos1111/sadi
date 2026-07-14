-- Migración: detalle_recibo_concepto
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS detalle_recibo_concepto (
    id_detalle_recibo SERIAL PRIMARY KEY,
    id_detalle_planilla INTEGER,
    id_concepto INTEGER,
    monto_calculado REAL DEFAULT 0,
    FOREIGN KEY(id_detalle_planilla) REFERENCES detalle_planilla_nomina(id_detalle_planilla),
    FOREIGN KEY(id_concepto) REFERENCES concepto_nomina(id_concepto)
);
