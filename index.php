<?php

require __DIR__ . "/env.php";
require __DIR__ . "/bootstrap.php";

use \Core\Endpoints\TransferProvider;
use \Core\Endpoints\ImageProvider;
use \Core\Util\HttpResponse;

$request = $_SERVER['REQUEST_URI'];
$TransferProvider = new TransferProvider();
$ImageProvider = new ImageProvider();

switch(strtoupper($_SERVER["REQUEST_METHOD"])) {
    case "POST":
        if(str_contains($request, PROJECT_ROOT_NAME."/transfer")) $TransferProvider->httpPostTransfer();
        else return HttpResponse::send_404_NotFound();
    break;
    case "GET":
        if(str_contains($request, PROJECT_ROOT_NAME."/ndvi")) $ImageProvider->httpGetImage();
        else return HttpResponse::send_404_NotFound();
    break;
    case "PUT":
        if(str_contains($request, PROJECT_ROOT_NAME."/transfer")) $TransferProvider->httpPutTransfer();
        else return HttpResponse::send_404_NotFound();
    break;
    default:
        return HttpResponse::send_404_NotFound();
    break;
}


?>