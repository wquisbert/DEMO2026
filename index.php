<?php
session_start();
require 'config.php';

// Control de acceso
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login");
    exit;
}

$es_admin = ($_SESSION['rol'] === 'admin');
$status = null;
$status_msg = "";

// PROCESAMIENTO DE FORMULARIOS (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Acciones Pacientes
    if ($_POST['action'] == 'add_paciente') {
        $fecha_nac = $_POST['fecha_nacimiento'];
        $edad = $_POST['edad'];
        $diagnostico = trim($_POST['diagnostico']);
        $establecimiento = $_SESSION['establecimiento'];
        $usuario_id = $_SESSION['usuario_id'];

        $sql = "INSERT INTO pacientes (usuario_id, establecimiento, fecha_nacimiento, edad, diagnostico) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([$usuario_id, $establecimiento, $fecha_nac, $edad, $diagnostico])) {
            $status = "success"; $status_msg = "Paciente registrado correctamente.";
        }
    }

    if ($_POST['action'] == 'edit_paciente') {
        $id_paciente = $_POST['paciente_id'];
        $fecha_nac = $_POST['edit_fecha'];
        $edad = $_POST['edit_edad'];
        $diagnostico = trim($_POST['edit_diagnostico']);

        $sql = "UPDATE pacientes SET fecha_nacimiento = ?, edad = ?, diagnostico = ? WHERE id = ?" . (!$es_admin ? " AND usuario_id = ?" : "");
        $params = !$es_admin ? [$fecha_nac, $edad, $diagnostico, $id_paciente, $_SESSION['usuario_id']] : [$fecha_nac, $edad, $diagnostico, $id_paciente];
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $status = "success"; $status_msg = "Datos del paciente actualizados.";
        }
    }

    if ($_POST['action'] == 'delete_paciente') {
        $id_paciente = $_POST['delete_id'];
        $sql = "DELETE FROM pacientes WHERE id = ?" . (!$es_admin ? " AND usuario_id = ?" : "");
        $params = !$es_admin ? [$id_paciente, $_SESSION['usuario_id']] : [$id_paciente];
        
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $status = "success"; $status_msg = "Registro clínico eliminado.";
        }
    }

    // Acciones Usuarios (Solo Admin)
    if ($es_admin) {
        if ($_POST['action'] == 'admin_add_user') {
            $nombre = trim($_POST['new_nombre']);
            $estab = trim($_POST['new_establecimiento']);
            $user = trim($_POST['new_usuario']);
            $clave = password_hash($_POST['new_clave'], PASSWORD_BCRYPT);
            $rol = $_POST['new_rol'];

            $check = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $check->execute([$user]);
            if ($check->fetch()) {
                $status = "warning"; $status_msg = "El nombre de usuario ya existe.";
            } else {
                $sql = "INSERT INTO usuarios (nombre_completo, establecimiento, usuario, clave, rol) VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$nombre, $estab, $user, $clave, $rol])) {
                    $status = "success"; $status_msg = "Nuevo personal dado de alta.";
                }
            }
        }

        if ($_POST['action'] == 'edit_usuario') {
            $id_user = $_POST['usuario_id'];
            $nombre = trim($_POST['edit_user_nombre']);
            $estab = trim($_POST['edit_user_estab']);
            $user = trim($_POST['edit_user_login']);
            $rol = $_POST['edit_user_rol'];
            
            if (!empty($_POST['edit_user_clave'])) {
                $clave = password_hash($_POST['edit_user_clave'], PASSWORD_BCRYPT);
                $sql = "UPDATE usuarios SET nombre_completo = ?, establecimiento = ?, usuario = ?, rol = ?, clave = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $estab, $user, $rol, $clave, $id_user]);
            } else {
                $sql = "UPDATE usuarios SET nombre_completo = ?, establecimiento = ?, usuario = ?, rol = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $estab, $user, $rol, $id_user]);
            }
            $status = "success"; $status_msg = "Datos del usuario actualizados.";
        }

        if ($_POST['action'] == 'delete_usuario') {
            $id_user = $_POST['delete_id'];
            if ($id_user != $_SESSION['usuario_id']) {
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$id_user])) {
                    $status = "success"; $status_msg = "Cuenta de usuario eliminada.";
                }
            } else {
                $status = "warning"; $status_msg = "No puedes eliminar tu propia cuenta activa.";
            }
        }
    }
}

