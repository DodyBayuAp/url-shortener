<?php
$code = file_get_contents('index.php');
$tokens = token_get_all($code);

foreach ($tokens as $token) {
    if (is_array($token)) {
        // ID, Text, Line
        $text = $token[1];
        if (strpos($text, 'BASE_PATH') !== false) {
             $name = token_name($token[0]);
             echo "Line " . $token[2] . " | Token: $name | Content: " . trim(htmlspecialchars($text)) . "\n";
        }
    } else {
        // Single char token
        // unlikely to contain BASE_PATH
    }
}
?>
