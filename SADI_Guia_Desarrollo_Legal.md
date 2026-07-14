# SADI — Guía de Desarrollo Legal y Funcional
## Análisis de Alineación con el Marco Jurídico de la Administración Pública Venezolana

> **Versión:** 1.0 | **Fecha:** Julio 2026  
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

### 1.2 Brechas Identificadas 🔴
- **Ninguna.** El módulo de Presupuesto ha sido completado al 100%.

---

## MÓDULO 2 — CONTABILIDAD PÚBLICA

### 2.1 Estado Actual en SADI ✅
- Plan Único de Cuentas con registro libre
- Comprobante Diario de Contabilidad con partida doble (Debe/Haber)
- Reporte: Mayor Analítico en PDF
- Asiento contable automático al pagar una Nómina

### 2.2 Brechas Identificadas 🔴

**Crítico — Requerido por ONCOP:**

1. **Plan de Cuentas Patrimoniales Oficial (ONCOP):** La ONCOP publica el **Plan de Cuentas Patrimoniales oficial** que todos los entes deben usar. El plan actual en SADI es un ejemplo libre. Se debe cargar la estructura oficial de ONCOP.
   - El código de cuenta en la tabla `cuenta_contable` debe seguir la estructura oficial (ej: `1.1.01.01 = Efectivo en caja`).

2. **Tabla Única de Vinculación (ONCOP-ONAPRE):** El sistema debe vincular el clasificador presupuestario (PUC de ONAPRE) con el Plan de Cuentas Patrimoniales (de ONCOP). Actualmente son entidades separadas sin vínculo formal.
   - _Tabla a crear:_ `vinculacion_puc_contable (id_plan_unico_cuentas, id_cuenta_contable)`.

3. **Estados Financieros Obligatorios:** La normativa contable venezolana exige la generación de estados financieros básicos. Actualmente solo existe el Mayor Analítico.
   - _Reportes a implementar:_
     - **Balance de Comprobación** (saldos al Debe y Haber por cuenta)
     - **Estado de Resultado** (Ingresos vs. Egresos del período)
     - **Estado de la Situación Financiera / Balance General** (Activos, Pasivos y Patrimonio)

4. **Cierre Contable del Ejercicio:** No existe un proceso formal de cierre del ejercicio fiscal contable (regularización de cuentas de resultado a patrimonio).

5. **Comprobantes Automáticos por Proceso:** La LOAFSP y las instrucciones de ONCOP establecen que cada momento del gasto (Compromiso, Causación, Pago) debe generar un asiento contable automático. Actualmente el asiento se hace de forma manual.

---

## MÓDULO 3 — COMPRAS Y CONTRATACIONES PÚBLICAS

### 3.1 Estado Actual en SADI ✅
- Catálogo de Proveedores (con RIF, tipo de organización)
- Catálogo de Artículos y Servicios
- Requisiciones de Bienes y Servicios
- Órdenes de Compra y Órdenes de Servicio
- Documentos (Facturas, Notas de Entrega)
- Cuentas por Pagar
- Solicitudes de Pago

### 3.2 Brechas Identificadas 🔴

**Crítico — Requerido por la Ley de Contrataciones Públicas (LCP):**

1. **Modalidades de Contratación (LCP, Art. 55+):** La ley establece 4 modalidades según el monto (expresado en UCAU). El sistema no implementa ninguna. Un sistema alineado debe permitir gestionar el **expediente** de una contratación con su modalidad:
   - `CONSULTA_DE_PRECIOS`: Mínimo 3 cotizaciones
   - `CONCURSO_CERRADO`: Mínimo 5 invitados
   - `CONCURSO_ABIERTO`: Publicación y licitación pública
   - `CONTRATACION_DIRECTA`: Con justificación legal
   - _Tabla a crear:_ `proceso_contratacion`, `oferta_proveedor`, `evaluacion_oferta`

2. **Registro Nacional de Contratistas (RNC):** El número de certificado RNC del proveedor y su fecha de vencimiento deben ser campos obligatorios en la tabla `proveedor`. El sistema debe alertar cuando un proveedor tiene el RNC vencido.
   - _Campos a agregar:_ `numero_rnc`, `fecha_vencimiento_rnc` a la tabla `proveedor`.

