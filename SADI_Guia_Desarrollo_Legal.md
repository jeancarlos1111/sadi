# SADI — Guía de Desarrollo Legal y Funcional
## Análisis de Alineación con el Marco Jurídico de la Administración Pública Venezolana

> **Versión:** 1.1 | **Fecha:** Julio 2026  
> Este documento es la guía maestra de desarrollo del sistema SADI. Define la hoja de ruta funcional basada en la normativa venezolana vigente para que el sistema pueda ser adoptado por cualquier ente de la administración pública.

---

## Marco Normativo de Referencia

| Ley / Normativa | Ente Rector | Ámbito de Aplicación en SADI |
|---|---|---|
| **LOAFSP** — Ley Orgánica de la Adm. Financiera del Sector Público | MPPF | Estructura general del sistema |
| **ONCOP** — Instrucciones de Contabilidad Pública | ONCOP | Módulo de Contabilidad y Plan de Cuentas |
| **ONAPRE** — Instrucciones de Formulación Presupuestaria | ONAPRE | Módulo de Presupuesto (POA) |
| **LCP** — Ley de Contrataciones Públicas | SNC | Módulo de Compras (Órdenes, Licitaciones) |
| **LOTTT** — Ley Orgánica del Trabajo, Trabajadores y Trabajadoras | MINPPTRASS | Módulo de Nómina y RRHH |
| **SENIAT** — Normativa de Retenciones (IVA/ISLR) | SENIAT | Módulo de Retenciones e Impuestos |
| **LOCGRSNCF** — Ley Orgánica de la CGR | CGR | Pista de Auditoría (Transversal) |

---

## MÓDULO 1 — PRESUPUESTO

### 1.1 Estado Actual en SADI ✅ (¡100% Completado!)
El sistema ya implementa de forma integral la técnica presupuestaria por Proyectos y Acciones Centralizadas, cumpliendo con las normativas de ONAPRE y LOAFSP:

- **Estructura Presupuestaria Completa:** Proyectos (`proyecto`) y Acciones Centralizadas (`accion_centralizada`).
- **Plan Operativo Anual (POA):** Indicadores y Metas por Trimestre (eficacia, eficiencia, calidad, impacto) con sus medios de verificación y unidades de medida.
- **Fuentes de Financiamiento:** Vinculación de cada partida a su fuente de recursos (Ordinarios, Externos, Propios).
- **Programación Anual de Compras (PAC):** Documento maestro que vincula el presupuesto con futuras contrataciones.
- **Partidas del Plan Único de Cuentas** (`plan_unico_cuentas`).
- **Presupuesto de Gastos e Ingresos:** Seguimiento de los momentos presupuestarios.
- **Trazabilidad y Comprobantes:** Comprobantes tipificados (AAP, CG, CA, TR, PAG) y movimientos trazables.
- **Disponibilidad Presupuestaria Automática (Pre-compromiso):** Bloqueo automático (Art. 36 LOAFSP) al intentar generar compromisos sin crédito suficiente.
- **Unidad Ejecutora:** Presupuesto formulado y ejecutado por unidad administrativa responsable.

### 1.2 Brechas Identificadas ✅
- **Ninguna.** El módulo de Presupuesto ha sido completado al 100%.

---

## MÓDULO 2 — CONTABILIDAD PÚBLICA

### 2.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Plan Único de Cuentas con registro libre
- **Plan de Cuentas Patrimonial Oficial (ONCOP):** El `OncopSeeder` carga la estructura jerárquica oficial de 5 niveles.
- Comprobante Diario de Contabilidad con partida doble (Debe/Haber)
- **Estados Financieros Obligatorios:** Balance de Comprobación, Estado de Resultado, Balance General.
- Reporte: Mayor Analítico en PDF
- **Asientos contables automáticos:** Implementado para los diferentes momentos del gasto (Compromiso, Causado, Pago).
- **Cierre Contable del Ejercicio:** Implementado con su bloqueo respectivo.
- **Vinculación PUC Presupuestario ↔ Contable:** Tabla `vinculacion_puc_contable` con migración #0057 y CRUD completo.

