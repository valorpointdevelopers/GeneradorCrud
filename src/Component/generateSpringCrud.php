<?php
session_start();

header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);
chdir("C:\\xamppchido\\htdocs\\adminlte-dashboard\\templates\\javacrud");


if (isset($GLOBALS['uuid']) && !empty($GLOBALS['uuid'])) {
    $sessionUUID = $GLOBALS['uuid']; // viene directo desde CallerLenguage.php
} else {
    $uuidFile = __DIR__ . "/../../uuid.txt"; // ruta al uuid usado por crear_tablas
    if (file_exists($uuidFile)) {
        $sessionUUID = trim(file_get_contents($uuidFile));
    } else {
        $sessionUUID = uniqid("session_"); // último recurso
    }
}

if (isset($GLOBALS['databaseName']) && !empty($GLOBALS['databaseName'])) {
    $sessionUUID = $GLOBALS['databaseName']; 
} else {
    // Si no viene desde CallerLenguage, intenta obtenerlo del JSON recibido
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input['uuid'])) {
        $sessionUUID = $input['uuid']; 
    } elseif (isset($input['databaseName'])) {
        $sessionUUID = $input['databaseName']; 
    }
}

if (isset($GLOBALS['tableName']) && isset($GLOBALS['columns'])) {
    $entityName = $GLOBALS['tableName'];
    $columns = $GLOBALS['columns'];
    $databaseName = $GLOBALS['databaseName'] ?? $sessionUUID; // NUEVO
} else {
    // Si se llama directamente vía API (como antes)
    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input['tableName']) || !isset($input['columns'])) {
        echo json_encode(["success" => false, "message" => "Faltan datos (tableName o columns)"]);
        exit;
    }

    $entityName = $input['tableName'];
    $columns = $input['columns'];
    $databaseName = $input['databaseName'] ?? $sessionUUID; // NUEVO
}

$projectName = $entityName;
$databaseName = $input['databaseName'] ?? $sessionUUID; 


// RUTA BASE DEL PROYECTO

$baseDir = "C:\\xamppchido\\htdocs\\adminlte-dashboard\\outputSpring" . DIRECTORY_SEPARATOR . $sessionUUID; // NUEVO
if (!file_exists($baseDir)) {
    mkdir($baseDir, 0777, true); 
}

$to = $baseDir . DIRECTORY_SEPARATOR . $projectName;
    


//  Función para mapear tipos de BD a tipos Java (mejor manejo de 'varchar(100)')
function map_type($type) {
    $t = strtolower(trim((string)$type));

    // mappers para etiquetas en español/comunes del frontend
    if (in_array($t, ['texto', 'string', 'varchar', 'char', 'text'])) return 'String';
    if (in_array($t, ['número', 'numero', 'int', 'integer', 'number'])) return 'Integer';
    if (in_array($t, ['bigint', 'long'])) return 'Long';
    if (in_array($t, ['decimal', 'float', 'double'])) return 'Double';
    if (in_array($t, ['booleano', 'bool', 'boolean'])) return 'Boolean';
    if (in_array($t, ['date', 'fecha'])) return 'LocalDate';

    // inferencia por subcadena (por si mandan "varchar(255)" u "int(11)")
    if (str_contains($t, 'varchar') || str_contains($t, 'char') || str_contains($t, 'text')) return 'String';
    if (str_contains($t, 'bigint')) return 'Long';
    if (str_contains($t, 'int') && !str_contains($t, 'tinyint')) return 'Integer';
    if (str_contains($t, 'tinyint') || str_contains($t, 'bool')) return 'Boolean';
    if (str_contains($t, 'double') || str_contains($t, 'float') || str_contains($t, 'decimal')) return 'Double';
    if (str_contains($t, 'date') || str_contains($t, 'time')) return 'LocalDate';

    return 'String';
}   


