<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
// use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

use Artisan;
use DB;

use Share\PiciliFile;
use Share\User;

use SharedLibrary\ElasticHelper;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp()
    {
        $this->refreshApplication();
        parent::setUp();

        // migrate api specific things
        Artisan::call('migrate');
        // migrate shared tables
        Artisan::call('migrate', ['--path' => './../picili-shared/Migrations', '--database' => 'picili']);

        Artisan::call('db:seed');

        $bStatus = ElasticHelper::bCreateAndPutMapping();

        $aoFiles = PiciliFile::all();

        foreach ($aoFiles as $oFile)
        {
            ElasticHelper::bSaveFileToElastic($oFile);
        }
        sleep(1);
    }

    public function tearDown()
    {
        Artisan::call('migrate:rollback', ['--path' => '../picili-shared/Migrations', '--database' => 'picili']);
        Artisan::call('migrate:rollback', ['--database' => 'picili']);

        DB::connection('picili')->disconnect();
        parent::tearDown();
    }

    public function getHeaderForTest($sEmail = "seeded@user.com", $sPassword = "pass") {

        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->login($sEmail, $sPassword)
        ];
    }

    public function login($sEmail = "seeded@user.com", $sPassword = "pass")
    {
        $response = $this->call('POST', '/app/authenticate', ["email" => $sEmail, "password" => $sPassword]);

        return json_decode($response->getContent())->token;
    }
    public function iGetSeedUserId()
    {
        return User::where('email', "seeded@user.com")->first()->id;
    }
}
