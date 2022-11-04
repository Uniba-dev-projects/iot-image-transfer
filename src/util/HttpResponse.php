<?php
namespace Core\Util;

class HttpResponse {

    public static function send_200_Ok($json) {
        $json = json_encode($json);
        header_remove('Set-Cookie');
        header("HTTP/1.1 200 OK");
        echo $json;
    }

    public static function send_204_NoContent() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 204 No Content");
    }

    public static function send_400_BadRequest() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 400 Bad Request");
    }

    public static function send_403_Forbidden() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 403 Forbidden");
    }

    public static function send_404_NotFound() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 404 Not Found");
    }

    public static function send_409_Conflict() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 409 Conflict");
    }

    public static function send_500_InternalServerError() {
        header_remove('Set-Cookie');
        header("HTTP/1.1 500 Internal Server Error");
    }

}