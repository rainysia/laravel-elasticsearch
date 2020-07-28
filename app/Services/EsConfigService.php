<?php
namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as guzzleClient;
use App\Utils\Constants;

class EsConfigService
{
    private $esUrl;
    private $esIndex;
    protected $guzzleClient;

    public function __construct()
    {
        $this->esUrl   = config('scout.elasticsearch.hosts')[0];
        $this->esIndex = config('scout.elasticsearch.index');

        $this->guzzleClient = new guzzleClient([
            'timeout' => 5.0,
            //'http_errors' => false, // This will override the http response
        ]);
    }

    /**
     * Return All information
     *
     * @param string $type /mapping/template/indices
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function allIndex($type)
    {
        $res = Constants::$result;

        $typeArr = [
            'mapping'  => true,
            'template' => true,
            //'indices'  => true,
        ];

        if (!isset($typeArr[$type])) {
            $res['code'] = -1;
            $res['msg'] = 'error, wrong type';
            return $res;
        }

        $url = $this->esUrl.'/_'.$type;
        if ($type == 'indices') {
            $url = $this->esUrl.'/_cat/indices?v&pretty';
        }

        try {
            $response = $this->guzzleClient->request('GET', $url);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }

        return $res;
    }

    /**
     * Query Index information with specified name
     *
     * @param string $indexName Index Name
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryIndex($indexName)
    {
        $res = Constants::$result;
        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal indexName';
            return $res;
        }

        $url = $this->esUrl.'/'.$indexName;

        try {
            $response = $this->guzzleClient->request('GET', $url);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }
            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }
        return $res;
    }

    /**
     * Create Index
     *
     * @param $paramArr ['indexName', 'settings', 'mappings']
     *
     * @return array
     */
    public function createIndex($paramArr)
    {
        $res = Constants::$result;

        if (isset($paramArr['index_name'])) {
            $indexName = $paramArr['index_name'];
        }

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal indexName';
            return $res;
        }

        if (isset($paramArr['settings'])) {
            $settings = $paramArr['settings'];
        } else {
            $settings = [
                'number_of_shards' => 3,
                'number_of_replicas' => 1,
                'refresh_interval' => '5s'
            ];
        }

