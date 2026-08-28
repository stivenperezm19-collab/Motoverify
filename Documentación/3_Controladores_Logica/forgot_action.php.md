# Documentación: `forgot_action.php`

**Ubicación:** `controllers/forgot_action.php`

---

## 1. Propósito
Este archivo es el encargado de iniciar el proceso de recuperación de contraseña cuando un administrador olvida sus datos para entrar al sistema. Su función es verificar el correo, crear un código de seguridad (token) temporal y enviar un correo electrónico con las instrucciones para restablecer la clave.

## 2. Lógica Principal
El proceso paso a paso es el siguiente:
1. Recibe el correo electrónico que el usuario ingresó.
2. Si no existe una tabla llamada `password_resets` en la base de datos (donde se guardan las solicitudes de cambio de clave), la crea automáticamente.
3. Busca en la base de datos de administradores si ese correo realmente está registrado.
4. Si está registrado, genera un "token" (un texto largo y aleatorio imposible de adivinar).
5. Guarda este token en la base de datos.
6. Crea un enlace mágico (que incluye el token) y envía un correo electrónico al administrador. Al hacer clic en el enlace, el administrador podrá crear una nueva contraseña.

## 3. Relación con Otros Archivos
- **Depende de:** `config/database.php` (para conexión) y la configuración de correo del servidor (`mail()`).
- **Es utilizado por:** El formulario de "Olvidé mi contraseña" en la página pública.
- **Se conecta con:** `public/reset_password.php`, que es la página a la que el usuario será dirigido cuando haga clic en el enlace del correo.

## 4. Detalle de Seguridad Importante
```php
if ($result->num_rows > 0) {
    // Proceso real de generar token y enviar correo
} else {
    // Si el correo NO existe, el sistema dice:
    echo json_encode(['status' => 'success', 'message' => 'Si el correo existe, recibirás un enlace de recuperación.']);
}
```
**¿Por qué hace esto?** 
Es una medida de seguridad avanzada. Si el sistema dijera *"Ese correo no existe"*, una persona malintencionada podría probar miles de correos hasta descubrir cuáles están registrados. Al mostrar siempre el mismo mensaje, protegemos la identidad de los administradores.
