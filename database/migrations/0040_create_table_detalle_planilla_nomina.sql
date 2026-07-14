-- Migración: detalle_planilla_nomina
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS detalle_planilla_nomina (
    id_detalle_planilla SERIAL PRIMARY KEY,
    id_planilla INTEGER,
    cod_ficha INTEGER,
    neto_trabajador REAL DEFAULT 0,
    FOREIGN KEY(id_planilla) REFERENCES planilla_nomina(id_planilla),
    FOREIGN KEY(cod_ficha) REFERENCES ficha(cod_ficha)
);
