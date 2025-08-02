<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tesis";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Obtener la fecha actual del servidor MySQL
$dateQuery = "SELECT NOW() as server_date";
$dateResult = $conn->query($dateQuery);
$serverDate = ($dateResult->num_rows > 0) ? $dateResult->fetch_assoc()['server_date'] : null;

// Obtener parámetros GET
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50; // Límite de registros por página
$offset = ($page - 1) * $limit;

// Contar el total de registros
$countQuery = "SELECT COUNT(*) as total FROM classes_database";
$countResult = $conn->query($countQuery);
$totalRows = ($countResult->num_rows > 0) ? $countResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalRows / $limit); // Calcula el total de páginas necesarias

// Consulta SQL con paginación
$sql = "SELECT DATE_, START_TIME, END_TIME, FACILITY_ID, FIRST_NAME, LAST_NAME, TAG FROM classes_database LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Configurar encabezado JSON
header('Content-Type: application/json');

$response = [
    "total_pages" => $totalPages,
    "server_date" => $serverDate, // Se agrega la fecha del servidor
    "data" => []
];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $response["data"][] = $row;
    }
}

// Enviar respuesta en JSON
echo json_encode($response);
$conn->close();
?>

