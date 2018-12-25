<?php

use Illuminate\Database\Seeder;

use Share\User;
use Share\DropboxToken;
use Share\DropboxFilesource;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $oSeededUser = ['email' => 'seeded@user.com', 'password' => 'pass'];

        $oUser = User::create([
            'email' => $oSeededUser['email'],
            'password' => bcrypt($oSeededUser['password']),
        ]);
        $oUser->id = 666;
        $oUser->save();

        $oDropboxToken = new DropboxToken;
        $oDropboxToken->user_id = $oUser->id;
        $oDropboxToken->access_token = 'fake token';
        $oDropboxToken->save();

        $oDropboxFileSource = new DropboxFilesource;
        $oDropboxFileSource->user_id = $oUser->id;
        $oDropboxFileSource->folder = 'test folder';
        $oDropboxFileSource->save();
    }
}
