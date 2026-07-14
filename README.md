# SADI — Sistema Administrativo Integrado

> Migración modernizada del sistema **SIGAFS** (Sistema Integrado de Gestión Administrativa y Financiera del Sector Público) hacia una arquitectura PHP moderna, limpia y mantenible.

---

## 📋 Descripción del Sistema

**SADI** es un sistema de gestión administrativa y financiera para organismos del sector público. Cubre los procesos de nómina de personal, contabilidad, presupuesto, compras y almacén, bancos, y cuentas por pagar, todo integrado bajo una única interfaz web.

El sistema es una reescritura desde cero de SIGAFS, conservando la fidelidad funcional del sistema original pero adoptando estándares modernos de PHP (PSR-1, PSR-4, PSR-12), arquitectura MVC estricta con separación de capas, y una base de datos que apunta a **PostgreSQL** en producción con soporte de desarrollo rápido sobre **SQLite**.

---

## ⚙️ Requisitos del Sistema

### Servidor

| Requisito        | Versión mínima        |
|------------------|-----------------------|
| PHP              | **8.4** o superior    |
| Servidor web     | Apache 2.4 / Nginx    |
| Base de datos    | PostgreSQL 12+         |
| Extensiones PHP  | `pdo`, `pdo_pgsql`, `pdo_sqlite`, `mbstring` |

### Desarrollo local

| Herramienta        | Descripción                                 |
|--------------------|---------------------------------------------|
| PHP 8.4+ (CLI)     | Para ejecutar el CLI y herramientas         |
| Podman / Docker    | Orquestación de servicios locales (BD, Web) |
| Composer           | Gestión de dependencias PHP                 |
| php-cs-fixer       | Corrección de estilo PSR-12 (dev)           |

---

## 🗂️ Estructura del Proyecto

```
sadi/
├── public/
│   └── index.php              # Punto de entrada único / Router automático
├── src/
│   ├── Controllers/           # Lógica de negocio y flujo de pantallas
│   ├── Models/                # Entidades / DTOs del dominio
│   ├── Repositories/          # Acceso a datos (extienden Repository base)
│   ├── Services/              # Servicios reutilizables (PDF, fórmulas, etc.)
│   ├── Libs/                  # Librerías de terceros (fpdf.php, etc.)
│   ├── Core/                  # Núcleo del framework (autoloader, etc.)
│   └── Database/              # Clase base Repository + gestión de conexión PDO
├── views/
│   ├── layouts/
│   │   └── main.phtml         # Layout principal de la aplicación
│   └── [modulo]/              # Vistas de cada módulo (archivos .phtml)
├── database/
│   ├── schema.sql             # Schema completo (compatible SQLite / PostgreSQL)
│   ├── sadi.sqlite            # Base de datos de desarrollo
│   └── seed.php               # Script de datos iniciales
├── .agents/
│   └── workflows/
│       └── reglas_proyecto.md # Convenciones y reglas del proyecto (para IA/equipo)
├── composer.json
└── .php-cs-fixer.dist.php     # Configuración de estilo de código
```

### Router automático

El router convierte `?route=nombre_modulo/accion` en una llamada directa al controlador:

- `?route=apertura_cuentas/index` → `AperturaCuentasController::index()`
- `?route=comprobante_presupuesto/guardar` → `ComprobantePresupuestoController::guardar()`

**No existe un archivo de rutas explícito** — el mapeo es 100% por convención de nombres.

---

## 📦 Módulos Disponibles

### 👥 Personal y Nómina

| Módulo | Descripción |
|--------|-------------|
| **Nómina** | Generación de nóminas con motor de fórmulas dinámicas |
| **Conceptos de Nómina** | Configuración de asignaciones y deducciones |
| **Retenciones** | Gestión de retenciones ISLR y similares |
| **Beneficiarios** | Registro de beneficiarios de pagos |

### 🏦 Operaciones Bancarias

| Módulo | Descripción |
|--------|-------------|
| **Bancos** | Registro de operaciones bancarias |
| **Cuentas Bancarias** | Gestión de cuentas de la institución |
| **Cheques** | Emisión y control de cheques |
| **Caja** | Control de ingresos y egresos de caja chica |
| **Conversiones** | Convertidor de divisas |

### 📒 Contabilidad

| Módulo | Descripción |
|--------|-------------|
| **Plan Único de Cuentas (PUC)** | Gestión del catálogo contable |
| **Apertura de Cuentas** | Proceso de apertura de cuentas presupuestarias |
| **Mayor Analítico** | Reporte de movimientos contables por cuenta (PDF) |
| **Documental** | Registro de documentos contables (comprobantes) |

