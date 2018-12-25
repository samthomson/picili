<?php

namespace App\Http\Controllers;

use Share\User;
use Share\DropboxToken;

use Share\PiciliFile;
use Share\Tag;
use Share\DropboxFilesource;
use Share\Task;

use App\Http\Controllers\Controller;

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
            return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
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
            // save it into the db and schedule an import task
            $oFolder = DropboxFilesource::where('user_id', $oUser->id)->first();
            if($oFolder === null) {
                $oFolder = new DropboxFilesource;
                $oFolder->user_id = $oUser->id;
            }
            $oFolder->access_token = $oUser->dropboxToken->access_token;
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
                // This was a callback request from google, get the token
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
        $oUser = Auth::user();
        $oUser->dropboxToken->delete();
        $oConnectedFileSource = $oUser->dropboxFileSource();
        if ($oConnectedFileSource) {
            $oConnectedFileSource->delete();
        }
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

        $aoPossibleTags = $oFile->tags->where('type', 'imagga');

        if (null !== $aoPossibleTags)
        {
            foreach($aoPossibleTags as $aTag) {
                if ($aTag['confidence'] > env('CONFIDENCE_THRESHOLD')) {
                    array_push($aTags, $aTag['value']);
                }
            }
        }
        $oResponse['tags'] = $aTags;

        return response()->json(['success' => true, 'file' => $oResponse]);
    }
}
