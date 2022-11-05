<?php

namespace Core\Service;

class TransferService
{
    public function transferChunk($uuid, $type, $index, $raw)
    {
        if(!TransferService::thereIsIdDir($uuid)) return -1;
        if(TransferService::thereIsOutputFile($uuid, $type)) return -2;
        if(TransferService::thereIsSameChunk($uuid, $type, $index)) return -3;

        $local_path = PROJECT_ROOT_PATH."/output/".$uuid."/".$type."/";
        $temp_size_path = $local_path.TEMP_FILE;
        $output_path_chunk = $local_path.$index;


        file_put_contents($output_path_chunk, $raw);
        $sizeChunk = filesize($output_path_chunk);
        
        $fileSize = json_decode(file_get_contents($temp_size_path, true));
        $residualsFileSize = $fileSize - $sizeChunk;

        $fileTempSize = fopen($temp_size_path, "w");
        fwrite($fileTempSize, $residualsFileSize);
        fclose($fileTempSize);

        if($residualsFileSize == 0) {
            if($type == "NIR") $this->assembleFile($local_path, NIR_FORMAT);
            else$this->assembleFile($local_path, RGB_FORMAT);
        }
        return 0;
    }

    public function initializeTransfer($size_rgb, $size_nir)
    {
        $uuid = uniqid("", true);
       
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid, 0777, true);
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid."/RGB/", 0777, true);
        $result = mkdir(PROJECT_ROOT_PATH."/output/".$uuid."/NIR/", 0777, true);
        
        if(!$result) throw new Exception('Error During Dir creation.');

        $file = fopen(PROJECT_ROOT_PATH."/output/".$uuid."/RGB/".TEMP_FILE, "w");
        fwrite($file, $size_rgb);
        fclose($file);

        $file = fopen(PROJECT_ROOT_PATH."/output/".$uuid."/NIR/".TEMP_FILE, "w");
        fwrite($file, $size_nir);
        fclose($file);

        return array (
            "id"  => $uuid,
            "size_rgb" => $size_rgb,
            "size_nir" => $size_nir
        );
    }

    private function assembleFile($LOCAL_PATH, $format)
    {
        $numChunks = count(scandir($LOCAL_PATH)) - 3;
        for($i = 0; $i < $numChunks; $i++) {
            $fileSize = filesize($LOCAL_PATH.$i);
            if($fileSize > 0) {
                $tempFile = fopen($LOCAL_PATH.$i, 'rb');
                $buffer = fread($tempFile,  $fileSize);
                fclose($tempFile);

                $final = fopen($LOCAL_PATH."/".OUTPUT_FILE.$format, 'ab');
                fwrite($final, $buffer);
                fclose($final);
            }     
        }
    }

    public static function thereIsIdDir($uuid)
    {
        return is_dir(PROJECT_ROOT_PATH."/output/".$uuid);
    }

    public static function thereIsOutputFile($uuid, $type)
    {
        return is_file(PROJECT_ROOT_PATH."/output/".$uuid."/".$type."/".OUTPUT_FILE.RGB_FORMAT);
    }

    public static function thereIsSameChunk($uuid, $type, $index)
    {
        return is_file(PROJECT_ROOT_PATH."/output/".$uuid."/".$type."/".$index);
    }
}
?>