3. **Expediente de Contratación Unificado:** La LCP exige la unidad del expediente. Debe existir un vínculo claro entre: `Proceso de Contratación → Oferta Ganadora → Orden de Compra/Servicio → Factura → Pago`.

4. **Compromiso de Responsabilidad Social (CRS):** Es un requisito obligatorio para contrataciones sobre ciertos montos. El sistema no tiene ningún campo para registrarlo.

5. **Programación Anual de Compras (PAC):** Ver Módulo 1 punto 3.

---

## MÓDULO 4 — NÓMINA Y RECURSOS HUMANOS

### 4.1 Estado Actual en SADI ✅
- Registro de Personal con datos básicos (cédula, nombre, fecha nacimiento)
- Cargos y Fichas laborales (sueldo básico, cargo, tipo nómina, fecha ingreso)
- Motor de cálculo de nómina por conceptos (asignaciones y deducciones configúrales)
- Deducciones de ley: SSO (4%) y FAOV (1%)
- Generación de Planillas históricas y Solicitudes de Pago automáticas
- Reportes ONAPRE (Formato de Nómina)

### 4.2 Brechas Identificadas 🔴

**Crítico — Requerido por la LOTTT:**

1. **Prestaciones Sociales (LOTTT, Art. 142):** Es el beneficio más importante y complejo de la legislación laboral venezolana. El sistema **no tiene ninguna implementación**. Requiere:
   - Cálculo de las prestaciones sobre **Salario Integral** (no salario básico)
   - Garantía mínima de 15 días de salario por cada trimestre de servicio
   - Acumulación histórica por trabajador
   - _Tablas a crear:_ `prestacion_social`, `garantia_prestaciones_trimestral`

2. **Vacaciones y Bono Vacacional (LOTTT, Art. 190):**
   - Vacaciones: 15 días hábiles + 1 día por año adicional (hasta 30)
   - Bono Vacacional: 15 días + 1 día/año (hasta 30)
   - El sistema no calcula ni registra vacaciones
   - _Tablas a crear:_ `vacacion`, `periodo_vacacional`

3. **Utilidades / Bonificación de Fin de Año (LOTTT, Art. 131):**
   - Mínimo 30 días de salario, máximo 120 días
   - El sistema no calcula utilidades
   - _Campo/Tabla a crear:_ `utilidades_trabajador`

4. **Salario Integral vs. Salario Normal:** El sistema solo maneja `sueldo_basico`. La LOTTT requiere distinguir entre:
   - **Salario Normal** (base para vacaciones/bono vacacional)
   - **Salario Integral** (incluye alícuotas de utilidades y bono vacacional, base para prestaciones)
   - Esta distinción es fundamental para calcular correctamente los pasivos laborales.

5. **Datos del Expediente del Trabajador (Incompletos):** La tabla `personal` solo tiene datos mínimos. Para un sistema de RRHH completo se requiere:
   - RIF del trabajador
   - Tipo de relación laboral (fijo, contratado, obrero)
   - Nivel de instrucción
   - Banco y cuenta para el pago de nómina
   - Estado civil y cargas familiares (para cálculo de ISLR sobre sueldos)
   - Historial de cargos

6. **Retención de ISLR sobre Sueldos (Decreto 1.808):** La retención del ISLR a empleados no está implementada. Es una obligación del patrono calcularla y retenerla sobre el salario anual proyectado.

---

## MÓDULO 5 — RETENCIONES E IMPUESTOS

### 5.1 Estado Actual en SADI ✅
- Registro de Facturas de Proveedores
- Emisión de Comprobantes de Retención (IVA e ISLR)
- Registro del porcentaje y monto retenido por comprobante

### 5.2 Brechas Identificadas 🔴

1. **Tabla de Porcentajes de Retención Configurable:** Los porcentajes del IVA (75% o 100%) y del ISLR varían según el tipo de actividad económica y si el proveedor es contribuyente ordinario o especial. Esto debe ser configurable y no estar "quemado" en el código.
   - _Tabla a crear:_ `tipo_retencion (id, nombre, tipo_impuesto, porcentaje_base, sustraendo)`

2. **Número de RIF en la retención:** El comprobante de retención oficial emitido por el SENIAT debe incluir el RIF del agente de retención (el ente público), el RIF del proveedor, el número de la factura y el número de comprobante con formato estándar (AAAAMMXXXXXXXX). El campo `numero_comprobante` en `comprobante_retencion` debe seguir este formato.

