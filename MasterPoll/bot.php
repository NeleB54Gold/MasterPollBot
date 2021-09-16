<?php

date_default_timezone_set('UTC');
require_once('./Languages.php');
require_once('./AntiFlood.php');

# Updates type filter
if ($update['message']) {
	$update_type = 'message';
	if (isset($update['message']['via_bot'])) die;
} elseif ($update['edited_message']) {
	die;
} elseif ($update['edited_channel_post']) {
	die;
} elseif ($update['channel_post']) {
	$update_type = 'channel_post';
} elseif ($update['edited_channel_post']) {
	$update_type =  'edited_channel_post';
} elseif ($update['inline_query']) {
	$update_type =  'inline_query';
} elseif ($update['chosen_inline_result']) {
	$update_type =  'chosen_inline_result';
} elseif ($update['callback_query']) {
	$update_type =  'callback_query';
} else {
	die('Unrecognized update type...');
}

if (in_array($update_type, ['inline_query', 'chosen_inline_result'])) {
	if (isset($update['inline_query']['from'])) {
		$user = $db->getUser($update['inline_query']['from']);
	} else {
		$user = $db->getUser($update['message']['from']);
	}
	$langs = new Languages($user['lang'], $report);
	require('./commands/inlineCommands.php');
} elseif (isset($update_type)) {
	if (isset($update['message'])) {
		$typechat = $update['message']['chat']['type'];
		$user = $db->getUser($update['message']['from']);
		if ($typechat == 'channel') $chat = $db->getChannel($update['message']['chat']);
		elseif (in_array($typechat, ['group', 'supergroup'])) $chat = $db->getGroup($update['message']['chat']);
	} elseif (isset($update['callback_query'])) {
		$typechat = $update['callback_query']['message']['chat']['type'];
		$user = $db->getUser($update['callback_query']['from']);
		if ($typechat == 'channel') $chat = $db->getChannel($update['callback_query']['message']['chat']);
		elseif (in_array($typechat, ['group', 'supergroup'])) $chat = $db->getGroup($update['callback_query']['message']['chat']);
	} elseif (isset($update['channel_post'])) {
		$typechat = $update['channel_post']['chat']['type'];
		$user = $db->getUser($update['channel_post']['from']);
		if ($typechat == 'channel') $chat = $db->getChannel($update['channel_post']['chat']);
		elseif (in_array($typechat, ['group', 'supergroup'])) $chat = $db->getGroup($update['channel_post']['chat']);
	} else {
		$report->error('Unsupported chat type...', 'plainText');
		die;
	}

	if (in_array($user['id'], $configs['server_admins'])) {
		$isAdmin = true;
	}
	$langs = new Languages($user['lang'], $report);
	if ($typechat == 'private') {
		if ($isAdmin) require('./commands/adminPanel.php');
		require('./commands/private.php');
	} elseif ($update['callback_query']['inline_message_id']) {
	} elseif ($typechat == 'channel') {
		require('./commands/channels.php');
	} elseif (in_array($typechat, ['group', 'supergroup'])) {
		require('./commands/groups.php');
	} else {
		$report->error('Unsupported chat type...', 'plainText');
		die('Unsupported chat type...');
	}
	require('./commands/globalCallback.php');
} else {
	die('Unsupported update type...');
}

?>
