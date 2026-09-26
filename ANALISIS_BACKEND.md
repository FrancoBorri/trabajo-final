# 🎓 Documento de Estudio y Guion de Exposición — Backend "Turnos Médicos"

> Proyecto: API REST para gestión de turnos médicos, historia clínica y agenda de profesionales.
> Stack: PHP 8.3 · Laravel 13.20 · PostgreSQL 16 · Eloquent + Sanctum.

---

## 1. 📌 Resumen Ejecutivo del Backend

### ¿Qué resuelve y cuál es su propósito principal?
Es el **backend (API REST) de un sistema de gestión de turnos y consultas médicas**. Resuelve el flujo completo de una clínica/consultorio:

- **Cliente/paciente**: se registra, se autentica, consulta profesionales y sus servicios, ve **los horarios libres disponibles** y reserva turnos.
- **Profesional**: carga su agenda (días y franjas horarias), administra sus servicios y completa/cancela turnos.
- **Admin**: administra usuarios, profesionales y ve todos los turnos del sistema.
- **Extensión clínica**: historias clínicas por paciente (`1:1`) y sesiones clínicas que registran la evolución de cada tratamiento.

El valor principal está en el **motor de disponibilidad**: dado un profesional, un servicio y una fecha, el sistema devuelve los **slots de tiempo libres** calculando conflictos contra los turnos ya reservados.

### Arquitectura utilizada
**Arquitectura en capas (Layered / MVC clásico de Laravel)** dentro de un **monolito**:

```
Rutas (routes/api.php)
   ↓
Controllers (app/Http/Controllers)
   ↓
Eloquent Models (app/Models) + Enums (app/Enums)
   ↓
Base de datos (PostgreSQL / migraciones / seeders)
```

- Es **monolito**: una sola aplicación sirve todos los dominios (auth, agenda, turnos, clínica).
- **Se eligió Laravel** porque ofrece, por convención, cada capa ya estructurada (`Controllers`, `Models`, `Migrations`, `Routes`), lo que acelera el desarrollo y mantiene el código ordenado sin renunciar al control.
- La API es **stateless**: no usa sesiones para la API; cada request se autentica con un **token Bearer** (Sanctum).
- **Ventajas del enfoque**: baja complejidad de despliegue (1 contenedor PHP + 1 Postgres), curva de aprendizaje estándar, y separación clara de responsabilidades por capa.
- *Decisión consciente*: no se usó Clean Architecture/Hexagonal porque, para el tamaño del dominio, aportaría boilerplate sin beneficio real; la separación por capas de Laravel ya aísla HTTP, lógica de negocio y persistencia.

### Stack Tecnológico

| Capa | Tecnología | Detalle |
|---|---|---|
| Lenguaje | **PHP 8.3** | Tipos, enums nativos (PHP 8.1+), attributes (`#[Fillable]`, `#[Hidden]`) |
| Framework | **Laravel 13.20** | Routing + Eloquent + middleware + validation |
| Base de datos | **PostgreSQL 16** (Docker) | **SQLite** como alternativa en `.env.example` |
| ORM | **Eloquent** | Relaciones, casts, mutators, soft deletes |
| Autenticación | **Laravel Sanctum 4.3** | Personal Access Tokens (Bearer), tabla `personal_access_tokens` |
| Fechas | **Carbon 3.13** | Cálculo de slots y solapamientos |
| Calidad | **Pint** (estilo) + **PHPUnit 12.5** (tests) | |
| Infraestructura | **Docker / docker-compose** | `app` (PHP-FPM+artisan) + `postgres:16`, entrypoint que corre `migrate --force` y `db:seed --force` |

---

## 2. 🏗️ Arquitectura y Flujo de Datos

### Mapeo de carpetas

| Ruta | Responsabilidad |
|---|---|
| `routes/api.php` | **Definición de endpoints REST** y asignación de middleware (`auth:sanctum`). Es el "mapa" de la API. |
| `app/Http/Controllers/` | **Capa HTTP**: recibe el `Request`, valida, orquesta y devuelve `Response` JSON. 8 controladores. |
| `app/Models/` | **Capa de dominio/persistencia**: Eloquent. Relaciones, casts, acceso/escritura normalizada. 8 modelos. |
| `app/Enums/` | Tipado de dominios acotados: `AppointmentStatus` (estados del turno) y `DayOfWeek` (días de la semana). |
| `database/migrations/` | **Esquema de BD** versionado (schema-as-code). |
| `database/seeders/` | Datos iniciales de prueba (cliente, professional, admin). |
| `bootstrap/app.php` | **Configuración del kernel**: enrutado y regla general de respuesta JSON para `api/*`. |
| `config/` | Configuración central del framework (CORS, auth, DB, Sanctum, sesiones, etc.). |

