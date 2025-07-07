<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Carga PHPMailer manualmente
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

include 'funciones.php';

$impresoras = json_decode(file_get_contents("impresoras.json"), true);
$alertas = [];
$impresorasNoRespondieron = [];

foreach ($impresoras as $impresora) {
    try {
        $datos = obtenerNivelToner($impresora['ip']);
        if (!$datos) {
            $impresorasNoRespondieron[] = $impresora['nombre'] . " ({$impresora['ip']})";
            continue;
        }

        $modelos = $impresora['cartuchos'];

        foreach ($datos as $index => $cartucho) {
            $porcentaje = $cartucho['porcentaje'];
            $nombreCartucho = $modelos[$index] ?? $cartucho['cartucho'];

            // Excluir cartuchos sin nombre explícito o con palabras clave
            if (empty($nombreCartucho) || stripos($nombreCartucho, 'unidad de recogi') !== false) {
                continue;
            }

            if ($porcentaje <= 10) {
                $alertas[] = [
                    'impresora' => $impresora['nombre'],
                    'modelo'    => $nombreCartucho,
                    'porcentaje'=> $porcentaje
                ];
            }
        }

    } catch (Exception $e) {
        $impresorasNoRespondieron[] = $impresora['nombre'] . " ({$impresora['ip']})";
        continue;
    }
}

if (!empty($alertas) || !empty($impresorasNoRespondieron)) {
    $mail = new PHPMailer(true);
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'alertas@almidones.com.mx';
        $mail->Password = 'Almex2025';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $logoPath = 'logo-almex.png'; 
        $mail->addEmbeddedImage($logoPath, 'almexLogo');

        $mail->setFrom('alertas@almidones.com.mx', 'Monitor de Impresoras');
        $mail->addAddress('bryan.alvarado@almidones.com.mx');
       // $mail->addCC('santiago.rodriguez@almidones.com.mx');
        //$mail->addCC('jose.campa@almidones.com.mx');
       // $mail->addCC('gustavo.valencia@almidones.com.mx');

        $html = '
        <div style="font-family: Arial, sans-serif; background-color: #ffffff; padding: 20px; border: 1px solid #ddd; border-radius: 10px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="cid:almexLogo" alt="ALMEX Logo" style="width: 150px;"/>
                <h2 style="color: #2a2a2a; margin-top: 10px;">Monitor de Impresoras</h2>
                <p style="font-size: 14px; color: #666;">Reporte automático de niveles de tóner - ' . date("Y-m-d") . '</p>
            </div>';

        if (!empty($alertas)) {
            $html .= '
            <h3 style="color: #d32f2f; margin-bottom: 10px;">Tóner bajo detectado</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="border: 1px solid #ccc; padding: 8px;">Impresora</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Cartucho</th>
                        <th style="border: 1px solid #ccc; padding: 8px; text-align:center;">Porcentaje</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($alertas as $a) {
                $html .= '
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . $a['impresora'] . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">' . $a['modelo'] . '</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold; color: #d32f2f;">' . $a['porcentaje'] . '%</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';
        }

        if (!empty($impresorasNoRespondieron)) {
            $html .= '
            <h3 style="color: #ff9800; margin-top: 30px;">Impresoras que no respondieron</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f5f5f5;">
                        <th style="border: 1px solid #ccc; padding: 8px;">Nombre / IP</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($impresorasNoRespondieron as $impresoraNR) {
                $html .= '
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; color: #f57c00;">' . $impresoraNR . '</td>
                    </tr>';
            }

            $html .= '
                </tbody>
            </table>';
        }

        $html .= '
            <div style="margin-top: 25px; font-size: 12px; color: #888; text-align: center;">
                Este es un mensaje automático del sistema de monitoreo de impresoras de ALMEX.<br/>
            </div>
        </div>';

        // Configurar correo
        $mail->isHTML(true);
        $mail->Subject = 'Alerta de toner bajo en impresoras';
        $mail->Body    = $html;

        // Enviar
        $mail->send();
        echo "Correo enviado correctamente.";
    } catch (Exception $e) {
        echo "Error al enviar correo: {$mail->ErrorInfo}";
    }
} else {
    echo "No hay cartuchos por debajo del 10% hoy y todas las impresoras respondieron correctamente.";
}
?>
