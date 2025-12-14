<?php
$code = file_get_contents('index.php');
$tokens = token_get_all($code);

foreach ($tokens as $token) {
    if (is_array($token)) {
        // ID, Text, Line
        if ($token[0] === T_CONSTANT_ENCAPSED_STRING || $token[0] === T_ENCAPSED_AND_WHITESPACE) {
            if (strpos($token[1], 'BASE_PATH') !== false) {
                 echo "Found literal BASE_PATH on line " . $token[2] . ": " . htmlspecialchars($token[1]) . "\n";
            }
        }
    }
}
?>
