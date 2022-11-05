<?php
namespace Core\Util;

class BaseController {
  
    /**
     * Get querystring params.
     * 
     * @return array
     */
    public static function getQueryStringParams()
    {
         parse_str($_SERVER['QUERY_STRING'], $query);
         return $query;
    }
 
}
?>