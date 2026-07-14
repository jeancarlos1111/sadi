-- Migración: presupuesto_ingresos
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS presupuesto_ingresos (

    id_presupuesto_ingreso SERIAL PRIMARY KEY,
    id_ramo INTEGER REFERENCES presupuesto_ingresos_ramo(id_ramo),
    monto_estimado_inicial REAL DEFAULT 0,
    monto_recaudado REAL DEFAULT 0,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_ramo) REFERENCES presupuesto_ingresos_ramo(id_ramo) ON DELETE SET NULL
);
