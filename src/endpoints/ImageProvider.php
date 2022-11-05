<?php

namespace Core\Endpoints;

use Core\Service\ImageService as ImageService;
use Core\Util\HttpResponse as HttpResponse;
use Core\Util\BaseController as BaseController;

class ImageProvider
{

    private $imageService;

    function __construct() {
        $this->imageService = new ImageService();
    }

    public function httpGetImage() {
        $arrQueryStringParams = BaseController::getQueryStringParams();
        if(count($arrQueryStringParams) == 0 || $arrQueryStringParams == NULL) return HttpResponse::send_400_BadRequest();
        if(!in_array("id", array_keys($arrQueryStringParams))) return HttpResponse::send_400_BadRequest();

        try {
           $result = $this->imageService->getNDVIImage($arrQueryStringParams['id']);
           if($result == -1) HttpResponse::send_404_NotFound();
           if($result == -2) HttpResponse::send_403_Forbidden();
        } catch(Exception $e) {
            return HttpResponse::send_500_InternalServerError();
        }
    }
}
?>