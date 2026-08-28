# Documentación: `MarcaModel.php`

**Ubicación:** `models/MarcaModel.php`

---

## 1. Propósito
Similar al modelo de categorías, este archivo administra las marcas de repuestos (ej. Yamaha, Honda, Bajaj). Permite verificar su existencia, crearlas, actualizarlas o eliminarlas (desactivarlas) de la base de datos.

## 2. Relación con Otros Archivos
- **¿Quién lo utiliza?** Principalmente `controllers/marcas_action.php`, el cual responde a las acciones que el administrador realiza en la vista `marcas.php`. También se consulta al crear repuestos para saber a qué marca pertenecen.

## 3. Funciones Importantes
- **`checkNameExists($nombre)`**: Evita la creación de marcas duplicadas comprobando el nombre.
- **`create($nombre, $descripcion)`**: Crea un registro nuevo de una marca.
- **`update($id, $nombre, $descripcion)`**: Actualiza el texto de una marca.
- **`delete($id)`**: Cambia el "estado" de una marca a Inactiva (o la elimina dependiendo de la implementación de la base de datos).

## 4. Diferencia clave con Repuestos
Las marcas en el sistema son una "entidad secundaria". Un repuesto (entidad principal) pertenece a una marca. Si se llegara a borrar una marca, los repuestos quedarían "huérfanos". Por eso este modelo maneja las marcas con mucho cuidado.
