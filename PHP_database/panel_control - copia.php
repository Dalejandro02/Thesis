<?php
$dispositivos = [
    'CR4_1_PC'    => ['ip' => 'http://172.31.0.111/activar', 'salon' => 'CR 4.1', 'nombre' => 'PC'],
    'CR4_1_VB'    => ['ip' => 'http://172.31.0.112/activar', 'salon' => 'CR 4.1', 'nombre' => 'Videobeam'],
    'CR4_1_LIGHT' => ['ip' => 'http://172.31.0.113/activar', 'salon' => 'CR 4.1', 'nombre' => 'Luces'],

    'CR4_2_PC'    => ['ip' => 'http://172.31.0.121/activar', 'salon' => 'CR 4.2', 'nombre' => 'PC'],
    'CR4_2_VB'    => ['ip' => 'http://172.31.0.122/activar', 'salon' => 'CR 4.2', 'nombre' => 'Videobeam'],
    'CR4_2_LIGHT' => ['ip' => 'http://172.31.0.123/activar', 'salon' => 'CR 4.2', 'nombre' => 'Luces'],

    'CR4_3_PC'    => ['ip' => 'http://172.31.0.131/activar', 'salon' => 'CR 4.3', 'nombre' => 'PC'],
    'CR4_3_VB'    => ['ip' => 'http://172.31.0.132/activar', 'salon' => 'CR 4.3', 'nombre' => 'Videobeam'],
    'CR4_3_LIGHT' => ['ip' => 'http://172.31.0.133/activar', 'salon' => 'CR 4.3', 'nombre' => 'Luces'],

    'CR4_4_PC'    => ['ip' => 'http://172.31.0.141/activar', 'salon' => 'CR 4.4', 'nombre' => 'PC'],
    'CR4_4_VB'    => ['ip' => 'http://172.31.0.142/activar', 'salon' => 'CR 4.4', 'nombre' => 'Videobeam'],
    'CR4_4_LIGHT' => ['ip' => 'http://172.31.0.143/activar', 'salon' => 'CR 4.4', 'nombre' => 'Luces'],
];

function enviarComando($url, $estado) {
    $urlFinal = $url . '?activacion=' . $estado;

    $ch = curl_init($urlFinal);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); // Tiempo máximo para conectar
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);        // Tiempo máximo total
    $response = curl_exec($ch);
    $success = !curl_errno($ch) && $response !== false;
    curl_close($ch);

    return $success ? "OK" : "Fallo";
}


$resultados = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dispositivos as $clave => $info) {
        if (isset($_POST[$clave])) {
            $estado = $_POST[$clave] === 'on' ? 1 : 0;
            $resultados[$clave] = enviarComando($info['ip'], $estado);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Dispositivos Cedro Rosado</title>
    <style>
        body { font-family: Arial; background: #f0f0f0; padding: 20px; }
        h1 { text-align: center; color: #2c3e50; }
        h2 { color: #34495e; margin-top: 40px; }
        .salon { margin-bottom: 30px; background: #ffffff; padding: 15px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        .dispositivo { margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 8px; }
        .estado { margin-top: 10px; }
        .boton { padding: 8px 16px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
        .on { background-color: #4CAF50; color: white; }
        .off { background-color: #f44336; color: white; }
    </style>
</head>
<body>

<h1>CONTROL DE DISPOSITIVOS CEDRO ROSADO</h1>

<form method="post">
    <?php
    $salonesAgrupados = [];
    foreach ($dispositivos as $clave => $info) {
        $salonesAgrupados[$info['salon']][$clave] = $info;
    }

    foreach ($salonesAgrupados as $salon => $disps): ?>
        <div class="salon">
            <h2><?php echo $salon; ?></h2>
            <?php foreach ($disps as $clave => $info): ?>
                <div class="dispositivo">
                    <strong><?php echo $info['nombre']; ?> (<?php echo $info['ip']; ?>)</strong><br>
                    <button class="boton on" name="<?php echo $clave; ?>" value="on">Encender</button>
                    <button class="boton off" name="<?php echo $clave; ?>" value="off">Apagar</button>
                    <?php if (isset($resultados[$clave])): ?>
                        <div class="estado">Resultado: <strong><?php echo $resultados[$clave]; ?></strong></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</form>

</body>
</html>
