-- Migración: 0082_create_table_fondo_avance
-- Crea tablas para el manejo de Fondo en Avance / Anticipo (Caja Chica ONCOP)

CREATE TABLE IF NOT EXISTS fondo_avance (
    id_fondo SERIAL PRIMARY KEY,
    denominacion TEXT NOT NULL,
    monto_maximo REAL NOT NULL,
    responsable_cedula TEXT NOT NULL,
    fecha_creacion DATE NOT NULL,
    estado TEXT NOT NULL DEFAULT 'ACTIVO', -- ACTIVO, CERRADO
    id_cuenta_contable INTEGER,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_cuenta_contable) REFERENCES cuenta_contable(id_cuenta_contable) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fondo_avance_reposicion (
    id_reposicion SERIAL PRIMARY KEY,
    id_fondo INTEGER NOT NULL,
    fecha_reposicion DATE NOT NULL,
    monto_rendido REAL NOT NULL DEFAULT 0,
    estado TEXT NOT NULL DEFAULT 'PENDIENTE', -- PENDIENTE, APROBADA, PAGADA
    id_solicitud_pago INTEGER, -- Referencia manual a la solicitud de pago generada
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_fondo) REFERENCES fondo_avance(id_fondo) ON DELETE CASCADE,
    FOREIGN KEY (id_solicitud_pago) REFERENCES solicitud_pago(id_solicitud_pago) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS fondo_avance_gasto (
    id_gasto SERIAL PRIMARY KEY,
    id_reposicion INTEGER NOT NULL,
    fecha_gasto DATE NOT NULL,
    concepto TEXT NOT NULL,
    monto REAL NOT NULL,
    factura_num TEXT,
    proveedor_rif TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_reposicion) REFERENCES fondo_avance_reposicion(id_reposicion) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_fondo_avance_estado ON fondo_avance (estado);
CREATE INDEX IF NOT EXISTS idx_fondo_avance_reposicion_fondo ON fondo_avance_reposicion (id_fondo);
CREATE INDEX IF NOT EXISTS idx_fondo_avance_gasto_reposicion ON fondo_avance_gasto (id_reposicion);
