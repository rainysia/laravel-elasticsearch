<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use App\Exceptions\EsException;
use App\Services\EsConfigService;
use App\Services\EsDataService;

//use Illuminate\Foundation\Bus\DispatchesJobs;
//use Illuminate\Foundation\Validation\ValidatesRequests;
//use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    //use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected $esConfigService;
    protected $esDataService;

    public function __construct(EsConfigService $esConfigService, EsDataService $esDataService)
    {
        $this->esConfigService = $esConfigService;
        $this->esDataService = $esDataService;
    }

    public static $levelList = [
        'info',
        'warning',
        'error',
    ];

    public static $returnCode = [
        0,
        -1,
    ];

    /**
     * Format Result and record log
     *
     * @param integer $retCode 0, -1
     * @param integer $errCode 1000,1000
     * @param string  $level   Info,warning,error
     * @param string  $msg     Msg
     * @param mixed   $data    Data
     *
     * @return array
     */
    public static function formatResult($retCode, $errCode, $msg = '', $level = 'warning', $data = '')
    {
        // -1, >0,
        $data = self::formatJson($data);
        if (!empty($retCode) && !empty($errCode) && !empty($level)) {
            if (in_array($retCode, self::$returnCode) && $retCode === -1) {
                $method = in_array($level, self::$levelList) ? $level : 'info';
                Log::$method(EsException::getEsErrorContent($errCode) . $msg, $data);
            }
            return [
                'code' => $retCode,
                'msg'  => EsException::getEsErrorContent($errCode) . $msg,
                'data' => $data,
            ];
        }
        return [
            'code' => $retCode,
            'msg'  => $msg,
            'data' => $data
        ];
    }

    /**
     * Handle Json
     *
     * @param string  $json    String or array data.
     * @param boolean $decode  Decode or Encode, decode=true/ encode=false
     * @param boolean $toAssoc True return associative arrays, else return object
     *
     * @return string Json
     */
    public static function formatJson($json, $decode = true, $toAssoc = false)
    {
        if (empty($json)) {
            return $json;
        }
        if (!$decode) {
            return json_encode($json);
        }

        try {
            if (is_string($json)) {
                @json_decode($json, $toAssoc);
                if (json_last_error()) {
                    Log::warning(EsException::getEsErrorContent(1001) . json_last_error(), ['func' => __FUNCTION__, 'warning' => 'json_last_error']);
                    return $json;
                }
                return json_decode($json, $toAssoc);
            }
        } catch (Exception $e) {
            Log::error(EsException::getEsErrorContent(1001) . $e->getMessage(), ['func' => __FUNCTION__, 'error' => 'formatJson' ]);
        }

        return $json;
    }
}
