<?php
class UNL_MediaHub_AmaraAPI
{
    const BASE_API_URI = 'https://www.amara.org/api2/partners/';
    
    public static $amara_username = false;
    public static $amara_api_key  = false;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;
    
    public function __construct()
    {
        $this->guzzle = new \GuzzleHttp\Client();
    }

    /**
     * @param string $request_path the path and query string parameters after the base API endpoint
     * @return string
     */
    public function get($request_path)
    {
        $response = $this->guzzle->get(self::BASE_API_URI.$request_path, array(
            'headers' => array(
                'X-api-username' => self::$amara_username,
                'X-apikey'       => self::$amara_api_key,
            ),
        ));
        return $response->getBody();
    }
    
    public function post($request_path, $content)
    {
        $response = $this->guzzle->post(self::BASE_API_URI.$request_path, array(
            'headers' => array(
                'X-api-username' => self::$amara_username,
                'X-apikey'       => self::$amara_api_key,
            ),
            'body' => json_encode($content),
        ));
        return $response->getBody();
    }

    /**
     * @param string $media_url the full media URL
     * @return bool|mixed
     */
    public function getMediaDetails($media_url)
    {
        if (!$info_json = $this->get('videos/?video_url=' . $media_url . '&format=json')) {
            return false;
        }
        
        return json_decode($info_json);
    }
    
    public function createMedia($media_url)
    {
        return $this->post('videos/?format=json', array(
            'video_url' => $media_url
        ));
    }

    /**
     * @param string $media_url the full media URL
     * @return bool|string
     */
    public function getCaptionEditURL($media_url)
    {
        $media_details = $this->getMediaDetails($media_url);

        if (!$media_details) {
            return false;
        }

        if ($media_details->meta->total_count == 0) {
            //create the media
            $result = $this->createMedia($media_url);
            
            //update the details
            $media_details = $this->getMediaDetails($media_url);
        }
        return 'http://amara.org/en/videos/' . $media_details->objects[0]->id . '/info';
    }

    /**
     * @param string $media_url the full media URL
     * @param string $format the format for the text track (srt or vtt)
     * @return bool|string
     */
    public function getTextTrack($media_url, $format = 'srt')
    {
        $media_details = $this->getMediaDetails($media_url);
        
        if (!$media_details) {
            return false;
        }
        
        if ($media_details->meta->total_count == 0) {
            return false;
        }

        return $this->get('videos/' . $media_details->objects[0]->id . '/languages/en/subtitles/?format='.$format);
    }
}