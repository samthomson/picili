<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;

use Elasticsearch\ClientBuilder;

class SearchController extends Controller
{

    public function search($sUserId, $aQuery = null)
    {
		// //
		// // configure
		// //
		//
		// $sSearchStatus = 'success';
		// $aResults = [];
		// $aData = [];
		//
		// $client = ClientBuilder::create()->build();
		//
		// //
		// // do search
		// //
		// $params = [
		//     'index' => 'files',
		//     'type' => 'file',
		//     'body' => [
		//         'query' => [
		//             'match' => [
		//                 'user_id' => 'sam'
		//             ]
		//         ]
		//     ]
		// ];
		//
		// $response = $client->search($params);
		// // print_r($response);
		//
		// $aResults = $response;
		//
		//
		// // return stuff
		// $aReturn = [
		// 	'status' => $sSearchStatus,
		// 	'results' => $aResults,
		// 	'data' => $aData
		// ];
		return response()->json($aReturn);
    }
}
