<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;

use Validator;
use Auth;
use Illuminate\Http\Request;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

use App\Library\ElasticHelper;

class AppController extends Controller
{

    public function getPageState($iUsername, Request $request)
    {
        $maResponse = [];

        $bUsersPage = false;

        $oUser = null;

        // assertain if username corresonds to a real user, and if we have an auth token, if we are the user
        $oUser = User::find($iUsername);

        if(is_null($oUser))
        {
            $maResponse['success'] = false;
            $maResponse['search'] = [
                'results' => []
            ];
        }else{
            // we have found a real user

            // is the requester the user?
            $maResponse['bYourPage'] = false;
            $oTokenHeader = $request->header('Authorization');
            if (!is_null($oTokenHeader))
            {
                $oAuthedUserCheck = Auth::user();
                if (isset($oAuthedUserCheck)) {
                    $maResponse['bYourPage'] = true;

                }else{
                    // todo - then add must be public check to query
                }
            }

            // get more data
            $sSearchMode = ($request->input('searchmode')) ? $request->input('searchmode') : 'search';
            //// $aQuery = ($request->input('query')) ? $request->input('query') : [];
            $iPage = ($request->input('page')) ? $request->input('page') : 1;

            $maResponse['sSearchMode'] = $sSearchMode;
            // $maResponse['sQuery'] = $sQuery;
            $maResponse['bHasFolders'] = false;
            $maResponse['bHasMap'] = false;
            $maResponse['bHasPeople'] = false;


            $maResponse['search'] = [];

            $sSearchMode = ($request->input('searchmode')) ? $request->input('searchmode') : 'default';
            $aQuery = ($request->input('q')) ? json_decode(urldecode($request->input('q')), TRUE) : [];

            // print_r($aQuery);die();

            // todo - parse query from url vars
            $aQueryForElastic = [];
            if(isset($aQuery['q']) && $aQuery['q'] !== '')
            // if(isset($aQuery->q) && $aQuery->q !== '')
            {
                $aQueryForElastic['q'] = $aQuery['q'];
                // die("got a query");
            }else{
                // die('no query');
            }
            if(isset($aQuery['filters']) && $aQuery['filters'] !== [])
            {
                $aQueryForElastic['filters'] = [];
                foreach($aQuery['filters'] as $aFilter)
                {

                    switch($aFilter['type'])
                    {
                        case 'folder':
                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => $aFilter['type'],
                                    'value' => $aFilter['value']

                                    // $aFilter['type'] => $aFilter['value']
                                ]
                            );
                            break;
                        case 'map':
                            $iaVals = explode(',', $aFilter['value']);

                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => 'geo',
                                    'value' => [
                                        'lat_min' => $iaVals[0],
                                        'lat_max' => $iaVals[1],
                                        'lon_min' => $iaVals[2],
                                        'lon_max' => $iaVals[3],
                                        'zoom' => $iaVals[4]
                                    ]

                                    // $aFilter['type'] => $aFilter['value']
                                ]
                            );
                            break;
                        case 'calendar':
                            // die($aFilter['value']);
                            $saVals = explode(':', $aFilter['value']);

                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => 'date',
                                    'value' => [
                                        'mode' => $saVals[0],
                                        'start_date' => $saVals[1]
                                    ]
                                ]
                            );
                            break;
                        case 'people.state':

                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => 'people_emotion',
                                    'value' => $aFilter['value']
                                ]
                            );
                            break;
                        case 'people.gender':

                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => 'people_gender',
                                    'value' => $aFilter['value']
                                ]
                            );
                            break;
                        case 'people.grouping':

                            array_push(
                                $aQueryForElastic['filters'],
                                [
                                    'type' => 'people_number',
                                    'value' => $aFilter['value']
                                ]
                            );
                            break;
                    }
                }
                // $aQueryForElastic['q'] = $aQuery['q'] ;
            }

            if(isset($aQuery['sort']))
            {
                $aQueryForElastic['sort'] = $aQuery['sort'];
            }

            $sElasticQuerySearchMode = 'search';
            switch($sSearchMode)
            {
                case 'folders':
                    $sElasticQuerySearchMode = 'folder';
                    break;
                case 'calendar':
                    $sElasticQuerySearchMode = 'calendar';
                    break;
                case 'map':
                    $sElasticQuerySearchMode = 'map';
                    break;
            }

            // elastic docs are stored with a user id, not username, so we'll use that now while debugging (22-4-2017)
            $sUsername = $oUser->id;
            try{
                // query elastic through helper utility
                $maElasticResponse = ElasticHelper::aSearch($sUsername, $aQueryForElastic, $sElasticQuerySearchMode, $iPage);
                // return them, and success status
                $maResponse['search'] = $maElasticResponse;
                $maResponse['success'] = true;
            } catch(\Elasticsearch\Common\Exceptions\NoNodesAvailableException $ex) {
                $maResponse['search'] = [
                    'results' => []
                ];
            }
        }

        return response()->json($maResponse);
    }

    public function search(Request $request)
    {
		$validator = Validator::make($request->all(), [
            'username' => 'required|max:255|alpha',
			'mode' => 'required|alpha'
        ]);
        if ($validator->fails()) {
            return response()->json([]);
        }else{

            $sSearchMode = $request->input('mode');

            return response()->json(
                [
                    'success' => true,
                    'results' => [],
                    'mode' => $sSearchMode
                ]
            );
        }
    }

    public function homeAggs(Request $request)
    {
        // get logged in user from session
        $oUser = Auth::user();

        // get and make queries
        $aHomeAggs = ElasticHelper::aHomeAggs($oUser->id);

        // return any results
        return response()->json([
            'success' => true,
            'home_aggs' => $aHomeAggs
        ]);
    }

    public function test(Request $request) {
        $aResults = ElasticHelper::aTestPHashSearch($request->input('q'));

        $binQ = base_convert($request->input('q'), 16, 2);

        echo strlen($binQ);

        echo "<br/>query length: {$binQ}<br/>";

        $cResuts = count($aResults);

        echo "<br/>{$cResuts} results <br/><hr/>";

        // print_r($aResults);

        foreach ($aResults as $aResult) {
            $sSource = "https://s3-eu-west-1.amazonaws.com/picili-bucket/t/1/m{$aResult['id']}.jpg";
            $sSource = "https://s3-ap-southeast-1.amazonaws.com/picili-test-bucket/t/1/m{$aResult['id']}.jpg";

            // https://s3-ap-southeast-1.amazonaws.com/picili-test-bucket/t/1/m23808.jpg
            echo "<img src=\"{$sSource}\" />";

            $match = self::compare($aResult['bin'], $binQ);
            
            echo "<br/>score: {$aResult['score']}<br/>bin :{$aResult['bin']}<br/>binQ:{$binQ}<br/>similarity: {$match}<hr/>";
        }
    }

    private static function compare($bin1, $bin2)
    {
        $iSame = 0;
        $iDiff = 0;
        $iTotal = 0;
        // return 0;

        if(strlen($bin1) === 63) {
            $bin1 = '0'.$bin1;
        }
        if(strlen($bin2) === 63) {
            $bin2 = '0'.$bin2;
        }

        for ($iPos = 0; $iPos < strlen($bin1); $iPos++, $iTotal++)
        {
            if ($bin1[$iPos] === $bin2[$iPos]) {
                $iSame++;
            }else{
                $iDiff++;
            }
        }
        return ($iSame / $iTotal) * 100;
    }
}
