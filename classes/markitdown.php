<?php

namespace local_cria;

class markitdown
{
    /**
     * Executes a conversion request to the Markitdown API.
     * @param $source
     * @return mixed|void
     * @throws \dml_exception
     */
    public static function exec($file_path, $mime_type)
    {
        $config = get_config('local_cria');


        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $config->markitdown_url . '/upload');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);

// Set headers
        $headers = [
            'accept: application/json',
            'Authorization: Bearer ' . $config->markitdown_api_key,
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Attach file
        $postFields = [
            'file' => new \CURLFile(
                $file_path,
                $mime_type,
                basename($file_path)
            )
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

// Execute and handle response
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        } else {
            return $response;
        }

        curl_close($ch);
    }
}