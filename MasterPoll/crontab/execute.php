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

# PHP Error logging
set_error_handler('errorHandler');
register_shutdown_function('shutdownHandler');
function errorHandler ($error_level, $error_message, $error_file, $error_line, $error_context) {
	global $bf;
	global $bot;
	global $configs;
	
	$error = $bf->textspecialchars($error_message) . PHP_EOL . $bf->bold('String: ') . $error_line . PHP_EOL . $bf->bold('File: ') . $error_file;
	switch ($error_level) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_PARSE:
			if ($configs['log_report']['FATAL']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			if ($configs['log_report']['ERROR']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_WARNING:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_USER_WARNING:
			if ($configs['log_report']['WARN']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
			if ($configs['log_report']['INFO']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_STRICT:
			if ($configs['log_report']['DEBUG']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		default:
			if ($configs['log_report']['WARN']) {
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
	}
}
function shutdownHandler () {
	global $bf;
	global $bot;
	global $configs;
	
	$lasterror = error_get_last();
	switch ($lasterror['type']) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_PARSE:
			if ($configs['log_report']['SHUTDOWN']) {
				$error = $bf->textspecialchars($lasterror['message']) . PHP_EOL . $bf->bold('String: ') . $lasterror['line'] . PHP_EOL . $bf->bold('File: ') . $lasterror['file'];
				if(file_exists('./errorPayload.php')) {
					require('./errorPayload.php');
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
	}
}

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