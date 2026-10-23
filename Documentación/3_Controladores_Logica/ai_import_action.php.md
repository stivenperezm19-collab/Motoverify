# Documentación: `ai_import_action.php`

**Ubicación:** `controllers/ai_import_action.php`

---

## 1. Propósito
Este controlador funciona como un "asistente de lectura". Su propósito es ayudar al administrador a ingresar decenas de repuestos al mismo tiempo. En lugar de escribir los repuestos uno por uno, el administrador sube una foto de una factura o una lista impresa, y este archivo utiliza Inteligencia Artificial (OpenAI/ChatGPT) para "leer" la foto y extraer todos los repuestos en una lista ordenada.

## 2. Lógica Principal
1. Recibe una imagen (foto de un documento) o un archivo PDF desde el formulario.
2. Lee la clave secreta de OpenAI de `config/api.php`.
3. Envía el archivo a la IA pidiéndole que extraiga todos los repuestos que vea en la lista y los devuelva en un formato estructurado (formato JSON, ideal para computadoras).
4. La IA responde con la tabla de repuestos ya "digitada".
5. Este controlador pasa esa lista estructurada a la pantalla para que el administrador la revise antes de guardarla.

## 3. Relación con Otros Archivos
- **Depende de:** `config/api.php` (para las claves secretas).
- **Es utilizado por:** La herramienta de "Importación con IA" que se encuentra en `views/admin/repuestos.php?ai_import=true`.
- **Trabaja junto a:** `bulk_insert_action.php`, quien tomará esta lista extraída y la guardará finalmente en la base de datos.

## 4. Fragmento de Código Clave

```php
// Obtenemos la clave de OpenAI (ChatGPT)
$config = require '../config/api.php';
$apiKey = $config['openai_api_key'];

// Le damos instrucciones muy estrictas a la Inteligencia Artificial
$prompt = "Extrae los repuestos de este documento. 
Quiero que respondas ÚNICAMENTE con una lista en formato JSON 
que tenga estos campos: nombre, codigo_interno, marca, caracteristicas. 
No digas 'Hola', no escribas nada más, solo la lista.";

// (Oculto): Se envía a los servidores de OpenAI y se espera la respuesta
```
**¿Qué hace esto?**
El secreto de este archivo está en decirle a la IA que responda **únicamente** en código JSON. De esta manera, cuando la respuesta llega, el sistema Motoverify puede leerla matemáticamente y dibujar una tabla editable en la pantalla del usuario sin que se confunda leyendo frases conversacionales de la IA.
