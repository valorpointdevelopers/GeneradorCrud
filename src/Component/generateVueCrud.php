<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 600);

session_start();

/***************************************
 *     LECTURA DE DATOS (MISMO FLUJO)
 ***************************************/
if (isset($GLOBALS['tableName']) && isset($GLOBALS['columns'])) {
    $tableName = $GLOBALS['tableName'];
    $columns = $GLOBALS['columns'];
    $databaseName = $GLOBALS['databaseName'] ?? ($GLOBALS['uuid'] ?? ($_SESSION['uuid'] ?? null));
} else {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input || !isset($input['tableName']) || !isset($input['columns'])) {
        echo json_encode(["success" => false, "message" => "Faltan datos (tableName o columns)"]);
        exit;
    }
    $tableName = $input['tableName'];
    $columns = $input['columns'];
    $databaseName = $input['databaseName'] ?? ($input['uuid'] ?? ($_SESSION['uuid'] ?? null));
}

if (empty($columns)) {
    echo json_encode(["success" => false, "message" => "No se especificaron columnas"]);
    exit;
}

ob_start();
echo "Generando CRUD Vue para tabla: $tableName\n";

/*******************************************
 *           RUTAS BASE 
 *******************************************/
$baseDir = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\templates\\vue-crud-app";
$outputBase = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\outputVue";

/*******************************************
 *    UTILS
 *******************************************/
function toKebabCase($string) {
    $s = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
    $s = preg_replace('/[^a-zA-Z0-9\-]/', '-', $s);
    return strtolower($s);
}

function normalizeColumns($cols) {
    $out = [];
    foreach ($cols as $c) {
        if (is_array($c)) {
            $name = $c[0] ?? $c['name'] ?? null;
            $type = $c[1] ?? $c['type'] ?? 'string';
        } elseif (is_object($c)) {
            $name = $c->name ?? null;
            $type = $c->type ?? 'string';
        } else {
            $name = $c;
            $type = 'string';
        }
        if (!$name) continue;
        $out[] = [(string)$name, (string)$type];
    }
    return $out;
}

function map_front_type($raw) {
    $t = strtolower(trim((string)$raw));
    if (in_array($t, ['int','integer','number','numeric','float','double','decimal','bigint'])) return 'number';
    if (in_array($t, ['boolean','bool'])) return 'boolean';
    if ($t === 'time') return 'time';
    if (in_array($t, ['datetime','fecha y hora','datetime-local','timestamp'])) return 'datetime';
    if (in_array($t, ['date','fecha'])) return 'date';

    if (strpos($t, 'date') !== false && strpos($t,'time') !== false) return 'datetime';
    if (strpos($t, 'date') !== false) return 'date';
    if (strpos($t, 'time') !== false) return 'time';
    if (strpos($t, 'int') !== false) return 'number';
    return 'string';
}

/*******************************************
 *     GENERADORES DE PLACEHOLDERS
 *******************************************/
function generateFormFields($columns) {
    $html = [];
    foreach ($columns as $col) {
        $name = $col[0];
        $type = map_front_type($col[1]);

        if (preg_match('/^id/i', $name)) continue;

        $label = ucfirst($name);
        switch ($type) {
            case 'number':
                $field = "<label>{$label}</label>\n      <input type='number' v-model='form.{$name}' placeholder='{$label}' />";
                break;
            case 'boolean':
                $field = "<label><input type='checkbox' v-model='form.{$name}' /> {$label}</label>";
                break;
            case 'date':
                $field = "<label>{$label}</label>\n      <input type='date' v-model='form.{$name}' />";
                break;
            case 'time':
                $field = "<label>{$label}</label>\n      <input type='time' v-model='form.{$name}' />";
                break;
            case 'datetime':
                $field = "<label>{$label}</label>\n      <input type='datetime-local' v-model='form.{$name}' />";
                break;
            default:
                $field = "<label>{$label}</label>\n      <input type='text' v-model='form.{$name}' placeholder='{$label}' />";
        }

        $html[] = "      $field";
    }
    return implode("\n\n", $html);
}

function generateFormState($columns) {
    $s = [];
    foreach ($columns as $col) {
        $name = $col[0];
        $type = map_front_type($col[1]);

        if (preg_match('/^id/i', $name)) {
            $s[] = "  {$name}: null";
            continue;
        }

        switch ($type) {
            case "number":
                $s[] = "  {$name}: null";
                break;
            case "boolean":
                $s[] = "  {$name}: false";
                break;
            default:
                $s[] = "  {$name}: null";
                break;
        }
    }
    return implode(",\n", $s);
}

function generateListData($columns) {
    $html = [];
    foreach ($columns as $col) {
        $name = $col[0];
        if (preg_match('/^id/i', $name)) continue;
        $html[] = "        <span>{{ u.{$name} }}</span>";
    }
    return implode("\n", $html);
}

function detect_primary_key($columns) {
    foreach ($columns as $col) {
        if (preg_match('/^id/i', $col[0])) return $col[0];
    }
    return "id";
}

/*******************************************
 *      SIDEBAR DINÁMICO
 *******************************************/
