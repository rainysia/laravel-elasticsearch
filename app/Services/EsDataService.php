<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as guzzleClient;
use App\Utils\Constants;

class EsDataService
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
     * Insert single data.
     *
     * @param string $indexName Index name
     * @param string $typeName  Type name
     * @param array  $data      Sequence Array {"id1": {"id1":id1, "xxx1": xxx1}}
     *
     * @return array
     */
    public function insertData($indexName, $typeName, $data)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        $_id = key($data);
        $_data = $data[$_id];

        if ($_id < 1) {
            $res['code'] = -1;
            $res['msg'] = 'illegal data id';
            return $res;
        }

        if (empty($_data)) {
            $res['code'] = -1;
            $res['msg'] = 'empty data';
            return $res;
        }

        $url = sprintf($this->esUrl.'/%s/%s/%s', $indexName, $typeName, $_id);

        try {
            $response = $this->guzzleClient->post($url, ['json' => $_data]);
            /*
            200 success
            201 created
            202 accepted
            203 unauthorized
            204 no-content
            205 reset-content
            206 part-content
            */
            if ($response->getStatusCode() > 210) {
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
     * Bulk Insert data.
     *
     * @param string $indexName Index name
     * @param string $typeName  Type name
     * @param array  $data      Sequence Array
     *
     * @return array
     */
    public function bulkInsertData($indexName, $typeName, $data)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        if (count($data) < 1) {
            $res['code'] = -1;
            $res['msg'] = 'empty data';
            return $res;
        }
        $newData = $this->splitArrByMaxNum($data, Constants::ES_MAX_BULK_ROWS);

        $url = sprintf($this->esUrl.'/%s/%s/_bulk', $indexName, $typeName);

        $_resMsg = 'success';
        $_resData = [];

        foreach ($newData as $vvv) {
            $_res = $this->bulkInsertMaxData($url, $indexName, $typeName, $vvv);

            if ($_res['code'] === -1) {
                $_resMsg .= __CLASS__.'/'.__FUNCTION__." ##### Error:".$_res['msg'].'###'.json_encode($_res['data']);
            } else {
                $_resData[] = $_res['data'];
            }
        }
        $res['msg'] = $_resMsg;
        $res['data'] = $_resData;

        return $res;
    }

    /**
     * Split array via specified nums.
     *
     * @param array   $arr Array.
     * @param integer $num Number
     *
     * @return array
     */
    private function splitArrByMaxNum($arr, $num)
    {
        $count = count($arr);

        if ($count < $num) {
            return [$arr];
        }

        $index = 1;
        $first = 0;
        $new = [];
        foreach ($arr as $k => $v) {
            $new[$first][$k] = $v;
            if ($index >= $num) {
                $first++;
                $index = 1;
            } else {
                $index++;
            }
        }
        return $new;
    }

    /**
     * Bulk insert less than Constants::ES_MAX_BULK_ROWS.
     *
     * @param string $url       Url.
     * @param string $indexName Index name
     * @param string $typeName  Type name
     * @param array  $data      rawData
     *
     * @return array
     */
    private function bulkInsertMaxData($url, $indexName, $typeName, $data)
    {
        $res = Constants::$result;
        $bulkData = '';

        foreach ($data as $k => $v) {
            $line1 = [
                'index' => [
                    '_index' => $indexName,
                    '_type' => $typeName,
                    '_id' => $k
                ]
            ];
            $line2 = $v;
            $bulkData .= json_encode($line1)."\n".json_encode($line2)."\n";
            $line1 = $line2 = [];
        }

        $bulkData .= "\n";
        $guzzleData = [
            'body' => $bulkData,
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ];

        try {
            $response = $this->guzzleClient->post($url, $guzzleData);

            if ($response->getStatusCode() > 210) {
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
     * Delete single Data.
     *
     * @param string  $indexName Index name
     * @param string  $typeName  Type name
     * @param integer $id        Doc ID
     *
     * @return array
     */
    public function deleteDataById($indexName, $typeName, $id)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        $url = sprintf($this->esUrl.'/%s/%s/%s', $indexName, $typeName, $id);

        try {
            $response = $this->guzzleClient->delete($url);

            if ($response->getStatusCode() > 210) {
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
     * Query single Data by doc ID.
     *
     * @param string  $indexName Index name
     * @param string  $typeName  Type name
     * @param integer $id        Doc ID
     *
     * @return array
     */
    public function queryDataById($indexName, $typeName, $id)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        $url = sprintf($this->esUrl.'/%s/%s/%s', $indexName, $typeName, $id);

        try {
            $response = $this->guzzleClient->get($url);

            if ($response->getStatusCode() > 210) {
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
     * Build Raw DSL for ES
     *
     * @param string $indexName Index name
     * @param string $typeName  Type name
     * @param array  $params    Raw query json
     *
        {
            "query": {
                "match_phrase": {"name_cn": "城市国盟"}
            },
            "sort": [
                {
                "hotel_product_id": "desc"
                }
            ],
            "from": 0,
            "size": 4,
            "highlight": {
                "fields": {
                    "name_cn": {}
                }
            },
            "_source": ["city_id", "hotel_product_id", "name_cn", "location", "tag_cn", "hotel_star"]
        }
     *
     * @return array
     */
    public function queryDataWithRawDSL($indexName, $typeName, $params)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        $url = sprintf($this->esUrl.'/%s/%s/_search', $indexName, $typeName);

        $guzzleData = [
            'body' => json_encode($params),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ];
//error_log(var_export(['guzd' => $guzzleData ], 1)."\n", 3, "/var/log/php_errors.log");
        try {
            $response = $this->guzzleClient->post($url, $guzzleData);

            if ($response->getStatusCode() > 210) {
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
     * Build DSL for ES
     *
     * @param string $indexName Index name
     * @param string $typeName  Type name
     * @param array  $params    ['query', 'filter', 'return', 'from', 'size', 'sort']
     *  {
            "index_name":"chotel",
            "type_name":"chotel_type",
            "params": {}
        }
     *
     * @return array
     */
    public function queryDataWithDSL($indexName, $typeName, $params)
    {
        $res = Constants::$result;

        if (empty($indexName) || !is_string($indexName) || is_numeric($indexName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal index_name';
            return $res;
        }
        if (empty($typeName) || !is_string($typeName) || is_numeric($typeName)) {
            $res['code'] = -1;
            $res['msg'] = 'illegal type_name';
            return $res;
        }

        $dslParams = $this->buildDSL($params);

        $url = sprintf($this->esUrl.'/%s/%s/_search', $indexName, $typeName);

        $guzzleData = [
            'body' => json_encode($dslParams),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
        ];
//error_log(var_export(['guz' => $guzzleData ], 1)."\n", 3, "/var/log/php_errors.log");

        try {
            if (!empty($dslParams)) {
                $response = $this->guzzleClient->post($url, $guzzleData);

                if ($response->getStatusCode() > 210) {
                    $res['code'] = -1;
                    $res['msg'] = 'error';
                }

                $res['data'] = $response->getBody()->getContents();
            }
        } catch (\Exception $e) {
            $res['code'] = -1;
            $res['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($res['msg']);
        }
        return $res;
    }

    /**
     * Build DSL.
     *
     * @param array $params Params.  ["query", "filter", "page", "sort", "return"]
     [
        "query":{
            "key":[
                "name_cn",
                "name_en",
                "tag_cn"
            ],
            "value":"机场"
        },
        "filter":{
            "equal":{
                "city_id":1
            },
            "range":{
                "hotel_star":{
                    "gte":5,
                    "lte":5
                }
            },
            "in": {
                "hotel_category_id": [123, 124]
            },
            "geo": {
                "{geo_fields}": {
                    "lat": 22.11,
                    "lon": 11.22,
                },
                "distance": 5km
            }
        },
        "page":{
            "cur_page":1,
            "page_size":6
        },
        "sort":[
            {
                "hotel_product_id":"desc"
            }
        ],
        "return":[
            "city_id",
            "hotel_product_id",
            "tom_product_id",
            "name_cn",
            "location",
            "tag_cn",
            "hotel_star"
        ]
    ]
     *
     * @return array
     */
    private function buildDSL($params)
    {
        $new = [];

        // Query Related
        /*
        'query' =>
            [
                'key' => ['xx1', 'xx2'],
                'value' => xxx
            ]
         */
        if (isset($params['query']) && isset($params['query']['value']) && isset($params['query']['key'])) {
            $query = $params['query'];
            if (count($query['key']) > 1) {
                $new['query']['multi_match'] = [
                    'query'    => $query['value'],
                    'type'     => 'best_fields',
                    'operator' => 'or',
                    'fields'   => $query['key'],
                ];
            } else {
                $new['query']['match'] = [
                    $query['key'][0] => [
                        'query' => $query['value'],
                        'operator' => 'or'
                    ]
                ];
            }
            unset($query);
        }

        // filter Related
        /*
        'filter' =>
            [
                'equal' => [ // 精确匹配过滤
                    "key1" => 123,
                    "key2" => "2012-12-31",
                ],
                'in' => [ // 包含
                    "key3" => [123, 124]
                ],
                'range' => [  // 范围
                    "key4" => [
                        "gte" => 20,  // gte, lte, gt, lt
                        "lte" => 30
                    ]
                ],
                'geo' => [
                    "{geo_fields}" =>  [
                        "lat": 22.11,
                        "lon": 11.22,
                    ],
                    "distance" => '5km'
                ]
            ]
        */
        $geo = [];
        if (isset($params['filter'])) {
            $filter = $params['filter'];
            if (isset($new['query']['multi_match'])) {
                $new['query']['bool']['must'][] = ['multi_match' =>  $new['query']['multi_match']];
                unset($new['query']['multi_match']);
            }
            if (isset($new['query']['match'])) {
                $new['query']['bool']['must'][] = ['match' => $new['query']['match']];
                unset($new['query']['match']);
            }

            if (isset($filter['equal']) && count($filter['equal']) > 0) {
                foreach ($filter['equal'] as $e => $v) {
                    //$new['query']['bool']['must'][] = ['term' => [ $e => $v ]];  // will affect score and relevant result, low performance
                    $new['query']['bool']['filter']['bool']['must'][] = ['term' => [ $e => $v ]];
                }
                unset($e, $v);
            }
            if (isset($filter['in']) && count($filter['in']) > 0) {
                foreach ($filter['in'] as $i => $v) {
                    //$new['query']['bool']['must'][] = ['terms' => [ $i => $v]];
                    $new['query']['bool']['filter']['bool']['must'][] = ['terms' => [ $i => $v]];
                }
                unset($i, $v);
            }
            if (isset($filter['range']) && count($filter['range']) > 0) {
                foreach ($filter['range'] as $r => $v) {
                    //$new['query']['bool']['must'][] = ['range' => [ $r => $v]];
                    $new['query']['bool']['filter']['bool']['must'][] = ['range' => [$r => $v]];
                }
                unset($r, $v);
            }
            if (isset($filter['geo']) && count($filter['geo']) > 0) {
                if (!isset($filter['geo']['distance'])) {
                    $filter['geo']['distance'] = '5km';
                }
                foreach ($filter['geo'] as $g => $v) {
                    $geo[$g] = $v;
                }
                $new['query']['bool']['filter']['bool']['must'][] = ['geo_distance' => $geo];
                unset($g, $v);
            }
            unset($filter);
        }

        // Pagination Related
        /*
        'page' =>
            [
                'cur_page' => 1,
                'page_size' => 5
            ]
        */
        $new['from'] = 0;
        $new['size'] = 15;
        if (isset($params['page'])) {
            $page = $params['page'];
            if (isset($page['page_size'])) {
                if (intval($page['page_size']) <= 0) {
                    $new['size'] = 15;
                } elseif ($page['page_size'] > 50) {
                    $new['size'] = 5000;
                } else {
                    $new['size'] = $page['page_size'];
                }
            }

            if (isset($page['cur_page'])) {
                if (intval($page['cur_page']) <= 1) {
                    $new['from'] = 0;
                } else {
                    $new['from'] = intval($page['cur_page']) * $new['size'];
                }
            }

            unset($page);
        }

        // Sort Related, must be number or date
        /*
        'sort' =>
            [
                [ 'geo'  => 'asc'],
                [ 'key1' => "desc" ],
                [ 'key2' => "asc" ]
            ]
        */
        if (isset($params['sort'])) {
            // geo related sort
            if (count($params['sort'][0]) > 1 && isset($params['sort'][0]['geo']) && count($geo) > 0) {
                unset($geo['distance']);
                // Keep the sort sequence
                $params['sort'][0] = array_reverse($params['sort'][0]);
                $params['sort'][0]['_geo_distance'] = array_merge($geo, [
                    'order' => isset($params['sort'][0]['geo']) ? $params['sort'][0]['geo'] : 'asc',
                    'unit' => 'km',
                ]);
                unset($params['sort'][0]['geo']);
                unset($geo);
                $params['sort'][0] = array_reverse($params['sort'][0]);
            }
            $new['sort'] = $params['sort'];
        } else {
            $new['sort'] = [
                '_score' => 'desc',
                '_index' => 'desc',
            ];
        }

        // Return fields
        /*
        'return' =>
            [
                'key1',
                'key2',
                'key3'
            ]
        */
        if (isset($params['return'])) {
            $new['_source'] = $params['return'];
        }
        return $new;
    }
}
