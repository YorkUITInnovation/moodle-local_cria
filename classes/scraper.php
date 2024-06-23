<?php

namespace local_cria;

class scraper
{

    /**
     * Using scrapper to get web pages
     * See more: https://github.com/amerkurev/scrapper
     * @param $url
     * @return mixed
     */
    public static function execute($url)
    {
        // Get local cria config
        $config = get_config('local_cria');
        // Device information: https://github.com/amerkurev/scrapper/blob/master/app/internal/deviceDescriptorsSource.json
        $url = "$config->criascraper_url/api/article?url=" . urlencode($url)
            . "&full-content=true"
            . "&resouce=document"
            . "&sleep=100"
            . "&scroll-down=1120"
            . "&stealth=true"
            . "&device=Desktop%20Chrome%20HiDPI";

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $result = json_decode(curl_exec($ch));

        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }

        curl_close($ch);

        return $result;
    }
}