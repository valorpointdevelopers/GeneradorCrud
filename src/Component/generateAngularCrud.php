<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 600);   // Extiende la ejecución
set_time_limit(600);      
ignore_user_abort(true);              // Evita corte si el usuario cierra la pestaña


// NUEVO: iniciar sesión para acceder al uuid guardado
session_start();

function appendRoute($outputPath, $config) {
    $routesFile = $outputPath . "/src/app/app.routes.ts";
    if (!file_exists($routesFile)) return;

    $content = file_get_contents($routesFile);

    $import = "import { {$config['Entidades']}Component } from './{$config['entidades']}/{$config['entidades']}.component';";
    $routeLine = "  { path: '{$config['entidades']}', component: {$config['Entidades']}Component },";

    // evitar duplicados
    if (strpos($content, $routeLine) !== false) return;

    // insertar import
    $content = preg_replace(
        "/(\/\/ IMPORTS START)/",
        "$1\n$import",
        $content
    );

    // insertar route
    $content = preg_replace(
        "/(\/\/ MODULES START)/",
        "$1\n$routeLine",
        $content
    );

    file_put_contents($routesFile, $content);
}


function appendSidebarLink($outputPath, $config) {
    $sidebarFile = $outputPath . "/src/app/sidebar/sidebar.component.html";
    if (!file_exists($sidebarFile)) return;

    $content = file_get_contents($sidebarFile);

    $newLink = "    <li><a routerLink=\"/{$config['entidades']}\">" . ucfirst($config['entidades']) . "</a></li>";

    if (strpos($content, $newLink) !== false) return;

    $content = preg_replace(
        "/(<!-- MENU START -->)/",
        "$1\n$newLink",
        $content
    );

    file_put_contents($sidebarFile, $content);
}


// NUEVO: leer el JSON enviado desde el frontend
$input = json_decode(file_get_contents("php://input"), true);


// NUEVO: validar que vengan datos correctos
if (!$input || !isset($input['tableName']) || !isset($input['columns'])) {
    echo json_encode(["error" => "Faltan parámetros tableName o columns"]);
    exit;
}

// NUEVO: obtener tableName y columns igual que en Spring
$moduleNames = is_array($input['tableName']) ? $input['tableName'] : [$input['tableName']];
$columns = $input['columns'];

// NUEVO: recuperar el uuid o databaseName de sesión o del frontend
$databaseName = $input['databaseName'] ?? ($_SESSION['uuid'] ?? null);
if (!$databaseName) {
    echo json_encode(["error" => "No se encontró databaseName ni uuid en sesión"]);
    exit;
}

$templateGlobalPath = 'C:\\xamppchido\\htdocs\\adminlte-dashboard\\templates\\frontend-crud';
$templateModulePath = 'C:\\xamppchido\\htdocs\\adminlte-dashboard\\templates\\frontend-crud\\src\\app\\productos';
$outputPath = 'C:\xamppchido\htdocs\adminlte-dashboard\outputAngular';



function normalizeColumns($cols) {
    $out = [];
    foreach ($cols as $col) {
        // si viene como array indexado [name,type]
        if (is_array($col)) {
            // admitir formatos: [name,type] o ['name' => 'x','type'=>'y']
            if (isset($col[0]) && isset($col[1])) {
                $name = $col[0];
                $type = $col[1];
            } else {
                // intentar detectar keys comunes
                $name = $col['name'] ?? $col['column'] ?? null;
                $type = $col['type'] ?? $col['datatype'] ?? 'string';
            }
        } elseif (is_object($col)) {
            $name = $col->name ?? $col->column ?? null;
            $type = $col->type ?? $col->datatype ?? 'string';
        } else {
            // si viene solo nombre como string
            $name = $col;
            $type = 'string';
        }

        if (!$name) continue;

        $out[] = [ (string)$name, (string)$type ];
    }
    return $out;
}