### 💰 Presupuesto

| Módulo | Descripción |
|--------|-------------|
| **Presupuesto de Gasto** | Administración del presupuesto de gasto institucional |
| **Presupuesto de Ingreso** | Administración del presupuesto de ingreso |
| **Comprobante de Presupuesto** | Registro de comprobantes de gasto / créditos adicionales / traspasos |
| **Disponibilidad Presupuestaria** | Consulta de saldo disponible por cuenta |
| **Ajustes / Reformulación** | Registro de ajustes a la reformulación presupuestaria |
| **Períodos de Presupuesto** | Gestión del cierre y apertura de períodos |
| **Tipos de Operación de Presupuesto** | Catálogo de tipos de movimiento presupuestario |
| **Proyectos** | Catálogo de proyectos presupuestarios |
| **Acciones Centralizadas** | Catálogo de acciones centralizadas |
| **Estructura Presupuestaria** | Gestión de la estructura de clasificación presupuestaria |

### 🛒 Compras y Almacén

| Módulo | Descripción |
|--------|-------------|
| **Órdenes de Compra** | Generación y seguimiento de órdenes de compra |
| **Órdenes de Servicio** | Gestión de órdenes de servicio |
| **Requisiciones de Bienes** | Solicitudes de bienes internos |
| **Requisiciones de Servicios** | Solicitudes de servicios internos |
| **Inventario** | Control de inventario y existencias |
| **Artículos** | Catálogo de bienes y materiales |
| **Proveedores** | Registro y gestión de proveedores |
| **Tipos de Artículos** | Clasificación de artículos |
| **Unidades de Medida** | Catálogo de unidades de medida |

### 💳 Cuentas por Pagar

| Módulo | Descripción |
|--------|-------------|
| **Cuentas por Pagar** | Gestión de compromisos de pago pendientes |
| **Documentos por Pagar** | Registro de documentos origen del compromiso |
| **Solicitudes de Pago** | Generación de órdenes de pago |
| **Deducciones CxP** | Aplicación de deducciones a documentos por pagar |
| **Reportes CxP** | Reportes de cuentas por pagar (PDF) |

### 🔧 Administración / Configuración

| Módulo | Descripción |
|--------|-------------|
| **Administrador** | Panel de administración del sistema |
| **Unidades Administrativas** | Catálogo de unidades organizativas |
| **Servicios / Tipos de Servicios** | Catálogo de servicios |
| **Tipo de Documentos** | Clasificación de tipos de documentos |
| **Tipos de Operaciones Bancarias** | Catálogo de operaciones del módulo bancario |
| **Reportes Generales** | Reportes transversales del sistema |

---

## 🚀 Cómo Desarrollar en el Proyecto

### 1. Clonar e instalar dependencias

```bash
git clone <repositorio>
cd sadi
composer install
```

### 2. Preparar la base de datos de desarrollo

El proyecto utiliza PostgreSQL. Asegúrate de tener el motor corriendo (puedes usar Podman/Docker con `compose.yaml`) y luego usa el CLI integrado para inicializar la estructura y los catálogos maestros:

```bash
php cli/sadi db:migrate
php cli/sadi db:seed
```

### 3. Iniciar el servidor de desarrollo

Te recomendamos levantar el proyecto usando Podman o Docker. Así tendrás Nginx, PHP-FPM y PostgreSQL configurados con un solo comando:

```bash
podman compose up --build -d
```

