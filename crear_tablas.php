<?php
// habilitar CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

// manejar solicitud OPTIONS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// datos de conexión
$servername = "localhost";
$username = "root";
$password = "";

//  ruta donde se guardará el UUID persistente
$uuidFile = __DIR__ . "/uuid.txt";

//0 genera o recuperar un UUID persistente de 32 dígitos
function getPersistentUUID($uuidFile) {
    if (file_exists($uuidFile)) {
        // ya existe un UUID, se reutiliza
        return trim(file_get_contents($uuidFile));
    } else {
        // NUEVO: usa exactamente el UUID del frontend sin modificarlo
$uuid = bin2hex(random_bytes(16)); // sin prefijo "db_"
file_put_contents($uuidFile, $uuid);
return $uuid;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    $action = $data['action'] ?? null;
    
    // NUEVO: usar siempre el UUID del frontend (sin modificarlo)
if (!empty($data['databaseName'])) {
    $databaseName = trim($data['databaseName']);
    file_put_contents($uuidFile, $databaseName); // sincroniza con archivo local
} elseif (file_exists($uuidFile)) {
    $databaseName = trim(file_get_contents($uuidFile));
} else {
    $databaseName = bin2hex(random_bytes(16)); // fallback si no hay nada
    file_put_contents($uuidFile, $databaseName);
}


    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        echo json_encode(['message' => "Conexión fallida: " . $conn->connect_error]);
        exit();
    }


    $conn->query("CREATE DATABASE IF NOT EXISTS `$databaseName`");

    // seleccionar base
    if (!$conn->select_db($databaseName)) {
        echo json_encode(['message' => "Error: No se pudo seleccionar la base de datos '$databaseName'."]);
        $conn->close();
        exit();
    }

    //  listar tablas existentes
    if ($action === 'listTables') {
        $tables = [];
        $res = $conn->query("SHOW TABLES");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_array()) {
                $tables[] = $row[0];
            }
        }
        echo json_encode(['success' => true, 'tables' => $tables]);
        $conn->close();
        exit();
    }

        // validar datos obligatorios solo si no estamos listando tablas
    if ($action !== 'listTables') {
        if (!isset($data['tableName']) || empty($data['tableName'])) {
            echo json_encode(['message' => "Error: 'tableName' es obligatorio."]);
            exit();
        }
        if (!isset($data['columns']) || !is_array($data['columns']) || count($data['columns']) === 0) {
            echo json_encode(['message' => "Error: 'columns' debe ser un array con al menos un campo."]);
            exit();
        }
    }

    $tableName = $data['tableName'];
    $columns = $data['columns'];
    $sqlQuery = $data['sqlQuery'] ?? null;

    $responseMessages = ["Usando base de datos: $databaseName"];

    // si no hay sqlQuery, generar automáticamente
  if (!$sqlQuery) {
    $columnDefs = [];
    $hasPrimary = false;

    foreach ($columns as $col) {
        // Admite arrays u objetos
        if (is_array($col)) {
            $colName = $col[0] ?? ($col['name'] ?? null);
            $colType = $col[1] ?? ($col['type'] ?? 'string');
        } elseif (is_object($col)) {
            $colName = $col->name ?? null;
            $colType = $col->type ?? 'string';
        } else {
            continue;
        }

        if (!$colName) continue;

        // Detectar columna id... solo una vez
        if (!$hasPrimary && preg_match('/^id/i', $colName)) {
            $hasPrimary = true;
            $columnDefs[] = "`$colName` INT AUTO_INCREMENT PRIMARY KEY";
            continue;
        }

        // Tipos SQL seguros
        $sqlType = match (strtolower($colType)) {
            'int', 'integer' => 'INT',
            'string', 'varchar', 'text' => 'VARCHAR(255)',
            'boolean', 'bool' => 'TINYINT(1)',
            'float', 'double', 'decimal' => 'FLOAT',
            default => 'VARCHAR(255)'
        };

        // Agregar NOT NULL por defecto
        $columnDefs[] = "`$colName` $sqlType NOT NULL";
    }

    // Si no hay id definido, agregar uno genérico
    if (!$hasPrimary) {
        array_unshift($columnDefs, "`id` INT AUTO_INCREMENT PRIMARY KEY");
    }

    // Crear tabla final
    $sqlQuery = "CREATE TABLE IF NOT EXISTS `$tableName` (" . implode(", ", $columnDefs) . ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";
}

// Ejecutar la creación
if ($conn->query($sqlQuery) === TRUE) {
    $responseMessages[] = "Tabla '$tableName' creada o ya existe.";
} else {
    echo json_encode(['message' => "Error al crear la tabla: " . $conn->error]);
    $conn->close();
    exit();
}


    $conn->close();

    echo json_encode([
        'databaseName' => $databaseName,
        'tableName' => $tableName,
        'messages' => $responseMessages
    ]);
} else {
    echo json_encode(['message' => "Método no soportado. Utiliza POST."]);
}
?>
