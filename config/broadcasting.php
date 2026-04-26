<?php

return [

	'default' => env('BROADCAST_CONNECTION', 'null'),

	'connections' => [

		'pusher' => [
			'driver' => 'pusher',
			'key' => env('PUSHER_APP_KEY'),
			'secret' => env('PUSHER_APP_SECRET'),
			'app_id' => env('PUSHER_APP_ID'),
			'options' => array_filter([
				'cluster' => env('PUSHER_APP_CLUSTER'),
				'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
				// Keep host/port optional so managed Pusher can use cluster routing.
				'host' => env('PUSHER_HOST') ?: null,
				'port' => env('PUSHER_PORT') ?: null,
				'scheme' => env('PUSHER_SCHEME', 'https'),
				'encrypted' => env('PUSHER_SCHEME', 'https') === 'https',
			], fn ($value) => !is_null($value) && $value !== ''),
		],

		'log' => [
			'driver' => 'log',
		],

		'null' => [
			'driver' => 'null',
		],

	],

];