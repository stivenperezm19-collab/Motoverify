# Documentación: `AdministradorModel.php`

**Ubicación:** `models/AdministradorModel.php`

---

## 1. Propósito
Este archivo es el guardián de los usuarios administradores. Su labor es interactuar con la base de datos para todo lo relacionado con las personas que administran el sistema de Motoverify (verificar inicios de sesión, crear usuarios, editar sus perfiles o cambiar sus contraseñas).

## 2. Lógica Principal
La clase `AdministradorModel` sigue un patrón estándar de conexión segura:
1. Recibe la conexión a la base de datos.
2. Contiene funciones que realizan consultas SQL sobre la tabla `ADMINISTRADOR`.
3. Extrae la información (como nombres y correos) de forma segura y estructurada para que los controladores puedan validarla.

## 3. Relación con Otros Archivos
- **¿De quién depende?** Depende de la conexión provista por `config/database.php`.
- **¿Quién lo utiliza?** Es utilizado fuertemente por:
  - `controllers/login_action.php` (para revisar si el correo y la contraseña coinciden al iniciar sesión).
  - `controllers/perfil_action.php` (para actualizar los datos personales o contraseña).
  - `controllers/verify_password.php` (para verificar identidad antes de cambios sensibles).

## 4. Funciones Importantes
- **`getByEmail($email)`**: Busca en la base de datos a un administrador usando su correo electrónico. Es vital para el inicio de sesión.
- **`getById($id)`**: Busca la información completa de un administrador sabiendo su código único (ID). Se usa para cargar los datos en la página del perfil.
- **`updateProfile($id, $nombre, $email, $nueva_password)`**: Guarda los cambios que el administrador hace en su perfil. Si no se cambia la contraseña, solo actualiza nombre y correo.

## 5. Proceso de Actualización de Perfil (Flujo)
1. El usuario envía sus datos nuevos desde la pantalla del perfil.
2. El controlador (`perfil_action.php`) le pide al modelo que actualice.
3. El modelo prepara la consulta SQL de `UPDATE`.
4. Si el usuario envió una contraseña nueva, el modelo la agrega a la actualización (ya encriptada desde el controlador).
5. Se ejecutan los cambios y se devuelve una confirmación de éxito.