3. **Libro de Retenciones IVA / ISLR:** El sistema debe ser capaz de generar el libro de retenciones mensuales (relación de facturas y retenciones practicadas) para su declaración ante el SENIAT.
   - _Reporte a implementar:_ Libro de Retenciones IVA (mensual), Relación de Retenciones ISLR.

4. **Retención de Municipio (1x1000 y similares):** Muchos entes públicos están sujetos a retenciones municipales adicionales. El campo `tipo_retencion TEXT` en `comprobante_retencion` ya contempla esta posibilidad, pero no hay configuración paramétrica del ente territorial.

---

## MÓDULO 6 — TESORERÍA Y BANCOS

### 6.1 Estado Actual en SADI ✅
- Catálogo de Bancos y Cuentas Bancarias
- Registro de Movimientos Bancarios (Depósito, Cheque, Transferencia, ND)
- Módulo de Caja Chica
- Emisión de Cheques
- Conciliación básica

### 6.2 Brechas Identificadas 🔴

1. **Conciliación Bancaria Formal:** El módulo actual no permite marcar movimientos como "conciliados" vs. "no conciliados". La conciliación bancaria es un proceso fundamental del control interno exigido por la CGR.
   - _Campo a agregar:_ `conciliado BOOLEAN, fecha_conciliacion DATE` en `movimiento_bancario`.
   - _Módulo:_ Proceso de conciliación mensual que genere el informe con las diferencias (partidas en tránsito, cheques en circulación, notas sin registrar).

2. **Fondo de Avance / Fondo en Anticipo:** La LOAFSP y los manuales de ONCOP establecen procedimientos específicos para los Fondos en Avance. La "Caja Chica" existente es una aproximación, pero falta: justificación del monto máximo, reposición formal, y cierre con comprobante contable automático.

3. **Vinculación Pago → Movimiento Bancario:** Cuando se registra un pago (Solicitud de Pago aprobada), debe existir una vinculación directa y automática al movimiento bancario generado (el cheque o la transferencia), no manejarse como entidades separadas.

---

## MÓDULO 7 — INVENTARIO Y ALMACÉN (BIENES PÚBLICOS)

### 7.1 Estado Actual en SADI ✅
- Catálogo de Artículos (Bienes e Insumos)
- Inventario de Insumos (con existencias)
- Inventario de Bienes (activos fijos con código único)
- Recepción de almacén vinculada a Órdenes de Compra

### 7.2 Brechas Identificadas 🔴

1. **Clasificación Presupuestaria de Bienes:** Todo artículo debe estar vinculado a una partida del PUC de ONAPRE (ya existe el campo `id_codigo_plan_unico` en `articulo`) y se debe validar que las salidas de almacén generan los movimientos contables correspondientes.

2. **Acta de Recepción Formal (Almacén):** El proceso de recepción de bienes debe generar un **Acta de Recepción** formal que certifica la conformidad del bien recibido vs. lo especificado en la Orden de Compra. Este documento es exigido por los entes de control.

3. **Traslado y Asignación de Bienes:** Los bienes patrimoniales (activos fijos) deben poder ser asignados a una unidad administrativa o funcionario específico, con registro del Acta de Asignación.
   - _Tabla a crear:_ `asignacion_bien (id_inventario_bienes, id_unidad_administrativa, cedula_responsable, fecha_asignacion)`

4. **Depreciación de Activos Fijos:** Los activos patrimoniales están sujetos a depreciación contable. El sistema no calcula ni registra la depreciación.

5. **Toma de Inventario Física:** Se debe poder registrar una toma de inventario física periódica y comparar contra el inventario del sistema, generando las diferencias.

---

## MÓDULO 8 — CONTROL INTERNO Y AUDITORÍA (TRANSVERSAL)

> Exigido por la **LOCGRSNCF** (Ley Orgánica de la Contraloría General de la República)

### 8.1 Estado Actual en SADI ✅
- El campo `eliminado` implementa el borrado lógico (no físico) en todas las tablas.

### 8.2 Brechas Críticas 🔴

1. **Pista de Auditoría (Audit Trail):** La LOCGRSNCF exige que los sistemas de información de la Administración Pública registren quién hizo qué y cuándo. **SADI no tiene ningún mecanismo de auditoría.** Se requiere:
   - _Tabla a crear:_ `auditoria_log (id, tabla, accion, id_registro, datos_antes, datos_despues, id_usuario, fecha_hora)`
   - Todos los `INSERT`, `UPDATE` y `DELETE` críticos deben registrarse en este log.

