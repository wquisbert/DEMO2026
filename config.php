<?php
// config.php
$host = "aws-1-us-east-1.pooler.supabase.com"; // Reemplaza con tu host de Supabase
$port = "5432";
$dbname = "postgres"; // Nombre por defecto en Supabase
$user = "postgres.rpswxiqerulkrxvaqnyo";
$password = "Sdscclp*2026";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>