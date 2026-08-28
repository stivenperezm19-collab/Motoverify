# Documento de Requerimientos de Software — MotoVerify

**Versión:** 2.0  
**Fecha:** 27/08/2026  
**Organización:** Monomotos / MotoVerify  
**Autor original:** Jhoan Stiven Monje Pérez  
**Actualizado por:** Equipo de desarrollo MotoVerify

---

## Historial de Versiones

| Fecha | Versión | Autor | Organización | Descripción |
|---|---|---|---|---|
| 10/12/2025 | 1.0 | Jhoan Stiven Monje Pérez | MotoVerify | Primera documentación del proyecto |
| 27/08/2026 | 2.0 | Equipo de desarrollo MotoVerify | MotoVerify | Actualización completa basada en el sistema implementado. Se incorporan nuevas funcionalidades de IA (Google Gemini, OpenAI), importación masiva, escaneo QR, dashboard administrativo, gestión de perfil, recuperación de contraseña y arquitectura web MVC con PHP/MySQL |

---

## Información del Proyecto

| Campo | Detalle |
|---|---|
| **Empresa / Organización** | Monomotos / MotoVerify |
| **Proyecto** | Plataforma de compatibilidad, verificación e información de repuestos de motocicletas |
| **Fecha de preparación** | 10/12/2025 |
| **Última actualización** | 27/08/2026 |
| **Cliente** | Administrador del Taller / Personal de Ventas |
| **Patrocinador principal** | Jhoan Stiven Monje Pérez |
| **Gerente / Líder de Proyecto** | Aprendiz SENA |

---

## Aprobaciones

| Nombre y Apellido | Cargo | Departamento u Organización | Fecha | Firma |
|---|---|---|---|---|
| Andrea Saad Cano | Instructora | Centro de formación SENA Agroindustrial La Angostura | 10/12/2025 | |

---

## 1. Propósito

En este documento se especifican los requisitos del software **MotoVerify** en su versión **2.0**, actualizado conforme al sistema implementado y en producción.

MotoVerify es una plataforma web diseñada para talleres y vendedores de repuestos de motocicleta. El sistema facilita los procesos de búsqueda, reconocimiento visual mediante Inteligencia Artificial, verificación de compatibilidad e información detallada de repuestos. Asimismo, permite a los administradores gestionar el catálogo completo de repuestos, marcas y categorías, incluyendo importación masiva asistida por IA.

Este documento abarca todas las funciones implementadas en el sistema, incluyendo:
- Búsqueda inteligente de repuestos por texto, código de barras y código QR
- Reconocimiento visual de repuestos mediante IA (Google Gemini)
- Importación masiva de repuestos desde facturas/documentos mediante IA (OpenAI)
- Gestión completa de repuestos, marcas y categorías
- Sistema de autenticación seguro con recuperación de contraseña por correo
- Dashboard administrativo con estadísticas del inventario
- Ficha técnica detallada de cada repuesto

---

## 2. Alcance del Producto / Software

MotoVerify es una **aplicación web** basada en la arquitectura **MVC (Modelo-Vista-Controlador)** desarrollada con **PHP** y **MySQL**, accesible desde cualquier navegador web moderno en dispositivos de escritorio, tablets y smartphones.

### Alcance funcional implementado:

| Módulo | Descripción |
|---|---|
| **Página pública de búsqueda** | Permite a cualquier usuario (empleado/cliente) buscar repuestos por nombre, código interno o código de barras, con sugerencias en tiempo real |
| **Escaneo QR/código de barras** | Búsqueda instantánea mediante la cámara del dispositivo |
| **Ficha técnica** | Página detallada con toda la información de un repuesto: fotos, marca, categoría, descripción, características y compatibilidad |
| **Panel de administración (Dashboard)** | Vista general del inventario con marcas destacadas, últimos repuestos agregados y acceso rápido a herramientas de IA |
| **Gestión de repuestos** | CRUD completo con paginación, filtros por marca/categoría/texto, carga de múltiples imágenes y validación de códigos duplicados |
| **Gestión de marcas** | CRUD con carga de logos y vinculación automática a repuestos |
| **Gestión de categorías (catálogo)** | CRUD con validación de nombres duplicados |
| **Análisis visual con IA** | Captura de foto desde cámara y envío a Google Gemini para identificación automática del repuesto |
| **Importación masiva con IA** | Lectura de facturas/listas mediante OpenAI, extracción automática de datos y guardado masivo con creación automática de marcas/categorías |
| **Autenticación y seguridad** | Login con contraseñas encriptadas (bcrypt), sesiones seguras, verificación de identidad para cambios sensibles |
| **Recuperación de contraseña** | Envío de enlace seguro por correo con token temporal |
| **Gestión de perfil** | Actualización de nombre, correo y contraseña con doble verificación |

### Beneficios del sistema:

