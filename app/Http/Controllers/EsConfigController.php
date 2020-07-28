<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exceptions\EsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Utils\Constants;

class ESConfigController extends Controller
{
    /**
     * Get Index.Info
     *
     * @return array
     */
    public function index(Request $request)
    {
        //$result = $this->esConfigService->index();
        $routes = Route::getRoutes()->get();
        $configRoutes = [];
        $domain = env('ES_SERVIECS_DOMAIN', 'http://localhost');

        foreach ($routes as $route) {
            if (isset($route->action['prefix']) && $route->action['prefix'] == '/config') {
                $configRoutes[$domain.'/'.$route->uri] = $route->methods[0];
            };
        }
        $result = Constants::$result;
        $result['data'] = $configRoutes;

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query all index.
     *
     * @param string $index_name Index name.
     *
     * @return array
     */
    public function allIndex()
    {
        $result = $this->esConfigService->allIndex('mapping');
        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query specified index.
     *
     * @param string $index_name Index name.
     *
     * @return array
     */
    public function queryIndex(Request $request)
    {
        $keyArr = ['index_name'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['index_name'])) {
            return parent::formatResult(-1, 1000, 'Empty index_name.', '', []);
        }

        $result = $this->esConfigService->queryIndex($paramArr['index_name']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Create ES index (had initial by php artisan es:init for :9200/config('scout.elasticsearch.index'))
     *
     * @return array
     */
    public function createIndex(Request $request)
    {
        $keyArr = ['index_name', 'mappings', 'settings'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['index_name'])) {
            return parent::formatResult(-1, 1000, 'Empty index_name.', '', []);
        }

        $result = $this->esConfigService->createIndex($paramArr);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Initial ES index, only run once.
     *
     * @warning: pls confirm again
     *
     * @return void
     */
    public function deleteIndex(Request $request)
    {
        $keyArr = ['index_name', 'mappings', 'settings'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['index_name'])) {
            return parent::formatResult(-1, 1000, 'Empty index_name.', '', []);
        }
        $result = $this->esConfigService->deleteIndex($paramArr['index_name']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query all template.
     *
     * @return array
     */
    public function allTemplate()
    {
        $result = $this->esConfigService->allIndex('template');

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Query specified template.
     *
     * @param string $template_name Template name.
     *
     * @return array
     */
    public function queryTemplate(Request $request)
    {
        $keyArr = ['template_name'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['template_name'])) {
            return parent::formatResult(-1, 1000, 'Empty template_name.', '', []);
        }

        $result = $this->esConfigService->queryTemplate($paramArr['template_name']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Create ES template
     *
     * @return array
     */
    public function createTemplate(Request $request)
    {
        $keyArr = ['template_name', 'mappings', 'settings'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['template_name'])) {
            return parent::formatResult(-1, 1000, 'Empty template_name.', '', []);
        }
        $result = $this->esConfigService->createTemplate($paramArr);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * Delete ES template
     *
     * @warning: pls confirm again
     *
     * @return array
     */
    public function deleteTemplate(Request $request)
    {
        $keyArr = ['template_name'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['template_name'])) {
            return parent::formatResult(-1, 1000, 'Empty template_name.', '', []);
        }
        $result = $this->esConfigService->deleteTemplate($paramArr['template_name']);

        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * IK Get the data.
     *
     * @return array
     */
    public function allIK(Request $request)
    {
        $ikCustomPath = base_path().'/doc/';
        $ikCustomFile = 'es_ik_custom.txt';
        $path = $ikCustomPath.$ikCustomFile;
        $result = Constants::$result;

        if (file_exists($path)) {
            $result['data'] = explode("\n", file_get_contents($path));
        }
        return parent::formatResult($result['code'], 1000, $result['msg'], '', $result['data']);
    }

    /**
     * IK add new key_word
     *
     * @param string $key_word 'xxx,xxx2,xxx3'
     *
     * @return array
     */
    public function addIK(Request $request)
    {
        // Step 1. Add into doc
        // Step 2. Commit the doc and deploy
        // Step 3. Copy into /etc/elasticsearch/analysis-ik/custom/new.txt
        // Step 4. Restart elasticsearch to make it work.
        $keyArr = ['key_word'];
        $paramArr = $request->all();
        $paramArr = array_intersect_key($paramArr, array_flip($keyArr));

        if (!isset($paramArr['key_word'])) {
            return parent::formatResult(-1, 1000, 'Empty key_word.', '', []);
        }

        $localIKCustomFile = base_path().'/doc/es_ik_custom.txt';
        $oldIKCustomData = explode("\n", file_get_contents($localIKCustomFile));

        $result = Constants::$result;

        try {
            $handle = fopen($localIKCustomFile, "a+b");

            if (strpos($paramArr['key_word'], ",") == false) {
                $keyWords = $paramArr['key_word'];

                if (!in_array($paramArr['key_word'], $oldIKCustomData)) {
                    fwrite($handle, $keyWords."\n");
                    $result['data'] = explode("\n", file_get_contents($localIKCustomFile));
                } else {
                    $result['code'] = -1;
                    $result['msg'] = 'error';
                    $result['data'] = 'IK Custom file has existed:'.$keyWords;
                }
            } else {
                $keyWordsArr = explode(",", $paramArr['key_word']);

                $diffkeyWords = array_diff($keyWordsArr, $oldIKCustomData);

                if (!empty($diffkeyWords)) {
                    $keyWords = implode("\n", $diffkeyWords);
                    fwrite($handle, $keyWords."\n");
                    $result['data'] = explode("\n", file_get_contents($localIKCustomFile));
                } else {
                    $result['code'] = -1;
                    $result['msg'] = 'error';
                    $result['data'] = 'IK Custom file has existed:'.$paramArr['key_word'];
                }
            }
            fclose($handle);
        } catch (Exception $e) {
            $result['code'] = -1;
            $result['msg'] = __CLASS__.'/'.__FUNCTION__." ##### Exception:".$e->getMessage();
            Log::error($result['msg']);
            fclose($handle);
        }
        return $result;
    }
}
