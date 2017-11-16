<?php

return [

	/*
	|--------------------------------------------------------------------------
	| oAuth Config
	|--------------------------------------------------------------------------
	*/

	/**
	 * Storage
	 */
	'storage' => '\\OAuth\\Common\\Storage\\Session',

	/**
	 * Consumers
	 */
	'consumers' => [

		'Dropbox' => [
			'client_id'     => env('DROPBOX_CLIENT_ID'),
			'client_secret' => env('DROPBOX_CLIENT_SECRET'),
			'scope'         => [],
		]
		/*,
		'Instagram' => [
			'client_id'     => env(''),
			'client_secret' => env(''),
			'scope'         => ['public_content'],
		],*/
	]
];
