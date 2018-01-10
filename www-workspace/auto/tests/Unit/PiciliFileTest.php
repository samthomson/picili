<?php

namespace Tests\Unit;

use Tests\TestCase;
use \Carbon\Carbon;


use Share\PiciliFile;


class PiciliFileTest extends TestCase
{
    public function testFileConstraint()
    {
        /*
        should schedule a new file for all the processors
        with some tasks dependent on another.
        */
        $oPiciliFile = new PiciliFile;
        $oPiciliFile->user_id = 1;
        $oPiciliFile->signature = 'unique';
        $oPiciliFile->save();


        $oDuplicatePiciliFile = new PiciliFile;
        $oDuplicatePiciliFile->user_id = 1;
        $oDuplicatePiciliFile->signature = 'unique';
        $mSaveResult = $oDuplicatePiciliFile->save();

        $this->assertTrue($mSaveResult !== false);
    }
}
