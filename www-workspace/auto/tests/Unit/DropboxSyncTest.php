<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

use \App\Library\DropboxHelper;
use \App\Library\Helper;

use App\Models\DropboxFiles;
use Share\PiciliFile;
use Share\Task;
use App\Models\Log;



class DropboxSyncTest extends TestCase
{

     public function testParseEntries()
     {
         // take json string and turn into array of key values

         $sEntries = file_get_contents(resource_path('test-files/dropbox/entries.json'));

         $aoEntries = json_decode($sEntries);
         $oFolderEntry = $aoEntries[0];
         $this->assertObjectHasAttribute('.tag', $oFolderEntry);

         $oFileEntry = $aoEntries[2];
         $this->assertObjectHasAttribute('.tag', $oFileEntry);
         $this->assertObjectHasAttribute('name', $oFileEntry);
         $this->assertObjectHasAttribute('path_lower', $oFileEntry);
         $this->assertObjectHasAttribute('path_display', $oFileEntry);
         $this->assertObjectHasAttribute('id', $oFileEntry);
         $this->assertObjectHasAttribute('client_modified', $oFileEntry);
         $this->assertObjectHasAttribute('server_modified', $oFileEntry);
         $this->assertObjectHasAttribute('rev', $oFileEntry);
         $this->assertObjectHasAttribute('size', $oFileEntry);
         $this->assertObjectHasAttribute('content_hash', $oFileEntry);
     }

