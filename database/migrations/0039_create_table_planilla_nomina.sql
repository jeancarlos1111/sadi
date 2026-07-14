-- Migración: planilla_nomina
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS planilla_nomina (
    id_planilla SERIAL PRIMARY KEY,
    nomina_cod_nomina INTEGER,
    fecha_emision TEXT,
    periodo TEXT, -- Ej: '1era Quincena Enero 2026'
    monto_total_asignaciones REAL DEFAULT 0,
    monto_total_deducciones REAL DEFAULT 0,
    monto_total_neto REAL DEFAULT 0,
    contabilizada BOOLEAN DEFAULT false, -- Si ya generó Solicitud de Pago
    FOREIGN KEY(nomina_cod_nomina) REFERENCES nomina(cod_nomina)
);
