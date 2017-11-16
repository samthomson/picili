<?php

namespace Tests;

use Artisan;
use DB;
use Schema;
use Config;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function setUp()
    {
        $this->refreshApplication();
        parent::setUp();

        Artisan::call('migrate', ['--env' => 'testing']);
        Artisan::call('migrate', ['--path' => '../picili-shared/Migrations', '--env' => 'testing']);
        // Artisan::call('migrate --database="mysql-queue-test"');
        Artisan::call('db:seed');
    }

    public function tearDown()
    {
        // clear out db, do with raw drop and create statement and not migrate:rollback as it will not find the migraitons from other folder..

        // rollback mongo migrations (as mongo is default db)
        // Artisan::call('migrate:rollback');
        
        // Artisan::call('migrate:rollback', ['--path' => '../picili-shared/Migrations']);
        // Artisan::call('migrate:rollback --database=mysql');
        // Artisan::call('migrate:refresh');

        $sDBName = config('database.connections.'.config('database.default').'.database');

        DB::statement('DROP DATABASE `'.$sDBName.'`;');
        DB::statement('CREATE DATABASE `'.$sDBName.'`;');
        
        parent::tearDown();
    }
}
