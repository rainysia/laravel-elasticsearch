Elastic Search API
==========
ES services API list, Including Config API and Data API

## 1. Config API

### 1.1. Config Template API
-----
1.1.1, Create Template
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| template_name |string | template name      | must |
| mappings      |array  | mapping data      | optional |
| settings      |array  | settings data     | optional |

Method:`POST`
URL:`/config/template/create`
Data:
```
{
    "template_name":"chotel"
}
```
Return:
```
{
    "code":0,
    "msg":"success",
    "data":{
        "acknowledged":true
    }
}
```

1.1.2, Get Specified Template
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| template_name |string | template name      | must |

Method:`GET`
URL:`config/template/get?template_name={template_name}`
Data:
```

```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "chotel": {
            "order": 1,
            "template": "chotel",
            "settings": {
                "index": {
                    "number_of_shards": "3",
                    "number_of_replicas": "1",
                    "refresh_interval": "5s"
                }
            },
            "mappings": {
                "my_type": {
                    "_all": {
                        "enabled": true
                    },
                    "_source": {
                        "enabled": true
                    },
                    "dynamic": "true",
                    "dynamic_templates": [
                        {
                            "es": {
                                "match": "*_es",
                                "match_mapping_type": "string",
                                "mapping": {
                                    "type": "text",
                                    "analyzer": "spanish"
                                }
                            }
                        },
                        {
                            "en": {
                                "match": "*",
                                "match_mapping_type": "string",
                                "mapping": {
                                    "type": "text",
                                    "analyzer": "english"
                                }
                            }
                        },
                        {
                            "date": {
                                "unmatch": "*_es",
                                "match_mapping_type": "date",
                                "mapping": {
                                    "type": "keyword"
                                }
                            }
                        }
                    ]
                }
            },
            "aliases": {}
        }
    }
}
```

1.1.3, Delete specified template
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| template_name |string | template name      | must |

Method:`Post`
URL:`config/template/delete`
Data:
```
{
    "template_name": "chotel"
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "acknowledged": true
    }
}
```

### 1.2. Config Index API
-----

1.2.1, Create Index
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name |string | index name      | must |

Method:`Post`
URL:`config/index/create`
Data:
```
{
    "index_name": "chotel"
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "acknowledged": true,
        "shards_acknowledged": true,
        "index": "chotel"
    }
}
```
1.2.2, Get specified Index
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name |string | index name      | must |

Method:`GET`
URL:`config/index/get?index_name={index_name}`
Data:
```
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "chotel": {
            "aliases": {},
            "mappings": {
                "my_type": {
                    "dynamic": "true",
                    "_all": {
                        "enabled": true
                    },
                    "dynamic_templates": [
                        {
                            "es": {
                                "match": "*_es",
                                "match_mapping_type": "string",
                                "mapping": {
                                    "analyzer": "spanish",
                                    "type": "text"
                                }
                            }
                        },
                        {
                            "en": {
                                "match": "*",
                                "match_mapping_type": "string",
                                "mapping": {
                                    "analyzer": "english",
                                    "type": "text"
                                }
                            }
                        },
                        {
                            "date": {
                                "unmatch": "*_es",
                                "match_mapping_type": "date",
                                "mapping": {
                                    "type": "keyword"
                                }
                            }
                        },
                        {
                            "strings": {
                                "match_mapping_type": "string",
                                "mapping": {
                                    "analyzer": "ik_smart",
                                    "fields": {
                                        "keyword": {
                                            "type": "keyword"
                                        }
                                    },
                                    "ignore_above": 256,
                                    "type": "text"
                                }
                            }
                        }
                    ]
                },
                "_default_": {
                    "_all": {
                        "enabled": true
                    },
                    "dynamic_templates": [
                        {
                            "strings": {
                                "match_mapping_type": "string",
                                "mapping": {
                                    "analyzer": "ik_smart",
                                    "fields": {
                                        "keyword": {
                                            "type": "keyword"
                                        }
                                    },
                                    "ignore_above": 256,
                                    "type": "text"
                                }
                            }
                        }
                    ]
                }
            },
            "settings": {
                "index": {
                    "refresh_interval": "5s",
                    "number_of_shards": "3",
                    "provided_name": "chotel",
                    "creation_date": "1595326614770",
                    "number_of_replicas": "1",
                    "uuid": "C6Z5DEQKQEi9XUWKR3c8FQ",
                    "version": {
                        "created": "5061699"
                    }
                }
            }
        }
    }
}
```
### 1.3. Config IK analyzer API
-----
1.3.1, Query Current IK custom dict
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name |string | index name      | must |

Method:`Post`
URL:`config/ik`
Data:
```
```
Return:
```
{
    "code":0,
    "msg":"success",
    "data":[
        "南京国盟",
        ""
    ]
}
```
1.3.2, Add new IK custom string or array into dict
------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name |string | index name      | must |

Method:`GET`
URL:`config/ik/add?key_word=四川宾馆,成都宾馆`
Data:
```
```
Return:
```
{
    "code":0,
    "msg":"success",
    "data":[
        "南京国盟",
        "四川宾馆"
        "成都宾馆"
        ""
    ]
}
```

## 2. Data API
-----
For ES doc

2.1, Insert single Doc
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| data      |array  | data array, id must by as key     | must |

Method:`POST`
URL:`/data/insert`
Data:
```
{
    "index_name":"chotel",
    "type_name":"chotel_type",
    "data":{
        "10000":{
            "id":10001,
            "v":13935,
            "hotel_type":"8f1d51b4d222ff59ec49129be40f3032"
        }
    }
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "_index": "chotel",
        "_type": "chotel_type",
        "_id": "346309",
        "_version": 9,
        "result": "updated",
        "_shards": {
            "total": 2,
            "successful": 1,
            "failed": 0
        },
        "created": false
    }
}
```

2.2, Bulk Insert Doc
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| data      |array  | data array, id must by as key     | must |

Method:`POST`
URL:`/data/insert`
Data:
```
{
    "index_name":"chotel",
    "type_name":"chotel_type",
    "data":{
        "10000":{
            "id":10000,
            "v":13935,
            "hotel_type":"8f1d51b4d221ff59ec49129be40f3032"
        },
        "10001":{
            "id":10001,
            "v":15681,
            "hotel_type":"20aaf06de6a37a6fc80a2b94711d7188"
        }
    }
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": [
        ""{\"took\":15,\"errors\":false,\"items\":[{\"index\":{\"_index\":\"chotel\",\"_type\":\"chotel_type\",\"_id\":\"10000\",\"_version\":1,\"result\":\"created\",\"_shards\":{\"total\":2,\"successful\":1,\"failed\":0},\"created\":true,\"status\":201}},{\"index\":{\"_index\":\"chotel\",\"_type\":\"chotel_type\",\"_id\":\"10001\",\"_version\":1,\"result\":\"created\",\"_shards\":{\"total\":2,\"successful\":1,\"failed\":0},\"created\":true,\"status\":201}}]}"
    ]
}
```

2.3, Delete single Doc
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| id      |integer  | doc id   | must |

Method:`POST`
URL:`/data/delete/{id}`
Data:
```
{
	"index_name": "chotel",
	"type_name": "chotel_type"
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "found": true,
        "_index": "chotel",
        "_type": "chotel_type",
        "_id": "10000",
        "_version": 9,
        "result": "deleted",
        "_shards": {
            "total": 2,
            "successful": 1,
            "failed": 0
        }
    }
}
```

2.4, Get single Doc by Id
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| id      |integer  | doc id   | must |

Method:`POST`
URL:`/data/{index_name}/{type_name}/{id}`
Data:
```
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "_index": "chotel",
        "_type": "chotel_type",
        "_id": "10000",
        "_version": 10,
        "found": true,
        "_source": {
            "id": 10001,
            "v": 13935,
            "hotel_type": "8f1d51b4d222ff59ec49129be40f3032"
        }
    }
}
```

2.5, Search data via raw query
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| params      |array  | raw query   | must |

Method:`POST`
URL:`/data/rawquery`
Data:
```
{
    "index_name":"chotel",
    "type_name":"chotel_type",
    "params":{
        "query":{
            "match_phrase":{
                "name_cn":"城市国盟"
            }
        },
        "sort":[
            {
                "hotel_product_id":"desc"
            }
        ],
        "from":0,
        "size":4,
        "_source":[
            "city_id",
            "hotel_product_id",
            "name_cn",
            "location",
            "tag_cn",
            "hotel_star"
        ]
    }
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "took": 4,
        "timed_out": false,
        "_shards": {
            "total": 3,
            "successful": 3,
            "skipped": 0,
            "failed": 0
        },
        "hits": {
            "total": 1,
            "max_score": null,
            "hits": [
                {
                    "_index": "chotel",
                    "_type": "chotel_type",
                    "_id": "451083",
                    "_score": null,
                    "_source": {
                        "hotel_product_id": 451083,
                        "hotel_star": 2,
                        "tag_cn": [
                            "地铁周边",
                            "酒店公寓",
                            "大学周边"
                        ],
                        "location": {
                            "latitude": 32.03052,
                            "longitude": 118.798741
                        },
                        "name_cn": "南京城市国盟酒店公寓",
                        "city_id": 12
                    },
                    "sort": [
                        451083
                    ]
                }
            ]
        }
    }
}
```

2.6, Search data via json
-------
| Param         | Type  |       DESC        | Required  |
|---------------:|:-----:|:-----------------:|:---------:|
| index_name|string | index name name      | must |
| type_name  |string  | type name | must |
| params      |array  | 'query', 'filter', 'page', 'sort', 'return' ]   | must |

Method:`POST`
URL:`/data/query`
Data:
```
{
    "index_name":"chotel",
    "type_name":"chotel_type",
    "params":{
        "query":{   // 查询部分 must
            "key":[
                "name_cn",
                "name_en",
                "tag_cn"
            ],
            "value":"机场" // 查询关键字
        },
        "filter":{ // 过滤部分 optional
            "equal":{    // 相等
                "city_id":1
            },
            "range":{   // 范围, gte 大于等于, lte 小于等于, gt 大于, lt 小于
                "hotel_star":{
                    "gte":5,
                    "lte":5
                }
            },
            "in": {  // 包含, 在这里面
                "hotel_cateogry_id": [
                    495,
                    1
                ]
            },
            "geo": { // 地理信息范围查询
                "location": {  // 地理信息字段
                    "lon": 116.4,  // 经度, must
                    "lat": 39.9    // 纬度, must
                },
                "distance": "50km"  // optional
            }
        },
        "page":{ // 分页, optional, default 1 and 15 rows
            "cur_page":1,  // 当前页
            "page_size":6  // 分页数
        },
        "sort":[   // 排序, optional, default _score and _index
            {
                "geo": "asc",  // optional, 有地理位置排序的才加
                "hotel_star": "desc",
                "hotel_product_id":"desc",
            }
        ],
        "return":[ // 返回字段. optional, default all
            "city_id",
            "hotel_product_id",
            "tom_product_id",
            "name_cn",
            "location",
            "tag_cn",
            "hotel_star"
        ]
    }
}
```
Return:
```
{
    "code": 0,
    "msg": "success",
    "data": {
        "took": 8,
        "timed_out": false,
        "_shards": {
            "total": 3,
            "successful": 3,
            "skipped": 0,
            "failed": 0
        },
        "hits": {
            "total": 2,
            "max_score": null,
            "hits": [
                {
                    "_index": "chotel",
                    "_type": "chotel_type",
                    "_id": "427870",
                    "_score": null,
                    "_source": {
                        "tom_product_id": 103059787,
                        "hotel_product_id": 427870,
                        "hotel_star": 5,
                        "tag_cn": [
                            "商务出行国内（高星）",
                            "商务出行",
                            "高尔夫国内",
                            "热卖酒店",
                            "特价频道",
                            "火车站周边",
                            "地铁周边",
                            "高端连锁",
                            "机场周边",
                            "酒店（默认）",
                            "酒店",
                            "test"
                        ],
                        "location": {
                            "latitude": 39.91576,
                            "longitude": 116.434802
                        },
                        "name_cn": "北京国际饭店",
                        "city_id": 1
                    },
                    "sort": [
                        1.598929961831965,
                        427870
                    ]
                },
                {
                    "_index": "chotel",
                    "_type": "chotel_type",
                    "_id": "375093",
                    "_score": null,
                    "_source": {
                        "tom_product_id": 103059563,
                        "hotel_product_id": 375093,
                        "hotel_star": 5,
                        "tag_cn": [
                            "商务出行国内（高星）",
                            "商务出行",
                            "test",
                            "特价频道",
                            "机场周边",
                            "高端连锁",
                            "酒店（默认）",
                            "酒店"
                        ],
                        "location": {
                            "latitude": 40.054448,
                            "longitude": 116.61935
                        },
                        "name_cn": "北京首都机场东海康得思酒店",
                        "city_id": 1
                    },
                    "sort": [
                        2.197419615909357,
                        375093
                    ]
                }
            ]
        }
    }
}
```

