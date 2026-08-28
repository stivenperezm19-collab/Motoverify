# Documentación: `CategoriaModel.php`

**Ubicación:** `models/CategoriaModel.php`

---

## 1. Propósito
Este archivo administra todo lo relacionado con las categorías de los repuestos (por ejemplo: "Sistema de frenos", "Motor", "Suspensión"). Se encarga de agregar nuevas categorías a la base de datos o modificar las ya existentes, además de asegurar que no se creen categorías con el mismo nombre.

## 2. Lógica Principal
Al igual que los otros modelos, contiene un conjunto de funciones de base de datos seguras. Cuando un administrador quiere añadir o editar una categoría, este modelo es quien ejecuta la acción final en la tabla `CATEGORIA`.

## 3. Relación con Otros Archivos
- **¿Quién lo utiliza?** Es utilizado por `controllers/catalogo_action.php`, que es el archivo que recibe las instrucciones de la página de Catálogo del administrador.

## 4. Funciones Importantes
- **`checkNameExists($nombre)`**: Revisa si ya existe una categoría con ese nombre exacto. Ayuda a evitar duplicados en el catálogo.
- **`create($nombre, $descripcion)`**: Inserta una nueva fila en la base de datos con el nombre y la descripción de la nueva categoría, devolviendo el ID que se le asignó.
- **`update($id, $nombre, $descripcion)`**: Cambia el nombre o descripción de una categoría que ya existe, basándose en su ID.
