<?php

namespace SharedLibrary;

class Dropbox {

    public static function disconnectedDropbox()
    {
        $oUser = Auth::user();
        
        // delete the associated dropbox token used to authenticate with dropbox
        $oUser->dropboxToken->delete();
        // delete the stored file source if there is one (user may not yet have added one)
        $oConnectedFileSource = $oUser->dropboxFileSource();
        if ($oConnectedFileSource) {

            // remove all upcoming import tasks including download tasks
            // delete task that periodically scans dropbox
            Task::where('related_file_id', $oConnectedFileSource->id)
                ->where('processor', 'full-dropbox-import')
                ->delete();
            // delete scheduled tasks that would have downloaded a file.
            Task::where('related_file_id', $oPiciliFile->id)
                ->where('bImporting', true)
                ->where('processor', '<>', 'full-dropbox-import')
                ->delete();


            $oConnectedFileSource->delete();
        }
        // todo later - email the user that their dropbox is now disconnected
        //// TODO
        
        return response()->json(['success' => true]);
    }
}
