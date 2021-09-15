<?php

if (isset($mpconfig)) unset($mpconfig);
$mpconfig = new Configs([
	'online'		=> true,
	'parse_mode'		=> 'html',
	'password'		=> '',
	'server_admins'		=> [
		123456789
	],
	'database'		=> [
		'host'			=> 'localhost',
		'user'			=> '',
		'name'			=> '',
		'password'		=> ''
	],
	'redis'			=> [
		'host'			=> 'localhost',
		'port'			=> 6379,
		'password'		=> false,
		'database'		=> 0
	],
	'console_id'	=> 123456789,
	'log_report'	=> [
		'SHUTDOWN'		=> true,
		'FATAL' 		=> true,
		'ERROR'			=> true,
		'WARN'			=> true,
		'INFO'			=> false,
		'DEBUG'			=> false
	],
	'limits'		=> [
		'pollTitle'		=> 256,
		'pollDescription'	=> 256,
		'pollChoice'		=> 120,
		'pollChoices'		=> 25,
		'pollVoters'		=> 100,
		'boardComments'		=> 120,
		'ratingMaxLimit'	=> 10
	],
	'oneskyapp'		=> [
		'api-key'		=> '',
		'secret'		=> '',
		'platform-id'		=> 0,
		'file_name'		=> ''
	]
]);
$configs = $mpconfig->configs;

?>
