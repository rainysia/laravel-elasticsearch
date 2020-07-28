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
                'number_of_shards' => 1,
                'number_of_replicas' => 1,
                'refresh_interval' => '5s',
                'max_result_window' => 100000,
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
                                    'analyzer' => 'ik_max_word',
                                    'search_analyzer' => 'ik_max_word',
                                    'fields' => [
                                        'raw' => [
                                            'ignore_above' => 256,
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
                'number_of_shards' => 1,
                'number_of_replicas' => 1,
                'refresh_interval' => '5s',
                "analysis" => [
                    "filter" => [
                        "pinyin_full_filter" => [
                            "keep_joined_full_pinyin" => "true",
                            "lowercase" => "true",
                            "keep_original" => "false",
                            "keep_first_letter" => "false",
                            "keep_separate_first_letter" => "false",
                            "type" => "pinyin",
                            "keep_none_chinese" => "false",
                            "limit_first_letter_length" => "50",
                            "keep_full_pinyin" => "true"
                        ],
                        "pinyin_simple_filter" => [
                            "keep_joined_full_pinyin" => "true",
                            "lowercase" => "true",
                            "none_chinese_pinyin_tokenize" => "false",
                            "padding_char" => " ",
                            "keep_original" => "true",
                            "keep_first_letter" => "true",
                            "keep_separate_first_letter" => "false",
                            "type" => "pinyin",
                            "keep_full_pinyin" => "false"
                        ]
                    ],
                    "analyzer" => [
                        "pinyinFullIndexAnalyzer" => [
                            "filter" => [
                                "asciifolding",
                                "lowercase",
                                "pinyin_full_filter"
                            ],
                            "type" => "custom",
                            "tokenizer" => "ik_max_word"
                        ],
                        "ik_pinyin_analyzer" => [
                            "filter" => [
                                "asciifolding",
                                "lowercase",
                                "pinyin_full_filter",
                                "word_delimiter"
                            ],
                            "type" => "custom",
                            "tokenizer" => "ik_smart"
                        ],
                        "ikIndexAnalyzer" => [
                            "filter" => [
                                "asciifolding",
                                "lowercase"
                            ],
                            "type" => "custom",
                            "tokenizer" => "ik_max_word"
                        ],
                        "pinyinSimpleIndexAnalyzer" => [
                            "type" => "custom",
                            "tokenizer" => "ik_max_word",
                            "filter" => [
                                "pinyin_simple_filter",
                                "lowercase"
                            ]
                        ]
                    ]
                ]
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
                                        'raw' => [
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
                                        'raw' => [
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
                                    'analyzer' => 'ik_max_word',
                                    'search_analyzer' => 'ik_max_word',
                                    'fields' => [
                                        'raw' => [
                                            'type' => 'keyword',
                                            'ignore_above' => 256,
                                        ]
                                    ]
                                ]
                            ],
                        ],
                        [
                            'py_fields' => [
                                "match" => '*_py',
                                'match_mapping_type' => 'string',
                                'mapping' => [
                                    'type' => 'text',
                                    'analyzer' => 'ik_max_word',
                                    'search_analyzer' => 'ik_max_word',
                                    'fields' => [
                                        'raw' => [
                                            'type' => 'keyword',
                                            'ignore_above' => 256,
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
                                    'analyzer' => 'ik_max_word',
                                    'search_analyzer' => 'ik_max_word',
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
                                        'raw' => [
                                            'type' => 'geo_point'
                                        ]
                                    ]
                                ]
                            ],
                        ],
                    ],
                    'properties' => [
                        'location' => [
                            'type' => 'geo_point'
                        ],
                        'name_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 50
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'name_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 50
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'city_name_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 50
                                ]
                            ],
                            'analyzer' => 'standard'
                        ],
                        'city_name_py' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'city_name_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'province_name_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'province_name_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'country_name_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'country_name_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'continent_name_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'address_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'address_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'hotel_business_district_cn' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
                        ],
                        'hotel_business_district_en' => [
                            'type' => 'text',
                            'fields' => [
                                'fpy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinFullIndexAnalyzer'
                                ],
                                'spy' => [
                                    'type' => 'text',
                                    'index' => true,
                                    'analyzer' => 'pinyinSimpleIndexAnalyzer'
                                ],
                                'raw' => [
                                    'type' => 'keyword',
                                    'ignore_above' =>250
                                ]
                            ],
                            'analyzer' => 'ikIndexAnalyzer'
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
