# Documentación: Vistas de Administrador

Estas pantallas se encuentran dentro de la carpeta `views/admin/` y conforman el "Panel de Control" privado del sistema. Todas ellas comparten una característica esencial: en sus primeras líneas verifican que el usuario haya iniciado sesión correctamente (leyendo `$_SESSION`). Si no hay sesión, devuelven al usuario a la página de inicio.

---

## `dashboard.php` (Inicio)

### 1. Propósito
Es el "Tablero Principal" del administrador. Su misión es brindar un resumen rápido de cómo está el inventario apenas se ingresa al sistema, mostrando atajos a las marcas más importantes y los últimos repuestos agregados.

### 2. Lógica y Funcionamiento
- Al cargar, se conecta a la base de datos y le pide: "Dame las 7 marcas más importantes y los 6 repuestos subidos más recientemente".
- Dibuja la barra de navegación lateral (el menú izquierdo con los botones de Inicio, Marcas, Repuestos, etc.) y la barra superior.
- **Herramienta IA:** Contiene la ventana emergente (modal) de la cámara. Desde aquí, el administrador puede abrir su cámara web o celular, tomar la foto de un repuesto, y enviarla a `controllers/ai_analyze.php` para que la Inteligencia Artificial la procese.

---

## `repuestos.php` (Gestor de Repuestos)

### 1. Propósito
Es la pantalla de trabajo pesado. Permite visualizar el inventario completo, buscar repuestos específicos usando múltiples filtros (por marca, categoría o texto), y ofrece los formularios para agregar nuevos repuestos o editar los existentes.

### 2. Lógica y Funcionamiento
- **Paginación Inteligente:** Calcula cuántos repuestos hay en total y solo le pide a la base de datos los siguientes 12 (dependiendo en qué página estemos). Esto hace que la página cargue instantáneamente, así existan miles de piezas.
- **Formulario Inteligente:** Cuando el administrador presiona el botón "Nuevo", se abre un formulario en blanco. Pero si presiona el ícono del lápiz en un repuesto ya existente, un código en JavaScript toma todos los datos de ese repuesto y rellena automáticamente el formulario para poder editarlo fácilmente.
- Envía todas las órdenes de creación o borrado a `controllers/repuestos_action.php`.

---

## `marcas.php` y `catalogo.php` (Ajustes de Inventario)

### 1. Propósito
Son pantallas hermanas. `marcas.php` permite gestionar las marcas de los repuestos (Honda, Yamaha) subiendo sus logos. `catalogo.php` permite gestionar las categorías del inventario (Motor, Eléctrico).

### 2. Lógica y Funcionamiento
Ambas funcionan de manera idéntica:
- Muestran una lista ordenada de todas las opciones existentes en formato de tarjetas o tabla.
- Tienen un botón para añadir nuevas (que abre una ventana emergente).
- Se comunican respectivamente con `marcas_action.php` y `catalogo_action.php` para guardar los cambios.

---

## `perfil.php` (Configuración Personal)

### 1. Propósito
Es la pantalla de ajustes de cuenta del administrador. Permite cambiar nombre, correo y contraseña.

### 2. Lógica y Funcionamiento
- Extrae el ID del usuario actual de la sesión.
- Pide a la base de datos la información de ese usuario y la muestra en la pantalla.
- **Doble Seguridad:** Si el administrador presiona "Guardar Cambios", la página (usando JavaScript) lanza un mensaje pidiendo su contraseña actual. Esta se envía a `verify_password.php`. Solo si responde que es correcta, la página permite que la nueva información sea enviada a `perfil_action.php` para guardarse de verdad.
