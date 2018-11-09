<?php
use Illuminate\Support\Facades\Cache;
use App\Models\CreativeStorage;

return [
    'commonSync'          => true,                  //通用处理转化
    'storage'             => 'offersLook',          //数据落地存储点
    'unit_testing'        => [                      //断点调试
        'api_offers'                => false,       //三方api获取情况
        'offer_list'                => false,       //offer data list
        'offers_item'               => false,       //single offer debug
        'conversion_value_creative' => false,       //数据转化格式后结果 与 素材采集结果
    ],
    //'http_basic_auth'  => ['karen@leanmobi.com', 'nVvq5bWEPGWu2for9vGLfuRPeQkNf0eV'],
    'offer_api'           => 'http://api-cpi.avatarmobi.com/index.php?m=advert&p=getoffer&app_id=586&app_key=1b5fe3904335d52a9d7ce45507d51d07',
    //'offer_api_post_json' => '',
    'advertiser_id'  => '307',
    'creative_api'        => '',
    'geo_api'             => '',
    'offer_list'          => function ($data) {
        return isset($data[ 'offers' ]) ? $data[ 'offers' ] : null;
    },
    'pay_out_rate'        => 0.8,
    'offer_filter'        => [
        'revenue' => function ($var) {
            return (float)$var[ 'price' ] < 0 ? false : true;
        },
        'name'    => function ($var) {
            //dd($var['name'],123);
            return isset($var['name']) ? true : false;
            
        },
       'offer_id'    => function ($var) {
            $arr = [3338568,3338878,3340792,3341375,3344204];
            if (!in_array($var['id'], $arr)) {
                return false;
            }
            return true;
        }

    ],
    'conversion_field'    => [
        'offer'          => [
            'advertiser_offer_id' => 'id',
            'name'            => function ($var) {
                //AE|SA
                $name = fmtOfferName($var[ 'name' ] . " " . $var[ 'platform' ]);

                return isset($var[ 'name' ]) ? $name : null;
            },
            'advertiser_id'   => function ($var) {
                return 307;
            },
            'start_date'      => function ($var) {
                //after 5 min
                return strtotime("now") + 300;
            },
            'end_date'        => function ($var) {
                return strtotime("now") + 365 * 86400;
            },
            'status'          => function ($var) { return "active"; },
            'offer_approval'  => function ($var) { return 1; },
            'revenue_type'    => function ($var) { return 'RPA'; },
            'revenue'         => function ($var) {
                //$payout = @$var['payout'][0]['payout'];
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                return $payout;
            },
            'payout_type'     => function ($var) { return 'CPA'; },
            'payout'          => function ($var) {
                $payout = isset($var[ 'price' ]) ? (float)$var[ 'price' ] : 0;

                //dd($payout);
                return round($payout * 1 * 0.8, 2);
            },
            'preview_url'     => function ($var) {
                //return $var['preview_url'];
                if ($var['preview_url']) {
                    return $var['preview_url'];
                } else {
                    if ($var[ 'platform' ] == 'android') {
                        return 'https://play.google.com/store/apps/details?id=' . $var[ 'packet_name' ];
                    }
                    if ($var[ 'platform' ] == 'ios') {
                        return 'https://itunes.apple.com/us/app/id' . $var[ 'packet_name' ];
                    }
                }
                
            },
            'destination_url' => function ($var) {
                $fill_vars = [
                    '{clickid}' => '{click_id}',
                    '{gaid}'        => '{google_aid}',
                    '{subid}'   => '{aff_id}_{source_id}',
                    '{idfa}' => '{ios_idfa}',
                ];
                /*if ($var[ 'opsystem' ] == 'ios') {
                    $fill_vars[ '{tp_aaid}' ] = '{ios_idfa}';
                }*/

                return $var[ 'tracklink' ] = str_replace(array_keys($fill_vars), array_values($fill_vars), $var[ 'tracklink' ]);
                
                //return $var[ 'tracklink' ] . "&dv1={click_id}&nw_sub_aff={source_id}";
            },
            'description'     => 'kpi',
            'currency'        => function ($var) {
                return 'USD';
            },
        ],
        'offer_platform' => [
            'target' => function ($var) {
                $platform = [];
                if ($var[ 'platform' ] == 'ios') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'iOS',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }
                if ($var[ 'platform' ] == 'android') {
                    $platform[] = [
                        'platform' => "Mobile",
                        'system'   => 'Android',
                        'version'  => [],
                        'is_above' => "0",
                    ];
                }

                return $platform;
            },
        ],
        'offer_geo'      => [
            'target' => function ($var) {
                $countries = [];
                $countries_cache = Cache::store('file')->get('country', []);
                if (!empty($countries_cache)) {
                    $countries = json_decode($countries_cache, true);
                }
                $geo = explode(',', $var[ 'countries' ]);
                $target = [];
                //dd($var[ 'geo' ],$geo,$countries[$var['geo']]);
                if (is_array($geo)) {
                    foreach ($geo as $item) {
                        if (isset($countries[ $item ][ 'country' ])) {
                            $target[] = [
                                "type"    => 1,
                                "country" => $countries[ $item ][ 'country' ],
                                "city"    => [],
                            ];
                        }
                    }
                } else {
                    $target[] = [
                        "type"    => 1,
                        "country" => $countries[ $var[ 'geo' ] ][ 'name' ],
                        "city"    => [],
                    ];
                }

                //dd($target);
                return $target;
            }
        ],
        'offer_cap'      => [

            'adv_cap_type'       => function ($var) { return 2; },
            //'adv_cap_click'      => function ($var) { return 0; },
            'adv_cap_conversion' => function ($var) { return $var['day_cap']; },
            //'adv_cap_revenue',
            'aff_cap_type'       => function ($var) { return 0; },
            //'aff_cap_click' => function ($var) { return 50; },
            //'aff_cap_conversion' => function ($var) { return },
            //'aff_cap_payout'
        ],
        'offer_category'  =>[
            'name' => function ($var) { return 'CPI';},
        ]
    ],
    'conversion_creative' => function ($item) {

        $creative = [];
        $icon_url = isset($item[ 'icon1' ]) ?
            $item[ 'icon1' ] : null;
        if (!empty($icon_url)) {
            $icon_url = str_replace('https', 'http', $icon_url);
            if (getUrlCode($icon_url) == 200) {
                //$file_name = basename($icon_url);
                $file_name = md5($icon_url) . "." . imageTypeByUrl($icon_url);
                $file_url = $icon_url;
                $creative[ 'thumbfile' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }
        if (isset($item[ 'creatives1' ]) && !empty($item[ 'creatives1' ])) {
            $creatives = $item[ 'creatives' ];
            if (is_array($creatives)) {
                foreach ($creatives as $key => $fitem) {
                    $fitem[ 0 ] = str_replace('https', 'http', $fitem[ 0 ]);
                    if (getUrlCode($fitem[ 0 ]) != 200) {
                        continue;
                    }
                    //$file_name = basename($fitem[ 0 ]);
                    $file_name = md5($fitem[ 0 ]) . "." . imageTypeByUrl($fitem[ 0 ]);
                    $file_url = $fitem[ 0 ];
                    $creative[ 'image' ][] = [
                        'name'       => $file_name,
                        'url'        => $file_url,
                        'local_path' => CreativeStorage::save($file_name, $file_url),
                    ];
                }

            } elseif (is_string($creatives)) {
                //$file_name = basename($creatives);
                $file_name = md5($creatives) . "." . imageTypeByUrl($creatives);
                $file_url = $creatives;
                $creative[ 'image' ][] = [
                    'name'       => $file_name,
                    'url'        => $file_url,
                    'local_path' => CreativeStorage::save($file_name, $file_url),
                ];
            }
        }

        return $creative;
    }
];