<?php
// Conexión a MySQL
$conn = new mysqli("localhost", "root", "", "uid");

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Verificar que se recibieron todos los datos
if (isset($_POST['uid'], $_POST['fecha'], $_POST['hora'], $_POST['facility_id'], $_POST['estado'])) {
    $uid      = $conn->real_escape_string($_POST['uid']);
    $fecha    = $conn->real_escape_string($_POST['fecha']);
    $hora     = $conn->real_escape_string($_POST['hora']);
    $facility = $conn->real_escape_string($_POST['facility_id']);
    $estado = $conn->real_escape_string($_POST['estado']);

    // Insertar en la tabla
    $sql = "INSERT INTO tarjetas_detectadas (uid, fecha, hora, facility_id, estado) 
            VALUES ('$uid', '$fecha', '$hora', '$facility', '$estado')";

    if ($conn->query($sql) === TRUE) {
        echo "OK";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Error: faltan parámetros (uid, fecha, hora, facility_id o estado)";
}

// Cerrar conexión
$conn->close();
?>