function addToVueSidebar($outputDir, $entidadUpper, $entidadLower, $entidadKebab) {
    $sidebar = $outputDir . "/src/components/Sidebar.vue";

    if (!file_exists($sidebar)) return;

    $content = file_get_contents($sidebar);

    if (str_contains($content, "/$entidadKebab")) return;

    $marker = "<!-- AUTO-GENERATED-LINKS -->";

    $link = <<<HTML
    <li>
      <router-link to="/$entidadKebab" class="sidebar-link">
        <span>$entidadUpper</span>
      </router-link>
    </li>
HTML;

    $content = str_replace($marker, $marker . "\n" . $link, $content);

    file_put_contents($sidebar, $content);
}

/*******************************************
 *     NORMALIZAR Y PREPARAR ENTIDAD
 *******************************************/
$columns = normalizeColumns($columns);
$primaryKey = detect_primary_key($columns);

$entidadUpper = ucfirst($tableName);
$entidadLower = strtolower($tableName);
$entidadKebab = toKebabCase($entidadUpper);

$outputDir = $outputBase . DIRECTORY_SEPARATOR . $entidadUpper;
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);

/*******************************************
 *   COPIAR PLANTILLAS DEL FOLDER + REPLACE
 *******************************************/
$rii = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        fn($file) => $file->isDir() ? !in_array($file->getFilename(), ['node_modules', '.git', 'dist']) : true
    ),
    RecursiveIteratorIterator::SELF_FIRST
);

$generatedFiles = [];

foreach ($rii as $file) {
    if ($file->isDir()) continue;

    $source = $file->getPathname();
    $relative = str_replace($baseDir, '', $source);

    $relative = str_replace("Entidad", $entidadUpper, $relative);

    $dest = $outputDir . $relative;
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0777, true);

    $content = file_get_contents($source);
    if ($content === false) continue;

    $rep = [
        "{Entidad}"        => $entidadUpper,
        "{entidad}"        => $entidadLower,
        "{EntidadKebab}"   => $entidadKebab,
        "{primaryKey}"     => $primaryKey,
    ];
    $content = str_replace(array_keys($rep), array_values($rep), $content);

    if (str_contains($content, "{columnsFormFields}"))
        $content = str_replace("{columnsFormFields}", generateFormFields($columns), $content);

    if (str_contains($content, "{columnsFormState}"))
        $content = str_replace("{columnsFormState}", generateFormState($columns), $content);

    if (str_contains($content, "{columnsListData}"))
        $content = str_replace("{columnsListData}", generateListData($columns), $content);


      /*******************************************
     * NUEVO: Reemplazar import y uso del service dinámico en List y View
     *******************************************/
    if (str_ends_with($dest, "List.vue") || str_ends_with($dest, "View.vue")) {
        $serviceImport = "import {$entidadLower}Service from '../services/{$entidadLower}.service.js';";
        if (str_contains($content, "{serviceImport}")) {
            $content = str_replace("{serviceImport}", $serviceImport, $content);
        }

        if (str_contains($content, "{serviceCalls}")) {
            $calls = <<<JS
// NUEVO: Llamadas al service dinámicas
const fetch{$entidadUpper} = async () => {
    const res = await {$entidadLower}Service.list();
    {$entidadLower}.value = res.data;
};
JS;
            $content = str_replace("{serviceCalls}", $calls, $content);
        }
    }


    file_put_contents($dest, $content);
    $generatedFiles[] = $dest;
}

/*******************************************
 *      AGREGAR AL SIDEBAR
 *******************************************/
addToVueSidebar($outputDir, $entidadUpper, $entidadLower, $entidadKebab);

/*******************************************
 *      NPM INSTALL AUTOMÁTICO
 *******************************************/
chdir($outputDir);
exec("npm install 2>&1", $npmOutput, $npmStatus);

/*******************************************
 *      CREAR SERVICE AXIOS DINÁMICO
 *******************************************/
$servicesDir = $outputDir . "\\src\\services";
if (!is_dir($servicesDir)) mkdir($servicesDir, 0777, true);

$serviceFile = $servicesDir . "\\{$entidadLower}.service.js";

$service = <<<JS
import axios from "axios";

const api = axios.create({
   baseURL: import.meta.env.VITE_API_BASE || "http://localhost:8080",
  timeout: 30000,
});

const basePath = "/api/{$entidadLower}";

export default {
  list() { return api.get(`\${basePath}/listar{$entidadLower}`); },
  get(id) { return api.get(`\${basePath}/obtener{$entidadLower}/\${id}`); },
  create(data) { return api.post(`\${basePath}/crear{$entidadLower}`, data); },
  update(id, data) { return api.put(`\${basePath}/actualizar{$entidadLower}/\${id}`, data); },
  delete(id) { return api.delete(`\${basePath}/eliminar{$entidadLower}/\${id}`); }
};
JS;

file_put_contents($serviceFile, $service);


/*******************************************
 *      RESPUESTA FINAL
 *******************************************/
$logs = ob_get_clean();

echo json_encode([
    "success" => true,
    "message" => "CRUD Vue generado exitosamente.",
    "entity" => $entidadUpper,
    "tableName" => $tableName,
    "primaryKey" => $primaryKey,
    "databaseName" => $databaseName,
    "outputDir" => $outputDir,
    "generatedFilesCount" => count($generatedFiles),
    "generatedFiles" => $generatedFiles,
    "service" => $serviceFile,
    "npmStatus" => $npmStatus,
    "npmOutput" => $npmOutput,
    "logs" => $logs
], JSON_PRETTY_PRINT);

exit;
