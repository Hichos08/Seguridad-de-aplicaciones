<?php

require_once 'functions.php';

// Pega aquí el secreto que aparece en tu app o en la base de datos (columna totp_secret)
$secret = '4VFJCN52CX6LZOCMZT7KIFM4OIA4MDFY';

$now = time();
$step = (int) floor($now / 30);
echo "Timestamp actual: $now\n";
echo "Bloque actual: $step\n";
echo "Código PHP (servidor): " . hotp($secret, $step) . "\n";

?>