function generateFormFieldsHtml($columns, $config) {
    $html = [];
    foreach ($columns as $col) {
        $colName = $col[0];
        $colType = strtolower($col[1]);

        if (in_array($colType, ['int','integer','number','float','double'])) {
            $inputType = 'number';
        } elseif (in_array($colType, ['boolean','bool'])) {
            $inputType = 'checkbox';
        } elseif (in_array($colType, ['text','textarea'])) {
            $inputType = 'textarea';
        } else {
            $inputType = 'text';
        }

        $model = "selected{$config['Entid']}.$colName";
        $nameAttr = $colName;

        if ($inputType === 'checkbox') {
            $field = "<label>\n  <input type=\"checkbox\" [(ngModel)]=\"$model\" name=\"$nameAttr\" /> " . ucfirst($colName) . "\n</label>";
        } elseif ($inputType === 'textarea') {
            $field = "<textarea [(ngModel)]=\"$model\" name=\"$nameAttr\" placeholder=\"" . ucfirst($colName) . "\"></textarea>";
        } else {
            $step = $inputType === 'number' ? " step=\"1\"" : "";
            $field = "<input type=\"$inputType\"$step [(ngModel)]=\"$model\" name=\"$nameAttr\" placeholder=\"" . ucfirst($colName) . "\" />";
        }

        $html[] = "      $field";
    }
    return implode("\n", $html);
}

function generateListItemsHtml($columns, $config) {
    $entid = $config['entid'];
    $list = [];
    $list[] = "    <li *ngFor=\"let {$entid} of paginated{$config['Entids']}\">";

    foreach ($columns as $col) {
        $colName = $col[0];

        if (stripos($colName, 'precio') !== false || stripos($colName, 'amount') !== false) {
            $list[] = "      <span>{{ {$entid}.{$colName} | currency:'MXN' }}</span>";
        } else {
            $list[] = "      <span>{{ {$entid}.{$colName} }}</span>";
        }
    }

    $list[] = "      <div>";
    $list[] = "        <button (click)=\"abrirModal({$entid})\"><i class=\"fas fa-edit\"></i></button>";
    $list[] = "        <button (click)=\"confirmDelete({$entid}.id)\"><i class=\"fas fa-trash\"></i></button>";
    $list[] = "      </div>";
    $list[] = "    </li>";

    return implode("\n", $list);
}





// NUEVO: usar columnas normalizadas
$columns = normalizeColumns($columns);


function generateModelConfig($baseName) {
    $capitalized = ucfirst($baseName);

    return [
        'Entidad' => $capitalized,
        'Entid' => $capitalized,
        'entid' => strtolower($baseName),
        'entids' => strtolower($baseName),
        'Entids' => $capitalized,
        'entidad' => strtolower($baseName),
        'entidades' => strtolower($baseName),
        'Entidades' => $capitalized
    ];
}

function createOutputFolder($outputPath) {
    if (!file_exists($outputPath)) {
        mkdir($outputPath, 0777, true);
        echo "Carpeta principal creada: $outputPath\n";
    } else {
        echo "La carpeta principal ya existe: $outputPath\n";
    }
}


function createAngularProjectStructure($templatePath, $outputPath) {
    $excludeDirs = ['.git', '.angular', '.idea', 'headphones','productos', 'node_modules', '.vscode'];
    $excludeFiles = ['.editorconfig', '.gitignore'];

    $directoryIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($templatePath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($directoryIterator as $item) {
        foreach ($excludeDirs as $dir) {
            if (str_contains($item->getPathname(), $dir)) {
                continue 2;
            }
        }

        foreach ($excludeFiles as $file) {
            if (str_contains($item->getFilename(), $file)) {
                continue 2;
            }
        }

       // NUEVO: forzar targetPath en minúsculas para evitar conflictos de casing en Windows
        $subPathName = $directoryIterator->getSubPathName();
        $targetPath = $outputPath . '/' . $subPathName;

        if ($item->isDir()) {
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
                echo "Carpeta creada: $targetPath\n";
            }
        } else {
            // NUEVO: si target file ya existe con distinto casing, sobrescribir la versión inferior
            if (file_exists($targetPath) && strtolower($targetPath) !== $targetPath) {
                // no hacemos nada especial, copy sobrescribe
            }
            copy($item, $targetPath);
            echo "Archivo copiado: $targetPath\n";
        }
    }
}


