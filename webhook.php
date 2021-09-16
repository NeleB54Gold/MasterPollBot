<?php 

# Close webhook request without the right parameters
if (!isset($_GET['token']) or !isset($_GET['password'])) die(403);

# Set error reporting level and log file
ini_set('display_errors', 0);
ini_set('error_reporting', !E_ALL | E_PARSE | E_WARNING | E_ERROR);
# ini_set('error_log', '/home/NeleBot/MasterPoll/error.log');
ini_set('error_log', null);
chdir('/home/NeleBot/MasterPoll');

# Require the config file for all Bot functions
# Warning: Do not touch this file if you don't know what are you doing!
require_once('./basicFunctions.php');
require_once('./configs.php');

# Require the main functions
$bf = new basicFunctions($configs);
$bot = new TelegramFunctions($_GET['token']);
$report = new ErrorReporting($bf, $bot);

if ($_GET['password'] == $configs['password']) {
	$content = file_get_contents('php://input');
	if ($content) {
		$update = json_decode($content, true);
		try {
			if ($configs['online']) {
				$db = new MasterPollDatabase($configs, $report, $bot);
				fastcgi_finish_request();
				require('/home/NeleBot/MasterPoll/bot.php');
			} else {
				require('/home/NeleBot/MasterPoll/offStatus.php');
			}
		} catch (Exception $e) {
			file_put_contents('error.json', $e->getMessage());
		}
		http_response_code(200);
	} else {
		http_response_code(403);
	}
} else {
	http_response_code(403);
}

?>
