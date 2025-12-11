<?php
// ===========================================
// Permitir solicitudes desde React (CORS)
// ===========================================
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===========================================
// Leer datos desde el frontend
// ===========================================
$data = json_decode(file_get_contents("php://input"), true);

$tableName = $data['tableName'] ?? null;
$columns = $data['columns'] ?? [];
$framework = $data['framework'] ?? 'Spring';

// ===========================================
// Manejo del UUID / databaseName
// ===========================================
$uuidFile = __DIR__ . "/../../uuid.txt";

// lo que venga del frontend
if (!empty($data['databaseName'])) {
    $databaseName = trim($data['databaseName']);
    // sincroniza con el archivo solo si es distinto
    if (!file_exists($uuidFile) || trim(file_get_contents($uuidFile)) !== $databaseName) {
        file_put_contents($uuidFile, $databaseName);
    }
}
//si no viene del frontend, usar el persistente
elseif (file_exists($uuidFile)) {
    $databaseName = trim(file_get_contents($uuidFile));
}
//   si no hay ninguno, error controlado
else {
    echo json_encode(['success' => false, 'message' => "No se recibió ni se encontró el UUID (databaseName)."]);
    exit;
}

// ===========================================
// Validaciones básicas
// ===========================================
if (!$tableName) {
    echo json_encode(['success' => false, 'message' => "Falta el parámetro 'tableName'"]);
    exit;
}

if (empty($columns)) {
    echo json_encode(['success' => false, 'message' => "Faltan columnas"]);
    exit;
}

// ===========================================
// Crear la base de datos y tabla (usa crear_tablas.php)
// ===========================================

// ruta absoluta al script de creación real
$crearTablasPath = __DIR__ . "/../../crear_tablas.php";
if (!file_exists($crearTablasPath)) {
    echo json_encode(['success' => false, 'message' => "No se encontró crear_tablas.php en $crearTablasPath"]);
    exit;
}

$payload = [
    "action" => "createTable",
    "databaseName" => $databaseName,
    "tableName" => $tableName,
    "columns" => $columns
];

//  Llama al script crear_tablas.php mediante CURL 
$ch = curl_init("http://localhost/adminlte-dashboard/crear_tablas.php"); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$createResultRaw = curl_exec($ch);
curl_close($ch);

$createResult = json_decode($createResultRaw, true);

// Validar respuesta de crear_tablas
if (!$createResult || empty($createResult['databaseName'])) {
    echo json_encode(['success' => false, 'message' => "Error al crear tabla o base de datos.", 'raw' => $createResultRaw]);
    exit;
}


// Llama al generador correspondiente (Spring Boot, Vue, etc.)

$response = [];

switch (strtolower($framework)) {
   case 'angular': // NUEVO
    $generatorPath = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\src\\Component\\generateAngularCrud.php";

    if (!file_exists($generatorPath)) {
        echo json_encode(["success" => false, "message" => "No se encontró el generador Angular: $generatorPath"]);
        exit;
    }

    // NUEVO: variables globales necesarias
    $GLOBALS['tableName'] = $tableName;
    $GLOBALS['columns'] = $columns;
    $GLOBALS['databaseName'] = $databaseName;
    $GLOBALS['uuid'] = $databaseName;

    ob_start();
    include($generatorPath);
    $generatorOutput = ob_get_clean();

    $generatorResult = json_decode($generatorOutput, true);
    if (!$generatorResult) {
        $generatorResult = [
            'success' => false,
            'message' => 'Error al ejecutar el generador de Angular.',
            'raw' => $generatorOutput
        ];
    }

    $response = [
        'success' => true,
        'message' => 'Proyecto Angular generado correctamente.',
        'framework' => $framework,
        'entityName' => ucfirst($tableName),
        'tableName' => $tableName,
        'columns' => $columns,
        'databaseName' => $databaseName,
        'uuid' => $databaseName,
        'generatorResult' => $generatorResult,
        'createResult' => $createResult,
        'logs' => $logs ?? ''
    ];
    break;

     case 'vue':   // NUEVO
        $generatorPath = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\src\\Component\\generateVueCrud.php";

        if (!file_exists($generatorPath)) {
            echo json_encode(["success" => false, "message" => "No se encontró el generador Vue: $generatorPath"]);
            exit;
        }

        // NUEVO: variables globales necesarias
        $GLOBALS['tableName'] = $tableName;
        $GLOBALS['columns'] = $columns;
        $GLOBALS['databaseName'] = $databaseName;
        $GLOBALS['uuid'] = $databaseName;

        ob_start();
        include($generatorPath);
        $generatorOutput = ob_get_clean();

        $generatorResult = json_decode($generatorOutput, true);
        if (!$generatorResult) {
            $generatorResult = [
                'success' => false,
                'message' => 'Error al ejecutar el generador de Vue.',
                'raw' => $generatorOutput
            ];
        }

        $response = [
            'success' => true,
            'message' => 'Proyecto Vue generado correctamente.',
            'framework' => $framework,
            'entityName' => ucfirst($tableName),
            'tableName' => $tableName,
            'columns' => $columns,
            'databaseName' => $databaseName,
            'uuid' => $databaseName,
            'generatorResult' => $generatorResult,
            'createResult' => $createResult,
            'logs' => $logs ?? ''
        ];
        break;

        
    case 'spring':
    default:
        $generatorPath = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\src\\Component\\generateSpringCrud.php";

        if (!file_exists($generatorPath)) {
            echo json_encode(["success" => false, "message" => "No se encontró el generador Spring: $generatorPath"]);
            exit;
        }

        //  asegurar coherencia global de variables
        $GLOBALS['tableName'] = $tableName;
        $GLOBALS['columns'] = $columns;
        $GLOBALS['databaseName'] = $databaseName;
        $GLOBALS['uuid'] = $databaseName;

        ob_start();
        include($generatorPath);
        $generatorOutput = ob_get_clean();

        $generatorResult = json_decode($generatorOutput, true);
        if (!$generatorResult) {
            $generatorResult = [
                'success' => false,
                'message' => 'Error al ejecutar el generador de Spring Boot.',
                'raw' => $generatorOutput
            ];
        }

        $response = [
            'success' => true,
            'message' => 'Proyecto Spring Boot generado correctamente.',
            'framework' => $framework,
            'entityName' => ucfirst($tableName),
            'tableName' => $tableName,
            'columns' => $columns,
            'databaseName' => $databaseName,
            'uuid' => $databaseName, 
            'generatorResult' => $generatorResult,
            'createResult' => $createResult,
            'logs' => $logs ?? ''
        ];
        break;



       

}


// Respuesta final al frontend

echo json_encode($response, JSON_PRETTY_PRINT);
?>
