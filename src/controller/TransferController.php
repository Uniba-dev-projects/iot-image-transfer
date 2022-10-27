<?php
class TransferController extends BaseController
{
    public function transfer()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
        
        if(count($arrQueryStringParams) > 0 &&
            in_array("id", array_keys($arrQueryStringParams)) &&
            in_array("type", array_keys($arrQueryStringParams)) &&
            in_array("index", array_keys($arrQueryStringParams))) {
            if($arrQueryStringParams["type"] == "NIR")
                $this->executeNIRTransfer($arrQueryStringParams, file_get_contents('php://input'));
            else if($arrQueryStringParams["type"] == "RGB")
                $this->executeRGBTransfer($arrQueryStringParams, file_get_contents('php://input'));
            else $this->sendOutput(json_encode(array('error' => "Missing query parameter")), 
                array('Content-Type: application/json', "HTTP/1.1 400 Bad Request")
            );
        } else {
            $this->sendOutput(json_encode(array('error' => "Missing query parameter")), 
                array('Content-Type: application/json', "HTTP/1.1 400 Bad Request")
            );
        }
    }

    public function getNDVIImage()
    {
        $arrQueryStringParams = $this->getQueryStringParams();
        if(count($arrQueryStringParams) == 0 || !in_array("id", array_keys($arrQueryStringParams)))
            return $this->sendOutput(json_encode(array('error' => "Missing query parameter")), 
                array('Content-Type: application/json', "HTTP/1.1 400 Bad Request")
            );

        $ID = $arrQueryStringParams["id"];
        $RGB_PATH = PROJECT_ROOT_PATH."/output/".$ID."/RGB/RESULT.PNG";
        $IMG_RGB = imagecreatefrompng($RGB_PATH);
        $width = imagesx($IMG_RGB);
        $height = imagesy($IMG_RGB);

        $R_MATRIX = array(array());
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $index = imagecolorat($IMG_RGB, $x, $y);
                $rgb = imagecolorsforindex($IMG_RGB, $index);
                $R_MATRIX[$x][$y] = $rgb['red'];
            }
        }

        $NIR_PATH = PROJECT_ROOT_PATH."/output/".$ID."/NIR/RESULT";
        $tempNIRfile = fopen($NIR_PATH, "r");
        $buffer = fread($tempNIRfile, filesize($NIR_PATH));
        fclose($tempNIRfile);
        $NIR_MATRIX = unpack('G*', $buffer);

        $c = 1;

        $NVDI_IMAGE = imagecreatetruecolor($width, $height);
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $nvdiValue = ($NIR_MATRIX[$c] - $R_MATRIX[$x][$y])/($NIR_MATRIX[$c] + $R_MATRIX[$x][$y]);
                $nvdiColorRef = $this->getDiscretizeNVDI($nvdiValue);
                $color = imagecolorallocate($NVDI_IMAGE, $nvdiColorRef["red"], $nvdiColorRef["green"], $nvdiColorRef["blue"]);
                imagesetpixel($NVDI_IMAGE, $x, $y, $color);
                $c += 1;
            }
        }

        header('Content-Type: image/png');
        imagepng($NVDI_IMAGE);
    }

    public function initTransfer()
    {
        $request_body = json_decode(file_get_contents('php://input'), true);
        if($request_body == NULL || !in_array("size_rgb", array_keys($request_body)) || !in_array("size_nir", array_keys($request_body)))
            $this->sendOutput(json_encode(array('error' => "Size not defined")), 
                array('Content-Type: application/json', "HTTP/1.1 400 Bad Request")
            );

        $uuid = uniqid("", true);
        $response = array (
            "id"  => $uuid,
            "size_rgb" => $request_body["size_rgb"],
            "size_nir" => $request_body["size_nir"]
        );
        
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid, 0777, true);
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid."/RGB/", 0777, true);
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid."/NIR/", 0777, true);

        $file = fopen(PROJECT_ROOT_PATH."/output/".$uuid."/RGB/temp-size.ini", "w");
        fwrite($file, $request_body["size_rgb"]);
        fclose($file);

        $file = fopen(PROJECT_ROOT_PATH."/output/".$uuid."/NIR/temp-size.ini", "w");
        fwrite($file, $request_body["size_nir"]);
        fclose($file);

        if($result) {
            $this->sendOutput(
                json_encode($response),
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => "Cannot Create Dir")), 
                array('Content-Type: application/json', "HTTP/1.1 500 Internal Server Error")
            );
        }
    }

    private function executeRGBTransfer($arrQueryStringParams, $chunk)
    {
        $ID = $arrQueryStringParams["id"];

        if(is_dir(PROJECT_ROOT_PATH."/output/".$ID)) {
            $LOCAL_PATH = PROJECT_ROOT_PATH."/output/".$ID."/RGB/";
            $TEMP_SIZE_PATH = $LOCAL_PATH."temp-size.ini";
            $OUTPUT_PATH_CHUNK = $LOCAL_PATH.$arrQueryStringParams["index"];

            file_put_contents($OUTPUT_PATH_CHUNK, $chunk);
            $sizeChunk = filesize($OUTPUT_PATH_CHUNK);
            
            $fileSize = json_decode(file_get_contents($TEMP_SIZE_PATH, true));
            $residualsFileSize = $fileSize - $sizeChunk;

            $fileTempSize = fopen($TEMP_SIZE_PATH, "w");
            fwrite($fileTempSize, $residualsFileSize);
            fclose($fileTempSize);

            if($residualsFileSize == 0)
                $this->assembleFile($LOCAL_PATH, ".PNG");
            
            $this->sendOutput(
                null,
                array('Content-Type: application/json', 'HTTP/1.1 201 No Content')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => "UUID Not found")), 
                array('Content-Type: application/json', "HTTP/1.1 Resource 404 Not Found")
            );
        }
    }

    private function executeNIRTransfer($arrQueryStringParams, $chunk)
    {
        $ID = $arrQueryStringParams["id"];
        if(is_dir(PROJECT_ROOT_PATH."/output/".$ID)) {
            $LOCAL_PATH = PROJECT_ROOT_PATH."/output/".$ID."/NIR/";
            $TEMP_SIZE_PATH = $LOCAL_PATH."temp-size.ini";
            $OUTPUT_PATH_CHUNK = $LOCAL_PATH.$arrQueryStringParams["index"];

            file_put_contents($OUTPUT_PATH_CHUNK, $chunk);
            $sizeChunk = filesize($OUTPUT_PATH_CHUNK);
            
            $fileSize = json_decode(file_get_contents($TEMP_SIZE_PATH, true));
            $residualsFileSize = $fileSize - $sizeChunk;

            $fileTempSize = fopen($TEMP_SIZE_PATH, "w");
            fwrite($fileTempSize, $residualsFileSize);
            fclose($fileTempSize);

            if($residualsFileSize == 0)
                $this->assembleFile($LOCAL_PATH, NULL);
            
            $this->sendOutput(
                null,
                array('Content-Type: application/json', 'HTTP/1.1 201 No Content')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => "UUID Not found")), 
                array('Content-Type: application/json', "HTTP/1.1 Resource 404 Not Found")
            );
        }
    }

    private function assembleFile($LOCAL_PATH, $format)
    {
        $numChunks = count(scandir($LOCAL_PATH)) - 4;
        for($i = 0; $i < $numChunks; $i++) {
            $tempFile = fopen($LOCAL_PATH.$i, 'rb');
            $buffer = fread($tempFile, filesize($LOCAL_PATH.$i));
            fclose($tempFile);

            $resultFileName = "RESULT";
            if($format != NULL) $resultFileName .= $format;
            $final = fopen($LOCAL_PATH."/".$resultFileName, 'ab');
            fwrite($final, $buffer);
            fclose($final);
        }
    }

    /**
     * Only for test purpose
     */
    private function createNIR($LOCAL_PATH)
    {
        $img = imagecreatefrompng($LOCAL_PATH."/RESULT.png");
        $width = imagesx($img);
        $height = imagesy($img);

        $rgb = imagecolorat($img, 0, 0);
        $rMin = ($rgb >> 16) & 0xFF;
        $gMax = ($rgb >> 8) & 0xFF;

        #Getting Min of R and max of G
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($img, 0, 0);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                // $b = $rgb & 0xFF;
                if($r < $rMin) $rMin = $r;
                if($g > $gMax) $gMax = $g;
            }
        }

    
        // imagefilter($img, IMG_FILTER_COLORIZE, $rMin, $gMax, 0);
        imagefilter($img, IMG_FILTER_CONTRAST, 255);
        imagefilter($img, IMG_FILTER_GRAYSCALE);


        $arrNIR = array(array());
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $index = imagecolorat($img, $x, $y);
                $rgb = imagecolorsforindex($img, $index);
                $v = ($rgb['red'] + $rgb['green'] + $rgb['blue'])/3;
                $arrNIR[$x][$y] = $v;
            }
        }

        
        $bin_str = '';
        for($x = 0; $x < $width; $x++) {
            for($y = 0; $y < $height; $y++) {
                $bin_str .= pack('G', $arrNIR[$x][$y]);
            }
        }
        $file = fopen(PROJECT_ROOT_PATH."/output/"."63582e4a103d49.32713650"."/NIR/RESULT", "wb");
        fwrite($file, $bin_str);
        fclose($file);
        
        // header('Content-Type: image/png');
        // imagepng($img);

    }

    private function getDiscretizeNVDI($value)
    {
        $discretization = array();
        $discretization[0] = array('red' => 235, 'green' => 52, 'blue' => 52); //[-1, -0.9)
        $discretization[1] = array('red' => 235, 'green' => 61, 'blue' => 52); //[-0.9, -0.8)
        $discretization[2] = array('red' => 235, 'green' => 70, 'blue' => 52); //[-0.8, -0.7)
        $discretization[3] = array('red' => 235, 'green' => 79, 'blue' => 52); //[-0.7, -0.6)
        $discretization[4] = array('red' => 235, 'green' => 89, 'blue' => 52); //[-0.6, -0.5)
        $discretization[5] = array('red' => 235, 'green' => 98, 'blue' => 52); //[-0.5, -0.4)
        $discretization[6] = array('red' => 235, 'green' => 107, 'blue' => 52); //[-0.4, -0.3)
        $discretization[7] = array('red' => 235, 'green' => 116, 'blue' => 52); //[-0.3, -0.2)
        $discretization[8] = array('red' => 235, 'green' => 125, 'blue' => 52); //[-0.2, -0.1)
        $discretization[9] = array('red' => 235, 'green' => 134, 'blue' => 52); //[-0.1, 0)
        $discretization[10] = array('red' => 156, 'green' => 235, 'blue' => 52); //[0, 0.1)
        $discretization[11] = array('red' => 147, 'green' => 235, 'blue' => 52); //[0.1, 0.2)
        $discretization[12] = array('red' => 137, 'green' => 235, 'blue' => 52); //[0.235, 0.52)
        $discretization[13] = array('red' => 128, 'green' => 235, 'blue' => 52); //[0.3, 0.4)
        $discretization[14] = array('red' => 119, 'green' => 235, 'blue' => 52); //[0.4, 0.5)
        $discretization[15] = array('red' => 110, 'green' => 235, 'blue' => 52); //[0.5, 0.6)
        $discretization[16] = array('red' => 101, 'green' => 235, 'blue' => 52); //[0.6, 0.7)
        $discretization[17] = array('red' => 92, 'green' => 235, 'blue' => 52); //[0.7, 0.8)
        $discretization[18] = array('red' => 83, 'green' => 235, 'blue' => 52); //[0.8, 0.9)
        $discretization[19] = array('red' => 70, 'green' => 235, 'blue' => 52); //[0.9, 1]
        
        if($value >= 0.9) return $discretization[19];
        if($value >= 0.8) return $discretization[18];
        if($value >= 0.7) return $discretization[17];
        if($value >= 0.6) return $discretization[16];
        if($value >= 0.5) return $discretization[15];
        if($value >= 0.4) return $discretization[14];
        if($value >= 0.3) return $discretization[13];
        if($value >= 0.2) return $discretization[12];
        if($value >= 0.1) return $discretization[11];
        if($value >= 0) return $discretization[10];
        if($value >= -0.1) return $discretization[9];
        if($value >= -0.2) return $discretization[8];
        if($value >= -0.3) return $discretization[7];
        if($value >= -0.4) return $discretization[6];
        if($value >= -0.5) return $discretization[5];
        if($value >= -0.6) return $discretization[4];
        if($value >= -0.7) return $discretization[3];
        if($value >= -0.8) return $discretization[2];
        if($value >= -0.9) return $discretization[1];
        return $discretization[0];

    }
}