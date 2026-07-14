-- Migración: cta_bancaria
-- Generada: 2026-07-13 01:39:34

CREATE TABLE IF NOT EXISTS cta_bancaria (

    id_cta_bancaria SERIAL PRIMARY KEY,
    id_banco INTEGER,
    numero_cta_bancaria TEXT,
    eliminado BOOLEAN DEFAULT false,
    FOREIGN KEY (id_banco) REFERENCES banco(id_banco) ON DELETE SET NULL
);