// Crear estructura del proyecto
function create_project_structure($to, $projectName) {
    $javaBaseDir = $to . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'java' . DIRECTORY_SEPARATOR . 'com' . DIRECTORY_SEPARATOR . strtolower($projectName);;
    $resourceDir = $to . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'resources';
    $subdirectories = ['controllers', 'entities', 'services', 'repository', 'utils'];

    if (!file_exists($to)) {
        mkdir($to, 0775, true);
        echo "Creando carpeta de proyecto: $to\n";
    }

    // Crear ruta Java base
    if (!file_exists($javaBaseDir)) {
        mkdir($javaBaseDir, 0775, true);
        echo "Creando carpeta java base: $javaBaseDir\n";
    }

    // Crear subdirectorios Java
    foreach ($subdirectories as $subdir) {
        $subdirPath = $javaBaseDir . DIRECTORY_SEPARATOR . $subdir;
        if (!file_exists($subdirPath)) {
            mkdir($subdirPath, 0775, true);
            echo "Creando carpeta: $subdirPath\n";
        }
    }

    // Crear resources
    if (!file_exists($resourceDir)) {
        mkdir($resourceDir, 0775, true);
        echo "Creando carpeta de recursos: $resourceDir\n";
    }
}

// Crear el archivo pom.xml
function generate_pom($to, $projectName) {
    $pomContent = <<<EOD
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/maven-v4_0_0.xsd">
    <modelVersion>4.0.0</modelVersion>
    <groupId>$projectName</groupId>
    <artifactId>$projectName</artifactId>
    <version>1.0-SNAPSHOT</version>

    <properties>
        <java.version>18</java.version>
        <spring.version>3.1.2</spring.version>
    </properties>

    <dependencies>
        <dependency>
            <groupId>com.h2database</groupId>
            <artifactId>h2</artifactId>
            <version>2.2.224</version>
            <scope>runtime</scope>
        </dependency>


        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter</artifactId>
            <version>\${spring.version}</version>
        </dependency>
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-data-jpa</artifactId>
            <version>\${spring.version}</version>
        </dependency>
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-web</artifactId>
            <version>\${spring.version}</version>
        </dependency>
        <dependency>
            <groupId>mysql</groupId>
            <artifactId>mysql-connector-java</artifactId>
            <version>8.0.33</version>
        </dependency>
    </dependencies>

    <repositories>
        <repository>
            <id>central</id>
            <url>https://repo.maven.apache.org/maven2</url>
        </repository>
    </repositories>

    <build>
        <plugins>
            <plugin>
                <groupId>org.apache.maven.plugins</groupId>
                <artifactId>maven-compiler-plugin</artifactId>
                <version>3.8.1</version>
                <configuration>
                    <source>\${java.version}</source>
                    <target>\${java.version}</target>
                </configuration>
            </plugin>
            <plugin>
                <groupId>org.springframework.boot</groupId>
                <artifactId>spring-boot-maven-plugin</artifactId>
                <version>\${spring.version}</version>
            </plugin>
        </plugins>
    </build>
</project>
EOD;

    file_put_contents($to . DIRECTORY_SEPARATOR . "pom.xml", $pomContent);
    echo "Archivo pom.xml generado.\n";
}

// Crear clase principal de Spring Boot
function generate_main_class($to, $projectName) {
    $javaBaseDir = $to . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'java' . DIRECTORY_SEPARATOR . 'com' . DIRECTORY_SEPARATOR . strtolower($projectName);
    if (!file_exists($javaBaseDir)) mkdir($javaBaseDir, 0775, true);

    $projectNameCamelCase = preg_replace('/[^a-zA-Z0-9]/', '', ucfirst(strtolower($projectName)));
    $mainClassContent = <<<JAVA
package com.$projectName;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;

@SpringBootApplication
public class {$projectNameCamelCase}Application {
    public static void main(String[] args) {
        SpringApplication.run({$projectNameCamelCase}Application.class, args);
    }
}
JAVA;

    file_put_contents($javaBaseDir . DIRECTORY_SEPARATOR . "{$projectNameCamelCase}Application.java", $mainClassContent);
    echo "Clase principal generada: {$projectNameCamelCase}Application.java\n";
}

