<?php
$conn = new mysqli("localhost", "root", "", "tesis");

if (isset($_GET['AMPERAGE'])) {
    $conn->query("INSERT INTO data_light_cr41 (AMPERAGE) VALUES ('" . $_GET['AMPERAGE'] . "')");
}

$conn->close();
?>
