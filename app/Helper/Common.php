<?php
namespace App\Helper;

use App\Exceptions\OrderException;
use Illuminate\Support\Facades\Log;
use App\Utils\Constants;
use TFF\MTeamServicesSDK\UserOpspService;

class Common
{
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
     * @param array   $data    Data
     *
     * @return array
     */
    public static function formatResult($retCode, $errCode, $msg = '', $level = 'warning', array $data = [])
    {
        if (!empty($retCode) && !empty($errCode) && !empty($level)) {
            if (in_array($retCode, self::$returnCode) && $retCode === -1) {
                $method = in_array($level, self::$levelList) ? $level : 'info';
                Log::$method(OrderException::getOrderErrorContent($errCode) . $msg, $data);
            }
            return [
                'code' => $retCode,
                'msg' => OrderException::getOrderErrorContent($errCode) . $msg,
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
     * @param boolean $deEn    Decode or Encode, decode=true/ encode=false
     * @param boolean $toAssoc True return associative arrays, else return objects
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
                    Log::warning(OrderException::getOrderErrorContent(1001) . json_last_error(), ['func' => __FUNCTION__, 'warning' => 'json_last_error']);
                    return $json;
                }
                return json_decode($json, $toAssoc);
            }
        } catch (Exception $e) {
            Log::error(OrderException::getOrderErrorContent(1001) . $e->getMessage(), ['func' => __FUNCTION__, 'error' => 'formatJson' ]);
        }

