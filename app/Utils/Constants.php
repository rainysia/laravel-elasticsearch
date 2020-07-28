<?php
namespace App\Utils;

class Constants
{
    // Max bulk rows
    const ES_MAX_BULK_ROWS = 2;

    // ES IK CUSTOM FILE
    const ES_IK_CUSTOM_FILE = '/doc/es_ik_custom.txt';

    // Return result structure
    public static $result = [
        'code' => 0,
        'msg' => 'success',
        'data' => null,
    ];
}
