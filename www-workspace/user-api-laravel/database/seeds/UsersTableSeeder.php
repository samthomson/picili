<?php

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\DropboxToken;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $oSeededUser = ['username' => 'seeduser', 'email' => 'seeded@user.com', 'password' => 'pass'];

        $oUser = User::create([
            'username' => $oSeededUser['username'],
            'email' => $oSeededUser['email'],
            'password' => bcrypt($oSeededUser['password']),
        ]);

        $oDropboxToken = new DropboxToken;
        $oDropboxToken->user_id = $oUser->id;
        $oDropboxToken->access_token = '';
        $oDropboxToken->save();
    }
}
