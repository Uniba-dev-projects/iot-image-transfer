<?php

namespace Core\Endpoints;

use Core\Service\TransferService as TransferService;
use Core\Util\HttpResponse as HttpResponse;
use Core\Util\BaseController as BaseController;

class TransferProvider
{

    private $transferService;

    function __construct() {
        $this->transferService = new TransferService();
    }

    public function httpPutTransfer() {
        $request_body = json_decode(file_get_contents('php://input'), true);
        if($request_body == NULL) return HttpResponse::send_400_BadRequest();
        if(!in_array("size_rgb", array_keys($request_body))) return HttpResponse::send_400_BadRequest();
        if(!in_array("size_nir", array_keys($request_body))) return HttpResponse::send_400_BadRequest();
        if(!is_numeric($request_body["size_nir"])  || !is_numeric($request_body["size_rgb"])) return HttpResponse::send_400_BadRequest();

        try {
            $response = $this->transferService->initializeTransfer($request_body['size_rgb'], $request_body['size_nir']);
            return HttpResponse::send_200_Ok($response);
        } catch(Exception $e) {
            return HttpResponse::send_500_InternalServerError();
        }
    }

    public function httpPostTransfer() {
        $arrQueryStringParams = BaseController::getQueryStringParams();
        if(count($arrQueryStringParams) == 0 || $arrQueryStringParams == NULL) return HttpResponse::send_400_BadRequest();
        if(!in_array("id", array_keys($arrQueryStringParams))) return HttpResponse::send_400_BadRequest();
        if(!in_array("type", array_keys($arrQueryStringParams))) return HttpResponse::send_400_BadRequest();
        if($arrQueryStringParams["type"] != "NIR" && $arrQueryStringParams["type"] != "RGB") return HttpResponse::send_400_BadRequest();
        if(!in_array("index", array_keys($arrQueryStringParams))) return HttpResponse::send_400_BadRequest();
        if(!is_numeric($arrQueryStringParams["index"])) return HttpResponse::send_400_BadRequest();
        $rawData = file_get_contents('php://input');

        try {
            $response = $this->transferService->transferChunk($arrQueryStringParams['id'], $arrQueryStringParams['type'], $arrQueryStringParams['index'], $rawData);
            if($response == -1) return HttpResponse::send_404_NotFound();
            else if($response == -2) return HttpResponse::send_403_Forbidden();
            else if($response == -3) return HttpResponse::send_409_Conflict();
            return HttpResponse::send_204_NoContent();
        } catch(Exception $e) {
            return HttpResponse::send_500_InternalServerError();
        }
    }
}
?>