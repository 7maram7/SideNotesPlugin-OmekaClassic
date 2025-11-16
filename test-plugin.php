<?php
/**
 * Test script to check if the SideNotes plugin can be loaded
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SideNotes Plugin Validation Test ===\n\n";

// Test 1: Check if plugin.ini exists and is readable
echo "Test 1: Checking plugin.ini...\n";
$iniFile = __DIR__ . '/SideNotes/plugin.ini';
if (!file_exists($iniFile)) {
    echo "ERROR: plugin.ini not found at: $iniFile\n";
    exit(1);
}
echo "✓ plugin.ini exists\n";

$ini = parse_ini_file($iniFile, true);
if ($ini === false) {
    echo "ERROR: Could not parse plugin.ini\n";
    exit(1);
}
echo "✓ plugin.ini is valid INI format\n";

if (!isset($ini['info'])) {
    echo "ERROR: plugin.ini missing [info] section\n";
    exit(1);
}
echo "✓ [info] section found\n";

$required = ['name', 'version', 'author'];
foreach ($required as $key) {
    if (!isset($ini['info'][$key])) {
        echo "ERROR: Missing required field: $key\n";
        exit(1);
    }
    echo "✓ $key = " . $ini['info'][$key] . "\n";
}

// Test 2: Check if main plugin file exists
echo "\nTest 2: Checking SideNotesPlugin.php...\n";
$pluginFile = __DIR__ . '/SideNotes/SideNotesPlugin.php';
if (!file_exists($pluginFile)) {
    echo "ERROR: SideNotesPlugin.php not found at: $pluginFile\n";
    exit(1);
}
echo "✓ SideNotesPlugin.php exists\n";

// Test 3: Check PHP syntax
echo "\nTest 3: Checking PHP syntax...\n";
$output = [];
$return = 0;
exec("php -l " . escapeshellarg($pluginFile) . " 2>&1", $output, $return);
if ($return !== 0) {
    echo "ERROR: PHP syntax error:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}
echo "✓ No syntax errors\n";

// Test 4: Try to include the file
echo "\nTest 4: Attempting to load plugin file...\n";
try {
    // Mock Omeka classes if they don't exist
    if (!class_exists('Omeka_Plugin_AbstractPlugin')) {
        echo "Note: Creating mock Omeka_Plugin_AbstractPlugin class\n";
        class Omeka_Plugin_AbstractPlugin {
            protected $_db;
            protected $_hooks = array();
            protected $_filters = array();
        }
    }

    require_once $pluginFile;
    echo "✓ Plugin file loaded successfully\n";

} catch (Exception $e) {
    echo "ERROR: Exception when loading plugin:\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 5: Check if class exists
echo "\nTest 5: Checking if class exists...\n";
if (!class_exists('SideNotesPlugin')) {
    echo "ERROR: SideNotesPlugin class not found\n";
    exit(1);
}
echo "✓ SideNotesPlugin class exists\n";

// Test 6: Check if class extends proper parent
echo "\nTest 6: Checking class inheritance...\n";
$reflection = new ReflectionClass('SideNotesPlugin');
$parent = $reflection->getParentClass();
if ($parent && $parent->getName() === 'Omeka_Plugin_AbstractPlugin') {
    echo "✓ SideNotesPlugin extends Omeka_Plugin_AbstractPlugin\n";
} else {
    echo "WARNING: Parent class is: " . ($parent ? $parent->getName() : 'none') . "\n";
}

// Test 7: Check directory structure
echo "\nTest 7: Checking directory structure...\n";
$requiredDirs = [
    'controllers',
    'views',
    'views/admin',
    'views/admin/index'
];

foreach ($requiredDirs as $dir) {
    $path = __DIR__ . '/SideNotes/' . $dir;
    if (!is_dir($path)) {
        echo "WARNING: Missing directory: $dir\n";
    } else {
        echo "✓ $dir exists\n";
    }
}

$requiredFiles = [
    'controllers/IndexController.php',
    'views/admin/index/browse.php',
    'config_form.php'
];

foreach ($requiredFiles as $file) {
    $path = __DIR__ . '/SideNotes/' . $file;
    if (!file_exists($path)) {
        echo "WARNING: Missing file: $file\n";
    } else {
        echo "✓ $file exists\n";
    }
}

echo "\n=== All Tests Passed! ===\n";
echo "\nThe plugin structure appears to be valid.\n";
echo "If Omeka still says 'not a valid plugin', the issue is likely:\n";
echo "1. The plugin is not in Omeka's plugins directory\n";
echo "2. File permissions are incorrect\n";
echo "3. Omeka needs a specific version of PHP or missing PHP extensions\n";
echo "\nPlugin should be located at: /path/to/omeka/plugins/SideNotes/\n";
