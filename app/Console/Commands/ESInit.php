<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

/**
 * Initial ES for index
 *
 * @HowToRun php artisan ESInit
 */
class ESInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init laravel ES for default';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new Client();

        $url = config('scout.elasticsearch.hosts')[0] . '/_template/default';

        $paramTemplate = [
            'json' => [
                'template' => config('scout.elasticsearch.index'),
                'settings' => [
                    'number_of_shards' => 1
                ],
                'mappings' => [
                    '_default_' => [
                        '_all' => [
                            'enabled' => true
                        ],
                        'dynamic_templates' => [
                            [
                                'strings' => [
                                    'match_mapping_type' => 'string',
                                    'mapping' => [
                                        'type' => 'text',
                                        'analyzer' => 'ik_max_word',
                                        'ignore_above' => 256,
                                        'fields' => [
                                            'raw' => [
                                                'type' => 'keyword'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ]
        ];
        $client->put($url, $paramTemplate);

        $this->info("================ Create template success ! ==============");

        // Create index
        $url = config('scout.elasticsearch.hosts')[0] . '/' . config('scout.elasticsearch.index');
        $paramIndex = [
            'json' => [
                'settings' => [
                    'refresh_interval' => '5s',
                    'number_of_shards' => 1,
                    'number_of_replicas' => 1,
                    'max_result_window' => 100000
                ],
                'mappings' => [
                    '_default_' => [
                        '_all' => [
                            'enabled' => false
                        ]
                    ]
                ]
            ]
        ];
        $client->put($url, $paramIndex);
        $this->info("================ Create index success ! ==============");
    }
}