2. **Roles y Permisos (RBAC):** El sistema solo tiene un nivel de usuario (autenticado/no autenticado). Un sistema para la administración pública debe implementar roles y permisos:
   - Ejemplos de roles: `Administrador`, `Analista de Presupuesto`, `Tesorero`, `Recursos Humanos`, `Director` (solo lectura y aprobación).
   - _Tablas a crear:_ `rol`, `permiso`, `usuario_rol`, `rol_permiso`.

3. **Flujos de Aprobación:** Documentos críticos (Órdenes de Compra, Solicitudes de Pago, Nóminas) deben pasar por un flujo de aprobación antes de generar efectos financieros. El campo `estado` en `comprobante_presupuestario` es un inicio, pero no hay un flujo formal.

4. **Número Correlativo Oficial:** Todos los documentos que generen efectos contables o presupuestarios deben tener un número correlativo único, irrepetible y continuo por año fiscal.

---

## HOJA DE RUTA DE DESARROLLO PRIORIZADA

```
PRIORIDAD CRÍTICA (Fundamentos legales)
├── [P1] Pista de Auditoría (LOCGRSNCF)
├── [P1] Roles y Permisos RBAC (LOCGRSNCF)
├── [COMPLETADO] Disponibilidad presupuestaria automática (LOAFSP)
├── [P1] Campos RNC y vencimiento en proveedores (LCP)
└── [P1] Retención ISLR sobre sueldos (Decreto 1.808)

PRIORIDAD ALTA (Completar módulos existentes)
├── [P2] Prestaciones Sociales (LOTTT)
├── [P2] Vacaciones y Bono Vacacional (LOTTT)
├── [P2] Utilidades anuales (LOTTT)
├── [P2] Estados Financieros (Balance General, Estado de Resultado) (ONCOP)
├── [P2] Tabla Única de Vinculación PUC-Contabilidad (ONCOP)
└── [P2] Conciliación Bancaria formal (LOAFSP/ONCOP)

PRIORIDAD MEDIA (Nuevos módulos)
├── [P3] Proceso de Contratación con Modalidades (LCP)
├── [COMPLETADO] Programación Anual de Compras — PAC (LCP)
├── [P3] Libro de Retenciones IVA/ISLR (SENIAT)
├── [COMPLETADO] Indicadores y Metas POA (ONAPRE)
├── [COMPLETADO] Fuentes de Financiamiento en Presupuesto (ONAPRE)
└── [P3] Asignación y traslado de Bienes Patrimoniales (LOCGRSNCF)

PRIORIDAD BAJA (Mejoras y completitud)
├── [P4] Depreciación de Activos Fijos
├── [P4] Toma de Inventario Física
├── [P4] Fondo en Avance/Anticipo (ONCOP)
├── [P4] Cierre contable del ejercicio fiscal
└── [P4] Expediente del Trabajador completo (LOTTT)
```

---

## RESUMEN EJECUTIVO

| Módulo | % Alineación Actual | Estado |
|---|:---:|---|
| Presupuesto (Ejecución) | 100% | ✅ Módulo completamente alineado y funcional |
| Contabilidad | 40% | ⚠️ Falta Plan ONCOP oficial y estados financieros |
| Compras y Contrataciones | 35% | 🔴 Falta todo el proceso de licitación (LCP) |
| Nómina y RRHH | 45% | 🔴 Faltan prestaciones, vacaciones y utilidades |
| Retenciones | 55% | ⚠️ Base presente, falta parametrización y reportes |
| Tesorería / Bancos | 50% | ⚠️ Falta conciliación formal |
| Inventario / Almacén | 60% | ⚠️ Falta asignación de bienes y depreciación |
| **Control Interno** | **5%** | 🔴 **CRÍTICO: No existe pista de auditoría ni RBAC** |

> [!IMPORTANT]
> La implementación de la **Pista de Auditoría** y el sistema de **Roles y Permisos** son transversales a todo el sistema y deben desarrollarse **antes** que cualquier otro módulo nuevo, ya que son requisitos de la LOCGRSNCF para que el sistema pueda considerarse legalmente utilizable por la Administración Pública.
