<?php

# Require other files
require_once('./telegramFunctions.php');
require_once('./databaseConnection.php');

# PHP Error logging
set_error_handler('errorHandler');
register_shutdown_function('shutdownHandler');

function errorHandler($error_level, $error_message, $error_file, $error_line, $error_context) {
	global $bf;
	global $bot;
	global $configs;
	
	$error = $bf->textspecialchars($error_message) . $bf->bold('\nString: ') . $error_line . $bf->bold('\nFile: ') . $error_file;
	$payload = '/home/NeleBot/MasterPoll/errorPayload.php';
	switch ($error_level) {
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_PARSE:
			if ($configs['log_report']['FATAL']) {
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			if ($configs['log_report']['ERROR']) {
				if(file_exists($payload)) {
					require($payload);
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
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_NOTICE:
		case E_USER_NOTICE:
			if ($configs['log_report']['INFO']) {
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		case E_STRICT:
			if ($configs['log_report']['DEBUG']) {
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
			break;
		default:
			if ($configs['log_report']['WARN']) {
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
	}
}

function shutdownHandler() {
	global $bf;
	global $bot;
	global $configs;
	
	$lasterror = error_get_last();
	$payload = '/home/NeleBot/MasterPoll/errorPayload.php';
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
				$error = $bf->textspecialchars($lasterror['message']) . $bf->bold('\nString: ') . $lasterror['line'] . $bf->bold('\nFile: ') . $lasterror['file'];
				if(file_exists($payload)) {
					require($payload);
				} else {
					$bot->sendMessage($configs['console_id'], $error);
				}
			}
	}
}

class basicFunctions 
{
	private $configs = [];
	
	public function __construct ($configs = []) {
		$this->configs = $configs;
	}
	
	# Do automatic bold text with parse_mode
	public function bold ($text, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (in_array($parse_mode, ['html', 'markdown', 'markdownv2'])) {
			if (strtolower($parse_mode) == 'html') {
				return '<b>' . htmlspecialchars($text) . '</b>';
			} elseif (strtolower($parse_mode) == 'markdown') {
				return '*' . $this->mdspecialchars($text) . '*';
			} elseif (strtolower($parse_mode) == 'markdownv2') {
				return '*' . $this->md2specialchars($text) . '*';
			} else {
				return $text;
			}
		} else {
			return $text;
		}
	}
	
	# Do automatic italic text with parse_mode
	public function italic ($text, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (in_array($parse_mode, ['html', 'markdown', 'markdownv2'])) {
			if (strtolower($parse_mode) == 'html') {
				return '<i>' . htmlspecialchars($text) . '</i>';
			} elseif (strtolower($parse_mode) == 'markdown') {
				return '_' . $this->mdspecialchars($text) . '_';
			} elseif (strtolower($parse_mode) == 'markdownv2') {
				return '_' . $this->md2specialchars($text) . '_';
			} else {
				return $text;
			}
		} else {
			return $text;
		}
	}
	
	# Do automatic code text with parse_mode
	public function code ($text, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (in_array($parse_mode, ['html', 'markdown', 'markdownv2'])) {
			if (strtolower($parse_mode) == 'html') {
				return '<code>' . htmlspecialchars($text) . '</code>';
			} elseif (strtolower($parse_mode) == 'markdown') {
				return '`' . $this->mdspecialchars($text) . '`';
			} elseif (strtolower($parse_mode) == 'markdownv2') {
				return '`' . $this->md2specialchars($text) . '`';
			} else {
				return $text;
			}
		} else {
			return $text;
		}
	}
	
	# Do automatic code text with parse_mode
	public function prefix ($text, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (in_array($parse_mode, ['html', 'markdown', 'markdownv2'])) {
			if (strtolower($parse_mode) == 'html') {
				return '<pre>' . htmlspecialchars($text) . '</pre>';
			} elseif (strtolower($parse_mode) == 'markdown') {
				return '```' . $this->mdspecialchars($text) . '```';
			} elseif (strtolower($parse_mode) == 'markdownv2') {
				return '```' . $this->md2specialchars($text) . '```';
			} else {
				return $text;
			}
		} else {
			return $text;
		}
	}
	
	# Do automatic text link with parse_mode
	public function text_link ($text, $link, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (in_array($parse_mode, ['html', 'markdown', 'markdownv2'])) {
			if (strtolower($parse_mode) == 'html') {
				return '<a href="' . $link . '">' . htmlspecialchars($text) . '</a>';
			} elseif (strtolower($parse_mode) == 'markdown') {
				return '[' . $this->mdspecialchars($text) . '](' . $link . ')';
			} elseif (strtolower($parse_mode) == 'markdownv2') {
				return '[' . $this->md2specialchars($text) . '](' . $link . ')';
			} else {
				return $text;
			}
		} else {
			return $text;
		}
	}
	
	# Do automatic text link with parse_mode
	public function tag ($id, $name, $surname = false, $parse_mode = 'def') {
		if (!empty($surname)) $name .= ' ' . $surname;
		return $this->text_link($name, 'tg://user?id=' . $id, $parse_mode);
	}
	
	# Markdown specialchars (like htmlspecialchars)
	public function mdspecialchars ($text) {
		$text = str_replace('_', '\_', $text);
		$text = str_replace('*', '\*', $text);
		$text = str_replace('`', '\`', $text);
		return str_replace('[', '\[', $text);
	}

	# MarkdownV2 specialchars
	public function md2specialchars ($text) {
		$text = str_replace('_', '\_', $text);
		$text = str_replace('*', '\*', $text);
		$text = str_replace('`', '\`', $text);
		$text =  str_replace('[', '\[', $text);
		$text =  str_replace(']', '\]', $text);
		$text =  str_replace('(', '\(', $text);
		$text =  str_replace(')', '\)', $text);
		$text =  str_replace('~', '\~', $text);
		$text =  str_replace('!', '\!', $text);
		$text =  str_replace('-', '\-', $text);
		$text =  str_replace('.', '\.', $text);
		return str_replace('=', '\=', $text);
	}
	
	# Like htmlspecialchars for both parse_mode
	public function textspecialchars ($text, $parse_mode = 'def') {
		if ($parse_mode === 'def') $parse_mode = $configs['parse_mode'];
		$parse_mode = strtolower($parse_mode);
		if (strtolower($parse_mode) == 'html') {
			return htmlspecialchars($text);
		} elseif (strtolower($parse_mode) == 'markdown') {
			return $this->mdspecialchars($text);
		} elseif (strtolower($parse_mode) == 'markdownv2') {
			return $this->md2specialchars($text);
		} else {
			return $text;
		}
	}
}

class Configs
{
	public $configs = [
		'online'		=> true,
		'parse_mode'	=> '',
		'password'		=> 0,
		'server_admins'	=> [],
		'database'		=> [
			'host'			=> "localhost",
			'user'			=> "",
			'name'			=> "",
			'password'		=> ""
		],
		'redis'		=> [
			'host'			=> "localhost",
			'port'			=> 6379,
			'password'		=> false,
			'database'		=> 1
		],
		'console_id'	=> 0,
		'log_report'	=> [
			'SHUTDOWN'		=> true,
			'FATAL' 		=> true,
			'ERROR'			=> false,
			'WARN'			=> false,
			'INFO'			=> false,
			'DEBUG'			=> false
		],
		'legal'			=> '',
		'limits'		=> [
			'pollTitle'			=> 256,
			'pollDescription'	=> 256,
			'pollChoices'		=> 25,
			'boardComments'		=> 120,
			'voteChoice'		=> 64
		],
		'oneskyapp'		=> [
			'api-key'		=> "",
			'secret'		=> "",
			'platform-id'	=> 173195,
			'file_name'		=> 'translations.json'
		]
	];
	
	function __construct ($configurations = []) {
		# Do not touch this configurations (go to config.php)
		if (is_array($configurations) and !empty($configurations)) {
			foreach($configurations as $key => $value) {
				if (in_array($key, array_keys($this->configs))) {
					$this->configs[$key] = $value;
				}
			}
		}
		return $this->configs;
	}
	
	public function getConfig () {
		return $this->configs;
	}
	
	public function setConfig ($configurations = []) {
		if (!is_array($configurations) or empty($configurations)) return $this->configs;
		foreach($configurations as $key => $value) {
			if (in_array($key, array_keys($this->configs))) {
				$this->configs[$key] = $value;
			}
		}
		return $this->configs;
	}
}

class ErrorReporting
{
	function __construct ($bf, $bot) {
		$this->bf = $bf;
		$this->configs = $bf->configs;
		$this->bot = $bot;
	}
	
	public function error ($error, $type = 'plainText') {
		if (!$this->configs['console_id']) return false;
		if ($type == 'phpError') {
			$error_message = $this->bf->textspecialchars($error['message']) . $this->bf->bold('\nString: ') . $error['line'] . $this->bf->bold('\nFile: ') . $error['file'];
		} elseif ($type == 'PDOException') {
			$error_message = 'PDOException: ' . $this->bf->code($error->getMessage());
		} elseif ($type == 'PDOError') {
			$error_message = 'Error {$error[0]}: ' . $this->bf->code($error[2]);
		} elseif ($type == 'Redis') {
			$error_message = json_encode($error);
		} elseif ($type == 'Languages') {
			$error_message = $error;
		} elseif ($type == 'plainText') {
			$error_message = $error;
		} else {
			$error_message = 'Unknown error report type: ' . json_encode($error);
		}
		$bot->sendMessage($this->configs['console_id'],  date('[c]') . '[MasterPoll]\n' . $error_message);
	}
}

?>
