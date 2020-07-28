<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exceptions\EsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Utils\Constants;

class ESDataController extends Controller
{
    /**
     * Get Data Routes
     *
     * @return array
     */
    public function index(Request $request)
    {
        $routes = Route::getRoutes()->get();
        $configRoutes = [];
        $domain = env('ES_SERVIECS_DOMAIN', 'http://localhost');

        foreach ($routes as $route) {
            if (isset($route->action['prefix']) && $route->action['prefix'] == '/data') {
                $configRoutes[$domain.'/'.$route->uri] = $route->methods[0];
            };
        }
        $result = Constants::$result;
        $result['data'] = $configRoutes;
        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Insert single Data
     *
     * @param object $request Request ['data', 'index_name', 'type_name']
     *
     * @JSON POST URL: http://es.xxx.com/data/insert
     * @JSON POST data format:
            {
                "index_name":"chotel",
                "type_name":"chotel_type",
                "data":{
                        "346309":{
                            "hotel_product_id":346309,
                            "hotel_category_id":495,
                            xxx:xxx
                        }
                }
            }
     *
     * @return array
     */
    public function insertData(Request $request)
    {
        $keyArr   = ['index_name', 'type_name', 'data'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['data']) || !isset($paramArr['index_name']) && !isset($paramArr['type_name'])) {
            return parent::formatResult(-1, 1000, 'Empty data or index_name or type_name.', '', []);
        }
        $result = $this->esDataService->insertData($paramArr['index_name'], $paramArr['type_name'], $paramArr['data']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Bulk Insert data
     *
     * @param object $request Request ['data', 'index_name', 'type_name']
     *
     * @JSON POST URL: http://es.xxx.com/data/insert
     * @JSON POST data format:
            {
                "index_name":"chotel",
                "type_name":"chotel_type",
                "data":{
                        "346309":{
                            "hotel_product_id":346309,
                            "hotel_category_id":495,
                            xxx1:xxx1
                        },
                        "346310":{
                            "hotel_product_id":346310,
                            "hotel_category_id":496,
                            xxx2:xxx2
                        }
                }
            }
     *
     * @return array
     */
    public function bulkInsertData(Request $request)
    {
        $keyArr   = ['index_name', 'type_name', 'data'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['data']) || !isset($paramArr['index_name']) && !isset($paramArr['type_name'])) {
            return parent::formatResult(-1, 1000, 'Empty data or index_name or type_name.', '', []);
        }
        $result = $this->esDataService->bulkInsertData($paramArr['index_name'], $paramArr['type_name'], $paramArr['data']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Delete one specified Data
     *
     * @param object  $request Request ['index_name', 'type_name']
     * @param integer $id      document ID
     *
     * @JSON POST URL: http://es.xxx.com/data/delete/{id}
     *
     * @return array
     */
    public function deleteDataById(Request $request, $id)
    {
        $keyArr   = ['index_name', 'type_name', 'id'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($id) || !isset($paramArr['index_name']) && !isset($paramArr['type_name'])) {
            return parent::formatResult(-1, 1000, 'Empty id or index_name or type_name.', '', []);
        }
        $result = $this->esDataService->deleteDataById($paramArr['index_name'], $paramArr['type_name'], $id);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Get one specified Data
     *
     * @param object $request Request 'index_name', 'type_name', 'id'
     *
     * @JSON GET URL: http://es.xxx.com/data/{index_name}/{type_name}/{id}
     *
     * @return array
     */
    public function queryDataById(Request $request)
    {
        $result = $this->esDataService->queryDataById($request->route('index_name'), $request->route('type_name'), $request->route('id'));

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query data with raw DSL
     *
     * @param object $request Request ['index_name', 'type_name', 'params']
     *
     * @JSON POST URL: http://es.xxx.com/data/rawquery/
     *
     * @return array
     */
    public function queryDataWithDSL(Request $request)
    {
        $keyArr   = ['index_name', 'type_name', 'params'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['index_name']) && !isset($paramArr['type_name'])) {
            return parent::formatResult(-1, 1000, 'Empty index_name or type_name.', '', []);
        }
        $result = $this->esDataService->queryDataWithDSL($paramArr['index_name'], $paramArr['type_name'], $paramArr['params']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query data with DSL
     *
     * @param object $request Request ['index_name', 'type_name', 'params']
     *
     * @JSON POST URL: http://es.xxx.com/data/query/
     *
     * @return array
     */
    public function queryDataWithRawDSL(Request $request)
    {
        $keyArr   = ['index_name', 'type_name', 'params'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['index_name']) && !isset($paramArr['type_name'])) {
            return parent::formatResult(-1, 1000, 'Empty index_name or type_name.', '', []);
        }
        $result = $this->esDataService->queryDataWithRawDSL($paramArr['index_name'], $paramArr['type_name'], $paramArr['params']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }
}
