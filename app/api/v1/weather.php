<?php
/**
 * Weather Report Solutions S.L. - Telemetry API Endpoint v1
 * Returns real-time meteorological metrics for registered stations.
 * Integrates live global satellite telemetry with local station cache.
 * VULN-02 (WR-RT-03): SQL Injection — $city parameter concatenated directly into SQL
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config.php';

$city = isset($_GET['city']) ? trim($_GET['city']) : 'Madrid';
if (empty($city)) {
    $city = 'TODAS';
}

// Enterprise Telemetry Station Coordinates & Real-time Nodes
$stations = [
    'MADRID'    => ['lat' => 40.4168, 'lon' => -3.7038, 'code' => 'ST-MAD-01', 'name' => 'Madrid (Centro)'],
    'BARCELONA' => ['lat' => 41.3879, 'lon' => 2.1699,  'code' => 'ST-BCN-01', 'name' => 'Barcelona (Port)'],
    'BERLIN'    => ['lat' => 52.5200, 'lon' => 13.4050, 'code' => 'ST-BER-01', 'name' => 'Berlín (Brandeburgo)'],
    'LONDON'    => ['lat' => 51.5074, 'lon' => -0.1278, 'code' => 'ST-LON-01', 'name' => 'Londres (Heathrow)'],
    'PARIS'     => ['lat' => 48.8566, 'lon' => 2.3522,  'code' => 'ST-PAR-01', 'name' => 'París (CDG)'],
    'ROME'      => ['lat' => 41.9028, 'lon' => 12.4964, 'code' => 'ST-ROM-01', 'name' => 'Roma (Fiumicino)'],
    'AMSTERDAM' => ['lat' => 52.3676, 'lon' => 4.9041,  'code' => 'ST-AMS-01', 'name' => 'Ámsterdam (Schiphol)'],
    'VALENCIA'  => ['lat' => 39.4699, 'lon' => -0.3763, 'code' => 'ST-VLC-01', 'name' => 'Valencia (Litoral)'],
    'SEVILLA'   => ['lat' => 37.3891, 'lon' => -5.9845, 'code' => 'ST-SVQ-01', 'name' => 'Sevilla (Guadalquivir)'],
    'BILBAO'    => ['lat' => 43.2630, 'lon' => -2.9350, 'code' => 'ST-BIO-01', 'name' => 'Bilbao (Cantábrico)'],
];

$city_upper = strtoupper($city);

// If city is a known station and not an injection attempt, fetch live real-time satellite telemetry
if (isset($stations[$city_upper]) && !preg_match("/['\"\\-;#]/", $city)) {
    $st = $stations[$city_upper];
    $api_url = sprintf(
        'https://api.open-meteo.com/v1/forecast?latitude=%s&longitude=%s&current=temperature_2m,relative_humidity_2m,surface_pressure,wind_speed_10m,weather_code',
        $st['lat'], $st['lon']
    );

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 2,
            'header'  => "User-Agent: WeatherReportSaaS-Telemetry/4.2\r\n"
        ]
    ]);

    $live_raw = @file_get_contents($api_url, false, $ctx);
    if ($live_raw) {
        $live_data = json_decode($live_raw, true);
        if (!empty($live_data['current'])) {
            $cur   = $live_data['current'];
            $temp  = floatval($cur['temperature_2m']);
            $hum   = intval($cur['relative_humidity_2m']);
            $wind  = floatval($cur['wind_speed_10m']);
            $press = intval(round($cur['surface_pressure'] ?? 1013));

            $wcode = intval($cur['weather_code'] ?? 0);
            $cond  = 'Despejado';
            if ($wcode >= 1 && $wcode <= 3) $cond = 'Parcialmente Nublado';
            elseif ($wcode >= 45 && $wcode <= 48) $cond = 'Niebla';
            elseif ($wcode >= 51 && $wcode <= 67) $cond = 'Lluvia Moderada';
            elseif ($wcode >= 71 && $wcode <= 77) $cond = 'Nieve';
            elseif ($wcode >= 80 && $wcode <= 82) $cond = 'Chubascos Dispersos';
            elseif ($wcode >= 95) $cond = 'Tormenta Eléctrica';

            try {
                $check = $pdo->prepare("SELECT id FROM weather_data WHERE city_name ILIKE ?");
                $check->execute(['%' . $city . '%']);
                if ($row = $check->fetch()) {
                    $upd = $pdo->prepare("UPDATE weather_data SET temp_celsius = ?, humidity = ?, wind_speed_kmh = ?, pressure_hpa = ?, conditions = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$temp, $hum, $wind, $press, $cond, $row['id']]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO weather_data (station_code, city_name, temp_celsius, humidity, wind_speed_kmh, pressure_hpa, conditions) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $ins->execute([$st['code'], $st['name'], $temp, $hum, $wind, $press, $cond]);
                }
            } catch (Exception $e) {
                // Silently continue if database write fails
            }
        }
    }
}

try {
    if ($city_upper === 'TODAS' || $city_upper === 'ALL') {
        $sql = "SELECT id, station_code, city_name, temp_celsius, humidity, conditions, wind_speed_kmh, pressure_hpa, visibility_km, updated_at FROM weather_data ORDER BY city_name ASC";
    } else {
        // VULN-02 (WR-RT-03): Direct SQL String Concatenation with exactly 10 columns for UNION exploitation
        $sql = "SELECT id, station_code, city_name, temp_celsius, humidity, conditions, wind_speed_kmh, pressure_hpa, visibility_km, updated_at FROM weather_data WHERE city_name = '" . $city . "' OR city_name ILIKE '%" . $city . "%'";
    }

    $query = $pdo->query($sql);
    $results = $query ? $query->fetchAll(PDO::FETCH_ASSOC) : [];

    // Fallback garantizado si la tabla no está poblada aún
    if (empty($results) && !preg_match("/['\"\\-;#]/", $city)) {
        if ($city_upper === 'TODAS' || $city_upper === 'ALL') {
            foreach ($stations as $key => $st) {
                $results[] = [
                    'id' => 100 + count($results),
                    'station_code' => $st['code'],
                    'city_name' => $st['name'],
                    'temp_celsius' => 20.5,
                    'humidity' => 50,
                    'conditions' => 'Operativo · Estable',
                    'wind_speed_kmh' => 14.0,
                    'pressure_hpa' => 1014,
                    'visibility_km' => 10.0,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }
        } elseif (isset($stations[$city_upper])) {
            $st = $stations[$city_upper];
            $results = [[
                'id' => 101,
                'station_code' => $st['code'],
                'city_name' => $st['name'],
                'temp_celsius' => 21.8,
                'humidity' => 48,
                'conditions' => 'Despejado · Óptimo',
                'wind_speed_kmh' => 12.5,
                'pressure_hpa' => 1015,
                'visibility_km' => 10.0,
                'updated_at' => date('Y-m-d H:i:s')
            ]];
        }
    }

    if ($results) {
        echo json_encode([
            "status" => "success",
            "telemetry_source" => "LIVE_SATELLITE_HYBRID",
            "timestamp" => date('c'),
            "station" => strtoupper($city),
            "results_count" => count($results),
            "data" => $results
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se encontraron datos telemétricos para la estación: " . htmlspecialchars($city)
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "error_code" => "DB_QUERY_FAILED",
        "message" => "SQL Query Error: " . $e->getMessage(),
        "query" => $sql
    ], JSON_PRETTY_PRINT);
}
