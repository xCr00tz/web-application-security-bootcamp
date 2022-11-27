<?php
$headers = apache_request_headers();

foreach ($headers as $header => $value) {
    echo "<h3>$header: $value </h3>\n";
}
?>