// NUEVO: función que normaliza y fuerza nombres de archivo en minúsculas en el output
function safeOutputFileName($moduleDir, $fileName, $moduleName) {
    // reemplazar 'productos' por $moduleName y forzar minúsculas donde importe
    $outputFileName = str_replace('productos', $moduleName, $fileName);
    if (str_contains($outputFileName, 'producto.model.ts')) {
        $outputFileName = "{$moduleName}.model.ts";
    }
    $outputFileName = preg_replace('/\.template\.(ts|html|css)$/', ".$1", $outputFileName);

    // NUEVO: forzar todo el nombre de archivo en minúsculas (menor riesgo de casing issue)
    $outputFileName = strtolower($outputFileName);

    return $moduleDir . '/' . $outputFileName;
}





function generateModuleFiles($inputDir, $outputDir, $config, $columns, $moduleName)
{
    $moduleDir = $outputDir . '/src/app/' . $config['entidades'];

    if (!file_exists($moduleDir)) {
        mkdir($moduleDir, 0777, true);
    }

    /* ============================================
        Detectar llave primaria automáticamente
       ============================================ */
    $primaryKey = null;

    foreach ($columns as $column) {
        $colName = $column[0];

        if (preg_match('/^id/i', $colName)) {
            $primaryKey = $colName;
            break;
        }
    }
    /* ============================================
       ============================================ */

    /* ============================================
       Mapa de tipos FRONT (Texto, Número...)
       ============================================ */
    $typeMap = [
        'texto' => 'text',
        'número' => 'number',
        'numero' => 'number',
        'fecha' => 'date',
        'fecha y hora' => 'datetime-local'
    ];
    /* ============================================ */


       /* ============================================
          Detectar PRIMERA columna tipo TEXTO
       ============================================ */
     $firstTextColumn = null;

    foreach ($columns as $column) {
        $colName = $column[0];
        $rawType = strtolower($column[1]);

        // columna NO numérica
        if (!preg_match('/^(int|integer|number|numeric|float|double|long|bigint|smallint|tinyint|boolean|bool)$/', $rawType)) {
            $firstTextColumn = $colName;
            break;
        }
    }

    // fallback: usar la primera columna siempre
    if (!$firstTextColumn) {
        $firstTextColumn = $columns[0][0];
    }
    /* ============================================ */

    // fallback: usar la primera columna siempre
    if (!$firstTextColumn) {
        $firstTextColumn = $columns[0][0];
    }
    /* ============================================
       ============================================ */

    $files = scandir($inputDir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $inputPath = $inputDir . '/' . $file;
        $outputPath = safeOutputFileName($moduleDir, $file, $moduleName);

        if (is_file($inputPath)) {

            $content = file_get_contents($inputPath);

            // Reemplazo estándar
            foreach ($config as $key => $value) {
                $content = str_replace("{" . $key . "}", $value, $content);
            }
            /* ============================================
            FIX: confirmDelete acepte null/undefined
            ============================================ */

            // 1. Reemplazos directos existentes
            $content = str_replace(
                "confirmDelete({entid}Id: number)",
                "confirmDelete({entid}Id: number | null | undefined)",
                $content
            );

            $content = str_replace(
                "confirmDelete({ID}: number)",
                "confirmDelete({ID}: number | null | undefined)",
                $content
            );

            $content = str_replace(
                "confirmDelete(id: number)",
                "confirmDelete(id: number | null | undefined)",
                $content
            );

            // 2. FIX universal → convierte *cualquier* confirmDelete(x: number)
            $content = preg_replace(
                '/confirmDelete\s*\(\s*([a-zA-Z0-9_]+)\s*:\s*number\s*\)/',
                'confirmDelete($1: number | null | undefined)',
                $content
            );

            // 3. Inyectar null-check si no existe
            $content = preg_replace_callback(
                '/confirmDelete\s*\(\s*([a-zA-Z0-9_]+)\s*:[^)]+\)\s*\{/',
                function ($m) {
                    $param = $m[1];
                    return "confirmDelete({$param}: number | null | undefined) {\n        if ({$param} == null) return;";
                },
                $content
            );



            // Reemplazar marcador {primaryKey} en cualquier archivo
if ($primaryKey !== null) {
    $content = str_replace('{primaryKey}', $primaryKey, $content);
    $content = str_replace('{PRIMARYKEY}', $primaryKey, $content);
}

if ($primaryKey !== null && strpos($file, 'service') !== false) {
    $protectPattern = '/(\/eliminar[a-zA-Z0-9_\/\-]*\/\$\{\s*)id(\s*\})/i';
    $content = preg_replace($protectPattern, '$1__KEEP_ID__$2', $content);
}

/* ============================================================
   FIX GENERAL PARA TODOS LOS ARCHIVOS → {id} y {ID}
   ============================================================ */
if ($primaryKey !== null) {
    // Reemplazos directos
    $content = str_replace('{id}', $primaryKey, $content);
    $content = str_replace('{ID}', $primaryKey, $content);

    // Reemplazo para item.{id} → item.primaryKey
    $content = preg_replace(
        '/([a-zA-Z_][a-zA-Z0-9_]*)\.\{id\}/',
        '$1.' . $primaryKey,
        $content
    );

    // Reemplazo para item.{ID} → item.primaryKey
    $content = preg_replace(
        '/([a-zA-Z_][a-zA-Z0-9_]*)\.\{ID\}/',
        '$1.' . $primaryKey,
        $content
    );

    // Reemplazo de selectedEntidad.{ID} y {id}
    $content = preg_replace(
        '/selected' . $config['Entid'] . '\.\{ID\}/',
        'selected' . $config['Entid'] . '.' . $primaryKey,
        $content
    );

    $content = preg_replace(
        '/selected' . $config['Entid'] . '\.\{id\}/',
        'selected' . $config['Entid'] . '.' . $primaryKey,
        $content
    );
}


if ($primaryKey !== null && strpos($file, 'service') !== false) {
    $content = str_replace('__KEEP_ID__', 'id', $content);
}
            
/* ============================================================
   FIX UPDATE — Forzar SIEMPRE item.<PRIMARYKEY>
   ============================================================ */
if ($primaryKey !== null && strpos($file, 'service') !== false) {

    // Forzar cualquier variable.id → variable.primaryKey
    $content = preg_replace(
        '/([a-zA-Z_][a-zA-Z0-9_]*)\.id\b/',
        '$1.' . $primaryKey,
        $content
    );

    // Forzar ${variable.id}
    $content = preg_replace(
        '/\$\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\.id\s*\}/',
        '${$1.' . $primaryKey . '}',
        $content
    );
}















/* ============================================================
   ==========  MODELO.TS  → {COLUMNS}  =========================
   ============================================================ */
     if (str_contains($content, '{COLUMNS}')) {

                $modelColumns = [];

                foreach ($columns as $column) {
                    $colName = $column[0];
                    $rawType = strtolower($column[1]);

                    // convertir Texto/Número
                    $frontType = $typeMap[$rawType] ?? 'text';

                    $isPrimary = preg_match('/^id/i', $colName);

                    if ($isPrimary) {
                        $modelColumns[] = "    {$colName}?: number | null;   // AUTO PK";
                        continue;
                    }

                    switch ($frontType) {

                    case 'number':
                        $tsType = 'number | null';
                        break;

                    case 'date':
                        $tsType = 'string | null';           
                        break;

                    case 'datetime-local':
                        $tsType = 'string | null';           
                        break;

                    default: // text
                        $tsType = 'string';
                        break;
                }

                    $modelColumns[] = "    {$colName}: {$tsType};";
                }

                $content = str_replace("{COLUMNS}", implode("\n", $modelColumns), $content);
            }



          /* ============================================================
            ==========  COMPONENT.TS VACÍO → {EMPTY_COLUMNS} ===========
            ============================================================ */
        if (str_contains($content, '{EMPTY_COLUMNS}')) {

    $empty = [];

    foreach ($columns as $column) {

        $colName   = $column[0];
        $rawType   = strtolower($column[1]);

        // NUEVO → uso del mapa para traducir "texto", "número", "fecha", etc.
        $frontType = $typeMap[$rawType] ?? 'text';

        $isPrimary = preg_match('/^id/i', $colName);

        // ========================
        //   PRIMARY KEY
        // ========================
        if ($isPrimary) {
            $empty[] = "      {$colName}: null,   // AUTO PK";
            continue;
        }

        // ========================
        //   Tipos soportados ya traducidos
        // ========================
        switch ($frontType) {

            case 'number':            // NUEVO
                $empty[] = "      {$colName}: null,";
                break;

            case 'date':              // NUEVO
                $empty[] = "      {$colName}: null,";
                break;

            case 'datetime-local':    // NUEVO
                $empty[] = "      {$colName}: null,";
                break;

            default:  // text
                $empty[] = "      {$colName}: '',";
                break;
        }
    }

    $content = str_replace('{EMPTY_COLUMNS}', implode("\n", $empty), $content);
}


            /* ============================================================
               =========== FORM HTML  → {FORM_FIELDS} =====================
               ============================================================ */
           if (str_contains($content, '{FORM_FIELDS}')) {

    $fields = [];

    foreach ($columns as $column) {
        $colName = $column[0];
        $rawType = strtolower($column[1]);

        // No mostrar la primary key en el formulario
        if (preg_match('/^id/i', $colName)) {
            continue;
        }

        // NUEVO → Detectar tipo exactamente como lo envía el frontend
        if ($rawType === 'número' || $rawType === 'numero') {
            $htmlType = "number";

        } elseif ($rawType === 'fecha y hora') {
            $htmlType = "datetime-local";

        } elseif ($rawType === 'fecha') {
            $htmlType = "date";

        } else {
            $htmlType = "text";
        }

        $fields[] = <<<HTML
      <label>{$colName}</label>
      <input type="{$htmlType}" [(ngModel)]="selected{$config['Entid']}.{$colName}"
             name="{$colName}" required />
HTML;
    }

    $content = str_replace('{FORM_FIELDS}', implode("\n", $fields), $content);
}

          /* ============================================================
   ========= LISTA HTML → {LIST_ITEMS} =======================
   ============================================================ */
if (str_contains($content, '{LIST_ITEMS}')) {

    $items = [];

    // Construir los <span> de cada columna
    $spans = [];
    foreach ($columns as $column) {
        $colName = $column[0];

        // Omitir llave primaria (id)
        if (preg_match('/^id/i', $colName)) continue;

        $spans[] = "        <span>{{ {$config['entid']}.{$colName} }}</span>";
    }

    $spanHtml = implode("\n", $spans);

    // === NUEVO: si hay PK úsala, si no NO genera punto ===
    $deleteParam = $primaryKey ? "{$config['entid']}.{$primaryKey}" : $config['entid'];

    // Crear el <li> completo con botones de acción
    $items[] = <<<HTML
    <li *ngFor="let {$config['entid']} of paginated{$config['Entids']}">
$spanHtml
        <button class="editar" (click)="abrirModal({$config['entid']})">
            <i class="fas fa-edit"></i>
        </button>
        <button class="eliminar" (click)="confirmDelete({$deleteParam})">
            <i class="fas fa-trash"></i>
        </button>
    </li>
HTML;

    // Reemplazar en el template
    $content = str_replace('{LIST_ITEMS}', implode("\n", $items), $content);
}


/* ============================================================
   =============== PAGINATION_VARS =============================
   ============================================================ */
if (str_contains($content, '/* PAGINATION_VARS */')) {

    $vars = <<<TS
itemsPerPage: number = 5;
currentPage: number = 1;


searchTerm: string = '';
TS;

    // Reemplazar placeholders dentro del bloque vars usando $config
    foreach ($config as $k => $v) {
        $vars = str_replace('{' . $k . '}', $v, $vars);
    }

    // Si existe primary key, reemplazar también {id}/{ID} dentro del bloque (por si acaso)
    if ($primaryKey !== null) {
        $vars = str_replace('{id}', $primaryKey, $vars);
        $vars = str_replace('{ID}', $primaryKey, $vars);
    }

    $content = str_replace('/* PAGINATION_VARS */', $vars, $content);
}

/* ============================================================
   FILTER_CODE — Buscar en múltiples columnas (no sólo id)
   Se genera un filtro que prueba todas las columnas no-PK
   y que no sean estrictamente numéricas.
   ============================================================ */
if (str_contains($content, '{FILTER_CODE}')) {

    // elegir columna por defecto para fallback (ya tienes $firstTextColumn)
    $textCol = $firstTextColumn;

    // construir condiciones JS dinámicas según las columnas recibidas
    $checks = [];
    foreach ($columns as $column) {
        $colName = $column[0];
        $rawType = strtolower($column[1]);

        // OMITIR llave primaria
        if (preg_match('/^id/i', $colName)) continue;

        // para seguridad, evitar usar campos numéricos en la búsqueda (no ayudan)
        if (preg_match('/^(int|integer|number|numeric|float|double|long|bigint|smallint|tinyint|boolean|bool)$/', $rawType)) {
            continue;
        }

        // condición que comprueba que exista la propiedad y que contenga el término buscado
        // usamos toString() para cubrir fechas y otros tipos convertibles
        $checks[] = "item?.{$colName} && String(item.{$colName}).toLowerCase().includes(this.searchTerm.toLowerCase())";
    }

    // si no se generó ninguna condición válida (por alguna razón), usar el fallback
    if (empty($checks)) {
        $checks[] = "item?.{$textCol} && String(item.{$textCol}).toLowerCase().includes(this.searchTerm.toLowerCase())";
    }

    $checksJs = implode(" ||\n        ", $checks);

    $filter = <<<TS
this.filtered{Entids} = this.searchTerm
    ? this.{entids}.filter(item =>
        {$checksJs}
      )
    : [...this.{entids}];

this.currentPage = 1;
this.updatePagination();
TS;

    // Reemplazar placeholders dentro del bloque filter usando $config
    foreach ($config as $k => $v) {
        $filter = str_replace('{' . $k . '}', $v, $filter);
    }

    // reemplazar {id}/{ID} si es necesario (mantén tu lógica actual)
    if ($primaryKey !== null) {
        $filter = str_replace('{id}', $primaryKey, $filter);
        $filter = str_replace('{ID}', $primaryKey, $filter);
    }

    $content = str_replace('{FILTER_CODE}', $filter, $content);
}

/* ============================================================
   =============== PAGINATION_METHODS (build & inject) ======
   ============================================================ */
if (str_contains($content, '/* PAGINATION_METHODS */')) {

    $methods = <<<TS
updatePagination() {
    const startIndex = (this.currentPage - 1) * this.itemsPerPage;
    const endIndex = startIndex + this.itemsPerPage;

    this.paginated{Entids} = this.filtered{Entids}.slice(startIndex, endIndex);
}

nextPage() {
    if (this.currentPage < this.totalPages) {
        this.currentPage++;
        this.updatePagination();
    }
}

previousPage() {
    if (this.currentPage > 1) {
        this.currentPage--;
        this.updatePagination();
    }
}

get totalPages(): number {
    return Math.ceil(this.filtered{Entids}.length / this.itemsPerPage);
}
TS;

    // Reemplazar placeholders dentro del bloque methods usando $config
    foreach ($config as $k => $v) {
        $methods = str_replace('{' . $k . '}', $v, $methods);
    }

    // reemplazar {id}/{ID} si es necesario
    if ($primaryKey !== null) {
        $methods = str_replace('{id}', $primaryKey, $methods);
        $methods = str_replace('{ID}', $primaryKey, $methods);
    }

    $content = str_replace('/* PAGINATION_METHODS */', $methods, $content);
}


/* ============================================================
   =============== PAGINACIÓN HTML =============================
   ============================================================ */
if (str_contains($content, '<!-- PAGINATION_PLACEHOLDER -->') &&
    !str_contains($content, 'currentPage')) {

    $paginationHtml = <<<HTML
<div class="paginacion">
    <button [disabled]="currentPage === 1" (click)="previousPage()">Anterior</button>
    <span>Página {{ currentPage }} de {{ totalPages }}</span>
    <button [disabled]="currentPage === totalPages" (click)="nextPage()">Siguiente</button>
</div>
HTML;

    $content = str_replace('<!-- PAGINATION_PLACEHOLDER -->', $paginationHtml, $content);
}


            /* ============================================================
               ============= Avisos de placeholders =======================
               ============================================================ */
            if (preg_match('/\{[A-Za-z]+}/', $content)) {
                echo "Advertencia: Existen placeholders sin reemplazar en $outputPath\n";
            }

            if (!file_exists(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0777, true);
            }

            file_put_contents($outputPath, $content);
            echo "Archivo generado: $outputPath\n";
        }
    }
}






