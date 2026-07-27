# SADI — Sistema Administrativo Integrado

> Migración modernizada del sistema **SIGAFS** (Sistema Integrado de Gestión Administrativa y Financiera del Sector Público) hacia una arquitectura PHP moderna, limpia y mantenible.

---

## 📋 Descripción del Sistema

**SADI** es un sistema de gestión administrativa y financiera para organismos del sector público. Cubre los procesos de nómina de personal, contabilidad, presupuesto, compras y almacén, bancos, y cuentas por pagar, todo integrado bajo una única interfaz web.

El sistema es una reescritura desde cero de SIGAFS, conservando la fidelidad funcional del sistema original pero adoptando estándares modernos de PHP (PSR-1, PSR-4, PSR-12), arquitectura MVC estricta con separación de capas, y una base de datos soportada en **PostgreSQL**.

---

## ⚙️ Requisitos Previos

| Herramienta        | Versión mínima        | Descripción                                 |
|--------------------|-----------------------|---------------------------------------------|
| PHP / CLI          | **8.4** o superior    | Para ejecutar el CLI y servidor web         |
| Base de datos      | PostgreSQL 12+         | Motor de base de datos relacional           |
| Extensiones PHP    | `pdo`, `pdo_pgsql`, `mbstring` | Extensiones requeridas para el funcionamiento|
| Podman / Docker    | Recomendado           | Para orquestación de servicios locales      |
| Composer           | Última versión        | Gestión de dependencias PHP                 |

---

## 🚀 Instalación y Despliegue Local

El proyecto cuenta con una configuración completa en contenedores para levantar el ecosistema completo (`PostgreSQL`, `PHP-FPM`, `Nginx`) de forma fácil.

### 1. Clonar e instalar dependencias

```bash
git clone <repositorio>
cd sadi
composer install
```

### 2. Iniciar los contenedores (Recomendado)

Asegúrate de tener instalado `podman-compose` o `docker-compose` y ejecuta en la raíz del proyecto:

```bash
podman compose up --build -d
```
Esto levantará el servidor web en el puerto `8000` y la base de datos en el puerto `5433`.

### 3. Preparar la base de datos

Usa el CLI integrado de SADI para inicializar la estructura y los catálogos maestros:

```bash
php cli/sadi db:migrate
php cli/sadi db:seed
```

