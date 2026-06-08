<?php
// login.php
session_start();
require 'config.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: index");
    exit;
}

$error_login = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    if (!empty($usuario) && !empty($clave)) {
        // Ahora también seleccionamos el rol
        $sql = "SELECT id, nombre_completo, establecimiento, clave, rol FROM usuarios WHERE usuario = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($clave, $user['clave'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre_completo'];
            $_SESSION['establecimiento'] = $user['establecimiento'];
            $_SESSION['rol'] = $user['rol']; // Guardamos el rol en sesión

            header("Location: index");
            exit;
        } else {
            $error_login = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso al Sistema</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <!-- Encabezado Amarillo -->
        <div class="bg-yellow-500 text-gray-900 text-center py-6 px-4">
            <h1 class="text-2xl font-black tracking-wide uppercase">Sistema de Gestión</h1>
            <p class="text-sm text-gray-800 mt-1 font-medium">Módulo de Autenticación Único</p>
        </div>

        <form method="POST" action="login.php" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nombre de Usuario</label>
                <input type="text" name="usuario" required 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 p-2.5 border text-sm">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="clave" required 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 p-2.5 border text-sm">
            </div>

            <button type="submit" 
                    class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2.5 px-4 rounded-md transition duration-200 shadow-md uppercase text-sm tracking-wider">
                Iniciar Sesión
            </button>
        </form>
    </div>

    <script>
        <?php if ($error_login): ?>
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: 'El usuario o la contraseña son incorrectos.',
                confirmButtonColor: '#eab308' // yellow-500
            });
        <?php endif; ?>
    </script>
</body>
</html>