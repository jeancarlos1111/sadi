-- 0069_create_table_vacaciones.sql
-- Crea la tabla de vacaciones para el cumplimiento de LOTTT

CREATE TABLE IF NOT EXISTS vacaciones (
    id_vacacion SERIAL PRIMARY KEY,
    cod_ficha INT NOT NULL REFERENCES ficha(cod_ficha) ON DELETE CASCADE,
    fecha_salida DATE NOT NULL,
    fecha_retorno DATE NOT NULL,
    dias_disfrute INT NOT NULL,
    dias_bono INT NOT NULL,
    monto_vacaciones REAL NOT NULL,
    monto_bono REAL NOT NULL,
    monto_total REAL NOT NULL,
    estatus VARCHAR(20) DEFAULT 'Pagado',
    eliminado BOOLEAN DEFAULT FALSE,
    creado_el TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_el TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_vacaciones_cod_ficha ON vacaciones(cod_ficha);
