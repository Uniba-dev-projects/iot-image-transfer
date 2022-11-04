<?php

namespace Core\Service;

use Core\Service\TransferService as TransferService;

class ImageService
{
    #TODO: CHECK FILE EXIST
    public function getNDVIImage($uuid)
    {
        if(!TransferService::thereIsIdDir($uuid)) return -1;
        if(!TransferService::thereIsOutputFile($uuid, "NIR")) return -2;
        if(!TransferService::thereIsOutputFile($uuid, "RGB")) return -2;

        $RGB_PATH = PROJECT_ROOT_PATH."/output/".$uuid."/RGB/".OUTPUT_FILE.RGB_FORMAT;
        $NIR_PATH = PROJECT_ROOT_PATH."/output/".$uuid."/NIR/".OUTPUT_FILE.NIR_FORMAT;
 
        $IMG_RGB = imagecreatefromjpeg($RGB_PATH);
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