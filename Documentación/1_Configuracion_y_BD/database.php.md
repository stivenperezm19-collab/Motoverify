# Documentación: `database.php`

**Ubicación:** `config/database.php`

---

## 1. Propósito
Este archivo actúa como la llave principal del sistema. Su única función es abrir una comunicación directa entre nuestra página web (la plataforma de Motoverify) y la base de datos MySQL (donde se guarda de manera segura toda la información de repuestos, administradores, marcas y categorías). Sin este archivo, la plataforma no tendría memoria ni datos.

## 2. Lógica Principal
El archivo sigue un flujo muy sencillo para establecer la conexión:
1. Define las "credenciales" necesarias (usuario, contraseña, dirección, etc.).
2. Intenta abrir la conexión usando estas credenciales.
3. Si lo logra, guarda esa conexión para que el resto del sistema la use.
4. Si falla (por ejemplo, porque la base de datos está apagada o la contraseña cambió), detiene completamente el programa y muestra un mensaje de error.

## 3. Relación con Otros Archivos
- **¿De quién depende?** De nadie. Es uno de los archivos base.
- **¿Quién lo utiliza?** Prácticamente **todo el sistema**. Modelos (`RepuestoModel.php`, `AdministradorModel.php`, etc.), Controladores (`login_action.php`, `repuestos_action.php`) y Vistas (`dashboard.php`, `index.php`) lo incluyen al principio usando `require_once '../../config/database.php';` para poder consultar datos.

## 4. Variables Importantes (Entradas y Salidas)
- **Entradas (Configuración interna):**
  - `$host`: La dirección del servidor (`127.0.0.1`).
  - `$user`: El usuario principal de la base de datos (`root`).
  - `$password`: La contraseña (vacía en el entorno de pruebas local).
  - `$bd`: El nombre exacto de la base de datos (`consulta_repuestos`).
  - `$port`: El "canal" o puerto de comunicación (`3306`).
- **Salida:**
  - `$conn`: La variable que representa la **conexión activa**. Todo el sistema usa `$conn` cuando necesita hablar con la base de datos.

## 5. Fragmento de Código Clave

```php
// Intentamos abrir la conexión
try {
    // Aquí creamos una nueva conexión directa usando las credenciales
    $conn = new mysqli($host, $user, $password, $bd, $port);
} catch (mysqli_connect_error $e) {
    // Si hay un error, el sistema se detiene y avisa
    die('Error de Conexion: ' . $e->getMessage());
}
```
**¿Qué hace esto?** 
Usa un bloque "intenta-y-atrapa" (`try/catch`). Primero intenta crear la conexión (`new mysqli`). Si el intento falla, en lugar de mostrar un montón de código incomprensible, atrapa el error y detiene el proceso con la función `die()`, mostrando un mensaje claro.
