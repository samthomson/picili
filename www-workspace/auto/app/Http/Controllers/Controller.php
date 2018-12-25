<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use DB;

use Carbon\Carbon;

use App\Models\PiciliFile;
use App\Models\DropboxFiles;
use App\Models\Task;
use Share\User;
use App\Models\ProcessorLog;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    function makeDashboard()
    {
        $aStats = [];

        $aStats['iUsers'] = User::count();
        $aStats['iFiles'] = PiciliFile::count();
        $aStats['iDropboxFiles'] = DropboxFiles::count();
        $aStats['iQueued'] = Task::count();


        //
        // age of tasks
        //
        $aStats['tasks'] = [];

        $aStats['tasks']['0_5mins'] = Task::where('dDateAfter', '<', Carbon::now())->where('dDateAfter', '>', Carbon::now()->addMinutes(-5))->count();

        $aStats['tasks']['5_10mins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-5))->where('dDateAfter', '>', Carbon::now()->addMinutes(-10))->count();

        $aStats['tasks']['10_15mins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-10))->where('dDateAfter', '>', Carbon::now()->addMinutes(-15))->count();

        $aStats['tasks']['15_30mins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-15))->where('dDateAfter', '>', Carbon::now()->addMinutes(-30))->count();

        $aStats['tasks']['30_45mins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-30))->where('dDateAfter', '>', Carbon::now()->addMinutes(-45))->count();

        $aStats['tasks']['45_60mins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-45))->where('dDateAfter', '>', Carbon::now()->addMinutes(-60))->count();

        $aStats['tasks']['60_plusmins'] = Task::where('dDateAfter', '<', Carbon::now()->addMinutes(-60))->count();




        $aStats['queues'] = [];
        $saQueues = Task::select('processor')->groupBy('processor')->get();

        $aStats['sa_queues'] = [];
        // print_r($saQueues->toArray());exit();
        foreach ($saQueues->toArray() as $value) {
            $sProcessorName = $value['processor'];

            $aQueue = [
                'name' => $sProcessorName,
                'i_count' => Task::where('processor', $sProcessorName)->count()
            ];
            array_push($aStats['sa_queues'], $aQueue);
        }

        //
        // times seen by processor
        //
        $aStats['tasks_times_seen'] = [];
        $aStats['tasks_times_seen']['2'] = Task::where('iTimesSeenByProccessor', 2)->count();
        $aStats['tasks_times_seen']['3'] = Task::where('iTimesSeenByProccessor', 3)->count();
        $aStats['tasks_times_seen']['3_plus'] = Task::where('iTimesSeenByProccessor', '>', 3)->count();

        //
        // pending tasks
        //
        $aStats['i_processed'] = ProcessorLog::count();


        //
        // processor log
        //
        $aStats['i_processed'] = ProcessorLog::count();


        // get all processors in log
        $saProcessors = ProcessorLog::distinct('processor')->get();

        $aStats['sa_processors'] = [];
        foreach ($saProcessors->toArray() as $value) {
            $sProcessorName = $value[0];

            $aProcessor = [
                'name' => $sProcessorName,
                'i_count' => ProcessorLog::where('processor', $sProcessorName)->count()
            ];
            array_push($aStats['sa_processors'], $aProcessor);
        }
        //
        // activity aggregations
        //
        $cursor = DB::collection('processor_logs')->raw(function($collection)
        {
            $start = new \MongoDB\BSON\UTCDateTime(Carbon::now()->addHours(-1)->timestamp * 1000);

            return $collection->aggregate(
                [
                    [
                        '$match' => [
                            'dTimeOccured' => [
                                '$gt' => $start
                            ]
                        ]
                    ],
                    [
                        '$project' => [

                            'year' => ['$substr' => ['$dTimeOccured', 0, 4] ],
                            'month' => ['$substr' => ['$dTimeOccured', 5, 2] ],
                            'day' => ['$substr' => ['$dTimeOccured', 8, 2] ],
                            'hour' => ['$substr' => ['$dTimeOccured', 11, 2] ],
                            'minute' => ['$substr' => ['$dTimeOccured', 14, 2] ],
                            'd' => ['$dateToString' => ['format' => "%d/%m %H:%M", 'date' => '$dTimeOccured']]

                            /*
                            'minute' => [ '$dateToString' => [ 'format' => "%S", 'date' => 'dTimeOccured' ] ]*/
                        ]
                    ],
                    [
                        '$group' => [
                            '_id' => [
                                'd' => '$d'
                            ],
                            'number' => [ '$sum' => 1 ]
                        ]
                    ],
                    [
                        '$sort'    => [ '_id' => 1 ]
                    ]
                ]
            );
        });

        // $project: {
        //         yearMonthDay: { $dateToString: { format: "%Y-%m-%d", date: "$date" } },
        //         time: { $dateToString: { format: "%H:%M:%S:%L", date: "$date" } }
        //  }

        // print_r($cursor);die();

        $aoTimeData = [];
        $aoTimeDataChart = [["Minute", "# processed count", [ "role" => "style" ] ]];

        $oDateAnHourAgo = Carbon::now()->addHours(-1);

        // print_r($oDateAnHourAgo);die();

        if(isset($cursor))
        {
            // print_r($cursor);

            $aKeyOrderedData = [];


            foreach($cursor->toArray() as $oData)
            {
                $sKey = explode(' ', $oData['_id']['d'])[1];

                $aKeyOrderedData[$sKey] =  [
                    'date' => $oData['_id']['d'],
                    'count' => $oData['number'],
                    "red"
                ];
            }

            for($i = 0; $i < 60; $i++)
            {
                $oDateAnHourAgo->addMinutes(1);
                $sMinuteKey = $oDateAnHourAgo->format('H:i');

                $oPushData = [];

                if(isset($aKeyOrderedData[$sMinuteKey]))
                {
                    $oPushData = $aKeyOrderedData[$sMinuteKey];
                }else{
                    $oPushData = [
                        'date' => $oDateAnHourAgo->format('d/m H:i'),
                        'count' => 0,
                        "red"
                    ];
                }

                // key like "16:43"

                array_push($aoTimeData, $oPushData);

                array_push($aoTimeDataChart, [
                    $oPushData['date'],
                    $oPushData['count'],
                    "red"
                ]);
            }
        }

        $aStats['timedata'] = $aoTimeData;
        $aStats['timedata_chart'] = $aoTimeDataChart;


        return view('dashboard', ['aStats' => $aStats]);
    }
}