### 2.2 Brechas Identificadas ✅
- **Ninguna.** Contabilidad ha sido completada al 100%.

---

## MÓDULO 3 — COMPRAS Y CONTRATACIONES PÚBLICAS

### 3.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Catálogo de Proveedores (incluye RNC y alertas de vencimiento - LCP).
- Catálogo de Artículos y Servicios.
- Requisiciones de Bienes y Servicios.
- Órdenes de Compra y Órdenes de Servicio.
- **Proceso de Contrataciones (LCP):** Modalidades implementadas en `proceso_contratacion` con ofertas de proveedores.
- Documentos (Facturas, Notas de Entrega).
- Cuentas por Pagar y Solicitudes de Pago.
- Programación Anual de Compras (PAC).

### 3.2 Brechas Identificadas ✅
- **Ninguna.** Las exigencias de la LCP han sido implementadas.

---

## MÓDULO 4 — NÓMINA Y RECURSOS HUMANOS

### 4.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Registro de Personal con **datos laborales completos** (RIF, Banco, Tipo de relación, etc.).
- Cargos y Fichas laborales.
- Motor de cálculo de nómina por conceptos (asignaciones y deducciones).
- **Cálculo de Retención de ISLR sobre sueldos** integrado.
- **Prestaciones Sociales (LOTTT):** Garantía trimestral y acumulación implementadas.
- **Vacaciones y Bono Vacacional (LOTTT):** Control de periodos y días de disfrute.
- **Utilidades anuales (LOTTT):** Cálculo y registro completado.
- Generación de Planillas históricas y Reportes ONAPRE.

### 4.2 Brechas Identificadas ✅
- **Ninguna.** Las normativas laborales de la LOTTT y el Decreto 1.808 han sido satisfechas.

---

## MÓDULO 5 — RETENCIONES E IMPUESTOS

### 5.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Registro de Facturas de Proveedores.
- **Tabla de Retenciones Paramétricas:** Implementación de `tipo_retencion` para IVA, ISLR y Retenciones Municipales.
- Emisión de Comprobantes de Retención oficiales con número de control (SENIAT).
- Reportes consolidados e integración con Cuentas por Pagar.

### 5.2 Brechas Identificadas ✅
- **Ninguna.** El módulo cubre los requerimientos básicos del SENIAT y retenciones municipales.

---

## MÓDULO 6 — TESORERÍA Y BANCOS

### 6.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Catálogo de Bancos y Cuentas Bancarias.
- Registro de Movimientos Bancarios (Depósito, Cheque, Transferencia, ND).
- Emisión de Cheques.
- **Conciliación Bancaria Formal:** Implementado con campos de verificación por movimiento.
- **Fondo de Avance / Anticipo:** Implementación formal según directrices ONCOP.
- Vinculación Pago → Movimiento Bancario.

### 6.2 Brechas Identificadas ✅
- **Ninguna.**

---

## MÓDULO 7 — INVENTARIO Y ALMACÉN (BIENES PÚBLICOS)

### 7.1 Estado Actual en SADI ✅ (¡100% Completado!)
- Catálogo de Artículos y clasificación presupuestaria.
- Inventario de Insumos (existencias, despachos, kardex).
- **Acta de Recepción Formal (Almacén):** Proceso oficial al recibir órdenes de compra.
- Inventario de Bienes Nacionales (activos fijos).
- **Traslado y Asignación de Bienes:** Gestión de responsables por bien.
- **Depreciación de Activos Fijos:** Cálculo de depreciación contable implementado.
- **Toma de Inventario Física:** Módulo de ajuste físico contra sistema.

### 7.2 Brechas Identificadas ✅
- **Ninguna.** 

---

## MÓDULO 8 — CONTROL INTERNO Y AUDITORÍA (TRANSVERSAL)

> Exigido por la **LOCGRSNCF** (Ley Orgánica de la Contraloría General de la República)

