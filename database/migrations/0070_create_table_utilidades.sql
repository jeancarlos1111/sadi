CREATE TABLE IF NOT EXISTS utilidades (
    id_utilidad SERIAL PRIMARY KEY,
    anio INT NOT NULL,
    dias_base INT NOT NULL DEFAULT 30,
    monto_total_nomina NUMERIC(15,2) NOT NULL DEFAULT 0.00,
    estatus VARCHAR(50) NOT NULL DEFAULT 'Generado',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    eliminado BOOLEAN DEFAULT false,
    UNIQUE (anio)
);

CREATE TABLE IF NOT EXISTS utilidades_detalle (
    id_detalle SERIAL PRIMARY KEY,
    id_utilidad INT NOT NULL REFERENCES utilidades(id_utilidad) ON DELETE CASCADE,
    cod_ficha INT NOT NULL REFERENCES ficha(cod_ficha),
    fecha_ingreso_calculo DATE NOT NULL,
    meses_laborados INT NOT NULL,
    salario_base NUMERIC(15,2) NOT NULL,
    monto_pagar NUMERIC(15,2) NOT NULL
);