- **Agilidad operativa:** Búsqueda instantánea con múltiples métodos (texto, código, QR, cámara IA)
- **Reducción de errores:** Validación automática de datos duplicados y verificación asistida por IA
- **Productividad:** Importación masiva que ahorra horas de trabajo manual
- **Precisión:** Ficha técnica centralizada con información completa y actualizada
- **Seguridad:** Autenticación robusta y separación de roles

### Objetivos y metas:

1. Que el personal de ventas acceda a información precisa de repuestos en menos de 3 segundos
2. Que el administrador pueda mantener el catálogo actualizado de forma ágil
3. Reducir errores humanos en la identificación y compatibilidad de repuestos
4. Facilitar la integración de nuevos repuestos al sistema mediante tecnologías de IA

---

## 3. Referencias

| Documento | Autor | Fecha | Enlace |
|---|---|---|---|
| Técnicas de elicitación | Jhoan Monje | I trimestre, 7/12/2025 | [Google Docs](https://docs.google.com/document/d/1wTAmNRqOfK63jUbqsXTrat6QgQ7WCTtq/edit) |
| Documentación de caso de uso | Jhoan Monje | I trimestre, 29/09/2025 | [Google Sheets](https://docs.google.com/spreadsheets/d/1JEtG6zuPQ1LO7RumKV2FORP44qRaQetu/edit) |
| Documentación técnica del proyecto | Equipo MotoVerify | 2026 | [Carpeta de documentación](./README.md) |
| Esquema de base de datos | Equipo MotoVerify | 2026 | [consulta_repuesto.sql](../sql/consulta_repuesto.sql) |

---

## 4. Funcionalidades del Producto

A continuación se especifican las funcionalidades principales del sistema implementado:

### RF-01: Búsqueda inteligente de repuestos (Búsqueda en vivo)
El personal del área de ventas y cualquier usuario público puede realizar búsquedas de repuestos en tiempo real mediante una barra de búsqueda inteligente. A medida que el usuario escribe, el sistema consulta la base de datos y muestra sugerencias sin recargar la página (AJAX). Los criterios de búsqueda incluyen: nombre del repuesto, código interno y código de barras.

### RF-02: Escaneo y búsqueda por código QR / código de barras
El sistema permite activar la cámara del dispositivo para escanear códigos QR o de barras. Al detectar un código, el sistema (`qr_search.php`) busca automáticamente el repuesto correspondiente y redirige a su ficha técnica.

### RF-03: Ficha técnica detallada del repuesto
Al seleccionar un repuesto, el sistema muestra una página con toda la información técnica: fotografía principal, galería de imágenes, marca, categoría, descripción completa, características técnicas, disponibilidad y compatibilidad con modelos de motocicleta.

### RF-04: Registro y gestión de repuestos (CRUD)
El administrador puede:
- **Crear** nuevos repuestos con: nombre, código de barras, código interno, marca, categoría, descripción, características y múltiples imágenes
- **Editar** repuestos existentes (el formulario se rellena automáticamente con los datos actuales)
- **Eliminar** repuestos (con eliminación en cascada de las relaciones de imágenes)
- **Buscar y filtrar** repuestos por marca, categoría o texto libre
- El sistema implementa **paginación inteligente** (12 repuestos por página) para rendimiento óptimo
- **Validación de duplicados**: No se permite registrar dos repuestos con el mismo código de barras o código interno

### RF-05: Gestión de marcas
El administrador puede registrar nuevas marcas de motocicletas/repuestos con nombre, descripción y logotipo. También puede editar marcas existentes o desactivarlas. Se valida que no existan nombres duplicados.

### RF-06: Gestión de categorías (catálogo)
El administrador puede crear, editar y eliminar/desactivar categorías de repuestos (ejemplo: "Motor", "Sistema de frenos", "Eléctrico"). Se valida que no existan nombres duplicados. La eliminación es lógica (desactivación) para proteger la integridad de los repuestos asociados.

### RF-07: Análisis visual con Inteligencia Artificial (Google Gemini)
Desde el dashboard o la sección de repuestos, el administrador puede:
1. Abrir la cámara web o del celular
2. Tomar una fotografía de un repuesto desconocido
3. El sistema envía la imagen en formato Base64 a la API de Google Gemini
4. La IA analiza la imagen y responde con: nombre probable del repuesto, marca/modelo compatible y sugerencias de búsqueda
5. Los resultados se muestran en pantalla para facilitar el registro o la búsqueda

### RF-08: Importación masiva con Inteligencia Artificial (OpenAI)
El administrador puede:
1. Subir una foto de una factura, lista impresa o documento PDF
2. El sistema envía el documento a la API de OpenAI (ChatGPT)
3. La IA extrae automáticamente los datos de los repuestos en formato JSON (nombre, código interno, marca, características)
4. Se muestra una tabla editable para que el administrador revise antes de guardar
5. Al confirmar, el sistema (`bulk_insert_action.php`) guarda todos los repuestos de forma masiva, **creando automáticamente marcas y categorías nuevas** si no existen en el sistema

### RF-09: Autenticación y control de acceso
- **Inicio de sesión**: El administrador ingresa correo y contraseña. El sistema verifica las credenciales contra la base de datos usando `password_verify()` (bcrypt)
- **Sesiones**: Al autenticarse, se crea una sesión PHP (`$_SESSION`) que habilita el acceso al panel administrativo
- **Protección de rutas**: Todas las vistas del panel verifican la sesión activa; si no existe, redirigen a la página pública
- **Cierre de sesión**: Destrucción segura de la sesión, cookies y redirección a la página pública

### RF-10: Recuperación de contraseña
1. El administrador ingresa su correo en el formulario "Olvidé mi contraseña"
2. El sistema genera un token seguro y lo almacena en la tabla `password_resets`
3. Se envía un correo con un enlace único que contiene el token
4. Al acceder al enlace, se valida el token y se permite crear una nueva contraseña
5. La nueva contraseña se encripta y el token se invalida (uso único)
6. **Seguridad**: El sistema siempre muestra el mismo mensaje (exista o no el correo) para evitar enumeración de usuarios

### RF-11: Gestión de perfil del administrador
El administrador puede actualizar su nombre, correo electrónico y contraseña desde la sección de perfil. Para guardar cambios, se requiere **doble verificación**: el sistema solicita la contraseña actual del administrador mediante una petición AJAX a `verify_password.php` antes de permitir la actualización.

### RF-12: Dashboard administrativo
Al iniciar sesión, el administrador accede a un tablero principal que muestra:
- Las 7 marcas más importantes del inventario
- Los 6 últimos repuestos agregados al sistema
- Barra de navegación lateral con accesos directos a: Inicio, Marcas, Repuestos, Catálogo, Perfil
- Acceso a la herramienta de análisis visual con IA (modal de cámara)

---

## 5. Clases y Características de Usuarios

### Rol: Usuario Público / Personal del Área de Ventas

| Atributo | Descripción |
|---|---|
| **Característica** | Persona que consulta el sistema para verificar información de repuestos, compatibilidad y proporcionar datos precisos al cliente |
| **Frecuencia de uso** | Alta y recurrente durante la jornada laboral |
| **Nivel de experiencia** | Normal/básico — solo necesita saber buscar repuestos |
| **Autenticación** | No requiere inicio de sesión |

**Funcionalidades disponibles:**
- Búsqueda inteligente de repuestos por texto (nombre, código)
- Escaneo de código QR / código de barras con la cámara
- Visualización de la ficha técnica detallada del repuesto
- Acceso al catálogo de repuestos organizados por categoría/marca

---

### Rol: Administrador

| Atributo | Descripción |
|---|---|
| **Característica** | Persona encargada de administrar todo el sistema: gestionar el inventario de repuestos, marcas, categorías, y mantener la información actualizada para el personal de ventas |
| **Frecuencia de uso** | Alta — responsable de actualizar y agregar nuevos productos continuamente |
| **Nivel de experiencia** | Alto — debe conocer todas las funcionalidades del sistema |
| **Autenticación** | Requiere inicio de sesión con correo y contraseña |

**Funcionalidades disponibles:**
- Todas las funcionalidades del usuario público
- Dashboard con resumen del inventario
- CRUD completo de repuestos (crear, leer, editar, eliminar)
- CRUD de marcas con carga de logotipos
- CRUD de categorías (catálogo)
- Análisis visual de repuestos con IA (Google Gemini)
- Importación masiva de repuestos desde documentos con IA (OpenAI)
- Asignación y validación de códigos de barras
- Vinculación de repuesto a uno o varios modelos de motocicleta
- Gestión de perfil personal (nombre, correo, contraseña)
- Recuperación de contraseña por correo electrónico

---

## 6. Entorno Operativo

### Plataforma actual (v2.0):

| Componente | Especificación |
|---|---|
| **Tipo de aplicación** | Aplicación web (accesible desde navegador) |
| **Servidor web** | Apache (Laragon en desarrollo) |
| **Lenguaje backend** | PHP 7.4+ |
| **Base de datos** | MySQL 8.0 (Community Server) |
| **Frontend** | HTML5, CSS3 (Bootstrap), JavaScript (vanilla + AJAX) |
| **APIs externas** | Google Gemini API, OpenAI API |
| **Compatibilidad de navegadores** | Chrome 90+, Firefox 88+, Safari 14+, Edge 90+ |
| **Dispositivos compatibles** | PC escritorio, laptops, tablets, smartphones (diseño responsive) |
| **Servidor de pruebas** | localhost (127.0.0.1:3306) |
| **Herramienta de administración BD** | HeidiSQL 12.1 |

### Notas sobre el entorno original (v1.0):
En la versión original se planificó como aplicación móvil para Android 9+. La implementación actual es una **aplicación web responsive** accesible desde cualquier navegador, incluyendo navegadores móviles en Android e iOS.

---

## 7. Requerimientos Funcionales

### RF-01: Búsqueda inteligente de repuestos

| Campo | Descripción |
|---|---|
| **ID** | RF-01 |
| **Nombre** | Búsqueda inteligente de repuestos en tiempo real |
| **Descripción** | El sistema debe permitir al usuario buscar repuestos escribiendo en una barra de búsqueda. Las sugerencias aparecen en tiempo real sin recargar la página |
| **Prioridad** | Alta |
| **Actor** | Usuario público / Personal de ventas |
| **Entradas** | Texto de búsqueda (nombre, código interno, código de barras) |
| **Salidas** | Lista de sugerencias de repuestos que coinciden con la búsqueda |
| **Precondiciones** | El sistema debe estar accesible y la base de datos disponible |
| **Postcondiciones** | Se muestra la lista de resultados coincidentes |
| **Dependencias** | `public/index.php`, `controllers/qr_search.php`, `config/database.php` |

---

### RF-02: Escaneo de código QR / código de barras

| Campo | Descripción |
|---|---|
| **ID** | RF-02 |
| **Nombre** | Búsqueda por escaneo de código QR o de barras |
| **Descripción** | El sistema permite activar la cámara del dispositivo para escanear un código de barras o QR. Al detectar el código, busca el repuesto y redirige automáticamente a su ficha técnica |
| **Prioridad** | Alta |
| **Actor** | Usuario público / Personal de ventas |
| **Entradas** | Código escaneado por la cámara |
| **Salidas** | Redirección a la ficha técnica del repuesto encontrado, o mensaje de error si no existe |
| **Precondiciones** | El dispositivo debe tener cámara funcional y permisos de acceso |
| **Postcondiciones** | El usuario es redirigido a `ficha_tecnica.php?id=X` |
| **Dependencias** | `controllers/qr_search.php`, librería de escaneo QR (JavaScript) |

---

### RF-03: Ficha técnica detallada

| Campo | Descripción |
|---|---|
| **ID** | RF-03 |
| **Nombre** | Visualización de ficha técnica del repuesto |
| **Descripción** | Al seleccionar un repuesto, el sistema muestra toda su información: fotografía principal, galería, marca, categoría, descripción, características, disponibilidad y compatibilidad |
| **Prioridad** | Alta |
| **Actor** | Usuario público / Personal de ventas |
| **Entradas** | ID del repuesto (vía URL: `ficha_tecnica.php?id=X`) |
| **Salidas** | Página con toda la información detallada del repuesto |
| **Precondiciones** | El ID del repuesto debe existir en la base de datos |
| **Postcondiciones** | Se visualiza la ficha técnica completa |
| **Dependencias** | `public/ficha_tecnica.php`, `config/database.php`, tablas `REPUESTO`, `IMAGEN`, `REPUESTO_IMAGEN` |

---

### RF-04: Gestión completa de repuestos (CRUD)

| Campo | Descripción |
|---|---|
| **ID** | RF-04 |
| **Nombre** | Crear, leer, editar y eliminar repuestos |
| **Descripción** | El administrador puede gestionar el inventario completo de repuestos con validación de duplicados, carga de múltiples imágenes y paginación inteligente |
| **Prioridad** | Alta |
| **Actor** | Administrador |
| **Entradas** | Nombre, código de barras, código interno, marca, categoría, descripción, características, imágenes |
| **Salidas** | Confirmación de operación exitosa o mensaje de error |
| **Precondiciones** | El administrador debe haber iniciado sesión |
| **Postcondiciones** | El repuesto es creado/modificado/eliminado en la base de datos |
| **Reglas de validación** | No se permite código de barras duplicado; no se permite código interno duplicado; nombre obligatorio |
| **Dependencias** | `views/admin/repuestos.php`, `controllers/repuestos_action.php`, `models/RepuestoModel.php`, `models/ImagenModel.php` |

---

### RF-05: Gestión de marcas

| Campo | Descripción |
|---|---|
| **ID** | RF-05 |
| **Nombre** | Crear, editar y desactivar marcas de repuestos |
| **Descripción** | El administrador gestiona las marcas (Honda, Yamaha, Suzuki, etc.) con nombre, descripción y logotipo |
| **Prioridad** | Alta |
| **Actor** | Administrador |
| **Entradas** | Nombre de marca, descripción, imagen del logotipo |
| **Salidas** | Confirmación de operación o error por nombre duplicado |
| **Precondiciones** | Sesión de administrador activa |
| **Postcondiciones** | La marca es creada/modificada/desactivada en la base de datos |
| **Dependencias** | `views/admin/marcas.php`, `controllers/marcas_action.php`, `models/MarcaModel.php`, `models/ImagenModel.php` |

---

### RF-06: Gestión de categorías (catálogo)

| Campo | Descripción |
|---|---|
| **ID** | RF-06 |
| **Nombre** | Crear, editar y desactivar categorías de repuestos |
| **Descripción** | El administrador gestiona las categorías del inventario (Motor, Frenos, Eléctrico, Suspensión) |
| **Prioridad** | Media |
| **Actor** | Administrador |
| **Entradas** | Nombre de categoría, descripción |
| **Salidas** | Confirmación de operación o error por nombre duplicado |
| **Precondiciones** | Sesión de administrador activa |
| **Postcondiciones** | La categoría es creada/modificada/desactivada |
| **Dependencias** | `views/admin/catalogo.php`, `controllers/catalogo_action.php`, `models/CategoriaModel.php` |

---

### RF-07: Análisis visual con IA (Google Gemini)

| Campo | Descripción |
|---|---|
| **ID** | RF-07 |
| **Nombre** | Reconocimiento visual de repuestos con Inteligencia Artificial |
| **Descripción** | El administrador toma una foto de un repuesto desconocido y la IA identifica qué es, para qué marca/modelo podría servir y sugiere un nombre de búsqueda |
| **Prioridad** | Alta |
| **Actor** | Administrador |
| **Entradas** | Imagen capturada en formato Base64 |
| **Salidas** | Análisis textual de la IA: nombre probable, marca/modelo compatible, sugerencia de búsqueda |
| **Precondiciones** | Sesión activa, cámara funcional, clave API de Gemini válida |
| **Postcondiciones** | Se muestran los resultados del análisis en pantalla |
| **Dependencias** | `controllers/ai_analyze.php`, `config/api.php`, API de Google Gemini |

---

### RF-08: Importación masiva con IA (OpenAI)

| Campo | Descripción |
|---|---|
| **ID** | RF-08 |
| **Nombre** | Importación masiva de repuestos desde documentos mediante IA |
| **Descripción** | El administrador sube una foto de factura o lista impresa. La IA extrae los datos y genera una tabla editable. Al confirmar, se guardan todos los repuestos con creación automática de marcas/categorías inexistentes |
| **Prioridad** | Alta |
| **Actor** | Administrador |
| **Entradas** | Imagen de factura/documento o archivo PDF |
| **Salidas** | Tabla de repuestos extraídos en formato editable; guardado masivo en la BD |
| **Precondiciones** | Sesión activa, clave API de OpenAI válida |
| **Postcondiciones** | Los repuestos extraídos se almacenan en la base de datos |
| **Dependencias** | `controllers/ai_import_action.php`, `controllers/bulk_insert_action.php`, `config/api.php`, `models/RepuestoModel.php`, `models/MarcaModel.php`, `models/CategoriaModel.php` |

---

### RF-09: Autenticación y control de acceso

| Campo | Descripción |
|---|---|
| **ID** | RF-09 |
| **Nombre** | Sistema de autenticación seguro |
| **Descripción** | Login con correo y contraseña encriptada (bcrypt), gestión de sesiones PHP, protección de rutas administrativas y cierre de sesión seguro |
| **Prioridad** | Alta |
| **Actor** | Administrador |
| **Entradas** | Correo electrónico, contraseña |
| **Salidas** | Acceso al panel administrativo o mensaje de error |
| **Precondiciones** | El administrador debe estar registrado en la tabla `ADMINISTRADOR` |
| **Postcondiciones** | Se crea la sesión y se redirige al dashboard |
| **Dependencias** | `controllers/login_action.php`, `controllers/logout.php`, `models/AdministradorModel.php` |

---

### RF-10: Recuperación de contraseña

| Campo | Descripción |
|---|---|
| **ID** | RF-10 |
| **Nombre** | Recuperación de contraseña por correo electrónico |
| **Descripción** | Generación de token temporal, envío de correo con enlace seguro, validación del token y restablecimiento de contraseña |
| **Prioridad** | Media |
| **Actor** | Administrador |
| **Entradas** | Correo electrónico |
| **Salidas** | Correo con enlace de recuperación |
| **Precondiciones** | El correo debe estar registrado (aunque el sistema no revela si existe o no por seguridad) |
| **Postcondiciones** | La contraseña es actualizada y el token invalidado |
| **Dependencias** | `controllers/forgot_action.php`, `public/reset_password.php`, tabla `password_resets` |

---

### RF-11: Gestión de perfil

| Campo | Descripción |
|---|---|
| **ID** | RF-11 |
| **Nombre** | Actualización de perfil del administrador |
| **Descripción** | El administrador puede cambiar su nombre, correo y contraseña. Se requiere verificación de contraseña actual antes de guardar cambios |
| **Prioridad** | Media |
| **Actor** | Administrador |
| **Entradas** | Nombre, correo, contraseña actual (verificación), nueva contraseña (opcional) |
| **Salidas** | Confirmación de actualización o error |
| **Precondiciones** | Sesión activa y verificación de contraseña actual exitosa |
| **Postcondiciones** | Datos del perfil actualizados en la base de datos |
| **Dependencias** | `views/admin/perfil.php`, `controllers/verify_password.php`, `controllers/perfil_action.php`, `models/AdministradorModel.php` |

---

### RF-12: Dashboard administrativo

| Campo | Descripción |
|---|---|
| **ID** | RF-12 |
| **Nombre** | Tablero principal del administrador |
| **Descripción** | Vista general del inventario con las 7 marcas más importantes, los 6 últimos repuestos agregados, barra de navegación lateral y acceso a la herramienta de IA |
| **Prioridad** | Media |
| **Actor** | Administrador |
| **Entradas** | Sesión activa del administrador |
| **Salidas** | Página con resumen del inventario y accesos rápidos |
| **Precondiciones** | Sesión de administrador activa |
| **Postcondiciones** | Se visualiza el dashboard con información actualizada |
| **Dependencias** | `views/admin/dashboard.php`, `config/database.php` |

---

## 8. Reglas de Negocio

| ID | Regla | Descripción |
|---|---|---|
| RN-01 | Separación de roles | Los usuarios públicos/personal de ventas solo pueden consultar. Los administradores pueden gestionar todo el sistema |
| RN-02 | Unicidad de códigos | No se pueden registrar dos repuestos con el mismo código de barras o código interno |
| RN-03 | Unicidad de nombres | No se pueden crear dos marcas o dos categorías con el mismo nombre |
| RN-04 | Eliminación protegida | Las categorías y marcas se **desactivan** en lugar de eliminarse, para proteger la integridad de los repuestos asociados |
| RN-05 | Eliminación en cascada (repuestos) | Al eliminar un repuesto, primero se eliminan sus relaciones de imágenes (`REPUESTO_IMAGEN`) y luego el registro principal |
| RN-06 | Solo administradores actualizan | Solo los administradores autenticados pueden crear, modificar o eliminar registros del catálogo |
| RN-07 | Retroalimentación continua | Los administradores deben mantener el catálogo actualizado para el personal de ventas |
| RN-08 | Información precisa | La información de compatibilidad y características debe ser 100% verificada |
| RN-09 | Seguridad en contraseñas | Todas las contraseñas se almacenan encriptadas con bcrypt (`password_hash()`) |
| RN-10 | Verificación de identidad | Los cambios sensibles (actualización de perfil) requieren verificación de contraseña actual |
| RN-11 | Protección contra enumeración | El sistema de recuperación de contraseña muestra el mismo mensaje independientemente de si el correo existe o no |
| RN-12 | Creación automática (IA) | En la importación masiva, si la IA detecta marcas o categorías que no existen, el sistema las crea automáticamente |

---

## 9. Requerimientos de Interfaces Externas

### 9.1. Interfaces de Usuario

#### Página Pública (`public/index.php`)
- Barra de búsqueda inteligente con sugerencias en tiempo real (AJAX)
- Botón/ícono para activar el escáner de código QR/barras (cámara)
- Modal de inicio de sesión para administradores
- Diseño responsive con Bootstrap
- Catálogo visual de repuestos con tarjetas

#### Ficha Técnica (`public/ficha_tecnica.php`)
- Imagen principal del repuesto en formato catálogo profesional
- Galería de imágenes adicionales
- Datos: marca, categoría, descripción, características, disponibilidad, compatibilidad
- Mensaje amable de "Repuesto no encontrado" si el ID no existe

#### Página de Restablecimiento de Contraseña (`public/reset_password.php`)
- Formulario para ingresar nueva contraseña
- Validación de token vía URL (`?token=...`)
- Mensajes de error si el token es inválido o expirado

#### Dashboard Administrativo (`views/admin/dashboard.php`)
- Barra de navegación lateral (Inicio, Marcas, Repuestos, Catálogo, Perfil)
- Barra superior con información de sesión
- Sección de marcas destacadas (top 7)
- Sección de últimos repuestos (últimos 6)
- Modal de cámara para análisis visual con IA

#### Gestor de Repuestos (`views/admin/repuestos.php`)
- Tabla/listado de repuestos con paginación (12 por página)
- Filtros por marca, categoría y texto libre
- Formulario modal para crear/editar repuestos (reutilizable)
- Carga de múltiples imágenes
- Botón de análisis con IA (cámara)
- Herramienta de importación masiva con IA

#### Gestor de Marcas (`views/admin/marcas.php`)
- Lista de marcas en formato tarjetas/tabla
- Formulario modal para crear/editar marcas con carga de logotipo
- Botones de editar y eliminar

#### Gestor de Categorías (`views/admin/catalogo.php`)
- Lista de categorías en formato tarjetas/tabla
- Formulario modal para crear/editar categorías
- Botones de editar y eliminar

#### Perfil del Administrador (`views/admin/perfil.php`)
- Formulario con datos actuales del administrador
- Campos: nombre, correo electrónico, nueva contraseña (opcional)
- Modal de verificación de contraseña actual antes de guardar
- Botón de cerrar sesión

---

### 9.2. Interfaces de Hardware

| Dispositivo | Uso | Descripción |
|---|---|---|
| **Dispositivos móviles** | Personal de ventas | Smartphones con navegador web moderno para consultas rápidas durante la jornada |
| **Tablets** | Administradores | Dispositivos con pantalla más grande para gestionar y actualizar la información del catálogo |
| **PC/Laptop** | Administradores | Estaciones de trabajo para gestión completa del sistema |
| **Cámara integrada** | Todos | Cámara del dispositivo para escaneo de códigos QR/barras y captura de fotos para la IA |
| **Lector de códigos de barras** (opcional) | Todos | Dispositivo periférico USB para lectura rápida de códigos |

---

### 9.3. Interfaces de Software

| Componente | Descripción |
|---|---|
| **Base de datos MySQL** | Sistema de almacenamiento de toda la información: repuestos, marcas, categorías, administradores, imágenes. Base de datos `consulta_repuestos` con tablas: `REPUESTO`, `MARCA`, `CATEGORIA`, `ADMINISTRADOR`, `IMAGEN`, `REPUESTO_IMAGEN`, `MARCA_IMAGEN`, `password_resets` |
| **Módulo de autenticación** | Sistema de login con sesiones PHP, contraseñas encriptadas con bcrypt, verificación de identidad y control de acceso por roles |
| **Interfaz gráfica (Frontend)** | Páginas web con HTML5, CSS3 (Bootstrap), JavaScript. Diseño responsive para todos los dispositivos |
| **API Google Gemini** | Servicio externo de IA para análisis visual de imágenes de repuestos |
| **API OpenAI (ChatGPT)** | Servicio externo de IA para lectura e interpretación de documentos/facturas y extracción de datos |
| **Sistema de archivos** | Gestión de archivos subidos (imágenes) en carpetas `uploads/repuestos/` y `uploads/marcas/` |
| **Servidor de correo** | Función `mail()` de PHP para envío de correos de recuperación de contraseña |

---

### 9.4. Interfaces de Comunicación

| Aspecto | Especificación |
|---|---|
| **Protocolo** | HTTP/HTTPS |
| **Formato de datos** | JSON para comunicación AJAX entre frontend y backend |
| **Red** | Funciona en red local (localhost) o Internet según la ubicación del servidor |
| **Sincronización** | Las consultas y actualizaciones se sincronizan en tiempo real con el servidor de base de datos |
| **Seguridad** | HTTPS cuando está disponible; contraseñas encriptadas con bcrypt |
| **APIs externas** | Comunicación HTTPS con servidores de Google (Gemini) y OpenAI mediante API keys |
| **AJAX** | Comunicación asíncrona JavaScript-PHP para búsquedas en vivo, verificación de contraseña y operaciones CRUD sin recarga de página |

---

## 10. Requerimientos No Funcionales

| ID | Categoría | Descripción |
|---|---|---|
| **RNF-01** | Rendimiento | El sistema debe mostrar resultados de búsqueda y compatibilidad en **menos de 3 segundos** por consulta |
| **RNF-02** | Rendimiento | La paginación inteligente debe cargar máximo 12 repuestos por página, garantizando tiempos de carga instantáneos incluso con miles de registros |
| **RNF-03** | Disponibilidad | El sistema debe mantener una disponibilidad mínima del **99.5%** de tiempo operativo |
| **RNF-04** | Fiabilidad | La información de compatibilidad debe ser **100% precisa y verificada** |
| **RNF-05** | Usabilidad | La interfaz debe ser intuitiva y fácil de usar, accesible para usuarios con experiencia empírica. Diseño responsive adaptable a móviles, tablets y escritorio |
| **RNF-06** | Mantenibilidad | El sistema debe permitir la actualización de catálogos y reglas de compatibilidad sin modificaciones al código principal (gestión dinámica desde el panel de administración) |
| **RNF-07** | Seguridad | Todas las contraseñas deben almacenarse encriptadas con bcrypt. Las consultas SQL deben usar sentencias preparadas para prevenir inyección SQL |
| **RNF-08** | Seguridad | Las APIs de IA deben usar claves secretas almacenadas en archivos de configuración no públicos (`config/api.php`) |
| **RNF-09** | Escalabilidad | La arquitectura MVC permite agregar nuevos módulos sin afectar los existentes |
| **RNF-10** | Portabilidad | Al ser una aplicación web, es accesible desde cualquier dispositivo con navegador moderno (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+) |

---

## 11. Otros Requerimientos

- **Base de datos relacional** MySQL 8.0 para almacenar información de repuestos, marcas, categorías, administradores e imágenes
- **Servidor web** Apache con soporte para PHP 7.4+
- **Conexión a Internet** requerida para las funcionalidades de IA (Google Gemini y OpenAI)
- **Certificado SSL** recomendado para producción (HTTPS)
- **Copias de seguridad** regulares de la base de datos y la carpeta `uploads/`

---

## 12. Glosario

| Término | Definición |
|---|---|
| **Administrador** | Usuario con acceso completo al panel de gestión. Puede registrar, editar y eliminar repuestos, marcas y categorías |
| **Empleado / Personal de ventas** | Usuario que solo consulta el sistema para buscar y verificar información de repuestos |
| **Base de Datos** | Sistema de almacenamiento MySQL (`consulta_repuestos`) donde se guarda toda la información del sistema |
| **Catálogo** | Lista organizada de categorías de repuestos registradas en el sistema (ej: Motor, Frenos, Eléctrico) |
| **Categoría** | Clasificación de repuestos por tipo o función (ej: "Sistema de frenos", "Suspensión") |
| **Compatibilidad** | Relación entre un repuesto y uno o varios modelos de motocicleta con los que es compatible |
| **Código de barras** | Identificador único asignado a cada repuesto para búsqueda rápida mediante escaneo |
| **Código interno** | Identificador alfanumérico único del repuesto dentro del inventario del taller |
| **Código QR** | Código de respuesta rápida que puede ser escaneado con la cámara para buscar un repuesto |
| **CRUD** | Siglas de Create, Read, Update, Delete (Crear, Leer, Actualizar, Eliminar) — operaciones básicas de gestión de datos |
| **Dashboard** | Tablero principal del administrador con resumen visual del inventario |
| **Ficha técnica** | Página con toda la información detallada de un repuesto específico |
| **Google Gemini** | Servicio de Inteligencia Artificial de Google utilizado para reconocimiento visual de repuestos |
| **Interfaz de usuario** | Pantallas y formularios del sistema con los que interactúa el usuario |
| **MVC** | Modelo-Vista-Controlador: patrón de arquitectura de software utilizado en MotoVerify |
| **Modelo de motocicleta** | Combinación de marca, modelo y año de una motocicleta |
| **OpenAI** | Servicio de Inteligencia Artificial (ChatGPT) utilizado para importación masiva desde documentos |
| **Repuesto** | Pieza o componente de motocicleta registrada en el sistema con código, nombre, descripción e imágenes |
| **Sesión** | Mecanismo de PHP (`$_SESSION`) que mantiene la autenticación del administrador mientras navega el panel |
| **Token** | Cadena de texto aleatoria y temporal utilizada para la recuperación segura de contraseña |
| **AJAX** | Técnica de comunicación asíncrona que permite actualizar partes de la página sin recargarla completamente |
| **bcrypt** | Algoritmo de encriptación de contraseñas utilizado por `password_hash()` y `password_verify()` en PHP |
| **Bootstrap** | Framework CSS utilizado para el diseño responsive de la interfaz |
| **JSON** | Formato de datos ligero utilizado para la comunicación entre el frontend y el backend |

---

## 13. Arquitectura del Sistema (v2.0)

```
MotoVerify/
├── config/                          # Configuración
│   ├── database.php                 # Conexión a MySQL
│   └── api.php                      # Claves de APIs (Gemini, OpenAI)
├── models/                          # Modelos de datos (capa de datos)
│   ├── AdministradorModel.php       # Gestión de administradores
│   ├── RepuestoModel.php            # Gestión de repuestos
│   ├── MarcaModel.php               # Gestión de marcas
│   ├── CategoriaModel.php           # Gestión de categorías
│   └── ImagenModel.php              # Gestión de imágenes
├── controllers/                     # Controladores (lógica de negocio)
│   ├── login_action.php             # Autenticación
│   ├── logout.php                   # Cierre de sesión
│   ├── forgot_action.php            # Recuperación de contraseña
│   ├── verify_password.php          # Verificación de identidad
│   ├── perfil_action.php            # Actualización de perfil
│   ├── repuestos_action.php         # CRUD de repuestos
│   ├── marcas_action.php            # CRUD de marcas
│   ├── catalogo_action.php          # CRUD de categorías
│   ├── qr_search.php                # Búsqueda por código QR/barras
│   ├── ai_analyze.php               # Análisis visual con Gemini
│   ├── ai_import_action.php         # Importación con OpenAI
│   └── bulk_insert_action.php       # Inserción masiva
├── views/admin/                     # Vistas del panel administrativo
│   ├── dashboard.php                # Tablero principal
│   ├── repuestos.php                # Gestor de repuestos
│   ├── marcas.php                   # Gestor de marcas
│   ├── catalogo.php                 # Gestor de categorías
│   └── perfil.php                   # Perfil del administrador
├── public/                          # Vistas públicas
│   ├── index.php                    # Página principal de búsqueda
│   ├── ficha_tecnica.php            # Ficha técnica del repuesto
│   ├── reset_password.php           # Restablecimiento de contraseña
│   ├── css/                         # Hojas de estilo
│   └── assets/                      # Recursos estáticos
├── uploads/                         # Archivos subidos
│   ├── repuestos/                   # Imágenes de repuestos
│   └── marcas/                      # Logotipos de marcas
├── sql/                             # Scripts de base de datos
│   └── consulta_repuesto.sql        # Esquema de la BD
└── Documentación/                   # Documentación del proyecto
```

---

*Este documento es un documento vivo y debe mantenerse actualizado a medida que el proyecto MotoVerify siga creciendo y evolucionando.*