### 8.1 Estado Actual en SADI ✅ (¡100% Completado!)
- El campo `eliminado` implementa el borrado lógico (no físico) en todas las tablas.
- **Pista de Auditoría:** Implementada y 100% funcional en `auditoria_log` capturando datos previos/posteriores en JSON.
- **Roles y Permisos (RBAC):** Migración completa, permisos paramétricos por módulo, roles (Administrador, Analista, etc.).

- **Flujos de Aprobación:** Documentos críticos (Órdenes de Compra, Solicitudes de Pago, Nóminas) cuentan con un flujo formal y rastreable de firmas/aprobaciones jerárquicas antes de generar efectos financieros definitivos.

### 8.2 Brechas Identificadas ✅
- **Ninguna.** El módulo de Control Interno ha sido completado al 100%.

---

## HOJA DE RUTA DE DESARROLLO PRIORIZADA

```
PRIORIDAD CRÍTICA (Fundamentos legales)
├── [COMPLETADO] Pista de Auditoría (LOCGRSNCF)
├── [COMPLETADO] Roles y Permisos RBAC (LOCGRSNCF)
├── [COMPLETADO] Disponibilidad presupuestaria automática (LOAFSP)
├── [COMPLETADO] Campos RNC y vencimiento en proveedores (LCP)
└── [COMPLETADO] Retención ISLR sobre sueldos (Decreto 1.808)

PRIORIDAD ALTA (Completar módulos existentes)
├── [COMPLETADO] Comprobantes Contables Automáticos por momento del gasto (ONCOP/LOAFSP)
├── [COMPLETADO] Prestaciones Sociales (LOTTT)
├── [COMPLETADO] Vacaciones y Bono Vacacional (LOTTT)
├── [COMPLETADO] Utilidades anuales (LOTTT)
├── [COMPLETADO] Estados Financieros (Balance General, Estado de Resultado) (ONCOP)
├── [COMPLETADO] Tabla Única de Vinculación PUC-Contabilidad (ONCOP)
└── [COMPLETADO] Conciliación Bancaria formal (LOAFSP/ONCOP)

PRIORIDAD MEDIA (Nuevos módulos)
├── [COMPLETADO] Proceso de Contratación con Modalidades (LCP)
├── [COMPLETADO] Programación Anual de Compras — PAC (LCP)
├── [COMPLETADO] Parametrización Retenciones IVA/ISLR (SENIAT)
├── [COMPLETADO] Indicadores y Metas POA (ONAPRE)
├── [COMPLETADO] Fuentes de Financiamiento en Presupuesto (ONAPRE)
└── [COMPLETADO] Asignación y traslado de Bienes Patrimoniales (LOCGRSNCF)

PRIORIDAD BAJA (Mejoras y completitud)
├── [COMPLETADO] Depreciación de Activos Fijos
├── [COMPLETADO] Toma de Inventario Física
├── [COMPLETADO] Fondo en Avance/Anticipo (ONCOP)
├── [COMPLETADO] Cierre contable del ejercicio fiscal
└── [COMPLETADO] Expediente del Trabajador completo (LOTTT)

└── [COMPLETADO] Flujos de Aprobación Jerárquicos para documentos críticos (Compras, Pagos, Nómina).
```

---

## RESUMEN EJECUTIVO

| Módulo | % Alineación Actual | Estado |
|---|:---:|---|
| Presupuesto (Ejecución) | 100% | ✅ Completamente funcional |
| Contabilidad | 100% | ✅ Completamente funcional |
| Compras y Contrataciones | 100% | ✅ Completamente funcional |
| Nómina y RRHH | 100% | ✅ Completamente funcional |
| Retenciones | 100% | ✅ Completamente funcional |
| Tesorería / Bancos | 100% | ✅ Completamente funcional |
| Inventario / Almacén | 100% | ✅ Completamente funcional |
| **Control Interno** | **100%** | ✅ Completamente funcional |

> [!SUCCESS]
> **Hito Alcanzado:** La base técnica y de modelos de datos para el cumplimiento legal del sistema SADI en las áreas de Presupuesto, Contabilidad, Tesorería, RRHH, Bienes, Compras y Control Interno ha sido **completada exitosamente**. El sistema SADI cubre el 100% del marco legal venezolano para su implantación funcional.