     public function testDelta()
     {
         $aDatabaseFiles = [];
         /*
         $oSeedDatabaseFiles = [
             [
                 ".tag" => "file",
                 "name" => "100_8870.JPG",
                 "path_lower" => "/test pics/subfolder/100_8870.jpg",
                 "path_display" => "/test pics/subfolder/100_8870.JPG",
                 "id" => "id:RSfj7I1LPuAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-24T04:43:08Z",
                 "server_modified" => "2016-04-24T04:43:21Z",
                 "rev" => "db730d2530c0",
                 "size" => 1443191,
                 "content_hash" => "6675cfab226e6f582915253afb036e5c7b1a107dffb31766923bb55fc07b583c"
             ], [
                 ".tag" => "file",
                 "name" => "DSC00716.JPG",
                 "path_lower" => "/test pics/dsc00716.jpg",
                 "path_display" => "/test pics/DSC00716.JPG",
                 "id" => "id:D36KzV4RcRAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-22T23:48:20Z",
                 "server_modified" => "2016-04-24T06:30:39Z",
                 "rev" => "db760d2530c0",
                 "size" => 4958820,
                 "content_hash" => "e3484652d3204fdf4be93c1354e781bb397e48c55bd05583f204b3dca28e16b5"
             ], [
                 ".tag" => "file",
                 "name" => "orange_flower.jpg",
                 "path_lower" => "/test pics/orange_flower.jpg",
                 "path_display" => "/test pics/orange_flower.jpg",
                 "id" => "id:Ee8ScLuL7SAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-24T08:34:18Z",
                 "server_modified" => "2016-04-24T08:34:27Z",
                 "rev" => "db780d2530c0",
                 "size" => 86369,
                 "content_hash" => "a3fec122f46b294f7d1fc30dfb6c67060be49ebf04503ff15192c547a9f20680"
             ], [
                 ".tag" => "file",
                 "name" => "DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
                 "path_lower" => "/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg",
                 "path_display" => "/test pics/DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABeYw",
                 "client_modified" => "2012-10-27T01:38:24Z",
                 "server_modified" => "2016-10-15T12:12:06Z",
                 "rev" => "f1590d2530c0",
                 "size" => 6684672,
                 "content_hash" => "8726c253014ccf223e1c90f0725cc57156f7e9e9e81458881eee7c2cecd67377"
             ], [
                 ".tag" => "file",
                 "name" => "DSC02914.JPG",
                 "path_lower" => "/test pics/dsc02914.jpg",
                 "path_display" => "/test pics/DSC02914.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCQ",
                 "client_modified" => "2016-03-27T11:28:24Z",
                 "server_modified" => "2017-03-03T14:02:15Z",
                 "rev" => "fa120d2530c0",
                 "size" => 8395180,
                 "content_hash" => "e54cda47b564d649c1cd6968ee1c8f167f917043ccce7695b2baac1de42a4e5e"
             ], [
                 ".tag" => "file",
                 "name" => "DSC02928.JPG",
                 "path_lower" => "/test pics/dsc02928.jpg",
                 "path_display" => "/test pics/DSC02928.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCA",
                 "client_modified" => "2016-03-28T11:34:50Z",
                 "server_modified" => "2017-03-03T14:02:15Z",
                 "rev" => "fa130d2530c0",
                 "size" => 6527406,
                 "content_hash" => "8774633a70b42120be001a94d157c5ae59b77ad0ecc66746c88d412d3aebabb3"
             ], [
                 ".tag" => "file",
                 "name" => "DSC06614.JPG",
                 "path_lower" => "/test pics/dsc06614.jpg",
                 "path_display" => "/test pics/DSC06614.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCg",
                 "client_modified" => "2016-06-27T09:55:16Z",
                 "server_modified" => "2017-03-03T14:02:23Z",
                 "rev" => "fa140d2530c0",
                 "size" => 12615680,
                 "content_hash" => "560a4b80c5f7eac7fcf84a8d9e5f7d285e87ef04a1faa79450cca73afeaa40b8"
             ], [
                 ".tag" => "file",
                 "name" => "DSC07562.JPG",
                 "path_lower" => "/test pics/dsc07562.jpg",
                 "path_display" => "/test pics/DSC07562.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCw",
                 "client_modified" => "2016-07-13T11:07:54Z",
                 "server_modified" => "2017-03-03T15:26:24Z",
                 "rev" => "fa150d2530c0",
                 "size" => 6324224,
                 "content_hash" => "ba206aec9d88a3b0fb8e79f533b0c334e047b41e13d2745c57cdc258d913cedd"
             ]
         ];
         */
         $oDatabaseFiles = [
             "/test pics/subfolder/100_8870.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e2",
                 "dropbox_id" =>"id:RSfj7I1LPuAAAAAAAAAAAQ",
                 "dropbox_path" =>"/test pics/subfolder/100_8870.jpg",
                 "dropbox_name" =>"100_8870.JPG",
                 "server_modified" =>"2016-04-24T04:43:21Z",
                 "ufo" =>false,
                 "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                 "sTempFileName" =>"58b9779a1109cf267c0067e2_58b977e07d4db.jpg",
                 "sha1" =>"c6140cd84c29a18015f47ddb0ff178482788efee",
                 "filesize" =>1.44319e+006,
                 "combinedSignature" =>"c6140cd84c29a18015f47ddb0ff178482788efee.1443191"

             ],
             "/test pics/dsc00716.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e3",
                  "dropbox_id" =>"id:D36KzV4RcRAAAAAAAAAAAQ",
                  "dropbox_path" =>"/test pics/dsc00716.jpg",
                  "dropbox_name" =>"DSC00716.JPG",
                  "server_modified" =>"2016-04-24T06:30:39Z",
                  "ufo" =>false,
                  "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                  "sTempFileName" =>"58b9779a1109cf267c0067e3_58b977e25ecfe.jpg",
                  "sha1" =>"bc296b091b71150a76459c53566f79fa4f73875a",
                  "filesize" =>4.95882e+006,
                  "combinedSignature" =>"bc296b091b71150a76459c53566f79fa4f73875a.4958820"
             ],
             "/test pics/orange_flower.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e4",
                 "dropbox_id" =>"id:Ee8ScLuL7SAAAAAAAAAAAQ",
                 "dropbox_path" =>"/test pics/orange_flower.jpg",
                 "dropbox_name" =>"orange_flower.jpg",
                 "server_modified" =>"2016-04-24T08:34:27Z",
                 "ufo" =>false,
                 "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                 "sTempFileName" =>"58b9779a1109cf267c0067e4_58b977e538471.jpg",
                 "sha1" =>"80f994c03d04868918c0ae4d6737b4a0748e864a",
                 "filesize" =>86369,
                 "combinedSignature" =>"80f994c03d04868918c0ae4d6737b4a0748e864a.86369"
             ],
             "/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg" => [
                 "id" =>"58b9779a1109cf267c0067e5",
                  "dropbox_id" =>"id:qEr0pTQv8p0AAAAAAABeYw",
                  "dropbox_path" =>"/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg",
                  "dropbox_name" =>"DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
                  "server_modified" =>"2016-10-15T12:12:06Z",
                  "ufo" =>false,
                  "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                  "sTempFileName" =>"58b9779a1109cf267c0067e5_58b977e61dadc.jpg",
                  "sha1" =>"1d85c63dc7f4abff7153d3d2fe2aa42741f52595",
                  "filesize" =>6.68467e+006,
                  "combinedSignature" =>"1d85c63dc7f4abff7153d3d2fe2aa42741f52595.6684672"
             ],
             "/test pics/dsc02914.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e6",
                  "dropbox_id" =>"id:qEr0pTQv8p0AAAAAAABkCQ",
                  "dropbox_path" =>"/test pics/dsc02914.jpg",
                  "dropbox_name" =>"DSC02914.JPG",
                  "server_modified" =>"2017-03-03T14:02:15Z",
                  "ufo" =>false,
                  "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                  "sTempFileName" =>"58b9779a1109cf267c0067e6_58b977e93910e.jpg",
                  "sha1" =>"0f8d6c72c9ba4c7de50591c19d0f23bdb10db3a4",
                  "filesize" =>8.39518e+006,
                  "combinedSignature" =>"0f8d6c72c9ba4c7de50591c19d0f23bdb10db3a4.8395180"
             ],
             "/test pics/dsc02928.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e7",
                  "dropbox_id" =>"id:qEr0pTQv8p0AAAAAAABkCA",
                  "dropbox_path" =>"/test pics/dsc02928.jpg",
                  "dropbox_name" =>"DSC02928.JPG",
                  "server_modified" =>"2017-03-03T14:02:15Z",
                  "ufo" =>false,
                  "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                  "sTempFileName" =>"58b9779a1109cf267c0067e7_58b977ed7e763.jpg",
                  "sha1" =>"e1d555a016a93dde3371ebb8ec465d6c9890f1ef",
                  "filesize" =>6.52741e+006,
                  "combinedSignature" =>"e1d555a016a93dde3371ebb8ec465d6c9890f1ef.6527406"
             ],
             "/test pics/dsc06614.jpg" => [
                 "id" =>"58b9779a1109cf267c0067e8",
                  "dropbox_id" =>"id:qEr0pTQv8p0AAAAAAABkCg",
                  "dropbox_path" =>"/test pics/dsc06614.jpg",
                  "dropbox_name" =>"DSC06614.JPG",
                  "server_modified" =>"2017-03-03T14:02:23Z",
                  "ufo" =>false,
                  "dropbox_folder_id" =>"58b7f1fc1109cf10dc003422",
                  "sTempFileName" =>"58b9779a1109cf267c0067e8_58b977f21a6d9.jpg",
                  "sha1" =>"4fc20a3d0ab32a41a1b5a3febed10607eb96dbb4",
                  "filesize" =>1.26157e+007,
                  "combinedSignature" =>"4fc20a3d0ab32a41a1b5a3febed10607eb96dbb4.12615680"

             ],
             "/test pics/dsc07562.jpg" => [
                 "id" =>"58b98b191109cf25c4003e72",
                 "dropbox_id" =>"id:qEr0pTQv8p0AAAAAAABkCw",
                 "dropbox_path" =>"/test pics/dsc07562.jpg",
                 "dropbox_name" =>"DSC07562.JPG",
                 "server_modified" =>"2017-03-03T15:26:24Z",
                 "ufo" => false,
                 "dropbox_folder_id" => "58b7f1fc1109cf10dc003422",
                 "sTempFileName" => "58b98b191109cf25c4003e72_58b98b1ce5b68.jpg",
                 "sha1" => "8e61ff469da2245dad4d86fce16d431a7bda9590",
                 "filesize" => 6.32422e+006,
                 "combinedSignature" =>"8e61ff469da2245dad4d86fce16d431a7bda9590.6324224"
             ]
         ];

         $aoDropboxFiles = [
             "/test pics/dsc00716.jpg" => [
                 ".tag" => "file",
                 "name" => "DSC00716.JPG",
                 "path_lower" => "/test pics/dsc00716.jpg",
                 "path_display" => "/test pics/DSC00716.JPG",
                 "id" => "id:D36KzV4RcRAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-22T23:48:20Z",
                 "server_modified" => "2016-04-24T06:30:39Z",
                 "rev" => "db760d2530c0",
                 "size" => 4958820,
                 "content_hash" => "e3484652d3204fdf4be93c1354e781bb397e48c55bd05583f204b3dca28e16b5"
             ],

             "/test pics/subfolder/100_8870.jpg" => [
                 ".tag" => "file",
                 "name" => "100_8870.JPG",
                 "path_lower" => "/test pics/subfolder/100_8870.jpg",
                 "path_display" => "/test pics/subfolder/100_8870.JPG",
                 "id" => "id:RSfj7I1LPuAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-24T04:43:08Z",
                 "server_modified" => "2016-04-24T04:43:21Z",
                 "rev" => "db730d2530c0",
                 "size" => 1443191,
                 "content_hash" => "6675cfab226e6f582915253afb036e5c7b1a107dffb31766923bb55fc07b583c"
             ],
             "/test pics/dsc00716.jpg" => [
                 ".tag" => "file",
                 "name" => "DSC00716.JPG",
                 "path_lower" => "/test pics/dsc00716.jpg",
                 "path_display" => "/test pics/DSC00716.JPG",
                 "id" => "id:D36KzV4RcRAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-22T23:48:20Z",
                 "server_modified" => "2016-04-24T06:30:39Z",
                 "rev" => "db760d2530c0",
                 "size" => 4958820,
                 "content_hash" => "e3484652d3204fdf4be93c1354e781bb397e48c55bd05583f204b3dca28e16b5"
             ],
             '/test pics/orange_flower.jpg' => [
                 ".tag" => "file",
                 "name" => "orange_flower.jpg",
                 "path_lower" => "/test pics/orange_flower.jpg",
                 "path_display" => "/test pics/orange_flower.jpg",
                 "id" => "id:Ee8ScLuL7SAAAAAAAAAAAQ",
                 "client_modified" => "2016-04-24T08:34:18Z",
                 "server_modified" => "2016-04-24T08:34:27Z",
                 "rev" => "db780d2530c0",
                 "size" => 86369,
                 "content_hash" => "a3fec122f46b294f7d1fc30dfb6c67060be49ebf04503ff15192c547a9f20680"
             ],
             "/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg" => [
                 ".tag" => "file",
                 "name" => "DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
                 "path_lower" => "/test pics/dsc00716 (sam-pc's conflicted copy 2016-10-15).jpg",
                 "path_display" => "/test pics/DSC00716 (sam-PC's conflicted copy 2016-10-15).JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABeYw",
                 "client_modified" => "2012-10-27T01:38:24Z",
                 "server_modified" => "2016-10-15T12:12:06Z",
                 "rev" => "f1590d2530c0",
                 "size" => 6684672,
                 "content_hash" => "8726c253014ccf223e1c90f0725cc57156f7e9e9e81458881eee7c2cecd67377"
             ],
             '/test pics/dsc02914.jpg' => [
                 ".tag" => "file",
                 "name" => "DSC02914.JPG",
                 "path_lower" => "/test pics/dsc02914.jpg",
                 "path_display" => "/test pics/DSC02914.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCQ",
                 "client_modified" => "2016-03-27T11:28:24Z",
                 "server_modified" => "2017-03-03T14:02:15Z",
                 "rev" => "fa120d2530c0",
                 "size" => 8395180,
                 "content_hash" => "e54cda47b564d649c1cd6968ee1c8f167f917043ccce7695b2baac1de42a4e5e"
             ],
             '/test pics/dsc02928.jpg' => [
                 ".tag" => "file",
                 "name" => "DSC02928.JPG",
                 "path_lower" => "/test pics/dsc02928.jpg",
                 "path_display" => "/test pics/DSC02928.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCA",
                 "client_modified" => "2016-03-28T11:34:50Z",
                 "server_modified" => "2017-03-03T14:02:15Z",
                 "rev" => "fa130d2530c0",
                 "size" => 6527406,
                 "content_hash" => "8774633a70b42120be001a94d157c5ae59b77ad0ecc66746c88d412d3aebabb3"
             ],
             '/test pics/dsc06614.jpg' => [
                 ".tag" => "file",
                 "name" => "DSC06614.JPG",
                 "path_lower" => "/test pics/dsc06614.jpg",
                 "path_display" => "/test pics/DSC06614.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCg",
                 "client_modified" => "2016-06-27T09:55:16Z",
                 "server_modified" => "2017-03-03T14:03:23Z",
                 "rev" => "fa140d2530c0",
                 "size" => 12615680,
                 "content_hash" => "560a4b80c5f7eac7fcf84a8d9e5f7d285e87ef04a1faa79450cca73afeaa40b8"
             ],
             '/test pics/dsc07562.jpg' => [
                 ".tag" => "file",
                 "name" => "DSC07562.JPG",
                 "path_lower" => "/test pics/dsc07562.jpg",
                 "path_display" => "/test pics/DSC07562.JPG",
                 "id" => "id:qEr0pTQv8p0AAAAAAABkCw",
                 "client_modified" => "2016-07-13T11:07:54Z",
                 "server_modified" => "2017-03-03T15:26:24Z",
                 "rev" => "fa150d2530c0",
                 "size" => 6324224,
                 "content_hash" => "ba206aec9d88a3b0fb8e79f533b0c334e047b41e13d2745c57cdc258d913cedd"
             ]
         ];


         //
         // zero change
         //

         $oDBFilesForZeroChangeTest = [];
         $oDBFilesForZeroChangeTest["/test pics/orange_flower.jpg"] = $oDatabaseFiles["/test pics/orange_flower.jpg"];

         $oDropboxFilesForZeroChangeTest = [];
         $oDropboxFilesForZeroChangeTest["/test pics/orange_flower.jpg"] = $aoDropboxFiles["/test pics/orange_flower.jpg"];

         $maDelta = DropboxHelper::findDifferenceInDropboxFileSystem(
             $oDBFilesForZeroChangeTest,
             $oDropboxFilesForZeroChangeTest
         );

         $this->assertTrue(count($maDelta['new']) === 0);
         $this->assertTrue(count($maDelta['deleted']) === 0);
         $this->assertTrue(count($maDelta['changed']) === 0);


         //
         // one deleted
         //
         // 2 db files, then 1 dropbox file
         $oDBFilesForOneDeletedTest = [];
         $oDBFilesForOneDeletedTest["/test pics/subfolder/100_8870.jpg"] = $oDatabaseFiles["/test pics/subfolder/100_8870.jpg"];
         $oDBFilesForOneDeletedTest["/test pics/dsc00716.jpg"] = $oDatabaseFiles["/test pics/dsc00716.jpg"];


         $oDropboxFilesForOneDeletedTest = [];
         // one the same - only
         $oDropboxFilesForOneDeletedTest["/test pics/subfolder/100_8870.jpg"] = $aoDropboxFiles["/test pics/subfolder/100_8870.jpg"];



         $maDelta = DropboxHelper::findDifferenceInDropboxFileSystem(
             $oDBFilesForOneDeletedTest,
             $oDropboxFilesForOneDeletedTest
         );



         $this->assertTrue(count($maDelta['new']) === 0);
         $this->assertTrue(count($maDelta['deleted']) === 1);
         $this->assertTrue(count($maDelta['changed']) === 0);





         //
         // one changed
         //

         $oDBFilesForOneChangedTest = [];
         $oDBFilesForOneChangedTest['/test pics/dsc06614.jpg'] = $oDatabaseFiles['/test pics/dsc06614.jpg'];


         $oDropboxFilesForOneChangedTest = [];
         // one the same - only
         $oDropboxFilesForOneChangedTest['/test pics/dsc06614.jpg'] = $aoDropboxFiles['/test pics/dsc06614.jpg'];



         $maDelta = DropboxHelper::findDifferenceInDropboxFileSystem(
             $oDBFilesForOneChangedTest,
             $oDropboxFilesForOneChangedTest
         );



         $this->assertTrue(count($maDelta['new']) === 0);
         $this->assertTrue(count($maDelta['deleted']) === 0);
         $this->assertTrue(count($maDelta['changed']) === 1);












         //
         // find new files, compare multiple dropbox file entries to zero db files
         //
         $oDBFilesForTwoNewTest = [];
         $oDBFilesForTwoNewTest["/test pics/orange_flower.jpg"] = $oDatabaseFiles["/test pics/orange_flower.jpg"];

         $oDropboxFilesForTwoNewTest = [];
         $oDropboxFilesForTwoNewTest["/test pics/orange_flower.jpg"] = $aoDropboxFiles["/test pics/orange_flower.jpg"];
         // two new
         $oDropboxFilesForTwoNewTest["/test pics/dsc00716.jpg"] = $aoDropboxFiles["/test pics/dsc00716.jpg"];
         $oDropboxFilesForTwoNewTest["/test pics/dsc07562.jpg"] = $aoDropboxFiles["/test pics/dsc07562.jpg"];

         $maDelta = DropboxHelper::findDifferenceInDropboxFileSystem(
             $oDBFilesForTwoNewTest,
             $oDropboxFilesForTwoNewTest
         );

         $this->assertTrue(count($maDelta['new']) === 2);
         $this->assertTrue(count($maDelta['deleted']) === 0);
         $this->assertTrue(count($maDelta['changed']) === 0);







         //
         // find one new and one deleted at same time
         //
         // 2 db files, then 2 dropbox file, with one the same and one replaced
         $oDBFilesForOneNewOneDeletedTest = [];
         $oDBFilesForOneNewOneDeletedTest["/test pics/subfolder/100_8870.jpg"] = $oDatabaseFiles["/test pics/subfolder/100_8870.jpg"];
         $oDBFilesForOneNewOneDeletedTest["/test pics/dsc00716.jpg"] = $oDatabaseFiles["/test pics/dsc00716.jpg"];


         $oDropboxFilesForOneNewOneDeletedTest = [];
         // one the same
         $oDropboxFilesForOneNewOneDeletedTest["/test pics/subfolder/100_8870.jpg"] = $aoDropboxFiles["/test pics/subfolder/100_8870.jpg"];
         // one replaced
         $oDropboxFilesForOneNewOneDeletedTest["/test pics/dsc07562.jpg"] = $aoDropboxFiles["/test pics/dsc07562.jpg"];



         $maDelta = DropboxHelper::findDifferenceInDropboxFileSystem(
             $oDBFilesForOneNewOneDeletedTest,
             $oDropboxFilesForOneNewOneDeletedTest
         );

         // echo "one new, one deleted?";
         // print_r($maDelta);


         $this->assertTrue(count($maDelta['new']) === 1);
         $this->assertTrue(count($maDelta['deleted']) === 1);
         $this->assertTrue(count($maDelta['changed']) === 0);
     }

     public function testNewDropboxFileEvent()
     {
         $sFakeDropboxid = 'id_'.uniqid();

         DropboxHelper::handleNewFileEvent(
             $sFakeDropboxid,
             'path_lower.jpeg',
             'name',
             "2017-03-03 16:08:32",
             0,
             1,
             121212
         );

         // check dropbox file object created
         $oDBXFL = \App\Models\DropboxFiles
            ::where('dropbox_id', $sFakeDropboxid)
            ->first();
         $this->assertTrue(isset($oDBXFL));

         // check initial import task has been scheduled

         $oFindTask = Task::where('related_file_id', $oDBXFL->id)->where('processor', 'download-dropbox-file')->first();

         $this->assertTrue(isset($oFindTask));
     }

    public function testChangedDropboxFileEvent()
    {
        $sSeedPath = "/test pics/dsc07562.jpg";
        $sOldTime = "2017-03-03 15:26:24";
        $sNewTime = "2017-03-03 16:08:32";
        $sSeedSize = 8395180;

        $oSeedDropboxFile = new DropboxFiles;
        $oSeedDropboxFile->server_modified = Carbon::parse($sOldTime);
        $oSeedDropboxFile->dropbox_path = $sSeedPath;
        $oSeedDropboxFile->dropbox_id = 2;
        $oSeedDropboxFile->dropbox_name = 'dsc07562.jpg';
        $oSeedDropboxFile->size = 456456;
        $oSeedDropboxFile->dropbox_folder_id = 9;
        $oSeedDropboxFile->dropbox_folder_id = 9;
        $oSeedDropboxFile->sTempFileName = Helper::sTempFilePathForDropboxFile(2);
        $oSeedDropboxFile->user_id = 2;
        $oSeedDropboxFile->save();
        $iId = $oSeedDropboxFile->id;

        DropboxHelper::handleChangedFileEvent(
            $sSeedPath,
            $sNewTime,
            $sSeedSize
        );


        $oUpdatedTask = DropboxFiles::where('dropbox_path', $sSeedPath)->first();

        // check timestamp updated
        $this->assertEquals($oUpdatedTask->server_modified, $sNewTime);

        $oFindTask = Task::where('related_file_id', $iId)->where('processor', 'import-changed-dropbox-file')->first();

        $this->assertTrue(isset($oFindTask));
     }

     public function testDeletedDropboxFileEvent()
     {
         // seed / prep
         $oDropboxFile = new DropboxFiles;
         $oDropboxFile->dropbox_path = 'test pics/orange.jpg';
         $oDropboxFile->dropbox_id = 1;
         $oDropboxFile->dropbox_name = 'orange.jpg';
         $oDropboxFile->server_modified = Carbon::now();
         $oDropboxFile->sTempFileName = Helper::sTempFilePathForDropboxFile(1);
         $oDropboxFile->size = 456456;
         $oDropboxFile->dropbox_folder_id = 9;
         $oDropboxFile->user_id = 2;
         $oDropboxFile->save();
         $iDropboxFileId = $oDropboxFile->id;

         $oAnotherDropboxFile = new DropboxFiles;
         $oAnotherDropboxFile->dropbox_path = 'test pics/green.jpg';
         $oAnotherDropboxFile->dropbox_id = 2;
         $oAnotherDropboxFile->dropbox_name = 'green.jpg';
         $oAnotherDropboxFile->server_modified = Carbon::now();
         $oAnotherDropboxFile->sTempFileName = Helper::sTempFilePathForDropboxFile(2);
         $oAnotherDropboxFile->size = 456456;
         $oAnotherDropboxFile->dropbox_folder_id = 9;
         $oAnotherDropboxFile->user_id = 2;
         $oAnotherDropboxFile->save();
         $iAnotherDropboxFileId = $oAnotherDropboxFile->id;

         $oPiciliFile = new PiciliFile;
         $oPiciliFile->signature = 'sigone';
         $oPiciliFile->user_id = 1;
         $oPiciliFile->save();
         $iPiciliFileId = $oPiciliFile->id;
         unset($oPiciliFile);

         $oMultiSourcePiciliFile = new PiciliFile;
         $oMultiSourcePiciliFile->signature = 'sigtwo';
         $oMultiSourcePiciliFile->user_id = 1;
         $oMultiSourcePiciliFile->save();
         $iMultiSourcePiciliFileId = $oMultiSourcePiciliFile->id;
         unset($oMultiSourcePiciliFile);

        // create an import task which shoudn't get deleted
        $iImportTaskId = Helper::QueueAnItem(
            'full-dropbox-import',
            $iPiciliFileId,
            1
        );


        Helper::addSourceToPiciliFile($iPiciliFileId, 'dropbox', $iDropboxFileId);
        Helper::addSourceToPiciliFile($iMultiSourcePiciliFileId, 'dropbox', $iAnotherDropboxFileId);
        Helper::addSourceToPiciliFile($iMultiSourcePiciliFileId, 'instagram', 89);

        $oPiciliFileWithSource = PiciliFile::find($iPiciliFileId);
        $this->assertTrue(isset($oPiciliFileWithSource->dropbox_filesource_id));
         
        DropboxHelper::handleDeletedFileEvent($oDropboxFile->dropbox_path);
        DropboxHelper::handleDeletedFileEvent($oAnotherDropboxFile->dropbox_path);

        unset($oPiciliFileWithSource);
        $oPiciliFileWithSource = PiciliFile::find($iPiciliFileId);

        // dropbox file should be gone
        $oMissingDropboxFile = DropboxFiles::find($iDropboxFileId);
        $this->assertFalse(isset($oMissingDropboxFile));

        // log of file removal exists
        $oCheckLog = Log::where('event', 'dropbox file deleted')->where('related_id', $iDropboxFileId)->first();
        $this->assertTrue(isset($oCheckLog));

        $oPiciliFileFoundAgain = PiciliFile::find($iPiciliFileId);
        $this->assertTrue(isset($oPiciliFileFoundAgain));
        // picili file no longer lists dropbox source

        // print_r($oPiciliFileFoundAgain);
        $this->assertFalse(isset($oPiciliFileFoundAgain->dropbox_filesource_id));

        // if picili file had no other sources it is now marked as deleted
        $this->assertTrue($oPiciliFileFoundAgain->bDeleted === 1);

        // if picili file had other sources it is not marked deleted
        $oMultiSourcePiciliFile = PiciliFile::find($iMultiSourcePiciliFileId);
        $this->assertFalse(!isset($oMultiSourcePiciliFile));

        // scheduled to be removed from elastic
        $oDeleteFromElasticTask = Task::where('processor', 'remove-from-elastic')->where('related_file_id', $iPiciliFileId)->where('bImporting', false)->first();
        $this->assertTrue(isset($oDeleteFromElasticTask));

        // scheduled to be removed from aws - dependent on previous task id
        $oDeleteFromElasticTask = Task::where('processor', 'remove-from-s3')->where('related_file_id', $iPiciliFileId)->where('bImporting', false)->first();
        $this->assertTrue(isset($oDeleteFromElasticTask));

        // if no other sources remove any tasks for this item from the queue
        $oaImportingTasks = Task::where('related_file_id', $iPiciliFileId)->where('bImporting', true)->get();
        $oaAllTasks = Task::where('related_file_id', $iPiciliFileId)->get();
        // full dropbox import should still exist
        $this->assertEquals(count($oaImportingTasks), 1);
        // remove from s3, elastic, processing file, import
        $this->assertEquals(count($oaAllTasks), 4);

        // full dropbox import task should still be there
        $oRetrievedImportTask = Task::find($iImportTaskId);
        $this->assertTrue(isset($oRetrievedImportTask));
     }
}
