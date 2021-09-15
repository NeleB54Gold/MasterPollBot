<?php

$content = file_get_contents('php://input');
if (!$content) {
	http_response_code(400);
	die;
} else {
	$update = json_decode($content, true);
}

if ($update['message']) {
	if ($update['message']['chat']['type'] !== 'private') die;
	header('Content-Type: application/json');
	echo json_encode([
		'method'		=> 'sendMessage',
		'chat_id'		=> $update['message']['chat']['id'],
		'text'			=> '🔴 See @MasterPoll for updates, our services are offline...'
	], JSON_PRETTY_PRINT);
	http_response_code(200);
} elseif ($update['callback_query']) {
	header('Content-Type: application/json');
	echo json_encode([
		'method'	=> 'answerCallbackQuery',
		'callback_query_id' => $update['callback_query']['id'],
		'text' => '🔴 See @MasterPoll for updates, our services are offline...',
		'show_alert' => true,
	], JSON_PRETTY_PRINT);
	http_response_code(200);
} elseif ($update['inline_query']) {
	$json = [];
	header('Content-Type: application/json');
	echo json_encode([
		'method'	=> 'answerInlineQuery',
		'inline_query_id' => $update['inline_query']['id'],
		'results' => json_encode($json),
		'cache_time' => 5,
		'switch_pm_text' => '🔴 See @MasterPoll for updates, our services are offline...',
		'switch_pm_parameter' => 'start'
	], JSON_PRETTY_PRINT);
	http_response_code(200);
} elseif ($update['chosen_inline_result']) {
	http_response_code(200);
}

?>