function generateModelColumns($columns) {
    $content = [];
     foreach ($columns as $column) {
        $name = $column[0];
        $typeRaw = strtolower($column[1]);

        // PRIMARY KEY: nombre que inicia con id
        if (preg_match('/^id/i', $name)) {
            $content[] = "  {$name}: number; // PRIMARY KEY";
            continue;
        }

        $type = match (strtolower($column[1])) {
            'string' => 'string',
            'int', 'float', 'double', 'number' => 'number',
            'boolean' => 'boolean',
            default => 'string'
        };
        $content[] = "  {$column[0]}: {$type};";
    }
    return implode("\n", $content);
}



function generateEmptyComponentColumns($columns) {
    $content = [];

    foreach ($columns as $column) {
        $colName = $column[0];
        $type = strtolower($column[1]);

        // NUEVO: detectar primary key auto-increment como Spring
        $isNumeric = in_array($type, ['int', 'integer', 'number', 'float', 'double']);
        $isPrimaryLikeSpring = $isNumeric && preg_match('/^id/i', $colName);

        if ($isPrimaryLikeSpring) {
            // id auto increment → siempre null
            $content[] = "      {$colName}: null";
            continue;
        }

        // valores por defecto para otros campos
        $defaultValue = match ($type) {
            'string' => "''",
            'int', 'number', 'float', 'double' => '0',
            'boolean' => 'false',
            default => "''"
        };

        $content[] = "      {$colName}: {$defaultValue}";
    }

    return implode(",\n", $content);
}