> Nota: en el repo las configs están bajo `database/migrations/config/` (parecen una copia de respaldo movida por error); deben vivir en `config/`.

### Flujo de una petición (End-to-End)

Tomemos como ejemplo `GET /api/professionals/{professional}/available-slots?service_id=1&date=2026-12-01`:

1. **Entrada**: `public/index.php` → arranca la app (`bootstrap/app.php`).
2. **Matching**: el **Router** hace match con `/professionals/{professional}/available-slots` → `AvailabilityController@availableSlots`.
3. **Middleware globales**: cookies, excepciones JSON (`shouldRenderJsonWhen` convierte errores a JSON porque la URL es `api/*`), CORS.
4. **Controller**: valida el `Request` (`service_id` existe, `date` es fecha), resuelve el servicio, verifica que pertenezca al profesional, calcula los slots (algoritmo en §3) y devuelve JSON.
5. **Modelo/Eloquent**: consultas a `services`, `availability` y `appointments` contra PostgreSQL.
6. **Response**: `response()->json([...])` → el cliente recibe `200 + { date, service_id, duration, slots: [...] }`.

Para rutas protegidas (`auth:sanctum`): el middleware `Authenticate` lee el token Bearer, lo busca en `personal_access_tokens`, resuelve el usuario y lo inyecta vía `Auth::user()` / `$request->user()`.

---

## 3. 🔑 Módulos y Dominio del Sistema

### Módulos / componentes clave

| Módulo | Controlador | Funcionalidad |
|---|---|---|
| **Auth** | `AuthController` | `register` (crea usuario + profesional si aplica), `login` (emite token), `logout` (revoca token actual). |
| **Profesionales** | `ProfessionalController` | CRUD, listado paginado con transformación de datos, `profile`/`updateProfile` para el profesional autenticado, borrado en cascada del usuario asociado. |
| **Agenda** | `AvailabilityController` | CRUD de disponibilidad semanal del profesional + **`availableSlots`**: el "cerebro" del sistema. |
| **Servicios** | `ServiceController` | CRUD de servicios (precio, duración minutos) con verificación de dueño/admin. |
| **Turnos** | `AppointmentController` | CRUD + listados por rol (cliente, profesional, admin) + acciones `cancel` (dueño) y `complete` (profesional). |
| **Historias clínicas** | `ClinicalHistoryController` | CRUD; listado de historias de pacientes que tienen sesiones con el profesional autenticado. |
| **Sesiones clínicas** | `ClinicalSessionController` | CRUD de sesiones (topic, evolution, observations) ligadas a historia clínica y turno. |
| **Usuarios** | `UserController` | CRUD + filtrado por `role` + paginación. |

### Entidades y relaciones (PostgreSQL)

```
users 1───1 professionals                     (SoftDeletes en ambos)
professionals 1───N services                  (SoftDeletes; cascadeOnDelete)
professionals 1───N availability
users 1───N appointments N───1 professionals
services 1───N appointments
users 1───1 clinical_histories                (user_id UNIQUE; restrictOnDelete → integridad clínica)
clinical_histories 1───N clinical_sessions
appointments 1───1 clinical_sessions?         (appointment_id nullable; restrictOnDelete)
```

Tablas auxiliares: `personal_access_tokens` (tokens Sanctum), `password_reset_tokens`, `sessions`.

Detalles de diseño importantes:
- **`users.role` es un string** (`cliente` / `professional` / `admin`), no una tabla normalizada. Existe un modelo `Role` con pivot `user_role`, **pero no hay migración** que cree `roles`/`user_role` → hoy el control de rol real es la columna `role`.
- **`clinical_histories.user_id` es `UNIQUE`** con `restrictOnDelete`: garantiza "una historia clínica por paciente" y **protege el historial de ser borrado** si tiene sesiones (integridad regulatoria).
- **Soft deletes** en `users`, `professionals`, `services`: se preserva el dato ante un borrado (auditoría).
- `price` es `decimal(10,2)` (precisión monetaria) y `duration` en minutos (base del cálculo de slots).
- Casts: `password → hashed` (bcrypt automático), `email_verified_at → datetime`.
- Mutators en `User`: `name`/`lastName` se normalizan a "Primera en mayúscula" al guardar.

### Algoritmo más complejo: `availableSlots` (AvailabilityController:105-203)