Acceder en el navegador: [http://localhost:8000](http://localhost:8000)

### 4. Convenciones de código

El proyecto sigue **PSR-1 / PSR-4 / PSR-12** de forma estricta:

| Regla | Detalle |
|-------|---------|
| `declare(strict_types=1)` | Obligatorio en **todos** los archivos PHP |
| Tipado explícito | Parámetros y tipos de retorno siempre declarados |
| Namespace | `App\Controllers`, `App\Models`, `App\Repositories`, `App\Services` |
| Clases | PascalCase |
| Métodos | camelCase |
| Tablas / columnas BD | snake_case |
| Rutas URL | `?route=nombre_modulo/accion` (snake_case) |

Verificar estilo de código:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/php-cs-fixer fix
```

Verificar sintaxis PHP:

```bash
php -l src/Controllers/MiController.php
```

### 5. Añadir un nuevo módulo

Para garantizar la estandarización de DTOs y Arquitectura Limpia, **utiliza el CLI de SADI** para generar módulos. No crees los archivos manualmente.

```bash
php cli/sadi make:section MiEntidad
```

Esto generará automáticamente:

1. `src/Models/MiEntidad.php` (Readonly DTO)
2. `src/Repositories/MiEntidadRepository.php`
3. `src/Controllers/MiEntidadController.php`
4. `database/migrations/mi_entidads.sql`
5. Vistas CRUD en `views/mi_entidad/`

**[Ver Manual Técnico del CLI](docs/MANUAL_TECNICO_CLI.md)** para más detalles y comandos avanzados.

El módulo estará disponible automáticamente en:
`http://localhost:8000/?route=mi_entidad/index`

### 6. Arquitectura y Guías de Desarrollo

El proyecto utiliza un patrón Repositorio estricto con DTOs inmutables, evitando ORMs activos. Para entender cómo interactuar con la base de datos, revisa estas guías obligatorias:

- 📖 **[Tutorial: Creación de un Módulo CRUD desde Cero](docs/TUTORIAL_CREAR_MODULO.md)**: Guía paso a paso desde el scaffolding hasta los tests de Pest.
- 📖 **[Manejo de Relaciones entre Modelos](docs/MANEJO_DE_RELACIONES.md)**: Cómo hacer JOINs y evitar el problema N+1 sin usar Lazy Loading.
- 📖 **[Operaciones CRUD y Queries SQL](docs/CRUD_Y_QUERIES.md)**: Uso obligatorio de PDO Prepared Statements y convenciones de Inserción, Soft Deletes y Búsquedas.

### 7. Generar reportes PDF

Se usa **FPDF** a través de `App\Services\PdfService`. Recordar limpiar output buffers antes de enviar:

```php
while (ob_get_level() > 0) {
    ob_end_clean();
}
$pdf->Output('I', 'reporte.pdf');
exit;
```

---

## 🗄️ Base de Datos

| Entorno      | Motor       | Archivo / Conexión            |
|--------------|-------------|-------------------------------|
| Desarrollo   | PostgreSQL  | `.env` y Podman/Docker        |
| Producción   | PostgreSQL  | Variable de entorno / config  |
| Pruebas      | PostgreSQL  | `.env.testing` (`sadi_test`)  |

El esquema maestro se encuentra en `database/schema.sql` y las migraciones generadas por el CLI van a `database/migrations/`.

---

## 📜 Estándares y Herramientas

| Herramienta | Propósito |
|-------------|-----------|
| [Composer](https://getcomposer.org/) | Gestión de dependencias y autoloading PSR-4 |
| [PHP CS Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) | Formateo automático PSR-12 |
| [FPDF](http://www.fpdf.org/) | Generación de reportes en PDF |
| PostgreSQL | Motor de Base de Datos para Pruebas, Desarrollo y Producción |

---

## 📁 Historial de Migración desde SIGAFS

| Fase | Módulos | Estado |
|------|---------|--------|
| Fase 1–4 | Personal, Nómina, Contabilidad, Almacén, Bancos | ✅ Completado |
| Fase 5.1 | Catálogos de Presupuesto (Proyectos, Acciones Centralizadas, PUC) | ✅ Completado |
| Fase 5.2 | Procesos Presupuestarios (Apertura, CG/CA/TR, Períodos, Disponibilidad, Reformulación) | ✅ Completado |
| Fase 5.3 | Reportes — Mayor Analítico PDF | ✅ Completado |

---

## 🐳 Despliegue Local (Docker / Podman)

El proyecto cuenta con una configuración completa en contenedores para levantar el ecosistema (`PostgreSQL`, `PHP-FPM`, `Nginx`) con un solo comando de forma nativa.

### Cómo ejecutarlo
1. Asegúrate de tener instalado `podman-compose` o `docker-compose`.
2. Ejecuta en la raíz del proyecto:
   ```bash
   podman compose up --build -d
   ```

### Accesos Rápidos
- **Aplicación Web:** Abre en tu navegador [http://localhost:8000](http://localhost:8000)
- **Base de Datos directa:** Usa un cliente SQL apuntando a `127.0.0.1` en el puerto `5433` (User: `sadi`, BD: `sadi_db`, Pass: `sadi`).

### Credenciales de Prueba
El sistema incluye datos iniciales generados en el esquema para que inicies sesión rápidamente:
- **Usuario:** `ADMINISTRADOR`
- **Contraseña:** `123456`
