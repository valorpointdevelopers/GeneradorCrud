<?php
// db_functions.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Obtiene un UUID global persistente para la base de datos.
 * Si existe, lo reutiliza; si no, lo crea y lo guarda.
 */
function getGlobalDatabaseName() {
    $dbFile = __DIR__ . '/db_names.json'; // Archivo donde guardamos el UUID global
    if (file_exists($dbFile)) {
        $data = json_decode(file_get_contents($dbFile), true);
        if (!empty($data['global_uuid'])) {
            return "db_" . $data['global_uuid'];
        }
    }
    $uuid = bin2hex(random_bytes(16));
    file_put_contents($dbFile, json_encode(['global_uuid' => $uuid], JSON_PRETTY_PRINT));
    return "db_" . $uuid;
}

/**
 * Crea la base de datos y tabla usando crear_tablas.php
 * @param string $databaseName
 * @param string $tableName
 * @param array $columns Formato: [['columna', 'tipo'], ...]
 * @param string|null $sqlQuery Opcional: consulta SQL personalizada
 */
function createDatabaseAndTable($databaseName, $tableName, $columns, $sqlQuery = null) {
    $url = "http://localhost/adminlte-dashboard/crear_tablas.php";

    // Columnas en el formato esperado
$postColumns = [];
foreach ($columns as $col) {
    if (is_array($col)) {
        // Si viene como ['name' => '...', 'type' => '...']
        if (isset($col['name']) && isset($col['type'])) {
            $postColumns[] = [$col['name'], $col['type']];
        }
        // Si viene como ['columna', 'tipo']
        elseif (isset($col[0]) && isset($col[1])) {
            $postColumns[] = [$col[0], $col[1]];
        }
    } 
    // Si viene como string simple
    else {
        $postColumns[] = [$col, 'string'];
    }
}


    $postData = [
        "databaseName" => $databaseName,
        "tableName" => $tableName,
        "columns" => $postColumns
    ];

    if ($sqlQuery !== null) {
        $postData['sqlQuery'] = $sqlQuery;
    }

    // POST a crear_tablas.php
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result ?: ['success' => false, 'message' => 'No se pudo conectar con crear_tablas.php'];
}