**Entrada**: `professional_id` + `service_id` + `date`.
**Pasos**:
1. **Validación de pertenencia**: el servicio debe ser del profesional (evita reservar con el servicio de otro).
2. **Mapeo de día**: `Carbon::parse($date)->dayOfWeekIso` (1=Lun … 7=Dom) → busca la fila de `availability`.
3. **Si no trabaja ese día** → responde `slots: []`.
4. **Generación de candidatos**: desde `time_start`, avanza de a `duration` minutos y genera rangos `[start, start+duration)` mientras `end <= time_end`.
5. **Detección de conflictos** (lógica de intervalos): para cada turno **no-cancelado** del día, calcula su rango `[appointment.time, appointment.time + servicio.duration)` y descarta el candidato si hay solapamiento:

```
conflict ⟺  candidatoStart < appointmentEnd  ∧  candidatoEnd > appointmentStart
```
6. Devuelve los slots libres en formato `"H:i"`.

Es un algoritmo clásico de **interval scheduling / detección de solapamientos**, de costo O(n×m), perfectamente defendible académicamente.

---

## 4. 🔒 Seguridad, Configuración y Buenas Prácticas

### Autenticación y Autorización
- **Autenticación**: tokens **Bearer (Sanctum / Personal Access Tokens)**. `POST /api/login` valida credenciales con `Hash::check`, emite el token y lo devuelve; el cliente lo envía en `Authorization: Bearer <token>`. `logout` **revoca** el token (`currentAccessToken()->delete()`).
- **Stateless**: la API no usa sesiones/cookies; ideal para consumirse desde un SPA/Vite (`allowed_origins: http://localhost:5173`).
- **Autorización (RBAC liviano)**: el rol es una propiedad del usuario (`role` string). Los checks se hacen **ad-hoc en cada acción**:
  - `AppointmentController::cancel` → solo `user_id === Auth::id()`, si no 403.
  - `AppointmentController::complete` → solo el profesional dueño del turno.
  - `ServiceController::store` → dueño del profesional o `admin`.
  - Rutas de perfil profesional → solo el profesional autenticado.
- **Protección de datos**: `#[Hidden(['password', 'remember_token'])]` y el cast `hashed` garantizan que nunca se devuelva ni se persista la contraseña en claro.

### Manejo de errores y validaciones
- **Validación declarativa vía `$request->validate()`** (reglas estándar): `required`, `email`, `unique:users,email`, `min:8`, `date_format:H:i`, `after:time_start`, `exists:tabla,id`, `numeric|min:0`, etc. Laravel responde automáticamente `422` con el JSON de errores.
- **Respuestas JSON forzadas para la API**: `$exceptions->shouldRenderJsonWhen(fn ($r) => $r->is('api/*'))` → cualquier excepción en rutas `api/*` se serializa como JSON.
- **Errores de dominio**: `findOrFail` → 404; checks de autorización → `403` explícito; reglas de negocio inválidas → `422`.
- **Códigos HTTP correctos**: 201 en creación, 204 en borrado, 401 credenciales, 403 no autorizado.

### Variables de entorno críticas (`.env` / `.env.example`)
| Variable | Rol |
|---|---|
| `APP_KEY` | Cifrado de sesiones/encryption. **Crítica, nunca versionar en producción.** |
| `APP_ENV` / `APP_DEBUG` | `debug=true` solo en local (nunca en producción). |
| `DB_CONNECTION/HOST/PORT/DATABASE/USERNAME/PASSWORD` | Conexión a PostgreSQL (`pgsql`, host `postgres` en Docker). |
| `BCRYPT_ROUNDS=12` | Coste de hashing (compromiso seguridad/rendimiento). |
| `SANCTUM_STATEFUL_DOMAINS` | Dominios que usan auth por cookie (para SPA). |
| `CORS allowed_methods=['*']`, `allowed_origins=['http://localhost:5173']` | Origen permitido = frontend Vite. |
| `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database` | Drivers portables sin servicios externos. |

### Buenas prácticas aplicadas
- Schema-as-code con **migraciones** (evolucionable y reproducible en Docker).
- **Soft deletes** para trazabilidad.
- **Integridad referencial** en BD + validación en capa HTTP (defensa en profundidad).
- Normalización de nombres (`ucfirst(strtolower())`), ocultamiento de atributos sensibles.
- **Separación de entornos** por `.env`; Docker para reproducibilidad (`migrate --force && db:seed --force`).
- Enums tipados (`AppointmentStatus`, `DayOfWeek`) en lugar de strings "mágicos" esparcidos.

