<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    var_dump(error_reporting());
});



$paises=['chile','argentina','peru'];

$a1=@$paises['abc'];