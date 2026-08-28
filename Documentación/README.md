# Documentación del Proyecto Motoverify

Bienvenido a la documentación oficial de **Motoverify**. Esta carpeta contiene las explicaciones detalladas y sencillas de todos los archivos que componen la plataforma.

El objetivo de esta documentación es que cualquier persona, con o sin conocimientos técnicos avanzados, pueda entender cómo funciona el sistema, para qué sirve cada pieza y cómo se conectan entre sí.

## Estructura de la Documentación

La documentación está organizada en las siguientes categorías (carpetas) para facilitar su consulta:

### 1. [Configuración y Base de Datos](1_Configuracion_y_BD/)
Archivos que se encargan de la conexión principal del sistema y de preparar el terreno para que todo funcione. Aquí encontrarás cómo nos conectamos a la base de datos y cómo interactuamos con servicios externos.

### 2. [Modelos de Datos](2_Modelos_de_Datos/)
En el sistema, los "Modelos" son los encargados directos de hablar con la base de datos (guardar, buscar, eliminar). Aquí explicamos cómo funcionan los modelos de Administrador, Categorías, Marcas y Repuestos.

### 3. [Controladores (Lógica)](3_Controladores_Logica/)
Los "Controladores" son el cerebro de las operaciones. Reciben las peticiones del usuario, procesan la información y toman decisiones (por ejemplo, validar un inicio de sesión o guardar un repuesto nuevo).

### 4. [Vistas Públicas (Frontend)](4_Vistas_Publicas/)
Explicación de las páginas que ven los clientes finales, como la pantalla principal de búsqueda y la ficha técnica detallada de cada repuesto.

### 5. [Vistas de Administrador (Panel)](5_Vistas_Administrador/)
Explicación de las pantallas internas exclusivas para los administradores, desde donde pueden gestionar el inventario, ver gráficas de inicio (Dashboard) y modificar su perfil.

---

*Nota: Esta documentación es un documento vivo y debe mantenerse actualizada a medida que el proyecto Motoverify siga creciendo.*