### Puntos a mejorar (honestidad académica — anticiparse al tribunal)
1. **`role` como string** en vez de tabla normalizada (el modelo `Role`/pivot `user_role` no tiene migración de soporte).
2. **CRUD de `professionals` y `users` sin `auth:sanctum`** en las rutas → `store/update/destroy` quedan expuestos públicamente.
3. **Sin Form Requests / DTOs**: la validación vive en el controlador.
4. **Posible N+1 en `availableSlots`**: `$appointment->service->duration` hace lazy-loading del servicio por cada turno.
5. `create`/`edit` vacíos (boilerplate heredado de `apiResource`); `Role` model sin uso.
6. **Sin tests del dominio** (solo `ExampleTest`); no hay tests de `availableSlots` — gran oportunidad para mencionarlo como trabajo futuro.

---

## 5. 🎤 Guion de Exposición para la Defensa Final

### Introducción (1–2 min)
> "Buenos días. Mi trabajo final es el **backend de un sistema de gestión de turnos médicos**. Es una **API REST construida con Laravel 13 y PHP 8.3**, con **PostgreSQL** como base de datos y **autenticación por tokens (Sanctum)**. El sistema permite que los pacientes reserven turnos viendo los horarios realmente libres, que los profesionales gestionen su agenda y sus servicios, y que se registre la historia clínica y las sesiones de cada paciente.
>
> Elegí arquitectura **en capas (Layered/MVC)** porque Laravel organiza por convención controladores, modelos y migraciones, lo que da separación de responsabilidades sin complejidad innecesaria para el dominio. El frontend consume esta API desde **Vite en el puerto 5173**."

### Demostración del código (3–5 min) — qué mostrar en pantalla y por qué

| # | Archivo | Qué mostrar | Por qué lo eligieron |
|---|---|---|---|
| 1 | `routes/api.php` | Todos los endpoints y el middleware `auth:sanctum` | Demuestra el mapa completo de la API y el diseño REST. |
| 2 | `app/Http/Controllers/AvailabilityController.php` → **`availableSlots`** (L105-203) | El algoritmo de slots libres con detección de solapamientos | Es la **lógica de negocio más valiosa**: interval scheduling. |
| 3 | `database/migrations/2026_09_09_112538..._clinical_histories` | `user_id UNIQUE` + `restrictOnDelete` | Muestra criterio de **integridad regulatoria** en el modelo de datos. |
| 4 | `app/Models/User.php` | `#[Hidden]`, cast `hashed`, mutators `name/lastName` | Seguridad y normalización de datos en la capa de dominio. |
| 5 | `AuthController.php` (login/logout) | Emisión y **revocación** de tokens | Muestra el flujo stateless de autenticación. |
| 6 | `app/Enums/AppointmentStatus.php` | States tipados (`pending→confirmed→completed/cancelled/no_show`) | Tipado de dominio, evita strings mágicos. |
| 7 | `bootstrap/app.php` | `shouldRenderJsonWhen` | Contrato de API: toda respuesta/error es JSON. |

**Cierre de demo**: "Con esto, el flujo completo es: el paciente pregunta por slots → el backend valida el servicio, calcula horarios libres sin conflictos → el turno se crea → el profesional lo completa o cancela → y opcionalmente se registra la sesión en la historia clínica."

### Puntos fuertes a destacar (para impresionar)
1. **Motor de disponibilidad correcto**: algoritmo de solapamiento de intervalos que respeta la duración real del servicio y descarta turnos cancelados (regla de negocio cuidada).
2. **Seguridad**: bcrypt (coste 12), tokens revocables, atributos ocultos, autorización por acción (dueño vs admin), errores JSON consistentes, CORS acotado.
3. **Integridad de datos**: soft deletes + claves `RESTRICT` para proteger el historial clínico + `UNIQUE` para la 1:1 historia-paciente.
4. **Evolución del dominio**: el agregado clínico (historias + sesiones) evidencia un diseño incremental y pensado (2º feature sobre turnos).
5. **Despliegue reproducible**: Docker + entrypoint que migra y seedea solo.
6. **Tipado moderno de PHP 8.3**: enums con `label()`, attributes de Eloquent, match expressions — código limpio y a prueba de valores inválidos.

---

## 6. 🧠 Preguntas Frecuentes del Tribunal (con respuestas preparadas)

