<?php

namespace SharedLibrary;

class Dropbox {

    public static function disconnectedDropbox()
    {
        $oUser = Auth::user();
        // remove all upcoming import tasks including download tasks
        //// TODO
        // delete the associated dropbox token used to authenticate with dropbox
        $oUser->dropboxToken->delete();
        // delete the stored file source if there is one (user may not yet have added one)
        $oConnectedFileSource = $oUser->dropboxFileSource();
        if ($oConnectedFileSource) {
            $oConnectedFileSource->delete();
        }
        // todo later - email the user that their dropbox is now disconnected
        //// TODO
        
        return response()->json(['success' => true]);
    }
}
