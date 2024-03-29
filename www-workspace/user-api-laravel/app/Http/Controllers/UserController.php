<?php

namespace App\Http\Controllers;

use Share\User;
use Share\DropboxToken;

use Share\PiciliFile;
use Share\Tag;
use Share\DropboxFilesource;
use Share\Task;

use SharedLibrary\Dropbox;

use App\Http\Controllers\Controller;

use App\Library\Helper;

use Auth;
use Validator;

use Illuminate\Http\Request;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use \Carbon\Carbon;


class UserController extends Controller
{

    public function register(Request $request)
    {
		$validator = Validator::make($request->all(), [
			'email' => 'required|email|unique:picili.users|max:255',
	        'password' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => ['Please enter a valid email, and password to register.']]);
        }

		$data = $request->only(['email', 'password']);

		$oUser = User::create([
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $token = JWTAuth::attempt($data);

        return response()->json(
            [
                'success' => true,
                'username' => $oUser->id,
                'token' => compact('token')['token']
            ], 200
        );
    }

    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'email' => 'required|email|max:255',
	        'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
        }

        $credentials = $request->only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['success' => false, 'errors' => ['invalid_credentials']]);
            }else{
                // it was a success, we have a token
                $oUser = User::where('email', $credentials['email'])->first();
                return response()->json(['success' => true, 'username' => $oUser->id, 'token' => compact('token')['token']]);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'errors' => ['could_not_create_token']]);
        }
    }

    public function getUser(Request $request)
    {
        $sError = 'unknown error';
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }else{

                $cTasks = Task::where('user_id', $user->id)
                ->where('processor', '<>', 'full-dropbox-import')->count();

                $cFiles = Task::where('user_id', $user->id)
                ->where('processor', '<>', 'full-dropbox-import')
                ->distinct('related_file_id')
                ->count('related_file_id');

                return response()->json(
                    [
                        'success' => true,
                        'username' => $user->id,
                        'bProcessing' => ($cTasks > 0) ? true : false,
                        'cProcessing' => $cTasks,
                        'cFiles' => $cFiles
                    ]
                );
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            $sError = 'token_expired';

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            $sError = 'token_invalid';
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            $sError = 'token_absent';
        }


        return response()->json(['success' => false, 'errors' => $sError]);
    }

    public function getSettings(Request $request)
    {
        $sError = 'unknown error';
        try {

            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }else{

                $maReturn = [
                    'success' => true,
                    'username' => $user->id,
                    'public' => (boolean)$user->public
                ];

                $user->load('dropboxToken', 'dropboxFileSource');

                if(isset($user->dropboxToken))
                {
                    $maReturn['dropbox'] = [
                        'bWorking' => true
                    ];

                    if(isset($user->dropboxFileSource)) {
                        $maReturn['dropbox']['folder'] = $user->dropboxFileSource->folder;
                    }

                }else{
                    $maReturn['dropbox'] = null;
				}
				
				// get mitigating task count
				$maReturn['mitigating-tasks'] = Helper::cMitigatingTasksForUser($user->id);

                return response()->json($maReturn);
            }

        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {

            $sError = 'token_expired';

        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {

            $sError = 'token_invalid';
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            $sError = 'token_absent';
        }


        return response()->json(['success' => false, 'errors' => $sError]);
    }

    public function updateDropboxFilesource(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'folder' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
        }

        $oUser = Auth::user();
		$oUser->load('dropboxToken');
		

        if(isset($oUser->dropboxToken))
        {
			$bBlockingTasks = Helper::cMitigatingTasksForUser($oUser->id) > 0;

			if ($bBlockingTasks) {
				return response()->json(['success' => false, 'errors' => ['blocking-tasks']]);
			}

            // save it into the db and schedule an import task
            $oFolder = DropboxFilesource::where('user_id', $oUser->id)->first();
            if($oFolder === null) {
                $oFolder = new DropboxFilesource;
                $oFolder->user_id = $oUser->id;
            }
            $oFolder->folder = $request->input('folder');
            $oFolder->save();

            $oImportTask = Task::where('processor', 'full-dropbox-import')->where('related_file_id', $oFolder->id)->first();

            if (!isset($oImportTask)) {
                $oImportTask = new Task;
                $oImportTask->processor = 'full-dropbox-import';
                $oImportTask->related_file_id = $oFolder->id;
            }

            $oImportTask->iAfter = -1;
            $oImportTask->dDateAfter = Carbon::now()->addSeconds(-1);
            $oImportTask->bImporting = true;
            $oImportTask->user_id = $oUser->id;
            $oImportTask->save();

            return response()->json(['success' => true]);
        }else{
            return response()->json(['success' => false, 'errors' => ['dropbox not connected']]);
        }
    }

    public function updatePrivacy(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'public' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
        }

        $oUser = Auth::user();
        $oUser->public = (boolean)$request->input('public');

        $oUser->save();

        return response()->json(['success' => true]);
    }

    public static function connectDropbox(Request $request)
    {
        // get data from request
        $code = request('code');

        // get oauth service
        $dropboxService = \OAuth::consumer('Dropbox', env('USER_API_URL') . '/oauth/dropbox');

        if(isset($dropboxService))
        {
            // check if code is valid

            // if code is provided get user data and sign in
            if ( ! is_null($code))
            {
                // This was a callback request from dropbox, get the token
                $token = $dropboxService->requestAccessToken($code);

                $sAccessToken = $token->getAccessToken();

                if(session('user.id'))
                {
                    $oDropboxToken = new DropboxToken;
                    $oDropboxToken->access_token = $sAccessToken;

                    $oUser = User::find(session('user.id'));

                    $oUser->dropboxToken()->save($oDropboxToken);
                    $sSPARoute = '/' . $oUser->id . '/settings';
                    return redirect(env('SPA_URL') . $sSPARoute);
                }else{
                    return response("not authed at this stage");
                }
            }
            // if not ask for permission first
            else
            {
                // flash user into session for when they return from oauth
                $oUser = self::getUserFromTokenIfPresent($request);

                // print_r($oUser);
                // die($oUser);

                if(isset($oUser))
                {
                    $request->session()->flash('user.id', $oUser->id);
                }else{
                    return response("no token");
                }

                // get oauth authorization
                $url = $dropboxService->getAuthorizationUri();

                // return to oauth login url
                return redirect((string)$url);
            }
        }else{
            // problem creating oauth consumer
            return response("unable to create connection to dropbox");
        }
    }

    private static function getUserFromTokenIfPresent(Request $request)
    {
        $oTokenHeader = $request->input('token');

        if (isset($oTokenHeader)) {

            $oToken = JWTAuth::parseToken();
            $user = JWTAuth::parseToken()->authenticate();

            return (isset($user)) ? $user : null;
        }

        return null;
    }

    public function disconnectDropbox()
    {
        Dropbox::disconnectedDropbox(Auth::id());
        return response()->json(['success' => true]);
    }

    public function getFileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
			'file' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
        }

        $oResponse = [];

        $oFile = PiciliFile::find($request->input('file'));

        if(!isset($oFile)) {
            return response()->json(['success' => false, 'errors' => 'file_not_found']);
        }

        $oResponse['lat'] = $oFile->latitude ? $oFile->latitude : null;
        $oResponse['lon'] = $oFile->longitude ? $oFile->longitude : null;

        $oResponse['altitude'] = $oFile->altitude ? $oFile->altitude : null;
        $oResponse['address'] = $oFile->address ? $oFile->address : null;
        $oResponse['date'] = $oFile->datetime ? Carbon::parse($oFile->datetime)->format('jS \o\f F, Y g:i a') : null;

        $aTags = [];
        $aPlantData = [];

		$aoPossibleTags = $oFile->tags->where('confidence', '>', (int)env('SEARCH_CONFIDENCE_THRESHOLD'));
		
        if (null !== $aoPossibleTags)
        {
            $validTags = ['imagga', 'opencage', 'ocr.text', 'ocr.numberplate'];
            $aPlantTags = [];

            foreach($aoPossibleTags as $aTag) {
				if (in_array($aTag['type'] , $validTags)) {
					array_push($aTags, [
						'type' => $aTag['type'],
						'literal' => $aTag['value'],
						'confidence' => $aTag['confidence']
					]);
                }
                if ($aTag['type'] === 'plantnet') {
					array_push($aPlantTags, [
						'type' => $aTag['type'],
						'subtype' => $aTag['subtype'],
						'literal' => $aTag['value'],
						'confidence' => $aTag['confidence']
					]);
                }
            }

            if (count($aPlantTags) > 0) {
                $aRawPlantTags = array_values($aPlantTags);
                $genus = array_values(array_filter($aRawPlantTags, function($value){
                    return $value['subtype'] === 'genus';
                }));
                $family = array_values(array_filter($aRawPlantTags, function($value){
                    return $value['subtype'] === 'family';
                }));
                $scientificname = array_values(array_filter($aRawPlantTags, function($value){
                    return $value['subtype'] === 'scientificname';
                }));                
                $gbif = array_values(array_filter($aRawPlantTags, function($value){
                    return $value['subtype'] === 'gbif';
                }));
                
                $aPlantData['commonname'] = array_values(array_filter($aRawPlantTags, function($value){
                    return $value['subtype'] === 'commonname';
                }));

                $aPlantData['genus'] = count($genus) > 0 ? $genus[0] : null;
                $aPlantData['family'] = count($family) > 0 ? $family[0] : null;
                $aPlantData['scientificname'] = count($scientificname) > 0 ? $scientificname[0] : null;
                $aPlantData['gbif'] = count($gbif) > 0 ? $gbif[0] : null;
            }
		}
		$aImaggaTags = array_values(array_filter($aTags, function($value){
			return $value['type'] === 'imagga';
		}));
		$aPlaceTags = array_values(array_filter($aTags, function($value){
			return $value['type'] === 'opencage';
        }));
        $aOCRTags = array_values(array_filter($aTags, function($value){
			return $value['type'] === 'ocr.text';
		}));
        $aNumberPlateTags = array_values(array_filter($aTags, function($value){
			return $value['type'] === 'ocr.numberplate';
        }));

        $oResponse['tags'] = $aImaggaTags;
        $oResponse['ocr'] = $aOCRTags;
        $oResponse['numberplate'] = $aNumberPlateTags;
        $oResponse['plant'] = $aPlantTags;
        $oResponse['plantdata'] = $aPlantData;
        $oResponse['place_tags'] = $aPlaceTags;

        if (isset($oFile->dropboxFile) && isset($oFile->dropboxFile->dropbox_path)) {
            $oResponse['dropboxPath'] = $oFile->dropboxFile->dropbox_path;
		}

        return response()->json(['success' => true, 'file' => $oResponse]);
    }
}
