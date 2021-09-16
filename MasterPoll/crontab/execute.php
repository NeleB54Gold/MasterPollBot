<?php

# Set error reporting level and log file
ini_set('display_errors', 0);
ini_set('error_reporting', E_ALL | E_PARSE | E_WARNING | E_ERROR);
ini_set('error_log', '/home/NeleBot/MasterPoll/error.log');

# Require the main functions
chdir('/home/NeleBot/MasterPoll');
require_once('./basicFunctions.php');
require_once('./Languages.php');
require_once('./masterpoll.php');
$bf = new basicFunctions();
$bot = new TelegramFunctions('YOUR_TOKEN'); // Bot Token (create more cron exec file for more bots)

# Require the config file for all Bot functions
# Warning: Do not touch this file if you don't know what are you doing!
require('./configs.php');
$reporting = new ErrorReporting($configs, $bot);

# General settings
date_default_timezone_set('UTC');
$db = new MasterPollDatabase($configs, $report, $bot);
$langs = new Languages('en', $report);
$mp = new MasterPoll($bf);

# Getting all crontabs to exec
$crontabs = $db->query('SELECT * FROM crontabs WHERE bot_id = ? and time < ? ORDER BY type ASC', [$bot->getID(), time()], 'fetch');
$db->query('DELETE FROM crontabs WHERE bot_id = ? and time < ?', [$bot->getID(), time()], false);
foreach ($crontabs as $cron) {
	if ($cron['type'] == 1) {		// Schedule open poll
		$mp->reopenPoll($cron['poll_id']);
	} elseif ($cron['type'] == 2) {	// Schedule close poll
		$mp->closePoll($cron['poll_id']);
	} elseif ($cron['type'] == 3) {	// Schedule send message
		$poll = $mp->getPoll($cron['poll_id'], ['*', 'votes']);
		$message = $mp->createPollMessage($poll);
		$m = $bot->sendMessage($cron['chat_id'], $message['text'], $message['reply_markup'], 'def', $message['disable_web_page_preview']);
		if ($m['ok']) {
			$mp->addPollMessage($poll['id'], $m['result']['message_id'], $cron['chat_id'], ['in_chat']);
		}
	}
	$updatePoll[$cron['poll_id']] = true;
}

# Update of crontabs polls
if (isset($updatePoll) and !empty($updatePoll)) {
	foreach ($updatePoll as $poll_id => $ok) {
		$mp->updatePollMessages($mp->getPoll($poll_id, ['*', 'votes']));
	}
}

?>
