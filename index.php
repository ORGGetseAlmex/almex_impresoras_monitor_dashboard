<?php
include 'funciones.php';
$impresoras = json_decode(file_get_contents("impresoras.json"), true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Portal de Tóner ALMEX</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
      background-color: #f0f2f5;
      color: #333;
    }

    .container {
      display: flex;
      height: 100vh;
    }

    .sidebar {
      width: 280px;
      background-color: #1c1f26;
      padding-top: 20px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .logo-container {
      text-align: center;
      margin-bottom: 30px;
      padding: 0 20px;
    }

    .logo-container img {
      max-width: 180px;
      height: auto;
      display: block;
      margin: 0 auto;
      filter: drop-shadow(0 0 8px rgba(255, 0, 0, 0.4));
    }

    .sidebar h2 {
      color: #ecf0f1;
      text-align: center;
      margin-bottom: 15px;
    }

    .printer-item {
      width: 100%;
      padding: 15px 20px;
      border-top: 1px solid #2f343d;
      cursor: pointer;
      color: #ecf0f1;
      transition: background 0.2s;
    }

    .printer-item:hover,
    .printer-item.active {
      background-color: #2c313c;
    }

    .printer-name {
      font-weight: bold;
    }

    .content {
      flex-grow: 1;
      padding: 30px;
      overflow-y: auto;
    }

    .panel {
      position: relative;
      background: linear-gradient(145deg, #f4f6f8, #ffffff);
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.08);
      border: 1px solid #e0e4e7;
      backdrop-filter: blur(3px);
      transition: all 0.3s ease;
    }

    .panel h3 {
      margin-top: 0;
      color: #2c3e50;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      padding: 12px;
      text-align: center;
    }

    th {
      background-color: #ecf0f1;
      color: #2c3e50;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .barra {
      height: 18px;
      background: #ddd;
      border-radius: 8px;
      overflow: hidden;
    }

    .nivel {
      height: 100%;
    }

    .critico { background-color: #e74c3c; }
    .medio   { background-color: #f39c12; }
    .normal  { background-color: #2ecc71; }

    .warning-icon {
      color: #e67e22;
      font-size: 14px;
    }

    .hidden {
      display: none;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }

      .sidebar {
        width: 100%;
        height: auto;
      }

      .content {
        padding: 15px;
      }
    }
    body::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('fondo-almex.jpg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        opacity: 0.40; 
        z-index: -1;
         
        }

  </style>

  <script>
    function mostrarPanel(id) {
      const panels = document.querySelectorAll('.panel');
      panels.forEach(p => p.classList.add('hidden'));

      document.getElementById(id).classList.remove('hidden');

      const items = document.querySelectorAll('.printer-item');
      items.forEach(i => i.classList.remove('active'));
      document.getElementById("item-" + id).classList.add('active');
    }
  </script>
</head>
<body>

<div class="container">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo-container">
      <img src="logo-almex.png" alt="ALMEX Logo">
    </div>
    <h2>Impresoras</h2>
    <?php foreach ($impresoras as $i => $impresora): ?>
      <div class="printer-item <?= $i === 0 ? 'active' : '' ?>" id="item-panel<?= $i ?>" onclick="mostrarPanel('panel<?= $i ?>')">
        <div class="printer-name"><?= htmlspecialchars($impresora['nombre']) ?></div>
        <div style="font-size: 13px;"><?= htmlspecialchars($impresora['ip']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Contenido -->
  <div class="content">
    <?php foreach ($impresoras as $i => $impresora): ?>
      <div class="panel <?= $i > 0 ? 'hidden' : '' ?>" id="panel<?= $i ?>">
        <h3><?= htmlspecialchars($impresora['nombre']) ?> (<?= htmlspecialchars($impresora['ip']) ?>)</h3>
        <?php
          $datos = obtenerNivelToner($impresora['ip']);
          $modelos = $impresora['cartuchos'];
          $esColor = $impresora['color'] ?? true;
          $limite = $esColor ? count($datos) : count($modelos);
        ?>
        <?php if (!$datos): ?>
          <p style="color:red;">❌ No se pudo obtener información de la impresora.</p>
        <?php else: ?>
          <table>
            <tr>
              <th>Cartucho</th>
              <th>Nivel</th>
              <th>Porcentaje</th>
            </tr>
            <?php
              for ($index = 0; $index < $limite; $index++):
                $cartucho = $datos[$index];
                $porcentaje = $cartucho['porcentaje'];
                $clase = $porcentaje < 20 ? 'critico' : ($porcentaje < 50 ? 'medio' : 'normal');
            ?>
              <tr>
                <td><?= htmlspecialchars($modelos[$index] ?? 'Desconocido') ?></td>
                <td><?= $cartucho['nivel'] ?> / <?= $cartucho['maximo'] ?></td>
                <td>
                  <div class="barra">
                    <div class="nivel <?= $clase ?>" style="width: <?= $porcentaje ?>%;"></div>
                  </div>
                  <?= $porcentaje ?>% <?= $porcentaje < 10 ? '<span class="warning-icon">⚠️</span>' : '' ?>
                </td>
              </tr>
            <?php endfor; ?>
          </table>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

</body>
</html>