        if (isset($paramArr['mappings'])) {
            $mappings = $paramArr['mappings'];
        } else {
            $mappings = [
                '_default_' => [
                    '_all' => [
                        'enabled' => true
                    ],
                    '_source' => [
                        'enabled' => true
                    ],
                    'dynamic_templates' => [
                        [
                            'strings' => [
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'text',
                                    'analyzer' => 'ik_smart',
                                    'ignore_above' => 256,
                                    'fields' => [
                                        'keyword' => [
                                            'type' => 'keyword'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }

        $url = $this->esUrl.'/'.$indexName;

        $paramIndex = [
            'json' => [
                'settings' => $settings,
                'mappings' => $mappings,
            ],
        ];

        try {
            $response = $this->guzzleClient->put($url, $paramIndex);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }
        return $res;
    }

    /**
     * Delete Index.
     *
     * @param string $indexName Index Name.
     *
     * @return array
     */
    public function deleteIndex($indexName)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }

        $url = $this->esUrl.'/'.$indexName;

        try {
            $response = $this->guzzleClient->delete($url);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            Log::info(__CLASS__.'/'.__FUNCTION__." ##### Info: Delete index:".$indexName. ', url:'.$url);

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }

        return $res;
    }

    /**
     * Create index template.
     *
     * @param $paramArr ['template_name', 'order', 'settings', 'mappings', 'aliases']
     *
     * @return array
     */
    public function createTemplate($paramArr)
    {
        $res = Constants::$result;
        $templateName = null;

        if (isset($paramArr['template_name'])) {
            $templateName = $paramArr['template_name'];
        }

        if (empty($templateName) || !is_string($templateName) || is_numeric($templateName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal template_name';
            return $res;
        }

        if (isset($paramArr['settings'])) {
            $settings = $paramArr['settings'];
        } else {
            $settings = [
                'number_of_shards' => 3,
                'number_of_replicas' => 1,
                'refresh_interval' => '5s'
            ];
        }

        if (isset($paramArr['mappings'])) {
            $mappings = $paramArr['mappings'];
        } else {
            $mappings = [
                $templateName.'_type' => [
                    '_all' => ['enabled' => true],
                    '_source' => ['enabled' => true],
                    'dynamic' => "true",
                    'dynamic_templates' => [
                        [
                            'id_fields' => [
                                'match_pattern' => 'regex',
                                'match' => '[a-z_]*(id){1}|[a-z]*(_num){1}|[a-z]*(_type){1}|[a-z]*(_star){1}|[a-z_]*(facility){1}',
                                'match_mapping_type' => 'long',
                                'mapping' => [
                                    'type' => 'integer',
                                    'fields' => [
                                        'keyword' => [
                                            'type' => 'keyword'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        [
                            'float_fields' => [
                                'match_pattern' => 'regex',
                                "match" => '([a-z]*(_score_){1}[a-z]*)',
                                'match_mapping_type' => 'double',
                                'mapping' => [
                                    'type' => 'float',
                                    'fields' => [
                                        'keyword' => [
                                            'type' => 'keyword'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        [
                            'cn_fields' => [
                                "match" => '*_cn',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'text',
                                    'analyzer' => 'ik_smart',
                                    'ignore_above' => 256,
                                    'fields' => [
                                        'keyword' => [
                                            'type' => 'keyword'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        [
                            'en_fields' => [
                                "match" => '*_en',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'text',
                                    'analyzer' => 'english',
                                    'fields' => [
                                        'raw' => [
                                            'type' => 'keyword',
                                            'ignore_above' => 256
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        [
                            'es_fields' => [
                                "match" => '*_es',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'text',
                                    'analyzer' => 'spanish',
                                    ]
                                ]
                            ],
                        [
                            'date_fields' => [
                                'match_pattern' => 'regex',
                                'match' => '[a-z_]*(date){1}|[a-z_]*(time){1}',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'date',
                                    "format" => "epoch_millis||strict_date_optional_time"
                                ]
                            ],
                        ],
                        [
                            'geo_fields' => [
                                "match" => 'location',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'geo_point',
                                    'location' => [
                                        'keyword' => [
                                            'type' => 'geo_point'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                    ],
                    'properties' => [
                        "location" => [
                            "type" => "geo_point"
                        ]
                    ]
                ]
            ];
        }

        $urlTemp = $this->esUrl.'/_template/'.$templateName;

        $paramTemp = [
            'json' => [
                'template' => $templateName,
                'order'    => 1,
                'index_patterns' => [$templateName."*"],
                'settings' => $settings,
                'mappings' => $mappings,
            ],
        ];

        try {
            $response = $this->guzzleClient->put($urlTemp, $paramTemp);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }

        return $res;
    }

    /**
     * Delete template.
     *
     * @param string $templateName Template Name
     *
     * @return array
     */
    public function deleteTemplate($templateName)
    {
        $res = Constants::$result;

        if (empty($templateName) || !is_string($templateName) || is_numeric($templateName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal template_name';
            return $res;
        }

        $url = $this->esUrl.'/_template/'.$templateName;

        try {
            $response = $this->guzzleClient->delete($url);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            Log::info(__CLASS__.'/'.__FUNCTION__." ##### Info: Delete template:".$templateName. ', url:'.$url);

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }

        return $res;
    }

    /**
     * Query template information with specified name
     *
     * @param string $templateName Template Name
     *
     * @return array
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryTemplate($templateName)
    {
        $res = Constants::$result;

        if (empty($templateName) || !is_string($templateName) || is_numeric($templateName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal template_name';
            return $res;
        }

        $url = $this->esUrl.'/_template/'.$templateName;

        try {
            $response = $this->guzzleClient->request('GET', $url);

            if ($response->getStatusCode() != 200) {
                $res['code'] = -1;
                $res['msg'] = 'error';
            }

            $res['data'] = $response->getBody()->getContents();
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }

        return $res;
    }
}
