<?php
header('Content-Type: text/plain');
$db_content = file_get_contents(__DIR__ . '/config/db.php');
$start = strpos($db_content, 'function get_category_icon_url');
if ($start !== false) {
    echo substr($db_content, $start, 700);
} else {
    echo "Function not found!";
}
?>
