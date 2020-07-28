<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class EsException extends Exception
{
    // Exception list
    private static $exceptionList = [
        1000 => 'param null : ',
        1001 => 'param illegal : ',
        1002 => 'missing parameter : ',
        1010 => 'ENV configuration error : ',
        1011 => 'invalid params!',
    ];

    /**
     * @brief construct function
     *
     * @param integer $code    Exception code
     * @param string  $message The specific message
     *
     * @return void
     */
    public function __construct($code, $message = '')
    {
        $this->code = $code;
        $this->message = (isset(self::$exceptionList[$code]) ? self::$exceptionList[$code] : "").$message;

        if (empty($this->message)) {
            $this->message = "unknown error!";
        }
    }

    /**
     * Get OrderException Code Content
     *
     * @param integer $code Error Code
     *
     * @return string
     */
    public static function getEsErrorContent($code)
    {
        return isset(self::$exceptionList[$code]) ? self::$exceptionList[$code] : '';
    }
}