        return $json;
    }

    /**
     * Is new Order or old Order.
     *
     * @param integer $orderId OrderId.
     * @param string  $func    'isNewOrder' or caller string.
     *
     * @return boolean true NewOrder, false oldOrder
     */
    public static function isNewOrder($orderId, $func = __FUNCTION__ )
    {
        $orderId = (int)$orderId;
        try {
            if (strtolower(env('APP_ENV')) == 'prod' || strtolower(env('APP_ENV')) == 'production') {
                if ($orderId >= 2000000) {
                    return true;
                }
                Log::warning(OrderException::getOrderErrorContent(2000), ['orderId' => $orderId, 'funcName' => $func]);
                return false;
            }
            if (strtolower(env('APP_ENV')) == 'staging' || strtolower(env('APP_ENV')) == 'qa') {
                if ($orderId >= 10000000) {
                    return true;
                }
                Log::warning(OrderException::getOrderErrorContent(2000), ['orderId' => $orderId, 'funcName' => $func]);
                return false;
            }
            return true;
        } catch (Exception $e) {
            Log::error(OrderException::getOrderErrorContent(2000) . $e->getMessage(), ['orderId' => $orderId, 'funcName' => $func]);
            return false;
        }
        return true;
    }

    /**
     * Get current time with microsecond.
     *
     * @return string 1500257500.7149
     */
    public static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return ((float)$usec + (float)$sec);
    }

    /**
     * process ItineraryData.
     *
     * @param string $productLine
     * @param array  $itinerary
     *
     * @return array
     */
    public static function filterItineraryData($productLine, $itinerary)
    {
        $result = $itinerary;
        $itineraryData = [];
        if ($productLine == 'tour') {
            $tmpItinerary = $itinerary;
            $firstItinerary = array_shift($tmpItinerary);
            $dayFlag = $firstItinerary['day_no'] == 1 ? true: false;
            foreach ($itinerary as $itineraryItem) {
                $hotels = $ownexpense = '';
                foreach ($itineraryItem['itineraryItem'] as $item) {
                    if ($itineraryItem['has_hotel'] && !empty($item['hotel_item'])) {
                        foreach ($item['hotel_item'] as $hotelItem) {
                            if (isset($hotelItem['name'])) {
                                // PM need shorter the hotel info display. 2017-05-04 14:15:14
                                //$hotels .= $hotelItem['name'] . '<br/>';
                                if (strlen($hotels) > 1 ) {
                                    $hotels .= '或'.$hotelItem['name'];
                                } else {
                                    $hotels .= $hotelItem['name'];
                                }
                            }
                        }
                    }

                    if (isset($item['ownexpense_item'])) {
                        foreach ($item['ownexpense_item'] as $ownexpInfo) {
                            $ownexpense .= isset($ownexpInfo['ownexpense_description']['name']) ?
                                $ownexpInfo['ownexpense_description']['name'] . ' '.$ownexpInfo['price'] . '<br/>' : '';
                            unset($ownexpInfo);
                        }
                    }
                }
                $itineraryData[] = [
                    'day_no' => $dayFlag ? $itineraryItem['day_no'] : $itineraryItem['day_no'] + 1,
                    'itinerary_profile' => $itineraryItem['title'],
                    'hotel_info' => $hotels,
                    'tips' => null,
                    'own_expense' => $ownexpense,
                ];
            }
            $result = $itineraryData;
        }

        if ($productLine == 'combine') {
            $tmpItinerary = $itinerary;
            $sort = [];
            $i = 1;
            foreach ($itinerary as $itineraryItem) {
                $hotels = $ownexpense = '';

                if (isset($itineraryItem['hotel']) && !empty($itineraryItem['hotel'])) {
                    foreach ($itineraryItem['hotel'] as $hotelItem) {
                        if (isset($hotelItem['name'])) {
                            // PM need shorter the hotel info display. 2017-05-04 14:15:14
                            //$hotels .= $hotelItem['name'] . '<br/>';
                            if (strlen($hotels) > 1 ) {
                                $hotels .= '或'.$hotelItem['name'];
                            } else {
                                $hotels .= $hotelItem['name'];
                            }
                        }
                    }
                }

                if (isset($itineraryItem['ownexpense']) && !empty($itineraryItem['ownexpense'])) {
                    foreach ($itineraryItem['ownexpense'] as $ownexpInfo) {
                        $ownexpense .= isset($ownexpInfo['ownexpense_description']['name']) ?
                            $ownexpInfo['ownexpense_description']['name'] . ' '.$ownexpInfo['price'] . '<br/>' : '';
                        unset($ownexpInfo);
                    }
                }
                if (!isset($itineraryItem['day_no'])) {
                    $itineraryItem['day_no'] = $i++;
                }
                $sort[] = $itineraryItem['day_no'];
                $itineraryData[] = [
                    'day_no' => $itineraryItem['day_no'],
                    'itinerary_profile' => $itineraryItem['title'],
                    'hotel_info' => $hotels,
                    'tips' => null,
                    'own_expense' => $ownexpense,
                ];
            }
            if (!empty($itineraryData)) {
                sort($sort);
                array_multisort($itineraryData, $sort);
            }
            $result = $itineraryData;
        }

        return $result;
    }

    /**
     * get itinerary by attribute info
     *
     * @param  string $productLine
     * @param  array  $itineraries
     * @return mixed
     * @author Milan Chhaniyara <milanc.bipl@gmail.com>
     * @date 25 November 2017 10:02 PM IST
     */
    public static function filterV1ItineraryData($productLine, $itineraries)
    {
        $result = $itineraries;
        $itineraryData   = [];
        if (in_array($productLine, ['tour', 'combine'])) {
            $tmpItinerary    = $itineraries;
            $firstItinerary  = array_shift($tmpItinerary);
            $dayFlag         = array_get($firstItinerary, 'day_no', 0) == 1 ? true: false;
            $days            = [];
            $_day            = 1;
            foreach ($itineraries as $itinerary) {
                $hotels      = $ownexpense = '';
                $hotelDetail = (array) array_get($itinerary, "hotel", []);
                if (!empty($hotelDetail)) {
                    $hotels  = implode('或', array_column($hotelDetail, "name"));
                }
                $ownexpenses = (array) array_get($itinerary, "ownexpense", []);
                foreach ($ownexpenses as $ownexpenseItem) {
                    $ownexpense .= isset($ownexpenseItem['ownexpense_description']['name']) ?
                        $ownexpenseItem['ownexpense_description']['name'] . ' '.$ownexpenseItem['price'] . '<br/>' : '';
                    unset($ownexpenseItem);
                }
                $dayNo           = $dayFlag ? $itinerary['day_no'] : ($_day ++);
                $days[]          = $dayNo;

                //filter subItinerary's data
                $subItinerary    = null;
                if (isset($itinerary['sub_itinerary_info']) && !empty($itinerary['sub_itinerary_info'])) {
                    foreach ($itinerary['sub_itinerary_info'] as $subInfo) {
                        if (isset($subInfo['product_sub_id']) && isset($subInfo['title'])) {
                            $subItinerary[$subInfo['product_sub_id']] = [
                                'product_sub_id' => $subInfo['product_sub_id'],
                                'title' => $subInfo['title'],
                            ];
                        }
                    }
                }

                $itineraryData[] = [
                    'day_no'            => $dayNo,
                    'itinerary_profile' => $itinerary['title'],
                    'sub_itinerary_info'=> $subItinerary,
                    'hotel_info'        => $hotels,
                    'tips'              => array_get($itinerary,"tips", null),
                    'own_expense'       => $ownexpense,
                ];
            }
            array_multisort($itineraryData, $days);
            $result = $itineraryData;
        }

        return $result;
    }

    /**
     * Process Product AttributeData to order attribute_info_en.
     *
     * @param string $productLine   Tour/activity/ticket
     * @param string $orderAttrInfo Order attr_info
     * @param array  $originProduct Upgrades_en'
     *
     * @return array
     */
    public static function filterAttributeData($productLine, $orderAttrInfo, $originProduct)
    {
        if (!$orderAttrInfo || !isset($originProduct['en']['upgrades_info'])) {
            return [];
        }
        $upgradesEn = $originProduct['en']['upgrades_info'];
        $upgradeName = $optionName = [];
        foreach ($upgradesEn as $upgrade) {
            if ($productLine == 'activity') {
                $upgradeKey = 'upgrade_id';
                $optionKey = 'option_id';
            } elseif ($productLine == 'tour') {
                $upgradeKey = 'option_id';
                $optionKey = 'value_id';
            } elseif ($productLine == 'ticket') {
                $upgradeKey = 'product_option_id';
                $optionKey = 'product_option_value_id';
            } elseif ($productLine == 'combine') {
                //TODO
            }

            if (!isset($upgrade[$upgradeKey])) {
                $upgradeKey = 'product_option_id';
                $optionKey = 'product_option_value_id';
            }


            $upgradeName[$upgrade[$upgradeKey]] = isset($upgrade['name']) ? $upgrade['name'] : '';
            foreach ($upgrade['value'] as $option) {
                // some option_id/value_id/product_option_value_id didn't exist 2017-02-04 11:51:22
                if (isset($option[$optionKey]) && !empty($option[$optionKey])) {
                    $optionName[$upgrade[$upgradeKey]][$option[$optionKey]] = isset($option['name']) ? $option['name'] : '';
                }
            }
        }

        $return = [];
        foreach ($orderAttrInfo as $att) {
            $upgradeId = isset($att['type_id']) ? $att['type_id'] : $att['upgrade_id'];
            $optionId = isset($att['value_id']) ? $att['value_id'] : $att['option_id'];
            $return[] = [
                'upgrade_id' => $upgradeId,
                'option_id' => $optionId,
                'name' => isset($upgradeName[$upgradeId]) ? $upgradeName[$upgradeId] : '',
                'option_name' => isset($optionName[$upgradeId][$optionId]) ? $optionName[$upgradeId][$optionId] : ''
            ];
        }
        return $return;
    }

    /**
     * Filter own expense
     *
     * @param string $productLine Tour/activity/ticket
     * @param array  $data        OwnExpense
     * @param string $lang        Language cn/en
     *
     * @return string
     */
    public static function filterOwnExpense($productLine, $data, $lang = "cn")
    {
        $ret = '';
        if (empty($data) || !is_array($data)) {
            return $ret;
        }
        if ($productLine == 'ticket') {
            foreach ($data as $own) {
                if (isset($own) && isset($own['old']) && isset($own['old']['desc'])) {
                    foreach ($own['old']['desc'] as $dataItem) {
                        if (isset($dataItem['tips']) && !empty($dataItem['tips'])) {
                            $ret .= '<li>'.$dataItem['name']. ' '. $dataItem['price']. ' ('. $dataItem['tips']. ')</li>';
                        } else {
                            $ret .= '<li>'.$dataItem['name'] . ' ' . $dataItem['price'] . ' ' . '</li>';
                        }
                    }
                }
            }
            $res = '<ul>'.$ret.'</ul>';
        } else {
            if ($lang == "en") {
                foreach ($data as $dataItem) {
                    if (isset($dataItem['name']) && isset($dataItem['price'])) {
                        if (isset($dataItem['tips']) && !empty($dataItem['tips'])) {
                            $ret .= '<li>'.$dataItem['name'] . ' ' . $dataItem['price'] . ' (' . $dataItem['tips'] . ')</li>';
                        } else {
                            $ret .= '<li>'.$dataItem['name'] . ' ' . $dataItem['price'] . ' ' . '</li>';
                        }

                    }
                }
                $res = '<ul>'.$ret.'</ul>';
            } else {
                foreach ($data as $item) {
                    if (!empty($item)) {
                        foreach ($item as $val) {
                            if (isset($val['name']) && isset($val['price'])) {
                                if (isset($val['tips']) && !empty($val['tips'])) {
                                    $ret .= '<li>'.$val['name'] . ' ' . $val['price'] . ' (' . $val['tips'] . ')</li>';
                                } else {
                                    $ret .= '<li>'.$val['name'] . ' ' . $val['price'] . ' ' . '</li>';
                                }
                            }
                        }
                    }
                }
                $res = '<ul>'.$ret.'</ul>';
            }
        }
        return $ret;
    }


    /**
     * Filter own expense V1 from itinerary V1
     *
     * @param array $data itineraryData
     * $@param string $lang language
     *
     * @return string
     */
    public static function filterOwnExpenseV1($itineraryData, $lang = "cn")
    {
        $ret = '';
        if (empty($itineraryData) || !is_array($itineraryData)) {
            return null;
        }

        $ownexpenses = [];
        if (isset($itineraryData["ownexpenses"])) {
            $ownexpenses = $itineraryData["ownexpenses"];
        } else {
            foreach ($itineraryData as $itineraryItem) {
                if (isset($itineraryItem['ownexpense']) && !empty($itineraryItem['ownexpense'])) {
                    $ownexpenses = array_merge($ownexpenses, $itineraryItem['ownexpense']);
                }
            }
        }

        if (empty($ownexpenses)) {
            return null;
        }

        if ($lang == "en" || $lang == "cn") {
            foreach ($ownexpenses as $ownexpense) {
                if (isset($ownexpense['ownexpense_description']['name']) && isset($ownexpense['price'])) {
                    if (isset($ownexpense['tips']) && !empty($ownexpense['tips'])) {
                        $ret .= '<li>'.$ownexpense['ownexpense_description']['name'] . ' ' . $ownexpense['price'] . ' (' . $ownexpense['tips'] . ')</li>';
                    } else {
                        $ret .= '<li>'.$ownexpense['ownexpense_description']['name'] . ' ' . $ownexpense['price'] . ' ' . '</li>';
                    }
                } else if (isset($ownexpense['name']) && isset($ownexpense['price'])) {
                    if (isset($ownexpense['tips']) && !empty($ownexpense['tips'])) {
                        $ret .= '<li>'.$ownexpense['name'] . ' ' . $ownexpense['price'] . ' (' . $ownexpense['tips'] . ')</li>';
                    } else {
                        $ret .= '<li>'.$ownexpense['name'] . ' ' . $ownexpense['price'] . ' ' . '</li>';
                    }
                }
            }
            $res = '<ul>'.$ret.'</ul>';
        }
        return $ret;
    }

    /**
     * Filter eticket_special_note \r\n to <br />.
     *
     * @param string $special_note Product->eticket_special_note.
     *
     * @return string
     */
    public static function filterEticketSpecialNote($special_note)
    {
        if (empty($special_note)) {
            return $special_note;
        }
        $rule = ["\r\n", "\r", "\n"];
        $replace = '<br />';
        $res = str_replace($rule, $replace, $special_note);
        $preg_rule = "/(<br \/>){2,}/";
        $res = preg_replace($preg_rule, $replace, $res);
        return $res;
    }

    /**
     * Filter itinerary Html &gt to > and &lt to <
     *
     * @param string $itinerary_html Product->itinerary_html
     *
     * @return string
     */
    public static function filterItineraryHtmlCss($itinerary_html)
    {
        if (empty($itinerary_html)) {
            return $itinerary_html;
        }
        $rule_gt = ["&gt;"];
        $replace_gt = '>';
        $res = str_replace($rule_gt, $replace_gt, $itinerary_html);
        $rule_lt = ["&lt;"];
        $replace_lt = '<';
        $res = str_replace($rule_lt, $replace_lt, $res);
        return $res;
    }

    /**
     * Filter departure location to departure time and departure location
     *
     * @param string $departureLocation Departure Location
     *
     * @return array
     */
    public static function filterDepartureLocation($departureLocation)
    {
        $ret = [
            'departure_time' => '',
            'departure_location' => $departureLocation
        ];
        //we have departure_time
        return $ret;
        if (empty($departureLocation)) {
            return $ret;
        }
        $pattern = '/^\d\d:\d\d\:\d\d,/';
        preg_match($pattern, $departureLocation, $match);
        if (!empty($match)) {
            $departureTime = array_pop($match);
            $ret = [
                'departure_time' => substr($departureTime, 0 ,-1),
                'departure_location' => str_replace($departureTime, '', $departureLocation)
            ];
        }
        return $ret;
    }

    /**
     * Filter product_type for specified product or provider.
     *
     * @param integer $productType Product Type NUll/1,2,3,4,5,6.
     * @param integer $productID   Product ID
     * @param integer $providerID  Provider ID
     *
     * @return integer
     */
    public static function filterProductType($productType, $productID, $providerID)
    {
        // 1,TDD  2,票务  3,跟团游 4,机票 5,签证 6,邮轮
        if (!empty($productID) && in_array($productID, constants::AIRLINE_TICKET_PRODUCTS)) {
            $productType = 4;
        }
        if (!empty($providerID) && $providerID == 2063) {
            $productType = 5;
        }
        return $productType;
    }

    /**
     * Filter hotel room_amenity data.
     *
     * @param array $amenity $product['amenity']
     *
     * @return array [
     *                  ['name' => '会议设施', 'amenity' => ['小会议室', '会议中心']],
     *                  ['name' => '网络链接', 'amenity' => ['WI-FI 免费', '无限上网(收费)']],
     *              ]
     */
    public static function filterAmenity($amenity)
    {
        $res = [];
        if (count($amenity) > 0) {
            foreach ($amenity as $k => $v) {
                if (!isset($v['name_zh_cn']) || !isset($v['amenity'])) {
                    continue;
                }
                $res[$k] = ['name' => $v['name_zh_cn'],
                    'amenity' => array_map(function($tmp) {
                        return $tmp['name'];
                    }, $v['amenity']),
                ];
            }
        }
        return $res;
    }

    /**
     * Remove all space including chinese/full-width/\r\n.
     *
     * @param string $str String.
     *
     * @return string
     */
    public static function filterSpace($str)
    {
        $search = [" ", "　", "\n", "\r", "\t", " "];
        $replace = [' ', ' ', ' ', ' ', ' ', ' '];
        return str_replace($search, $replace, $str);
    }

    /**
     *  Formate and combine orderProduct Histories
     *
     *  @param  array  $histories
     *  @param  string $primaryKey
     *  @return array
     *
     *  @date   March 06, 2018 12 44 PM
     *  @author Milan Chhaniyara <milanc.bipl@gmail.com>
     */
    public static function formateOrderProductHistories($histories, $primaryKey){
        $res = [];
        if (!empty($histories)) {
            if (count($histories) < 2) {
                if ($histories[0]['type'] == 'retail') {
                    $adjust_retail = $histories[0]['amount'];
                    $adjust_cost = null;
                } else {
                    $adjust_cost = $histories[0]['amount'];
                    $adjust_retail = null;
                }
                $res[] = [
                    'adjust_id'        => $histories[0][$primaryKey],
                    'order_product_id' => $histories[0]['order_product_id'],
                    'adjust_cost'      => $adjust_cost,
                    'adjust_retail'    => $adjust_retail,
                    'adjust_reason'    => $histories[0]['adjust_reason'],
                    'gp_reason'        => $histories[0]['gp_reason'],
                    'comment'          => $histories[0]['comment'],
                    'update_person'    => $histories[0]['update_person'],
                    'created_at'       => $histories[0]['created_at'],
                    // Task_2692 [Milan Chhaniyara] save adjustment(CSR) currency 20180105
                    'adjust_currency'  => $histories[0]['adjust_currency'],
                ];
            } else {
                $old = null;
                $new = null;
                foreach ($histories as $k => $his) {
                    $new = $his;
                    if (!empty($old)) {
                        // less than 1 seconds and have same adjust_reason, merged as one row.
                        if ((strtotime($old['created_at']) - strtotime($new['created_at']) < 1) && $old['type'] != $new['type']) {
                            if ($new['type'] == 'retail') {
                                $adjust_retail = $new['amount'];
                            }
                            if ($old['type'] == 'retail') {
                                $adjust_retail = $old['amount'];
                            }
                            if ($new['type'] == 'cost') {
                                $adjust_cost = $new['amount'];
                            }
                            if ($old['type'] == 'cost') {
                                $adjust_cost = $old['amount'];
                            }
                            $res[$k] = [
                                'adjust_id'        => $new[$primaryKey],
                                'order_product_id' => $new['order_product_id'],
                                'adjust_cost'      => $adjust_cost,
                                'adjust_retail'    => $adjust_retail,
                                'adjust_reason'    => $new['adjust_reason'],
                                'gp_reason'        => $new['gp_reason'],
                                'comment'          => $new['comment'],
                                'update_person'    => $new['update_person'],
                                'created_at'       => $new['created_at'],
                                // Task_2692 [Milan Chhaniyara] save adjustment(CSR) currency 20180105
                                'adjust_currency'  => $new['adjust_currency'],
                            ];
                            // duplicate with the old, so will delete the old data.
                            unset($res[$k -1]);
                        } else {
                            if ($new['type'] == 'retail') {
                                $adjust_retail = $new['amount'];
                                $adjust_cost = null;
                            } else {
                                $adjust_cost = $new['amount'];
                                $adjust_retail = null;
                            }
                            $res[$k] = [
                                'adjust_id'        => $new[$primaryKey],
                                'order_product_id' => $new['order_product_id'],
                                'adjust_cost'      => $adjust_cost,
                                'adjust_retail'    => $adjust_retail,
                                'adjust_reason'    => $new['adjust_reason'],
                                'gp_reason'        => $new['gp_reason'],
                                'comment'          => $new['comment'],
                                'update_person'    => $new['update_person'],
                                'created_at'       => $new['created_at'],
                                // Task_2692 [Milan Chhaniyara] save adjustment(CSR) currency 20180105
                                'adjust_currency'  => $new['adjust_currency'],
                            ];
                        }
                    } else {
                        if ($new['type'] == 'retail') {
                            $adjust_retail = $new['amount'];
                            $adjust_cost = null;
                        } else {
                            $adjust_cost = $new['amount'];
                            $adjust_retail = null;
                        }
                        $res[$k] = [
                            'adjust_id' => $new[$primaryKey],
                            'order_product_id' => $new['order_product_id'],
                            'adjust_cost'      => $adjust_cost,
                            'adjust_retail'    => $adjust_retail,
                            'adjust_reason'    => $new['adjust_reason'],
                            'gp_reason'        => $new['gp_reason'],
                            'comment'          => $new['comment'],
                            'update_person'    => $new['update_person'],
                            'created_at'       => $new['created_at'],
                            // Task_2692 [Milan Chhaniyara] save adjustment(CSR) currency 20180105
                            'adjust_currency'  => $new['adjust_currency'],
                        ];
                    }
                    $old = $his;
                }
            }
            $res = array_values($res);
        }

        return $res;
    }

    /*
     *  get Total  count travellers from guestInfo
     *
     *  @param  $roomInfo
     *  @return int
     *
     *  @date   April 03, 2018 04:52 PM
     *  @author Milan Chhaniyara <milanc.bipl@gmail.com>
     */
    public static function getTotalGuests($roomInfo)
    {
        $totalGuest = 0;
        if (!is_array($roomInfo) || empty($roomInfo)) {
            return $totalGuest;
        }

        foreach ($roomInfo as $guest) {
            $adult      = array_get($guest, 'adult', 0);
            $child      = array_get($guest, 'child', 0);
            $totalGuest += $adult + $child;
        }

        return $totalGuest;
    }

    /**
     * Get end date by start date and duration.
     *
     * @param  string $date (yyyy-mm-dd/ yyyy-MM-dd HH:mm:ss)
     * @param  int    $duration
     * @param  string $durationType (day/hour/minute/year/second, Default 'day')
     * @return string
     *
     * @date   October 04, 2018 04:34 PM
     * @author Milan Chhaniyara <milanc.bipl@gmail.com>
     */
    public static function getEndDate($date, $duration, $durationType = 'day', $format = "Y-m-d")
    {
        $defaultFormate = ['day', 'hour', 'minute', 'year', 'second'];
        $duration       = intval($duration);
        $startDate      = strtotime($date);

        if(empty($startDate) || $duration < 1 || !in_array($durationType, $defaultFormate)) {
            return $date;
        }

        if ($duration > 0) {
            $durationType .= "s";
        }

        if ($durationType != 'second') {
            $startDate -= 1;
        }

        return date($format, strtotime("+ $duration $durationType", $startDate));
    }

    /**
     * Formate itinerary city data by day
     *
     * @param  string $productLine   tour/activity/ticket/combine
     * @param  array  $itineraryInfo
     * @return array
     *
     * @date   December 19, 2018 04:00 PM
     * @author Chirag Bhalara <chiragb.bipl@gmail.com>
     */
    public static function getItineraryCities($productLine, $itineraryInfo)
    {
        $itineraryCities = [];
        if (!is_array($itineraryInfo) || empty($itineraryInfo) || empty($productLine)) {
            return $itineraryCities;
        }

        if (in_array($productLine, [Constants::ENTITY_TYPE_TOUR, Constants::ENTITY_TYPE_COMBINE])) {
            foreach ($itineraryInfo as $index => $itinerary) {
                if(!empty($itinerary)) {
                    $cityInfo = array_get($itinerary, "city", []);
                    $dayNo    = array_get($itinerary, "day_no", $index + 1);
                    $cities   = [];
                    foreach ($cityInfo as $city) {
                        $cities[] = [
                            "city_id"   => array_get($city, "city_id"),
                            "name"      => array_get($city, "city_description.name"),
                            "latitude"  => array_get($city, "city_description.latitude"),
                            "longitude" => array_get($city, "city_description.longitude"),
                        ];
                    }

                    $itineraryCities[] = [
                        "day_no" => $dayNo,
                        "cities" => $cities,
                    ];
                }
            }
        }

        if (in_array($productLine, [Constants::ENTITY_TYPE_ONE_DAY, Constants::ENTITY_TYPE_TICKET, Constants::ENTITY_TYPE_TTD])) {
            $cityInfo = array_get($itineraryInfo, "cities", []);
            $cities = [];
            if (!empty($cityInfo)) {
                foreach ($cityInfo as $city) {
                    $cities[] = [
                        "city_id"   => array_get($city, "city_id"),
                        "name"      => array_get($city, "name"),
                        "latitude"  => array_get($city, "latitude"),
                        "longitude" => array_get($city, "longitude"),
                    ];
                }
            }

            $itineraryCities[] = [
                "day_no" => 1,
                "cities" => $cities,
            ];
        }

        return $itineraryCities;
    }

    public static function getLangCodeByStoreId($store_id) {
        return self::getLangCode(self::getLangIdByStoreId($store_id));
    }

    public static function getLangIdByStoreId($storeId) {
        $storeLangMap = Constants::$storeLangMap;
        $langId = isset($storeLangMap[$storeId]) ? $storeLangMap[$storeId] : Constants::LANG_ID_TFF;
        return $langId;
    }

    public static function getLangCode($lanId = '') {
        $langMap = Constants::$langMap;

        if (!empty($lanId)) {
            return $langMap[$lanId];
        }
        return $langMap;
    }

    /**
     * To format passenger form for air ticket type product(According to PM request, change the `name` and `label` field's name)
     *
     * @param array $passengerForm
     * @return array
     */
    public static function formatPassengerFormForFlightTicket($passengerForm = [])
    {
        $flightTicketPassengerForm                           = [];
        $areaCode                                            = UserOpspService::getInstance()->getAreaCode(1);
        $useAreaCode                                         = array_column($areaCode, 'country_name', 'country_name');
        $flightTicketPassengerForm['number']                 = ['name' => 'number', 'label' => 'No', 'type' => 'number', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['lastname_en']            = ['name' => 'lastname_en', 'label' => '姓氏', 'type' => 'text', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['firstname_en']           = ['name' => 'firstname_en', 'label' => '名字', 'type' => 'text', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['gender']                 = ['name' => 'gender', 'label' => '性别', 'type' => 'radio', 'min' => '', 'max' => '', 'options' => [1 => "男", 2 => "女"], 'placeholder' => ''];
        $flightTicketPassengerForm['dob']                    = ['name' => 'dob', 'label' => '出生日期', 'type' => 'date', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['passport']               = ['name' => 'passport', 'label' => '证件号码', 'type' => 'text', 'min' => '', 'max' => 100, 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['nation']                 = ['name' => 'nation', 'label' => '证件国籍', 'type' => 'select', 'min' => '', 'max' => '', 'options' => $useAreaCode, 'placeholder' => ''];
        $flightTicketPassengerForm['passport_issue_address'] = ['name' => 'passport_issue_address', 'label' => '证件签发地', 'type' => 'text', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['passport_expire']        = ['name' => 'passport_expire', 'label' => '证件有效期', 'type' => 'date', 'min' => '', 'max' => '', 'options' => [], 'placeholder' => ''];
        $flightTicketPassengerForm['ticket_number']          = ['name' => 'ticket_number', 'label' => '票号', 'type' => 'text', 'min' => '', 'max' => 100, 'options' => [], 'placeholder' => ''];
        foreach ($passengerForm as $keyName => $pa) {
            if (in_array($pa['name'], ['number', 'lastname_en', 'firstname_en', 'gender', 'dob', 'passport', 'nation', 'passport_issue_address', 'passport_expire', 'ticket_number'])) {
                continue;
            }
            $flightTicketPassengerForm += [$keyName => $pa];
        }
        return $flightTicketPassengerForm;
    }

    /**
     * To change the passenger form name
     *
     * @param string $originPassengerFormName
     * @param int $productType
     * @return string
     */
    public static function changePassengerFormName($originPassengerFormName = '', $productType = Constants::PRODUCT_TYPE_FLIGHT_TICKET)
    {
        if($productType == Constants::PRODUCT_TYPE_FLIGHT_TICKET){
            if ($originPassengerFormName == 'number') {
                $originPassengerFormName = 'No';
            } elseif ($originPassengerFormName == 'firstname_en') {
                $originPassengerFormName = 'Passenger';
            } elseif ($originPassengerFormName == 'lastname_en') {
                $originPassengerFormName = 'Passenger';
            } elseif ($originPassengerFormName == 'gender') {
                $originPassengerFormName = 'Gender';
            } elseif ($originPassengerFormName == 'dob') {
                $originPassengerFormName = 'Birthday';
            } elseif ($originPassengerFormName == 'passport') {
                $originPassengerFormName = 'Passport Number';
            } elseif ($originPassengerFormName == 'nation') {
                $originPassengerFormName = 'Nationality';
            }elseif ($originPassengerFormName == 'passport_issue_address') {
                $originPassengerFormName = 'Place of Passport';
            }elseif ($originPassengerFormName == 'passport_expire') {
                $originPassengerFormName = 'Expiration Date';
            }elseif ($originPassengerFormName == 'ticket_number') {
                $originPassengerFormName = 'Ticket Number';
            }
        }
        return $originPassengerFormName;
    }

}