function addUniqueImport($content, $importStatement) {
    // Extraer todos los imports actuales
    preg_match_all('/^import .*?;$/m', $content, $matches);
    $existingImports = $matches[0] ?? [];

    // Verificar si el import ya existe y evitar duplicados
    if (!in_array(trim($importStatement), array_map('trim', $existingImports))) {
        $existingImports[] = $importStatement; // Añadir solo si no existe
    }

    // Ordenar y consolidar los imports
    sort($existingImports);

    // Eliminar todos los imports del contenido original
    $contentWithoutImports = preg_replace('/^import .*?;$/m', '', $content);

    // Reescribir los imports únicos
    return implode("\n", $existingImports) . "\n\n" . ltrim($contentWithoutImports);
}


function consolidateImports($content) {
    // Extraer todos los imports únicos
    preg_match_all('/^import .*?;$/m', $content, $matches);
    $uniqueImports = array_unique($matches[0] ?? []);

    // Ordenar los imports alfabéticamente
    sort($uniqueImports);

    // Eliminar todos los imports existentes del contenido original
    $contentWithoutImports = preg_replace('/^import .*?;$/m', '', $content);

    // Reescribir los imports únicos al principio del archivo
    return implode("\n", $uniqueImports) . "\n\n" . ltrim($contentWithoutImports);
}

