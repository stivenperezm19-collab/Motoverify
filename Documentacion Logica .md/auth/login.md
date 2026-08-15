Login funciona mediante los name de los dos campo de que es email y password y el method="POST". 
y el boton submit lo envia al archivo loginController.php. 
el logincontroller esta conectado al modelo Usuario.php mediante require_once __DIR__ . '/../../models/Usuario.php';.
y mediante una clase AuthController. contiene todas las condiciones para la validacion de los datos ingresados en el formulario de login. y hay funcion especifica que contiene case '1', '2', '3' para redirigir al usuario a su respectivo dashboard. y esta conectado a la base de datos.