<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Share\User;
use App\Models\DropboxToken;
use Share\DropboxFilesource;
use Share\PiciliFile;
use Share\Tag;
use Share\Task;



class BlackboxTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testRootRoute()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    // skip now that registration is disabled
    /*
    public function testRegister()
    {
        $response = $this->json('POST', '/app/register', ['username' => 'user', 'email' => 'test@email.com', 'password' => 'pass']);

        $response
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true])
        ->assertJsonStructure(['success', 'username', 'token']);


        $response = $this->json('POST', '/app/register', ['username' => 'user', 'email' => 'fake@email', 'password' => 'pass']);

        $response
        ->assertStatus(200)
        ->assertJson(['success' => false]);


        $response = $this->json('POST', '/app/register', ['username' => 'user', 'email' => 'test@email.com', 'password' => 'emailalreadyinuse']);

        $response
            ->assertStatus(200)
            ->assertJson(['success' => false]);
    }
    */

    public function testLogin()
    {
        $sLoginRoute = '/app/authenticate';
        $oSeededUser = ['email' => 'seeded@user.com', 'password' => 'pass'];
        $response = $this->json('POST', $sLoginRoute, $oSeededUser);

        $response
        ->assertStatus(200)
        ->assertJsonFragment(['success' => true])
        ->assertJsonStructure(['success', 'token', 'username']);


        $response = $this->json('POST', $sLoginRoute, ['email' => $oSeededUser['email'], 'password' => 'wrong pass']);

        $response
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonStructure(['success', 'errors']);


        $response = $this->json('POST', $sLoginRoute, ['email' => 'random@email.com', 'password' => 'pass']);

        $response
        ->assertStatus(200)
        ->assertJson(['success' => false])
        ->assertJsonStructure(['success', 'errors']);
    }

    public function testGetAppUserState()
    {
        $sTestRoute = '/app/me';

        $response = $this->json('GET', $sTestRoute, []);

        $response
        ->assertStatus(400)
        ->assertExactJson(['error' => 'token_not_provided']);

        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);

        $response
            ->assertJsonFragment(['success' => true])
            ->assertJsonStructure(
                [
                    'success', 'username'
                ]
            )
            ->assertStatus(200);


        $response = $this->json('GET', $sTestRoute, ['fdsf' => 'fdfds', 'email' => 'trick@email.com', 'password' => 'alsoatrick'], $sHeader);

        $response
            ->assertJsonFragment(['success' => true])
            ->assertJsonStructure(
                [
                    'success', 'username'
                ]
            )
            ->assertStatus(200);
    }

    public function testGetHomeAggs()
    {
        $sTestRoute = '/app/homeaggs';
        // get settings logged out, get 400
        $response = $this->json('GET', $sTestRoute);

        $response
            ->assertStatus(400);

        // get aggregations logged in, get 200 success = true, and expected settings structure
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true])
            ->assertJson(
                [
                    'success' => true,
                    'home_aggs' => [
                        'on_this_day' => [
                            '5_years_ago' => [
                                [
                                    "id" => "1011"
                                ]
                            ],
                            '4_years_ago' => [
                                [
                                    "id" => "1017"
                                ]
                            ],
                            '3_years_ago' => [
                                [
                                    "id" => "1012"
                                ],
                                [
                                    "id" => "1013"
                                ],
                                [
                                    "id" => "1014"
                                ]
                            ],
                            '2_years_ago' => [
                                [
                                    "id" => "1018"
                                ]
                            ],
                            '1_year_ago' => [
                                [
                                    "id" => "1015"
                                ],
                                [
                                    "id" => "1016"
                                ]
                            ]
                        ]
                    ]
                ]
            );
    }

    public function testGetAppPageState()
    {
        /*
        return:

        'bYourPage' => $bUsersPage,
        'sUser' => $sUsername,
        'sSearchMode' => $sSearchMode,
        'sQuery' => $sQuery,
        'bHasFolders' => false,
        'bHasMap' => false,
        'bHasPeople' => false

        */
        $oUser = User::where('email', 'seeded@user.com')->first();
        $sTestRoute = '/app/pagestate/' . $oUser->id;
        $sInalidTestRoute = '/app/pagestate/notarealuser';

        $response = $this->json('GET', $sTestRoute);

        // not logged in yet
        $response
        ->assertStatus(400)
        ->assertExactJson(['error' => 'token_not_provided']);

        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);

        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true])
            ->assertJsonFragment(['bYourPage' => true]);

        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);

         $response
            ->assertJsonFragment(['success' => true])
            ->assertJsonFragment(['bYourPage' => true])
            ->assertJsonStructure(
                 [
                     'success', 'bYourPage', 'sSearchMode'
                 ]
            )
            ->assertStatus(200);


        $response = $this->json('GET', $sInalidTestRoute, [], $sHeader);

        $response
            ->assertJsonFragment(['success' => false])
            ->assertJsonStructure(
                [
                    'success'
                ]
            )
            ->assertStatus(200);
    }

    public function testGetSettings()
    {
        $sTestRoute = '/app/settings';
        // get settings logged out, get 400
        $response = $this->json('GET', $sTestRoute);

        $response
            ->assertStatus(400);

        // get settings logged in, get 200 success = true, and expected settings structure
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true])
            ->assertJsonFragment(['public' => false])
            ->assertJsonStructure(
                [
                    'success', 'username', 'dropbox' => ['folder'], 'public'
                ]
            );
    }

    public function testEncryptToken()
    {
        // save dropbox token

        $sTokenValue = 'sam';
        $sTokenUserId = 87;

        $oNewToken = new DropboxToken;
        $oNewToken->user_id = $sTokenUserId;
        $oNewToken->access_token = $sTokenValue;
        $oNewToken->save();

        // assert it was saved
        $oFoundToken = DropboxToken::where('user_id', $sTokenUserId)->first();
        $this->assertTrue(isset($oFoundToken));

        // assert token is legible
        $this->assertEquals($oFoundToken->access_token, $sTokenValue);
    }

    public function testGetFileInfo()
    {
        $oFile = new PiciliFile;
        $oFile->signature = 'sig';
        $oFile->user_id = 0;
        $oFile->save();

        $iFileId = $oFile->id;

        $cCreatedTags = 0;
        for ($i = 10; $i < 100; $i +=10, $cCreatedTags++)
        {
            $oTag = new Tag;
            $oTag->file_id = $iFileId;
            $oTag->type = 'imagga';
            $oTag->value = "tag-value-$i";
            $oTag->confidence = $i;
            $oTag->save();
        }

        // request info on a file
        $sTestRoute = '/app/fileinfo?file='.$iFileId;

        // don't get it without token
        $response = $this->json('GET', $sTestRoute, []);

        $response
        ->assertStatus(400)
        ->assertExactJson(['error' => 'token_not_provided']);

        // get it's tags
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);

        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true])
            ->assertJsonStructure(
                [
                    'success', 'file' => ['tags', 'address', 'altitude', 'lat', 'lon', 'date']
                ]
            );

        // don't get tags below threshold
        $aTags = json_decode($response->getContent())->file->tags;
        $this->assertNotEquals($cCreatedTags, count($aTags));
    }

    public function testSearch()
    {
        $aElasticQuery = [
            'q' => 'noresultsforthisquery'
        ];
        $aRouteParams = ['searchmode' => 'search', 'q' => urlencode(json_encode($aElasticQuery)), 'page' => 1];

        $iUserId = parent::iGetSeedUserId();

        $sTestRoute = '/app/pagestate/'.$iUserId;

        $sTestRoute .= '?' . http_build_query($aRouteParams);
        
        // don't get it without token
        $response = $this->json('GET', $sTestRoute, []);

        $response
        ->assertStatus(400)
        ->assertExactJson(['error' => 'token_not_provided']);

        // authed
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);
        
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true])
            ->assertJsonStructure(
                [
                    'success', 
                    'search' => [
                        'data' => [
                            'available',
                            'speed',
                            'more'
                        ],
                        'results',
                        'status'
                    ]
                ]
            );
        
    }

    public function testCurtailingTooHighAPage()
    {
        // if request asks for a page out of range it is limited to the highest available, and or
        
        // test too low
        $aElasticQuery = [
            'filters' => ['all' => true],
            'sort' => 'date_desc'
        ];
        $aRouteParams = ['searchmode' => 'search', 'q' => urlencode(json_encode($aElasticQuery)), 'page' => 0];

        $iUserId = parent::iGetSeedUserId();
        $sTestRoute = '/app/pagestate/'.$iUserId;
        $sTestRoute .= '?' . http_build_query($aRouteParams);

        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);
        
        $aResult = json_decode($response->getContent());
        $this->assertEquals($aResult->search->data->page, 1);

        // test too high
        $aElasticQuery = [
            'filters' => ['all' => true],
            'sort' => 'date_desc'
        ];
        $aRouteParams = ['searchmode' => 'search', 'q' => urlencode(json_encode($aElasticQuery)), 'page' => 100];

        $iUserId = parent::iGetSeedUserId();
        $sTestRoute = '/app/pagestate/'.$iUserId;
        $sTestRoute .= '?' . http_build_query($aRouteParams);
        
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('GET', $sTestRoute, [], $sHeader);
        
        $aResult = json_decode($response->getContent());
        $this->assertEquals($aResult->search->data->page, null);
        $this->assertEquals($aResult->search->data->range_min, null);
        $this->assertEquals($aResult->search->data->range_max, null);
    }

    // to do reinstate this test somehow, removing as it requires a different database to be created. which is not.    
    public function testUpdateDropboxFilesource()
    {
        $sTestRoute = '/app/settings/dropboxfolder';
        $sUpdateFolderTo = 'path/containing/my pics';

        // test auth guard
        $response = $this->json('PUT', $sTestRoute);
        $response
            ->assertStatus(400);

        // test update with invalid data
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('PUT', $sTestRoute, ['folder' => '256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256 256'], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => false]);

        // test update with valid data
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('PUT', $sTestRoute, ['folder' => $sUpdateFolderTo], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // test for db modal, verify it is updated
        $oUser = User::with('dropboxToken')->where('username', 'seeduser')->first();

        // and check an initial import task is created
        $oNewFolderSource = DropboxFilesource::where('user_id', $oUser->id)->first();
        $this->assertTrue(isset($oNewFolderSource));
        $this->assertEquals($oNewFolderSource->folder, $sUpdateFolderTo);

        $oImportTask = Task::where('processor', 'full-dropbox-import')->where('related_file_id', $oNewFolderSource->id)->first();
        $this->assertTrue(isset($oImportTask));

        // check can't update it twice
        $response = $this->json('PUT', $sTestRoute, ['folder' => 'second folder'], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $cCount = DropboxFilesource::where('user_id', $oUser->id)->count();

        $this->assertEquals(1, $cCount);
    }

    public function testDisconnectDropbox()
    {
        $sTestRoute = '/app/settings/dropbox';


        $iUserId = parent::iGetSeedUserId();
        $cInitialFilesources = DropboxFilesource::where('user_id', $iUserId)->count();
        $cInitialDropboxTokens = DropboxToken::where('user_id', $iUserId)->count();
        $this->assertEquals($cInitialFilesources, 1);
        $this->assertEquals($cInitialDropboxTokens, 1);

        // test auth guard
        $response = $this->json('DELETE', $sTestRoute);
        $response
            ->assertStatus(400);

        // test update with valid data
        $sHeader = parent::getHeaderForTest();
        $response = $this->json('DELETE', $sTestRoute, [], $sHeader);
        $response
            ->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // test for db modal, verify it is updated
        $oUser = User::with('dropboxToken')->where('username', 'seeduser')->first();
        
        $cEndFilesources = DropboxFilesource::where('user_id', $iUserId)->count();
        $cEndDropboxTokens = DropboxToken::where('user_id', $iUserId)->count();
        $this->assertEquals($cEndFilesources, 0);
        $this->assertEquals($cEndDropboxTokens, 0);
    }
}