**P1 — ¿Por qué una arquitectura en capas y no Clean Architecture o microservicios?**
> Porque el dominio es acotado y el equipo pequeño. La arquitectura en capas de Laravel ya separa transporte (controllers), negocio (models/enums) y persistencia (migraciones/BD), con acoplamiento bajo. Clean/Hexagonal agrega puertos y adaptadores cuyo costo supera el beneficio a esta escala. Y un monolito distribuible en Docker es más simple y barato de operar que microservicios: un solo ciclo de build, un solo ORM, transacciones naturales. Si el sistema creciera, los módulos (auth, agenda, clínica) están lo suficientemente desacoplados como para extraerse a servicios.

**P2 — ¿Cómo evitas que dos pacientes reserven el mismo horario? (concurrencia)**
> El cálculo de `availableSlots` filtra los turnos no-cancelados y descarta superposiciones, así el cliente solo ve huecos libres. *Sin embargo*: hoy la validación es de solo-lectura; la protección dura contra doble reserva en condiciones de carrera requiere una **restricción a nivel de BD** (constraint `EXCLUDE` en Postgres sobre el rango `[date, time, duration]`) o una **transacción con bloqueo/lock** sobre el profesional al momento del `store`. Ese es exactamente el tipo de refuerzo que plantearía como evolución.

**P3 — Noté que `users.role` es un string y existe un modelo `Role` sin migración. ¿No es un diseño incompleto?**
> Correcto, es una decisión pragmática: para tres roles fijos y checks sencillos (`dueño` o `admin`), una columna tipada es más simple y legible que una tabla pivote. El modelo `Role` quedó sin soporte porque el sistema no necesita roles dinámicos hoy. Si mañana hubiera permisos por módulo o asignación multicaza, se migraría a la tabla `roles` + `user_role` con un `Seeder`. Reconocer esa deuda técnica muestra criterio.

**P4 — Veo `Hash::make()` en el controlador y un cast `hashed` en el modelo: ¿te duplicas el hash?**
> No por dos razones: Eloquent en Laravel 13 detecta un valor ya hasheado con `Hash::isHashed()` y no vuelve a hashearlo; y el campo `password` está en `$hidden`, por lo que nunca se serializa. El `Hash::make()` del controlador es *redundante* con el cast, un punto de pulido que puedo quitar; de hecho, el cast por sí solo garantiza el hashing en cualquier punto de escritura, que es más robusto.

**P5 — ¿Cuál es el cuello de botella y cómo escalarías el sistema?**
> El cuello de botella es el endpoint `availableSlots`: por cada petición barre los turnos del mismo profesional/día en memoria. Escalaría en tres frentes: (a) **índice compuesto** en `appointments(professional_id, date, status)` para que el fetch sea por índice; (b) **cache** del resultado por `professional_id+service_id+date` (30s–1min) dado que los turnos cambian poco; (c) **eliminar el N+1** cargando `service` con la consulta (`with('service')`). A nivel de infraestructura, PHP-FPM + Postgres se escalan con más réplicas de app ante un balanceador, y la BD con read-replicas, ya que la carga es mayoritariamente de lectura (consulta de slots y agenda).

**P6 (bonus) — ¿Por qué `restrictOnDelete` en historias clínicas y soft deletes en usuarios?**
> Por normativa sanitaria, el historial clínico no debe perderse: la FK con `RESTRICT` impide borrar un paciente con historia clínica y a la vez la historia tiene sesiones. El soft delete (`deleted_at`) complementa: un usuario "eliminado" sigue existiendo para trazabilidad/auditoría y consultas históricas, sin romper las relaciones de turnos pasados. Es una defensa en profundidad entre el modelo relacional y el proceso.

---

## 📚 Cheatsheet para memorizar antes de la defensa
- **Ruta clave**: `GET /api/professionals/{id}/available-slots`.
- **Algoritmo**: sloteo por `duration` + solapamiento `start<end∧end>start`, excluye `cancelled`.
- **Auth**: Bearer token Sanctum, `logout` revoca; roles `cliente|professional|admin` en columna `role`.
- **BD**: 9 tablas → users 1:1 professionals 1:N services/availability; appointments N:1 users/professionals/services; histories 1:1 users, 1:N sessions.
- **Errores**: `validate()` → 422; `findOrFail` → 404; checks de dueño → 403; JSON forzado en `api/*`.
- **Seguridad**: bcrypt coste 12, `$hidden`, soft deletes, RESTRICT, CORS acotado.
- **Mejoras a mencionar con soltura**: constraint `EXCLUDE` (concurrencia), Form Requests, tests de `availableSlots`, índice compuesto + cache.