function updateAngularFiles($outputPath, $config, $isFirstModule = false) {
    $filesToUpdate = [
        'routes' => $outputPath . '/src/app/app.routes.ts',
        'sidebar' => $outputPath . '/src/app/sidebar.component.html',
        'appComponent' => $outputPath . '/src/app/app.component.ts',
    ];

    foreach ($filesToUpdate as $type => $filePath) {
        if (!file_exists($filePath)) {
            echo "Archivo no encontrado: $filePath\n";
            continue;
        }

        $content = file_get_contents($filePath);

        switch ($type) {
            case 'routes':
                $moduleSectionStart = "// MODULES START";
                $moduleSectionEnd = "// MODULES END";

                // Asegurar delimitadores
                if (!str_contains($content, $moduleSectionStart)) {
                    $content = preg_replace('/\];/', "$moduleSectionStart\n$moduleSectionEnd\n];", $content);
                }

                // Reemplazar placeholders antes de generar el contenido
                $content = str_replace(['{entidades}', '{Entidades}'], [$config['entidades'], $config['Entidades']], $content);

                // Generar nueva ruta
                $newRoute = "  { path: '{$config['entidades']}', component: {$config['Entidades']}Component }";
                if (!str_contains($content, "path: '{$config['entidades']}'")) {
                    $content = preg_replace(
                        '/' . preg_quote($moduleSectionStart, '/') . '(.*?)' . preg_quote($moduleSectionEnd, '/') . '/s',
                        "$moduleSectionStart\n$newRoute\n$1$moduleSectionEnd",
                        $content
                    );
                    echo "Ruta añadida en app.routes.ts\n";
                }

                // Añadir el import del componente sin duplicados
                $importStatement = "import { {$config['Entidades']}Component } from './{$config['entidades']}/{$config['entidades']}.component';";
                $content = addUniqueImport($content, $importStatement);

                break;


            case 'sidebar':
                $newLink = "    <li><a routerLink=\"/{$config['entidades']}\">{$config['Entidades']}</a></li>";
                if (!str_contains($content, "routerLink=\"/{$config['entidades']}\"")) {
                    $content = preg_replace('/<\/ul>/', "$newLink\n</ul>", $content);
                    echo "Enlace añadido en sidebar.component.html\n";
                }
                break;


            case 'appComponent':
                // Añadir el import del componente sin duplicados
                $importStatement = "import { {$config['Entidades']}Component } from './{$config['entidades']}/{$config['entidades']}.component';";
                $content = addUniqueImport($content, $importStatement);

                // Añadir componente al array de imports si no existe
                $componentImport = "{$config['Entidades']}Component";
                if (!preg_match('/imports: \[.*?\b' . preg_quote($componentImport, '/') . '\b.*?\]/s', $content)) {
                    $content = preg_replace(
                        '/imports: \[(.*?)\]/s',
                        "imports: [\$1, $componentImport]",
                        $content
                    );
                    echo "Componente añadido al array de imports en app.component.ts\n";
                }
                break;

            default:
                echo "Tipo de archivo no reconocido: $type\n";
        }

        // Consolidar y reescribir los imports al final
        $content = consolidateImports($content);
        file_put_contents($filePath, $content);
    }
}