// CONSULTAS Y ESTADÍSTICAS
if ($es_admin) {
    $stmt = $pdo->prepare("SELECT p.*, u.nombre_completo AS registrado_por FROM pacientes p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.fecha_registro DESC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM pacientes WHERE usuario_id = ? ORDER BY fecha_registro DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
}
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_pacientes = count($pacientes);

$usuarios = [];
$total_usuarios = 0;
if ($es_admin) {
    $stmt_us = $pdo->prepare("SELECT id, nombre_completo, establecimiento, usuario, rol, creado_en FROM usuarios ORDER BY creado_en DESC");
    $stmt_us->execute();
    $usuarios = $stmt_us->fetchAll(PDO::FETCH_ASSOC);
    $total_usuarios = count($usuarios);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Departamental Único</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button.current { background: #eab308 !important; color: #111827 !important; border: 1px solid #eab308 !important; font-weight: bold; border-radius: 0.375rem; }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #fef08a !important; color: #111827 !important; border: 1px solid #eab308 !important; }
        .dt-buttons { margin-bottom: 1rem; }
        button.dt-button.btn-excel {
            background: #10b981 !important; color: white !important; border: none !important; border-radius: 0.375rem !important;
            padding: 0.5rem 1rem !important; font-weight: bold !important; font-size: 0.875rem !important;
            transition: all 0.3s ease-in-out !important; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        }
        button.dt-button.btn-excel:hover { background: #059669 !important; transform: translateY(-2px) !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; }
        /* Animaciones para las pestañas */
        .fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased relative selection:bg-yellow-300 selection:text-gray-900">

    <form id="formDinamicoEliminar" method="POST" class="hidden">
        <input type="hidden" name="action" id="accion_eliminar">
        <input type="hidden" name="delete_id" id="id_eliminar">
    </form>

    <nav class="bg-gradient-to-r from-yellow-500 to-yellow-400 text-gray-900 shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-hospital-user text-2xl"></i>
                <div>
                    <h1 class="text-xl font-black tracking-wide uppercase">Sistema Departamental Único</h1>
                    <p class="text-xs font-bold">
                        <?= htmlspecialchars($_SESSION['nombre']) ?> 
                        <?php if($es_admin): ?> <span class="bg-gray-900 text-yellow-400 px-2 py-0.5 rounded text-[10px] ml-1 uppercase shadow-sm">Admin</span> <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden md:inline-block text-sm border border-gray-900 px-3 py-1 rounded-md font-bold bg-white bg-opacity-20 shadow-inner">
                    <i class="fa-solid fa-location-dot mr-1"></i> <?= htmlspecialchars($_SESSION['establecimiento']) ?>
                </span>
                <button onclick="cerrarSesion()" class="bg-gray-900 hover:bg-gray-800 text-white text-sm px-4 py-2 rounded-md font-bold shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                    <span>Salir</span> <i class="fa-solid fa-right-from-bracket"></i>
                </button>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-yellow-500 flex justify-between items-center transform hover:scale-[1.01] transition-transform">
            <div>
                <h3 class="text-gray-500 text-xs font-black uppercase tracking-wider mb-1">Total de Pacientes</h3>
                <span class="text-3xl font-black text-gray-900"><?= $total_pacientes ?></span>
            </div>
            <div class="bg-yellow-100 p-3 rounded-full">
                <i class="fa-solid fa-notes-medical text-3xl text-yellow-500"></i>
            </div>
        </div>
        
        <?php if($es_admin): ?>
        <div class="bg-white p-5 rounded-xl shadow-md border-l-4 border-gray-900 flex justify-between items-center transform hover:scale-[1.01] transition-transform">
            <div>
                <h3 class="text-gray-500 text-xs font-black uppercase tracking-wider mb-1">Personal Activo</h3>
                <span class="text-3xl font-black text-gray-900"><?= $total_usuarios ?></span>
            </div>
            <div class="bg-gray-100 p-3 rounded-full">
                <i class="fa-solid fa-user-doctor text-3xl text-gray-900"></i>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 mt-8 flex gap-2 border-b-2 border-gray-200">
        <button onclick="cambiarTab('pacientes')" id="btn_tab_pacientes" class="px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-yellow-500 text-gray-900 transition-colors shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-bed-pulse"></i> Módulo Clínico
        </button>
        
        <?php if($es_admin): ?>
        <button onclick="cambiarTab('usuarios')" id="btn_tab_usuarios" class="px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-gray-200 text-gray-500 hover:bg-gray-300 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-users-gear"></i> Gestión de Usuarios
        </button>
        <?php endif; ?>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6">

        <div id="seccion_pacientes" class="grid grid-cols-1 lg:grid-cols-4 gap-8 fade-in">
            <div class="lg:col-span-1">
                <div class="bg-white p-5 rounded-xl shadow-md border-t-4 border-yellow-500">
                    <h2 class="text-md font-black text-gray-900 mb-4 uppercase border-b pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-yellow-500"></i> Nuevo Paciente
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_paciente">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" required class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-yellow-400 outline-none" onchange="calcularEdad('fecha_nacimiento', 'edad')">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Edad</label>
                            <input type="number" name="edad" id="edad" readonly required class="w-full bg-gray-100 border-gray-300 rounded-lg p-2.5 border font-bold text-gray-600">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Diagnóstico</label>
                            <textarea name="diagnostico" required rows="2" class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-yellow-400 outline-none resize-none"></textarea>
                        </div>
                        <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-black py-2.5 px-4 rounded-lg text-xs uppercase shadow-md hover:shadow-lg transition-all">Registrar Guardar</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
                    <div class="overflow-x-auto pb-4">
                        <table id="tablaPacientes" class="display min-w-full text-sm text-left text-gray-700 w-full">
                            <thead class="bg-yellow-50 text-gray-900 border-b-2 border-yellow-500 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3">Fecha Nac.</th>
                                    <th class="px-4 py-3">Edad</th>
                                    <th class="px-4 py-3">Diagnóstico</th>
                                    <?php if($es_admin): ?>
                                        <th class="px-4 py-3">Establecimiento</th>
                                        <th class="px-4 py-3">Registrado por</th>
                                    <?php endif; ?>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($pacientes as $p): ?>
                                <tr class="hover:bg-yellow-50">
                                    <td class="px-4 py-2.5"><?= htmlspecialchars($p['fecha_nacimiento']) ?></td>
                                    <td class="px-4 py-2.5 font-black text-gray-900"><?= htmlspecialchars($p['edad']) ?> a</td>
                                    <td class="px-4 py-2.5 truncate max-w-[200px]" title="<?= htmlspecialchars($p['diagnostico']) ?>"><?= htmlspecialchars($p['diagnostico']) ?></td>
                                    <?php if($es_admin): ?>
                                        <td class="px-4 py-2.5 text-xs"><?= htmlspecialchars($p['establecimiento']) ?></td>
                                        <td class="px-4 py-2.5 text-xs"><?= htmlspecialchars($p['registrado_por']) ?></td>
                                    <?php endif; ?>
                                    <td class="px-4 py-2.5 text-center flex justify-center gap-2">
                                        <button onclick="abrirModalPaciente(<?= $p['id'] ?>, '<?= $p['fecha_nacimiento'] ?>', <?= $p['edad'] ?>, '<?= addslashes(htmlspecialchars($p['diagnostico'])) ?>')" class="bg-amber-100 hover:bg-yellow-400 text-yellow-900 font-bold w-8 h-8 rounded-full shadow-sm hover:scale-110 transition-all flex items-center justify-center"><i class="fa-solid fa-pen"></i></button>
                                        <button onclick="ejecutarEliminar('delete_paciente', <?= $p['id'] ?>)" class="bg-red-50 hover:bg-red-500 text-red-600 hover:text-white font-bold w-8 h-8 rounded-full shadow-sm hover:scale-110 transition-all flex items-center justify-center"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <?php if($es_admin): ?>
        <div id="seccion_usuarios" class="grid grid-cols-1 lg:grid-cols-4 gap-8 hidden fade-in">
            <div class="lg:col-span-1">
                <div class="bg-gray-50 p-5 rounded-xl shadow-md border-t-4 border-gray-900">
                    <h2 class="text-md font-black text-gray-900 mb-4 uppercase border-b border-gray-300 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-gray-900"></i> Alta Personal
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="admin_add_user">
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre Completo</label><input type="text" name="new_nombre" required class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-gray-800 outline-none bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Establecimiento</label><input type="text" name="new_establecimiento" required class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-gray-800 outline-none bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Usuario</label><input type="text" name="new_usuario" required class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-gray-800 outline-none bg-white"></div>
                        <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Contraseña</label><input type="password" name="new_clave" required class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-gray-800 outline-none bg-white"></div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Rol</label>
                            <select name="new_rol" class="w-full border-gray-300 rounded-lg p-2.5 border focus:ring-2 focus:ring-gray-800 outline-none bg-white"><option value="usuario">Usuario Estándar</option><option value="admin">Administrador General</option></select>
                        </div>
                        <button type="submit" class="w-full bg-gray-900 hover:bg-gray-800 text-white font-black py-2.5 px-4 rounded-lg text-xs uppercase shadow-md hover:shadow-lg transition-all">Crear Cuenta</button>
                    </form>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div class="bg-white p-6 rounded-xl shadow-md border border-gray-100">
                    <div class="overflow-x-auto pb-4">
                        <table id="tablaUsuarios" class="display min-w-full text-sm text-left text-gray-700 w-full">
                            <thead class="bg-gray-100 text-gray-900 border-b-2 border-gray-800 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3">Nombre Completo</th>
                                    <th class="px-4 py-3">Establecimiento</th>
                                    <th class="px-4 py-3">Usuario</th>
                                    <th class="px-4 py-3">Rol</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($usuarios as $u): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2.5 font-bold"><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                    <td class="px-4 py-2.5 text-xs"><?= htmlspecialchars($u['establecimiento']) ?></td>
                                    <td class="px-4 py-2.5 text-xs font-mono bg-gray-100 rounded px-2"><?= htmlspecialchars($u['usuario']) ?></td>
                                    <td class="px-4 py-2.5 text-xs uppercase font-black <?= $u['rol'] == 'admin' ? 'text-yellow-600' : 'text-gray-500' ?>"><?= htmlspecialchars($u['rol']) ?></td>
                                    <td class="px-4 py-2.5 text-center flex justify-center gap-2">
                                        <button onclick="abrirModalUsuario(<?= $u['id'] ?>, '<?= addslashes($u['nombre_completo']) ?>', '<?= addslashes($u['establecimiento']) ?>', '<?= addslashes($u['usuario']) ?>', '<?= $u['rol'] ?>')" class="bg-blue-50 hover:bg-blue-500 text-blue-700 hover:text-white font-bold w-8 h-8 rounded-full shadow-sm hover:scale-110 transition-all flex items-center justify-center"><i class="fa-solid fa-pen"></i></button>
                                        <button onclick="ejecutarEliminar('delete_usuario', <?= $u['id'] ?>)" class="bg-red-50 hover:bg-red-500 text-red-600 hover:text-white font-bold w-8 h-8 rounded-full shadow-sm hover:scale-110 transition-all flex items-center justify-center"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <div id="modalPaciente" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h2 class="text-lg font-black text-gray-900 mb-4 uppercase border-b pb-2 flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-yellow-500"></i> Modificar Diagnóstico</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_paciente">
                <input type="hidden" name="paciente_id" id="modal_pac_id">
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Fecha Nacimiento</label><input type="date" name="edit_fecha" id="modal_pac_fecha" required class="w-full border-gray-300 rounded-lg p-2.5 border" onchange="calcularEdad('modal_pac_fecha', 'modal_pac_edad')"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Edad</label><input type="number" name="edit_edad" id="modal_pac_edad" readonly class="w-full bg-gray-100 border-gray-300 rounded-lg p-2.5 border font-bold"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Diagnóstico</label><textarea name="edit_diagnostico" id="modal_pac_diag" required rows="3" class="w-full border-gray-300 rounded-lg p-2.5 border"></textarea></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" onclick="cerrarModal('modalPaciente')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2.5 px-5 rounded-lg text-xs">Cancelar</button><button type="submit" class="bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-black py-2.5 px-5 rounded-lg text-xs uppercase shadow-md hover:shadow-lg">Guardar</button></div>
            </form>
        </div>
    </div>

    <?php if ($es_admin): ?>
    <div id="modalUsuario" class="fixed inset-0 bg-black bg-opacity-60 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 border-t-4 border-gray-900">
            <h2 class="text-lg font-black text-gray-900 mb-4 uppercase border-b pb-2 flex items-center gap-2"><i class="fa-solid fa-user-pen"></i> Editar Personal</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_usuario"><input type="hidden" name="usuario_id" id="modal_usr_id">
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nombre</label><input type="text" name="edit_user_nombre" id="modal_usr_nombre" required class="w-full border-gray-300 rounded-lg p-2.5 border"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Establecimiento</label><input type="text" name="edit_user_estab" id="modal_usr_estab" required class="w-full border-gray-300 rounded-lg p-2.5 border"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Usuario</label><input type="text" name="edit_user_login" id="modal_usr_login" required class="w-full border-gray-300 rounded-lg p-2.5 border"></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Rol</label><select name="edit_user_rol" id="modal_usr_rol" class="w-full border-gray-300 rounded-lg p-2.5 border"><option value="usuario">Usuario</option><option value="admin">Administrador</option></select></div>
                <div><label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nueva Contraseña (Opcional)</label><input type="password" name="edit_user_clave" class="w-full border-gray-300 rounded-lg p-2.5 border"></div>
                <div class="flex justify-end gap-3 pt-4"><button type="button" onclick="cerrarModal('modalUsuario')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2.5 px-5 rounded-lg text-xs">Cancelar</button><button type="submit" class="bg-gray-900 hover:bg-gray-800 text-white font-black py-2.5 px-5 rounded-lg text-xs uppercase shadow-md hover:shadow-lg">Actualizar</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        $(document).ready(function() {
            // Inicialización de Excel para ambas tablas, excluyendo siempre la última columna de acciones.
            let opcionesExportacion = {
                pageLength: 5,
                language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                dom: '<"flex flex-col md:flex-row justify-between items-center mb-4 gap-2"Bf>rt<"flex justify-between items-center mt-4"ip>',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '<i class="fa-regular fa-file-excel mr-1"></i> Exportar a Excel',
                    className: 'btn-excel'
                }]
            };

            // Tabla Pacientes
            let tablaPac = $('#tablaPacientes').DataTable($.extend(true, {}, opcionesExportacion, {
                buttons: [{ 
                    title: 'Reporte Clínico de Pacientes', 
                    exportOptions: { columns: <?= $es_admin ? "':not(:eq(5))'" : "':not(:eq(3))'" ?> } 
                }]
            }));

            // Tabla Usuarios
            if($('#tablaUsuarios').length) { 
                let tablaUsu = $('#tablaUsuarios').DataTable($.extend(true, {}, opcionesExportacion, {
                    buttons: [{ 
                        title: 'Directorio del Personal', 
                        exportOptions: { columns: ':not(:eq(4))' } 
                    }]
                })); 
            }
        });

        // Lógica del Menú de Pestañas
        function cambiarTab(tabName) {
            if (tabName === 'pacientes') {
                document.getElementById('seccion_pacientes').classList.remove('hidden');
                document.getElementById('seccion_usuarios').classList.add('hidden');
                
                document.getElementById('btn_tab_pacientes').className = "px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-yellow-500 text-gray-900 shadow-sm flex items-center gap-2";
                document.getElementById('btn_tab_usuarios').className = "px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-gray-200 text-gray-500 hover:bg-gray-300 transition-colors flex items-center gap-2";
            } else {
                document.getElementById('seccion_usuarios').classList.remove('hidden');
                document.getElementById('seccion_pacientes').classList.add('hidden');
                
                document.getElementById('btn_tab_usuarios').className = "px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-gray-900 text-white shadow-sm flex items-center gap-2";
                document.getElementById('btn_tab_pacientes').className = "px-6 py-2.5 font-black text-sm uppercase rounded-t-lg bg-gray-200 text-gray-500 hover:bg-gray-300 transition-colors flex items-center gap-2";
            }
            
            // Recalcular anchos de DataTables al hacer visible el div oculto
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }

        function calcularEdad(idFecha, idEdad) { /* Igual que antes */
            let fn = document.getElementById(idFecha).value;
            if(fn){ let h = new Date(), c = new Date(fn), e = h.getFullYear() - c.getFullYear(), m = h.getMonth() - c.getMonth(); if (m < 0 || (m === 0 && h.getDate() < c.getDate())) { e--; } document.getElementById(idEdad).value = e; }
        }

        function abrirModalPaciente(id, fec, eda, dia) { document.getElementById('modal_pac_id').value=id; document.getElementById('modal_pac_fecha').value=fec; document.getElementById('modal_pac_edad').value=eda; document.getElementById('modal_pac_diag').value=dia; document.getElementById('modalPaciente').classList.remove('hidden'); }
        function abrirModalUsuario(id, nom, est, use, rol) { document.getElementById('modal_usr_id').value=id; document.getElementById('modal_usr_nombre').value=nom; document.getElementById('modal_usr_estab').value=est; document.getElementById('modal_usr_login').value=use; document.getElementById('modal_usr_rol').value=rol; document.getElementById('modalUsuario').classList.remove('hidden'); }
        function cerrarModal(id) { document.getElementById(id).classList.add('hidden'); }

        function ejecutarEliminar(accion, id) {
            Swal.fire({ title: '¿Confirmar?', text: "Se eliminará el registro de forma permanente.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#1f2937', confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i> Sí, eliminar' }).then((result) => {
                if(result.isConfirmed){ document.getElementById('accion_eliminar').value = accion; document.getElementById('id_eliminar').value = id; document.getElementById('formDinamicoEliminar').submit(); }
            });
        }
        function cerrarSesion() { Swal.fire({ title: '¿Salir?', icon: 'question', showCancelButton: true, confirmButtonColor: '#eab308', cancelButtonColor: '#1f2937', confirmButtonText: 'Cerrar Sesión', customClass:{confirmButton:'text-gray-900 font-bold'} }).then((result) => { if (result.isConfirmed) window.location.href = 'logout'; }); }

        <?php if ($status): ?>
            Swal.fire({ icon: '<?= $status === "success" ? "success" : "warning" ?>', title: '<?= $status_msg ?>', confirmButtonColor: '#eab308', customClass: {confirmButton:'text-gray-900 font-bold'} });
        <?php endif; ?>
    </script>
</body>
</html>