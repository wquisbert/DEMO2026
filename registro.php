<?php
// registro.php
session_start();
require 'config.php';

$status = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre_completo']);
    $establecimiento = trim($_POST['establecimiento']);
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    if (!empty($nombre) && !empty($establecimiento) && !empty($usuario) && !empty($clave)) {
        
        // Verificar si el nombre de usuario ya existe
        $checkSql = "SELECT id FROM usuarios WHERE usuario = ?";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute([$usuario]);
        
        if ($checkStmt->fetch()) {
            $status = "exists";
        } else {
            // Encriptar contraseña de forma segura antes de guardar
            $clave_encriptada = password_hash($clave, PASSWORD_BCRYPT);

            $sql = "INSERT INTO usuarios (nombre_completo, establecimiento, usuario, clave) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute([$nombre, $establecimiento, $usuario, $clave_encriptada])) {
                $status = "success";
            } else {
                $status = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="bg-red-900 text-white text-center py-5 px-4">
            <h1 class="text-xl font-bold tracking-wide uppercase">Crear Cuenta Nueva</h1>
            <p class="text-xs text-red-200 mt-1">Formulario de registro del personal</p>
        </div>

        <form method="POST" action="registro.php" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                <input type="text" name="nombre_completo" required 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-800 focus:border-red-800 p-2 border text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Establecimiento / Centro de Salud</label>
                <input type="text" name="establecimiento" required placeholder="Ej. Centro de Salud Villa Salomé"
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-800 focus:border-red-800 p-2 border text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Usuario</label>
                <input type="text" name="usuario" required 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-800 focus:border-red-800 p-2 border text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="clave" required 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-800 focus:border-red-800 p-2 border text-sm">
            </div>

            <button type="submit" 
                    class="w-full bg-red-800 hover:bg-red-900 text-white font-bold py-2.5 px-4 rounded-md transition duration-200 shadow-md uppercase text-sm tracking-wider">
                Registrar Cuenta
            </button>

            <div class="text-center pt-1">
                <a href="login.php" class="text-sm text-gray-600 hover:text-gray-900 font-medium transition duration-150">
                    Volver al inicio de sesión
                </a>
            </div>
        </form>
    </div>

    <script>
        <?php if ($status == 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: '¡Registro Exitoso!',
                text: 'Tu cuenta ha sido creada. Ya puedes iniciar sesión.',
                confirmButtonColor: '#991b1b'
            }).then(() => {
                window.location.href = 'login';
            });
        <?php elseif ($status == 'exists'): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Usuario no disponible',
                text: 'El nombre de usuario ya se encuentra registrado.',
                confirmButtonColor: '#991b1b'
            });
        <?php elseif ($status == 'error'): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un problema interno al registrar los datos.',
                confirmButtonColor: '#991b1b'
            });
        <?php endif; ?>
    </script>
</body>
</html>