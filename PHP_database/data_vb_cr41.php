<?php
$conn = new mysqli("localhost", "root", "", "tesis");

if (isset($_GET['AMPERAGE'])) {
    $conn->query("INSERT INTO data_vb_cr41 (AMPERAGE) VALUES ('" . $_GET['AMPERAGE'] . "')");
}

$conn->close();
?>
