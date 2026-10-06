# Instalación y ejecución

La aplicación utiliza **Docker Compose** para ejecutar todos los servicios necesarios: **frontend, API y PostgreSQL**.

## Requisitos

Antes de comenzar, es necesario tener instalado:

* Git
* Docker Engine o Docker Desktop con Docker Compose

## Configuración

### 1. Clonar el repositorio

Clona el repositorio y accede al directorio del proyecto:

```bash
git clone https://github.com/FrancoBorri/trabajo-final.git
cd trabajo-final
```

### 2. Crear los archivos de entorno

Copia los archivos `.env.example` para crear los archivos `.env` necesarios:

```bash
cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

Estos archivos contienen las variables de configuración necesarias para ejecutar la aplicación.

### 3. Configurar el backend

En `backend/.env`, verifica que la conexión a PostgreSQL utilice los siguientes valores:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

Estos valores deben coincidir con la configuración de PostgreSQL definida en el `.env` de la raíz del proyecto.

> **Importante:** dentro de Docker, el backend se conecta al servicio de PostgreSQL utilizando `postgres:5432`. El
> `DB_PORT` definido en el `.env` de la raíz corresponde al puerto que PostgreSQL expone en el equipo local.

Si modificás `DB_DATABASE`, `DB_USERNAME` o `DB_PASSWORD` en el `.env` de la raíz, también debés actualizar esos valores
en `backend/.env`.

### 4. Configurar el frontend

En `frontend/.env`, verifica que `VITE_API_URL` apunte a la API:

```dotenv
VITE_API_URL=http://localhost:8001/api
```

Este es el puerto predeterminado utilizado por el backend.

Si modificas `BACKEND_PORT` en el `.env` de la raíz, también debes actualizar `VITE_API_URL` para que apunte al nuevo
puerto.

> **Importante:** las credenciales incluidas en estos archivos son únicamente para el entorno de desarrollo. No deben
> utilizarse en producción.

---

# Iniciar la aplicación

Desde la raíz del proyecto, ejecuta:

```bash
docker compose up --build -d
```

Este comando construye las imágenes necesarias y levanta los contenedores de:

* Frontend
* Backend / API
* PostgreSQL

El contenedor del **backend ejecuta automáticamente las migraciones y los seeders al iniciarse**.

Las migraciones crean la estructura necesaria de la base de datos, mientras que los seeders cargan los datos iniciales
del sistema.

### Generar la clave de Laravel

Una vez iniciados los contenedores, genera la clave de la aplicación Laravel:

```bash
docker compose exec backend php artisan key:generate
```

### Acceder a la aplicación

Con la configuración y los puertos predeterminados:

* **Frontend:** http://localhost:5173
* **API:** `http://localhost:8001/api`

---

# Usuario administrador inicial

Durante el inicio del backend se ejecutan los **seeders de Laravel**.

Uno de estos seeders crea automáticamente un usuario con rol de **administrador**, por lo que no es necesario
registrarlo manualmente.

Las credenciales iniciales son:

| Campo      | Valor            |
|------------|------------------|
| Email      | `admin@test.com` |
| Contraseña | `pass`           |
| Rol        | Administrador    |

Estas credenciales están destinadas únicamente al entorno de desarrollo y son públicas.

---

# Ver los logs

Para visualizar los registros de todos los contenedores en tiempo real:

```bash
docker compose logs -f
```

También podés consultar los logs de un servicio específico:

```bash
docker compose logs -f backend
```

---

# Funcionalidades

El sistema presenta diferentes funcionalidades según el rol del usuario.

| Rol               | Funcionalidades                                                                                                                                          |
|-------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Cliente**       | Registrarse e iniciar sesión; consultar profesionales, servicios y horarios disponibles; reservar y consultar turnos.                                    |
| **Profesional**   | Administrar servicios y disponibilidad; aceptar, completar o cancelar turnos; consultar pacientes, sus historias clínicas y registrar sesiones clínicas. |
| **Administrador** | Consultar la agenda general; gestionar turnos y usuarios (administradores, clientes y profesionales); consultar los servicios disponibles.               |

## Flujo habitual de uso

El proceso para que un cliente reserve una atención es el siguiente:

1. **El administrador crea el profesional.** Desde la sección de gestión de
   profesionales, carga sus datos, correo, contraseña inicial y especialidad.
   El sistema crea el perfil profesional asociado a esa cuenta.

2. **El profesional publica sus servicios.** Inicia sesión y, en **Mis
   servicios**, agrega cada servicio con nombre, descripción, precio y duración.

3. **El profesional configura cuándo atiende.** En **Disponibilidad**, indica
   los días de la semana y las franjas horarias en las que recibe pacientes.

4. **El cliente reserva un turno.** Se registra o inicia sesión, elige un
   profesional y uno de sus servicios, selecciona una fecha y luego uno de los
   horarios disponibles que muestra el sistema. La disponibilidad considera la
   duración del servicio y evita superponer turnos existentes.

5. **El profesional gestiona la solicitud.** El turno aparece como pendiente
   en **Mis turnos**. El profesional puede aceptarlo o cancelarlo. Al aceptarlo,
   queda confirmado; después de la atención puede marcarlo como completado.

6. **El profesional registra la atención.** Los pacientes con turnos aceptados
   o completados aparecen en **Pacientes**. Allí el profesional puede crear o
   actualizar la historia clínica y agregar sesiones asociadas a los turnos del
   paciente.

7. **El cliente consulta sus turnos.** Desde **Mis turnos** puede revisar sus
   reservas y su estado.

Mientras tanto, el administrador puede consultar la agenda general y gestionar
los turnos y las cuentas desde su panel.

---

# Detener la aplicación

Para detener los contenedores:

```bash
docker compose down
```

Este comando detiene y elimina los contenedores, pero **conserva los datos de PostgreSQL** almacenados en el volumen
`postgres_data`.

Para volver a iniciar la aplicación:

```bash
docker compose up -d
```

No es necesario volver a ejecutar `--build` salvo que se hayan realizado cambios que requieran reconstruir las imágenes.

---

# Resumen rápido

Si ya tenés Docker instalado y querés levantar el proyecto desde cero:

```bash
git clone https://github.com/FrancoBorri/trabajo-final.git
cd trabajo-final

cp .env.example .env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

docker compose up --build -d

docker compose exec backend php artisan key:generate
```

Una vez iniciado el proyecto, el backend ejecuta automáticamente las **migraciones y los seeders**, incluyendo la
creación del usuario administrador.

**Usuario administrador:**

* Email: `admin@test.com`
* Contraseña: `pass`
* Rol: Administrador