// EJECUCIÓN
createOutputFolder($outputPath);

// Crear estructura Angular UNA sola vez
$angularReadyFlag = $outputPath . "/.angular_initialized";

if (!file_exists($angularReadyFlag)) {
    createAngularProjectStructure($templateGlobalPath, $outputPath);
    file_put_contents($angularReadyFlag, "initialized");
}

$isFirstModule = true;

foreach ($moduleNames as $moduleName) {

    $config = generateModelConfig($moduleName);

    // Generar archivos del módulo (componentes, servicios, models)
    generateModuleFiles($templateModulePath, $outputPath, $config, $columns, $moduleName);

    // Agregar ruta al routing.module.ts
    appendRoute($outputPath, $config);

    // Agregar link al sidebar
    appendSidebarLink($outputPath, $config);

    // Actualizar app.module.ts y otros archivos globales SOLO para el primer módulo
    updateAngularFiles($outputPath, $config, $isFirstModule);

    // Después del primer módulo, ya no se vuelve a marcar como first
    $isFirstModule = false;
}

// -------------------------------------------------
// VERIFICAR NPM Y EJECUTAR npm install UNA SOLA VEZ
// -------------------------------------------------

$angularPath = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\outputAngular";
$npmCmd = "C:\\Program Files\\nodejs\\npm.cmd";
$npmInstalledFlag = $angularPath . "/.npm_installed";

