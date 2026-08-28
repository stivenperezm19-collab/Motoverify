# Documentación: `index.php` (Página Principal)

**Ubicación:** `public/index.php`

---

## 1. Propósito
Es la "cara" del proyecto Motoverify. Es la primera pantalla que ven los clientes, mecánicos o usuarios de internet cuando entran a la plataforma. Su objetivo es permitir a cualquier persona buscar repuestos en el inventario o, si se es administrador, proporcionar un acceso directo para iniciar sesión.

## 2. Lógica Principal
1. Se conecta a la base de datos de manera silenciosa para ver qué información hay.
2. Dibuja la página usando HTML y la embellece usando CSS (Bootstrap).
3. **El buscador en vivo:** Tiene una barra de búsqueda inteligente. A medida que el cliente escribe un código o nombre de repuesto, la página utiliza un pequeño programa de JavaScript para hablar con la base de datos (mediante `controllers/qr_search.php` u otros) y mostrar sugerencias sin tener que recargar la pantalla.

## 3. Relación con Otros Archivos
- Depende de `config/database.php`.
- Se comunica constantemente con los archivos en la carpeta `controllers/` para buscar la información que pide el cliente.
- Contiene un pequeño formulario oculto (modal) que envía datos a `login_action.php` si un administrador quiere entrar.

---
---

# Documentación: `ficha_tecnica.php`

**Ubicación:** `public/ficha_tecnica.php`

---

## 1. Propósito
Es la pantalla de detalles. Cuando el cliente encuentra un repuesto en el buscador (ya sea escribiendo o usando la cámara), hace clic en él y es llevado a esta página. Su misión es mostrar toda la información técnica detallada de ese único repuesto.

## 2. Lógica Principal
1. Lee la barra de direcciones del navegador para buscar un número (ej: `ficha_tecnica.php?id=15`).
2. Usa ese ID (`15`) para preguntarle a la base de datos absolutamente todo sobre ese repuesto: fotos, marca, descripción, para qué motos sirve, características y si está disponible.
3. Si el ID no existe, muestra un mensaje amable diciendo "Repuesto no encontrado".
4. Dibuja la información en pantalla como si fuera un catálogo profesional, mostrando primero la foto principal y luego los detalles.

---
---

# Documentación: `reset_password.php`

**Ubicación:** `public/reset_password.php`

---

## 1. Propósito
Es una pantalla temporal y segura que le permite a un administrador crear una nueva contraseña cuando la olvidó. A esta pantalla **solo** se puede llegar a través del enlace secreto que el sistema envió al correo del administrador.

## 2. Lógica Principal
1. Revisa la barra de direcciones buscando el "token" (ej: `reset_password.php?token=abcd1234efgh...`).
2. Verifica en la base de datos (tabla `password_resets`) si ese código secreto es válido.
3. **Si es válido:** Le muestra un formulario al usuario para que escriba su nueva contraseña. Cuando la escribe, el sistema la encripta, actualiza su perfil y borra el código secreto para que no se pueda usar dos veces.
4. **Si es inválido:** Muestra un mensaje de error y no permite cambiar nada (evitando ataques cibernéticos).
