# KrayinGoogleAuth

Paquete Laravel para el **Museo de Ciencias (MuCi)** que reemplaza el formulario de contraseña del admin de Krayin CRM por un botón de **"Entrar con Google"**. El backend de autenticación nativo sigue funcionando como respaldo (desactivado por defecto, reactivable con una variable de entorno).

---

## Propósito

- El formulario de email/contraseña se oculta via CSS al cargar la página de login.
- Se inyecta un botón "Entrar con Google" mediante el sistema de eventos de Krayin, sin tocar archivos del core.
- Usuarios del dominio `@muci.org` con correo verificado por Google quedan **auto-aprobados** con el rol `Básico`.
- Usuarios de otros dominios quedan en estado **pendiente** (`status=0`) hasta que un administrador los apruebe.
- Si el administrador activa el toggle de emergencia, el formulario nativo reaparece sin necesidad de reiniciar.

---

## Instalación

### 1. Registrar el repositorio

El paquete se instala como dependencia de ruta local. En el `composer.json` raíz de `laravel-crm` ya está registrado el directorio de paquetes:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/*/*",
        "options": { "symlink": true }
    }
]
```

### 2. Requerir el paquete

```bash
composer require carlvallory/krayin-google-auth @dev
```

Laravel descubre automáticamente el `KrayinGoogleAuthServiceProvider` gracias a la sección `extra.laravel.providers` del `composer.json` del paquete.

### 3. Ejecutar las migraciones

```bash
php artisan migrate
```

Esto aplica dos migraciones incluidas en el paquete:

| Migración | Efecto |
|-----------|--------|
| `2026_06_29_100000_add_google_columns_to_users_table` | Agrega `users.google_id` (unique, nullable) y `users.auth_provider` (nullable) |
| `2026_06_29_100100_seed_basico_role` | Crea el rol `Básico` con permisos de solo lectura (dashboard, leads, contacts) |

> Ambas migraciones son idempotentes: comprueban si la columna o el rol ya existe antes de crearlo.

### 4. Crear credenciales OAuth 2.0 en Google Cloud Console

1. Ir a **APIs & Services → Credentials → Create Credentials → OAuth client ID**.
2. Tipo de aplicación: **Web application**.
3. En **Authorized redirect URIs**, agregar el valor que se asignará a `GOOGLE_REDIRECT_URI` (p. ej. `https://crm.muci.org/login/google/callback`).
4. Copiar el **Client ID** y el **Client Secret**.

### 5. Configurar las variables de entorno

Agregar al archivo `.env`:

```dotenv
GOOGLE_CLIENT_ID=xxxxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxxxxxxxxxxxx
GOOGLE_REDIRECT_URI=https://crm.muci.org/login/google/callback
```

El paquete inyecta estas credenciales en `services.google` al arrancar, por lo que **no es necesario editar `config/services.php`**.

---

## Comportamiento

### Flujo de login

```
Usuario hace clic en "Entrar con Google"
    ↓
GET /login/google  →  redirige a OAuth de Google
    ↓
GET /login/google/callback  →  procesa la respuesta
    ↓
GoogleUserResolver::resolve()
    ├── Usuario ya vinculado por google_id  →  respeta su status actual
    ├── Mismo email, email_verified=true    →  vincula google_id, respeta status
    ├── Mismo email, email_verified=false   →  rechaza (estado pendiente, nunca auto-link)
    └── Usuario nuevo
        ├── Dominio muci.org + email_verified=true  →  auto-aprobado (status=1, rol Básico)
        └── Cualquier otro caso                     →  pendiente (status=0, rol Básico)
```

### Reglas clave

- **Auto-aprobación**: requiere que el claim `hd` del token Google sea `muci.org` **y** que `email_verified` sea `true`. Ambas condiciones son obligatorias.
- **Sin auto-link para correos no verificados**: si un correo de Google coincide con un usuario existente pero `email_verified=false`, el acceso queda pendiente. Esto evita que una cuenta de Google con email no verificado tome el control de una cuenta local.
- **Rol por defecto obligatorio**: si el rol `Básico` no existe al momento del login (p. ej. las migraciones no se ejecutaron), el resolver lanza una excepción descriptiva.

---

## Aprobación de usuarios pendientes

Los administradores acceden al listado de usuarios pendientes en:

```
GET /admin/google-auth/pending
```

La ruta está protegida por el middleware `user` (guard admin de Krayin). Desde esa vista se puede aprobar cada usuario con un `POST` a:

```
POST /admin/google-auth/pending/{id}/approve
```

---

## Toggle de emergencia

Si el acceso vía Google falla (credenciales revocadas, servicio caído, etc.), el formulario nativo de contraseña puede reactivarse sin desinstalar el paquete:

```dotenv
GOOGLE_AUTH_SHOW_PASSWORD_LOGIN=true
```

Al establecer esta variable, el CSS que oculta el formulario nativo deja de inyectarse y el login con contraseña vuelve a estar disponible. Ten en cuenta que el login con contraseña solo funciona para usuarios que tengan un `password` definido; los usuarios creados exclusivamente por Google tienen `password=NULL` y no pueden usar el formulario nativo.

---

## Configuración

El archivo `config/google-auth.php` (publicable o sobreescribible via `mergeConfigFrom`) expone las siguientes claves:

| Clave | Por defecto | Descripción |
|-------|-------------|-------------|
| `allowed_domains` | `['muci.org']` | Dominios cuyas cuentas se auto-aprueban (requiere `email_verified=true`). |
| `default_role_name` | `'Básico'` | Nombre del rol que se asigna a todos los usuarios nuevos que ingresan por Google. |
| `show_password_login` | `false` | Si es `true`, muestra el formulario nativo de contraseña. Corresponde a `GOOGLE_AUTH_SHOW_PASSWORD_LOGIN`. |
| `uninstall_fallback_role` | `'Administrator'` | Rol al que se reasignan los usuarios con rol `Básico` al ejecutar `google-auth:uninstall`. Si el rol no existe y hay usuarios en `Básico`, el comando aborta con error. |
| `credentials.client_id` | `env('GOOGLE_CLIENT_ID')` | Client ID de OAuth 2.0. |
| `credentials.client_secret` | `env('GOOGLE_CLIENT_SECRET')` | Client Secret de OAuth 2.0. |
| `credentials.redirect` | `env('GOOGLE_REDIRECT_URI')` | URI de redirección registrado en Google Cloud Console. |

---

## Desinstalación

### 1. Ejecutar el comando de desinstalación

```bash
php artisan google-auth:uninstall
```

El comando realiza las siguientes acciones en orden:

1. Busca el rol `Básico`; si existe y hay usuarios asignados a él, muestra una advertencia e solicita confirmación antes de reasignarlos al rol de respaldo.
2. Reasigna los usuarios del rol `Básico` al rol definido en `uninstall_fallback_role` (por defecto `Administrator`).
3. Elimina el rol `Básico`.
4. Elimina el índice único de `google_id` (con protección ante doble ejecución parcial).
5. Elimina las columnas `users.google_id` y `users.auth_provider`.

> **Confirmación interactiva**: si hay usuarios con rol `Básico`, el comando solicita confirmación antes de reasignarlos al rol de respaldo (que puede tener más privilegios). Responder `no` cancela la operación sin realizar ningún cambio.

> **Modo no interactivo** (scripts automatizados): usar `--force` para omitir la confirmación:
> ```bash
> php artisan google-auth:uninstall --force
> ```

> **Condición de aborto**: si el rol de respaldo (`Administrator` por defecto) no existe **y** hay usuarios con rol `Básico`, el comando falla con un mensaje de error y no realiza ningún cambio. Crear el rol de respaldo antes de volver a ejecutar. Si el rol de respaldo no existe pero ningún usuario tiene el rol Básico, el comando emite una advertencia y continúa con la desinstalación.

### 2. Quitar el paquete de Composer

```bash
composer remove carlvallory/krayin-google-auth
```

### 3. Limpiar las variables de entorno

Eliminar del `.env`:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GOOGLE_AUTH_SHOW_PASSWORD_LOGIN=   # solo si fue agregada
```

---

## Nota sobre cambios de esquema

Todo cambio en el esquema de base de datos o datos semilla (columnas, roles, permisos) del paquete se gestiona **exclusivamente mediante migraciones y seeders de Laravel**, de forma que sean reproducibles en producción con un simple `php artisan migrate`. Nunca se deben aplicar estos cambios con SQL manual.

---

## Verificación manual

Los siguientes pasos requieren credenciales reales de Google y **no están cubiertos por los tests automáticos**:

1. **Crear las credenciales**: ir a Google Cloud Console → APIs & Services → Credentials → Create Credentials → OAuth client ID (tipo Web), agregar `GOOGLE_REDIRECT_URI` en los URIs de redirección autorizados.
2. **Configurar el entorno**: poblar `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` y `GOOGLE_REDIRECT_URI` en el `.env` de producción/staging.
3. **Probar login con cuenta @muci.org**: el usuario debe ingresar directamente al dashboard sin intervención del administrador.
4. **Probar login con cuenta externa**: el usuario debe ver un mensaje de "acceso pendiente de aprobación"; aprobarlo desde `/admin/google-auth/pending`.
5. **Probar el toggle de emergencia**: establecer `GOOGLE_AUTH_SHOW_PASSWORD_LOGIN=true` y verificar que el formulario nativo de contraseña reaparece en la pantalla de login.

---

## Compatibilidad

- PHP `^8.1`
- Laravel `^10.0`
- Krayin CRM `^2.x`
- `laravel/socialite` `^5.0`

---

## Licencia

MIT — Carlos Vallory / Museo de Ciencias (MuCi)
