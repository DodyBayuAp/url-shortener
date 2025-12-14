<?php
$lines = file('index.php');
foreach ($lines as $i => $line) {
    // Look for the literal sequence: " . BASE_PATH . "
    // This sequence is fine if it is code (outside of string or inside double quotes used for something else?)
    // But if it is inside a SINGLE quoted string, it is bad.
    
    // Heuristic: If the line contains [ ' ] (single quote) AND [ " . BASE_PATH . " ]
    if (strpos($line, "'") !== false && strpos($line, '" . BASE_PATH . "') !== false) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
?>