// SI YA SE EJECUTÓ → evitar repetir siempre
if (file_exists($npmInstalledFlag)) {
    $npmMessage = "npm install ya se había ejecutado previamente. (Saltado)";
} else {

    // Verificar que npm exista
    if (!file_exists($npmCmd)) {
        $npmMessage = "ERROR: npm no está instalado o la ruta es incorrecta: $npmCmd";
    } else {

        // Verificar que npm responda
        exec("\"$npmCmd\" -v", $vOut, $vCode);

        if ($vCode !== 0) {
            $npmMessage = "ERROR: npm existe pero no responde correctamente";
        } else {

            // Ejecutar instalación desde la carpeta Angular
            $cmd = "cd /d \"$angularPath\" && \"$npmCmd\" install --force";

            // Ejecutar con buffers pequeños para evitar logs enormes
            $descriptorSpec = [
                1 => ["file", $angularPath . "/npm.log", "a"], // STDOUT → archivo
                2 => ["file", $angularPath . "/npm.log", "a"]  // STDERR → archivo
            ];

            $process = proc_open($cmd, $descriptorSpec, $pipes);

            if (is_resource($process)) {
                $npmCode = proc_close($process);

                if ($npmCode === 0) {
                    file_put_contents($npmInstalledFlag, "installed");
                    $npmMessage = "npm install ejecutado correctamente";
                } else {
                    $npmMessage = "Error en npm install. Ver archivo npm.log";
                }
            }
        }
    }
}


    
// Instalar dependencias
echo json_encode([
    "success" => true,
    "message" => "Proyecto Angular generado exitosamente.",
    "databaseName" => $databaseName,
      "log" => $log
]);

