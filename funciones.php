<?php
function obtenerNivelToner($ip, $comunidad = "public") {
    $nombres = @snmpwalk($ip, $comunidad, "1.3.6.1.2.1.43.11.1.1.6.1"); // Nombre cartucho
    $nivel   = @snmpwalk($ip, $comunidad, "1.3.6.1.2.1.43.11.1.1.9.1"); // Nivel actual
    $maximo  = @snmpwalk($ip, $comunidad, "1.3.6.1.2.1.43.11.1.1.8.1"); // Capacidad máxima

    $datos = [];
    if (!$nombres || !$nivel || !$maximo) return false;

    foreach ($nombres as $i => $linea) {
        // Decodificar Hex-STRING o STRING
        if (strpos($linea, 'Hex-STRING:') !== false) {
            preg_match('/Hex-STRING:\s+(.*)/', $linea, $hex);
            $hex = str_replace(' ', '', $hex[1]);
            $nombre = trim(hex2bin($hex));
        } elseif (strpos($linea, 'STRING:') !== false) {
            preg_match('/STRING: "(.*)"/', $linea, $matches);
            $nombre = trim($matches[1]);
        } else {
            $nombre = "Cartucho #" . ($i + 1);
        }

        preg_match('/INTEGER: (\d+)/', $nivel[$i] ?? '', $n);
        preg_match('/INTEGER: (\d+)/', $maximo[$i] ?? '', $m);

        $actual = isset($n[1]) ? intval($n[1]) : 0;
        $total  = isset($m[1]) ? intval($m[1]) : 100;
        $porcentaje = ($total > 0) ? round(($actual / $total) * 100) : 0;

        $datos[] = [
            'cartucho'   => $nombre ?: "Cartucho #" . ($i + 1),
            'nivel'      => $actual,
            'maximo'     => $total,
            'porcentaje' => $porcentaje
        ];
    }

    return $datos;
}