### 4. Accesos Rápidos
- **Aplicación Web:** Abre en tu navegador [http://localhost:8000](http://localhost:8000)
- **Base de Datos directa:** Usa un cliente SQL apuntando a `127.0.0.1` en el puerto `5433` (User: `sadi`, BD: `sadi_db`, Pass: `sadi`).

---

## 🔐 Acceso al Sistema y Permisos (RBAC)

El sistema cuenta con Control de Acceso Basado en Roles (RBAC). Al ejecutar los comandos de migración y los *seeders* (`db:seed`), se configuran automáticamente:

- **180 permisos** granulares agrupados por módulo.
- **23 roles** predefinidos (Director, Coordinador, Analista por módulo, etc.).

### Credenciales Principales
- **Usuario de Acceso Total (Root):** `ADMINISTRADOR`
- **Contraseña:** `Admin2026!`

### Seguridad de Contraseñas (Bcrypt + Pepper)
Todas las contraseñas están protegidas utilizando **Bcrypt** puro. Para agregar una capa de seguridad criptográfica adicional contra ataques de fuerza bruta o diccionarios en caso de filtración de la base de datos, el sistema implementa un *pepper* (sal estática) que se añade automáticamente antes del hashing.

Para configurar esta llave, define la variable `PASSWORD_SALT` en tu archivo `.env`:
```env
PASSWORD_SALT=tu_llave_secreta_aqui
```

> ⚠️ **¡ADVERTENCIA CRÍTICA PARA PRODUCCIÓN!**
> Si cambias el valor de la variable `PASSWORD_SALT` en el futuro, **TODAS** las contraseñas antiguas que se hayan guardado usando el salt anterior dejarán de funcionar de manera irreversible. Asegúrate de establecer un valor fuerte desde el principio y **mantenerlo fijo y respaldado** una vez que el sistema esté en producción.

### Datos Falsos de Prueba (Faker)
Si deseas poblar toda la base de datos con información ficticia (Proveedores, Proyectos, Presupuestos, Usuarios, etc.) para realizar pruebas en desarrollo, puedes ejecutar el **MockDatabaseSeeder**. 

Para limpiar la base de datos y levantarla desde cero con todos los datos falsos y los catálogos requeridos, ejecuta el siguiente comando:
```bash
php cli/sadi db:migrate --fresh --seed --class=MockDatabaseSeeder
```
*(Nota: Este comando generará usuarios aleatorios con roles aleatorios. La contraseña base para todos los usuarios generados por Faker es `Test2026!`)*

---

## 📦 Módulos Disponibles

### 👥 Personal y Nómina
- **Nómina:** Generación de nóminas con motor de fórmulas dinámicas
- **Conceptos de Nómina:** Configuración de asignaciones y deducciones
- **Retenciones:** Gestión de retenciones ISLR y similares
- **Beneficiarios:** Registro de beneficiarios de pagos

### 🏦 Operaciones Bancarias
- **Bancos:** Registro de operaciones bancarias
- **Cuentas Bancarias:** Gestión de cuentas de la institución
- **Cheques & Caja:** Emisión de cheques y control de caja chica
- **Conversiones:** Convertidor de divisas

### 📒 Contabilidad
- **Plan Único de Cuentas (PUC):** Gestión del catálogo contable
- **Mayor Analítico:** Reporte de movimientos contables por cuenta (PDF)
- **Documental & Apertura:** Registro de documentos y apertura de cuentas

### 💰 Presupuesto
- **Presupuesto (Gasto/Ingreso):** Administración del presupuesto institucional
- **Comprobantes:** Registro de comprobantes de gasto / créditos / traspasos
- **Disponibilidad:** Consulta de saldo disponible por cuenta
- **Catálogos:** Proyectos, Acciones Centralizadas, Estructura Presupuestaria

### 🛒 Compras y Almacén
- **Órdenes & Requisiciones:** Seguimiento de compras y servicios internos
- **Inventario:** Control de inventario y existencias
- **Catálogos:** Artículos, Proveedores, Unidades de Medida

### 💳 Cuentas por Pagar
- **Cuentas por Pagar:** Gestión de compromisos de pago pendientes
- **Solicitudes de Pago:** Generación de órdenes de pago y deducciones

### 🔧 Administración y Control Interno
- **Administrador:** Panel central para gestionar usuarios y matriz de roles/permisos.
- **Auditoría Global:** Pista de auditoría detallada de creación, modificación y eliminación (borrado lógico) de registros.
- **Flujos de Aprobación:** Motor de estados dinámico (`ELABORACION`, `REVISION`, `PRE-APROBADO`, `APROBADO`) para documentos críticos, integrado con bloqueo financiero.
- **Configuración Institucional:** Parametrización de la institución, membretes, logos y firmas dinámicas para reportes PDF.

---

## 🛠️ Guía de Desarrollo

### Estructura del Proyecto

```
sadi/
├── public/
│   └── index.php              # Punto de entrada único / Router automático
├── src/
│   ├── Controllers/           # Lógica de negocio y flujo de pantallas
│   ├── Models/                # Entidades / DTOs del dominio (Inmutables)
│   ├── Repositories/          # Acceso a datos nativo con PDO
│   ├── Services/              # Servicios reutilizables (Auth, PDF, etc.)
│   └── Core/                  # Núcleo del framework y contenedor
├── views/
│   └── [modulo]/              # Vistas de cada módulo (.phtml)
├── database/
│   └── migrations/            # Migraciones SQL del sistema
├── cli/
│   └── sadi                   # Herramienta de línea de comandos CLI
└── docs/                      # Guías y tutoriales de arquitectura
```

### Router Automático

El router convierte la variable GET `route` en una llamada directa al controlador de forma automática. **No existe un archivo de rutas explícito**:
- `?route=apertura_cuentas/index` → `AperturaCuentasController::index()`

### Convenciones de Código (PSR)

El proyecto sigue estrictamente **PSR-1, PSR-4 y PSR-12**:
- `declare(strict_types=1)` es obligatorio en todos los archivos.
- Tipado explícito para argumentos y retornos.
- Variables en `camelCase`, Base de datos en `snake_case`.

**Formateo de código:**
```bash
vendor/bin/php-cs-fixer fix
```

### Añadir un Nuevo Módulo

Para garantizar la estandarización, **utiliza siempre el CLI de SADI** para generar módulos completos (CRUD, Modelo, Repositorio, Controlador, Migración):

```bash
php cli/sadi make:section MiEntidad
```
> 📖 **[Ver Manual Técnico del CLI](docs/MANUAL_TECNICO_CLI.md)**

### Arquitectura y Guías de Lectura Obligatoria

El proyecto utiliza un **patrón Repositorio** estricto con DTOs inmutables y consultas SQL puras preparadas, evitando ORMs. Es imprescindible leer estas guías:

1. 📖 **[Tutorial: Creación de un Módulo CRUD desde Cero](docs/TUTORIAL_CREAR_MODULO.md)**
2. 📖 **[Manejo de Relaciones entre Modelos (N+1)](docs/MANEJO_DE_RELACIONES.md)**
3. 📖 **[Operaciones CRUD y Queries SQL Seguras](docs/CRUD_Y_QUERIES.md)**

---

## 🗄️ Entornos y Base de Datos

| Entorno      | Configuración                 | Notas                          |
|--------------|-------------------------------|--------------------------------|
| Desarrollo   | `.env` y Podman/Docker        | Levanta en automático con up -d|
| Producción   | Variables de entorno OS       | Usar Nginx y PHP-FPM nativos   |
| Pruebas      | `.env.testing` (`sadi_test`)  | Usada para testing automatizado|

---

## 📁 Historial de Migración desde SIGAFS

| Fase | Módulos | Estado |
|------|---------|--------|
| Fase 1–4 | Personal, Nómina, Contabilidad, Almacén, Bancos | ✅ Completado |
| Fase 5.1 | Catálogos de Presupuesto (Proyectos, Acc. Cent., PUC) | ✅ Completado |
| Fase 5.2 | Procesos Presupuestarios (Comprobantes, Períodos, Reformulación) | ✅ Completado |
| Fase 5.3 | Seguridad y Autenticación RBAC | ✅ Completado |
| Fase 6 | Control Interno (Auditoría, Flujos de Aprobación, PDF dinámicos) | ✅ Completado |
| Fase 7 | Pruebas Integrales de Sistema (Compras, Pagos, Contabilidad) | ✅ Completado |
