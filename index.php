<?php

require __DIR__ . "/bootstrap.php";

$TransferController = new TransferController();
$request = $_SERVER['REQUEST_URI'];

if(str_contains($request, PROJECT_ROOT_NAME."/transfer") && strtoupper($_SERVER["REQUEST_METHOD"]) === "PUT") {
    $TransferController->initTransfer();
}

else if(str_contains($request, PROJECT_ROOT_NAME."/transfer") && strtoupper($_SERVER["REQUEST_METHOD"]) === "POST") {
    $TransferController->transfer();
}

else if(str_contains($request, PROJECT_ROOT_NAME."/transfer") && strtoupper($_SERVER["REQUEST_METHOD"]) === "GET") {
    $TransferController->getNDVIImage();
} else {
    header('HTTP/1.1 404 Resource Not Found');
}


?>