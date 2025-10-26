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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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
    <title>Panel de Control – Proyecto CoMo</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #E8F1F5;
            margin: 0;
        }

        header {
            background-color: #FAFAFA;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        header img {
            height: 60px;
        }

        header h1 {
            font-size: 20px;
            margin: 0;
            text-align: center;
            flex-grow: 1;
            color: #004A7C;
        }

        .contenido {
            max-width: 1200px;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .salon {
            background: #eef3f7;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .salon h2 {
            text-align: center;
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 15px;
        }

	.dispositivo {
   		 margin-top: 15px;
   		 padding: 10px;
   		 background: #ffffff;
   		 border-radius: 8px;
   		 box-shadow: 0 0 5px rgba(0, 0, 0, 0.05);
   		 text-align: center; /* Centra texto y botones */
	}

	.boton {
  		  padding: 8px 16px;
  		  margin: 5px;
  		  border: none;
  		  border-radius: 5px;
  		  cursor: pointer;
 		   display: inline-block;
	}

        

        .on {
            background-color: #4CAF50;
            color: white;
        }

        .off {
            background-color: #f44336;
            color: white;
        }

        .estado {
            margin-top: 8px;
            font-weight: bold;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <img src="img/logo_javeriana.png" alt="Logo Javeriana">
    <h1>Panel de Control – Proyecto CoMo</h1>
    <img src="img/logo_como.png" alt="Logo CoMo">
</header>

<div class="contenido">
    <form method="post">
        <div class="grid">
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
                            <strong><?php echo $info['nombre']; ?></strong><br>
                            <button class="boton on" name="<?php echo $clave; ?>" value="on">Encender</button>
                            <button class="boton off" name="<?php echo $clave; ?>" value="off">Apagar</button>
                            <?php if (isset($resultados[$clave])): ?>
                                <div class="estado">Resultado: <?php echo $resultados[$clave]; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </form>
</div>

</body>
</html>