// Crear archivo application.properties
function generate_application_properties($to, $projectName, $databaseName) {
    $resourceDir = $to . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'resources';
    if (!file_exists($resourceDir)) mkdir($resourceDir, 0775, true);

    // Configura conexión a MySQL usando la misma UUID que usa tu app
    $content = <<<PROP
spring.datasource.url=jdbc:mysql://localhost:3306/{$databaseName}?useSSL=false&serverTimezone=UTC
spring.datasource.username=root
spring.datasource.password=
spring.jpa.hibernate.ddl-auto=none
spring.jpa.properties.hibernate.dialect=org.hibernate.dialect.MySQLDialect
spring.jpa.hibernate.naming.physical-strategy=org.hibernate.boot.model.naming.PhysicalNamingStrategyStandardImpl
server.port=8080
PROP;

    file_put_contents($resourceDir . DIRECTORY_SEPARATOR . "application.properties", $content);
    echo "Archivo application.properties generado en: $resourceDir\n";
}

// Genera un controlador
function generate_controller($tablename, $columns, $to, $projectName) {
    $tablenameCamelCase = ucfirst(strtolower($tablename));
    $tablenameLowerCase = strtolower($tablename);
    $template = "controllers" . DIRECTORY_SEPARATOR . "EntidadController.java";

    if (!file_exists($template)) {
        echo "Plantilla controlador no encontrada: $template\n";
        return;
    }

    $readfile = file_get_contents($template);

    // Ruta de la carpeta controllers dentro del proyecto (usar $to)
    $ruta = $to . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "main" . DIRECTORY_SEPARATOR .
        "java" . DIRECTORY_SEPARATOR . "com" . DIRECTORY_SEPARATOR . strtolower($projectName) .
        DIRECTORY_SEPARATOR . "controllers";

    if (!file_exists($ruta)) mkdir($ruta, 0775, true);

    // Reemplazos
    $package = "package com." . strtolower($projectName) . ".controllers;\n\n";
    $readfile = str_replace('package com.{PACKAGE}.controllers;', $package, trim($readfile));

    $importEntities = "import com." . strtolower($projectName) . ".entities." . $tablenameCamelCase . "Entity;\n";
    $importServices = "import com." . strtolower($projectName) . ".services." . $tablenameCamelCase . "Service;\n";
    $importRepositories = "import com." . strtolower($projectName) . ".repository." . $tablenameCamelCase . "Repository;\n";

    $readfile = str_replace("import com.{PACKAGE}.entities.{EntidadM}Entity;", $importEntities, $readfile);
    $readfile = str_replace("import com.{PACKAGE}.services.{EntidadService};", $importServices, $readfile);
    $readfile = str_replace("import com.{PACKAGE}.repositorys.{EntidadRepository};", $importRepositories, $readfile);

    $prestep1 = str_replace("{ENTIDAD}", $tablename, $readfile);
    $prestep2 = str_replace("{EntidadM}", $tablenameCamelCase, $prestep1);
    $prestep3 = str_replace("{entidad}", $tablenameLowerCase, $prestep2);

    file_put_contents($ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Controller.java", $prestep3);
    echo "Archivo generado: " . $ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Controller.java\n";
}

// Genera una entidad
function generate_entity($tablename, $columns, $to, $projectName) {
    $tablenameCamelCase = ucfirst(strtolower($tablename));
    $tablenameLowerCase = strtolower($tablename);
    $template = "entitys" . DIRECTORY_SEPARATOR . "EntidadEntity.java";

    if (!file_exists($template)) {
        echo "Plantilla entidad no encontrada: $template\n";
        return;
    }

    $readfile = file_get_contents($template);

    // Ruta de la carpeta entities dentro del proyecto
    $ruta = $to . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "main" . DIRECTORY_SEPARATOR .
        "java" . DIRECTORY_SEPARATOR . "com" . DIRECTORY_SEPARATOR . strtolower($projectName) .
        DIRECTORY_SEPARATOR . "entities";

    if (!file_exists($ruta)) mkdir($ruta, 0775, true);

    // Reemplazos
    $package = "package com." . strtolower($projectName) . ".entities;\n\n";
    $readfile = str_replace('package com.{PACKAGE}.entities;', $package, trim($readfile));

    // Reemplazar imports genéricos si existen
    $readfile = str_replace("import com.{PACKAGE}.entities.{EntidadM}Entity;", "import javax.persistence.*;\n", $readfile);

    // Reemplazos de nombres
    $prestep1 = str_replace("{ENTIDAD}", $tablename, $readfile);
    $prestep2 = str_replace("{EntidadM}", $tablenameCamelCase, $prestep1);
    $prestep3 = str_replace("{entidad}", $tablenameLowerCase, $prestep2);

    // ===================== NUEVO: detectar si se usa LocalDate y agregar import =====================
$needsLocalDate = false;
foreach ($columns as $col) {
    // Compatibilidad: columnas pueden venir como array asociativo o numérico
    $rawType = is_array($col) ? ($col['type'] ?? $col[1] ?? '') : '';

    // Si contiene "date" o "fecha" se considera campo LocalDate
    if (str_contains(strtolower($rawType), 'date') || str_contains(strtolower($rawType), 'fecha')) {
        $needsLocalDate = true;
        break;
    }
}

// Insertar el import SOLO si hace falta
if ($needsLocalDate) {
    // Insertarlo debajo del package
    $prestep3 = preg_replace(
        "/package[^;]+;/",
        "$0\n\nimport java.time.LocalDate;",
        $prestep3,
        1
    );
}
// ==============================================================================================



    $columnsCode = "";
    $constructorParams = "";
    $assignments = "";
    $gettersSetters = "";


   foreach ($columns as $col) {
        // Soporta tanto formato [name, type] como ['name'=>'', 'type'=>'']
        $colName = is_array($col) ? ($col[0] ?? $col['name'] ?? '') : '';
        $colTypeRaw = is_array($col) ? ($col[1] ?? $col['type'] ?? 'String') : 'String';
        $colTypeRaw = strtolower($colTypeRaw);

        // Mapeo SQL → Java
        if (str_contains($colTypeRaw, 'int')) $colType = 'Integer';
        elseif (str_contains($colTypeRaw, 'double') || str_contains($colTypeRaw, 'float') || str_contains($colTypeRaw, 'decimal')) $colType = 'Double';
        elseif (str_contains($colTypeRaw, 'bool')) $colType = 'Boolean';
        elseif (str_contains($colTypeRaw, 'date') || str_contains($colTypeRaw, 'fecha')) $colType = 'LocalDate';  
        else $colType = 'String';


        $columnsCode .= "    @Column(name = \"$colName\")\n";
        $columnsCode .= "    private $colType $colName;\n\n";

        $constructorParams .= "$colType $colName, ";
        $assignments .= "        this.$colName = $colName;\n";

        $methodName = ucfirst($colName);
        $gettersSetters .= "    public $colType get$methodName() {\n";
        $gettersSetters .= "        return $colName;\n";
        $gettersSetters .= "    }\n\n";
        $gettersSetters .= "    public void set$methodName($colType $colName) {\n";
        $gettersSetters .= "        this.$colName = $colName;\n";
        $gettersSetters .= "    }\n\n";
    }

    $constructorParams = rtrim($constructorParams, ", ");

    $parts = generate_columns($columns); // $columns es el array que recibes del frontend
    $finalCode = str_replace("{COLUMNS}", $parts['COLUMNS'], $prestep3);
    $finalCode = str_replace("{CONSTRUCTOR_PARAMS}", $parts['CONSTRUCTOR_PARAMS'], $finalCode);
    $finalCode = str_replace("{ASSIGNMENTS}", $parts['ASSIGNMENTS'], $finalCode);
    $finalCode = str_replace("{GETTERS_SETTERS}", $parts['GETTERS_SETTERS'], $finalCode);

    file_put_contents($ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Entity.java", $finalCode);
    echo "Archivo generado: " . $ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Entity.java\n";
}

// Generar las columnas de la entidad
function generate_columns($columns) {
    // Devuelve un array con las 4 piezas que reemplazarás en la plantilla
    $columns_code = "";
    $constructor_params = [];
    $assignments = [];
    $getters_setters = [];
    $has_primary = false;

    foreach ($columns as $col) {
        // soporta dos formatos: objeto asociativo o array indexado [$name,$type]
        if (is_array($col) && array_key_exists('name', $col)) {
            $name = $col['name'];
            $rawType = $col['type'] ?? ($col['tipo'] ?? 'String');
            $is_primary = !empty($col['isPrimaryKey']) || !empty($col['isPrimary']);
            $is_auto = !empty($col['isAutoIncrement']) || !empty($col['autoIncrement']);
            $is_nullable = isset($col['isNullable']) ? (bool)$col['isNullable'] : (!empty($col['nullable']) ? (bool)$col['nullable'] : true);
        } elseif (is_array($col) && isset($col[0])) {
            // fallback a formato indexado [$name,$type]
            $name = $col[0];
            $rawType = $col[1] ?? 'String';
            $is_primary = !empty($col[2]) && ($col[2] === 'pk' || $col[2] === true);
            $is_auto = !empty($col[3]) && $col[3] === 'ai';
            $is_nullable = true;
        } else {
            // si viene como string simple
            continue;
        }

        // normalizar nombre (no tocar, pero por si vienen espacios)
        $name = trim($name);
        $javaType = map_type($rawType);

        // montar anotaciones
        $anns = "";
        if ($is_primary) {
            $has_primary = true;
       //  asegurar siempre @GeneratedValue para PK numéricas
        if (in_array($javaType, ['Integer', 'Long', 'int', 'long'])) {
    $anns .= "    @Id\n    @GeneratedValue(strategy = GenerationType.IDENTITY)\n";
        } elseif ($is_auto && $javaType === 'String') {
    $anns .= "    @Id\n    @GeneratedValue(strategy = GenerationType.UUID)\n";
        } else {
    $anns .= "    @Id\n";
        }

    }
        // columna
        $anns .= "    @Column(name = \"$name\"";
        if ($is_nullable === false) $anns .= ", nullable = false";
        $anns .= ")\n";

        // campo
        $columns_code .= $anns . "    private {$javaType} {$name};\n\n";

        // constructor param y asignación
        $constructor_params[] = "{$javaType} {$name}";
        $assignments[] = "        this.{$name} = {$name};";

        // getter / setter (usar nombres en Pascal para métodos)
        $camel = ucfirst($name);
        $getters_setters[] = "    public {$javaType} get{$camel}() {\n        return {$name};\n    }\n\n    public void set{$camel}({$javaType} {$name}) {\n        this.{$name} = {$name};\n    }\n";
    }

    // Si no se detectó primary key, agregamos una 'id' por defecto (Long auto-increment)
    if (!$has_primary) {
        $columns_code = "    @Id\n    @GeneratedValue(strategy = GenerationType.IDENTITY)\n    private Long id;\n\n" . $columns_code;
        array_unshift($constructor_params, "Long id");
        array_unshift($assignments, "        this.id = id;");
        array_unshift($getters_setters, "    public Long getId() { return id; }\n\n    public void setId(Long id) { this.id = id; }\n");
    }

    return [
        'COLUMNS' => $columns_code,
        'CONSTRUCTOR_PARAMS' => implode(', ', $constructor_params),
        'ASSIGNMENTS' => implode("\n", $assignments),
        'GETTERS_SETTERS' => implode("\n", $getters_setters)
    ];
}



// Generar los parámetros del constructor
function generate_constructor_params($columns) {
    $params = "";
    $assignments = "";

    foreach ($columns as $col) {
        $columnName = $col[0];
        $columnType = map_type($col[1]);

        $params .= "$columnType $columnName, ";
        $assignments .= "        this.$columnName = $columnName;\n";
    }

    return [
        'params' => rtrim($params, ", "),
        'assignments' => $assignments
    ];
}

// Generar los getters y setters
function generate_getters_setters($columns) {
    $gettersSettersCode = "";

    foreach ($columns as $column) {
        $name = $column[0];
        $type = map_type($column[1]);

        $capitalizedName = ucfirst($name);

        $gettersSettersCode .= "    public $type get$capitalizedName() {\n";
        $gettersSettersCode .= "        return $name;\n";
        $gettersSettersCode .= "    }\n\n";

        $gettersSettersCode .= "    public void set$capitalizedName($type $name) {\n";
        $gettersSettersCode .= "        this.$name = $name;\n";
        $gettersSettersCode .= "    }\n\n";
    }

    return $gettersSettersCode;
}

// Genera un servicio
function generate_service($tablename, $columns, $to, $projectName) {
    $tablenameCamelCase = ucfirst(strtolower($tablename));
    $template = "services" . DIRECTORY_SEPARATOR . "EntidadService.java";

    if (!file_exists($template)) {
        echo "Plantilla servicio no encontrada: $template\n";
        return;
    }

    $readfile = file_get_contents($template);

    $ruta = $to . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "main" . DIRECTORY_SEPARATOR .
        "java" . DIRECTORY_SEPARATOR . "com" . DIRECTORY_SEPARATOR . strtolower($projectName) .
        DIRECTORY_SEPARATOR . "services";

    if (!file_exists($ruta)) mkdir($ruta, 0775, true);

    $package = "package com." . strtolower($projectName) . ".services;\n\n";
    $readfile = str_replace('package com.{PACKAGE}.services;', $package, trim($readfile));

    $importEntities = "import com." . strtolower($projectName) . ".entities." . $tablenameCamelCase . "Entity;\n";
    $importRepositories = "import com." . strtolower($projectName) . ".repository." . $tablenameCamelCase . "Repository;\n";

    $readfile = str_replace("import com.{PACKAGE}.entities.{EntidadM}Entity;", $importEntities, $readfile);
    $readfile = str_replace("import com.{PACKAGE}.repositorys.{EntidadRepository};", $importRepositories, $readfile);

    $prestep1 = str_replace("{ENTIDAD}", $tablename, $readfile);
    $prestep2 = str_replace("{EntidadM}", $tablenameCamelCase, $prestep1);
    $prestep3 = str_replace("{entidad}", strtolower($tablename), $prestep2);

    $updateLogic = '';
    foreach ($columns as $col) {
        $colName = is_array($col) ? ($col[0] ?? $col['name'] ?? '') : '';

         if (stripos($colName, 'id') === 0 || !empty($col['isPrimaryKey']) || !empty($col['isPrimary'])) {
        continue;
     }

        $updateLogic .= "        " . strtolower($tablename) . "Existente.set" . ucfirst($colName) .
            "(" . strtolower($tablename) . "Actualizada.get" . ucfirst($colName) . "());\n";
    }

    $prestep3 = str_replace("{COLUMNS}", $updateLogic, $prestep3);

    file_put_contents($ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Service.java", $prestep3);
    echo "Archivo generado: " . $ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Service.java\n";
}

// Genera un repositorio
function generate_repository($tablename, $to, $projectName) {
    $tablenameCamelCase = ucfirst(strtolower($tablename));
    $template = "repositorys" . DIRECTORY_SEPARATOR . "EntidadRepository.java";

    if (!file_exists($template)) {
        echo "Plantilla repositorio no encontrada: $template\n";
        return;
    }

    $readfile = file_get_contents($template);

    $ruta = $to . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "main" . DIRECTORY_SEPARATOR .
        "java" . DIRECTORY_SEPARATOR . "com" . DIRECTORY_SEPARATOR . strtolower($projectName) .
        DIRECTORY_SEPARATOR . "repository";

    if (!file_exists($ruta)) mkdir($ruta, 0775, true);

    $package = "package com." . strtolower($projectName) . ".repository;\n\n";
    $readfile = str_replace('package com.{PACKAGE}.repository;', $package, $readfile);

    $importEntities = "import com." . strtolower($projectName) . ".entities." . $tablenameCamelCase . "Entity;\n";
    $readfile = str_replace("import com.{PACKAGE}.entities.{EntidadM}Entity;", $importEntities, $readfile);

    $prestep1 = str_replace("{ENTIDAD}", $tablename, $readfile);
    $prestep2 = str_replace("{EntidadM}", $tablenameCamelCase, $prestep1);
    $prestep3 = str_replace("{entidad}", strtolower($tablename), $prestep2);

    file_put_contents($ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Repository.java", $prestep3);
    echo "Archivo generado: " . $ruta . DIRECTORY_SEPARATOR . $tablenameCamelCase . "Repository.java\n";
}

// Generar la clase WebConfig en el paquete utils
function generate_web_config($to, $projectName) {
    $packagePath = strtolower($projectName);
    $ruta = $to . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "main" . DIRECTORY_SEPARATOR . "java" . DIRECTORY_SEPARATOR . "com" . DIRECTORY_SEPARATOR . $packagePath . DIRECTORY_SEPARATOR . "utils";

    if (!file_exists($ruta)) mkdir($ruta, 0775, true);

    $webConfigContent = <<<EOD
package com.$packagePath.utils;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

@Configuration
public class WebConfig {

    @Bean
    public WebMvcConfigurer corsConfigurer() {
        return new WebMvcConfigurer() {
            @Override
            public void addCorsMappings(CorsRegistry registry) {
                registry.addMapping("/**") // Permitir todos los endpoints
                        .allowedOrigins("http://localhost:4200", "http://localhost:5173") // Permitir el origen específico de tu frontend
                        .allowedMethods("GET", "POST", "PUT", "DELETE", "OPTIONS") // Asegúrate de incluir OPTIONS
                        .allowedHeaders("*"); // Permitir todos los encabezados
            }
        };
    }
}
EOD;

    file_put_contents($ruta . DIRECTORY_SEPARATOR . "WebConfig.java", $webConfigContent);
    echo "Archivo generado: " . $ruta . DIRECTORY_SEPARATOR . "WebConfig.java\n";
}


// EJECUCIÓN (flujo principal)

ob_start();

create_project_structure($to, $projectName); // crea carpetas necesarias
generate_pom($to, $projectName);
generate_main_class($to, $projectName);
generate_application_properties($to, $projectName, $databaseName);

// Pasar $to (raíz proyecto) a cada generador para que calcule rutas internas
generate_entity($entityName, $columns, $to, $projectName);
generate_controller($entityName, $columns, $to, $projectName);
generate_service($entityName, $columns, $to, $projectName);
generate_repository($entityName, $to, $projectName);
generate_web_config($to, $projectName);

$logs = ob_get_clean();


echo json_encode([
    "success" => true,
    "message" => "Proyecto Spring Boot generado exitosamente.",
    "projectName" => $projectName,
    "entityName" => $entityName,
    "databaseName" => $databaseName,
    "tableName" => $entityName, 
     "uuid" => $sessionUUID,
    "columns" => $columns,
    "logs" => $logs
]);


exit;
?>
