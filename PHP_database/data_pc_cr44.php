<?php
$conn = new mysqli("localhost", "root", "", "tesis");

if (isset($_GET['AMPERAGE'])) {
    $conn->query("INSERT INTO data_pc_cr44 (AMPERAGE) VALUES ('" . $_GET['AMPERAGE'] . "')");
}

$conn->close();
?>
