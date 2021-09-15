<?php

if ($update_type == "callback_query") {
	$user_id = $update['callback_query']['from']['id'];
	$chat_id = $update['callback_query']['message']['chat']['id'];
	$callback_data = $update['callback_query']['data'];
	$gcommand = str_replace("adminPanel-", '', $callback_data);
} else {
	$message = $update['message']['text'];
	if (isset($message)) {
		if ($message[0] == "/") {
			$command = substr($message, 1, strlen($message));
			$gcommand = $command;
		}
	}
	$user_id = $update['message']['from']['id'];
	$chat_id = $update['message']['chat']['id'];
}

if ($isAdmin and $typechat == "private") {
	
	if ($command == "checkStatus") {
		$text = $bf->bold("⚙️ Bot Status\n");
		$tables = $db->setupCheck(['users', 'bots', 'groups', 'channels', 'polls', 'choices', 'blacklists', 'crontabs']);
		function exists($t) {
		  if ($t == "Exists") return $t;
		}
		function created($t) {
		  if ($t == "Created") return $t;
		}
		$exists = array_filter($tables, 'exists', ARRAY_FILTER_USE_BOTH);
		$created = array_filter($tables, 'created', ARRAY_FILTER_USE_BOTH);
		$count = (count($created) + count($exists)) / count($tables) * 100;
		$text .= "\nDatabase: " . round($count) . "%";
		$rping = $db->redisPing();
		if ($rping) {
			$text .= "\nRedis: 100%";
		} else {
			$text .= "\nRedis: 0%";
		}
		if (file_exists("./languages.json") or file_exists("./translations.json")) {
			if (file_exists("./languages.json") and file_exists("./translations.json")) {
				if ($rping) {
					$json = json_decode(file_get_contents("./translations.json"), true);
					$db->rdel($db->rkeys("tr-MasterPoll*"));
					foreach ($json as $lang => $strings) {
						foreach($strings as $string => $translation) {
							$db->rset("tr-MasterPoll-{$lang}-{$string}", $translation);
						}
					}
					$text .= "\nLanguages: 100%";
				} else {
					$text .= "\nLanguages: 50%";
				}
			} else {
				$text .= "\nLanguages: 25%";
			}
		} else {
			$text .= "\nLanguages: 0%";
		}
		if (!empty($callback_data)) {
			$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
			$bot->answerCallbackQuery($update['callback_query']['id']);
		} else {
			$bot->sendMessage($chat_id, $text, $menu);
		}
		die;
	} elseif ($gcommand == "database") {
		$tables = ['users', 'groups', 'channels', 'polls', 'choices'];
		foreach ($tables as $table) {
			$result = $db->query("SELECT COUNT(id) FROM $table", false, true);
			$table[0] = strtoupper($table[0]);
			$list .= "\n{$table}: " . number_format($result['count'], 0, ',', '.');
		}
		$menu[] = [
			[
				"text" => "🔄 Update",
				"callback_data" => "adminPanel-database"
			],
			[
				"text" => "👁‍🗨 Activity",
				"callback_data" => "adminPanel-activity"
			]
		];
		$text = $bf->bold("🗂 Database count\n") . $list;
		if (!empty($callback_data)) {
			$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
			$bot->answerCallbackQuery($update['callback_query']['id']);
		} else {
			$bot->sendMessage($chat_id, $text, $menu);
		}
		die;
	} elseif (strpos($gcommand, "activity") === 0) {
		$summary[] = time() - (60 * 60 * 24); // 1 day
		$summary[] = time() - (60 * 60 * 24 * 7); // 7 day
		$summary[] = time() - (60 * 60 * 24 * 30); // 30 day
		$tables = ['users', 'groups', 'channels', 'polls', 'choices'];
		$list = PHP_EOL . '<b>Table: Day | Week | Month</b>';
		foreach ($tables as $table) {
			if ($table !== "choices") {
				$last_update = "last_update";
			} else {
				$last_update = "choice_date";
			}
			$result[] = $db->query("SELECT COUNT(id) FROM $table WHERE $last_update > ?", [round($summary[0])], true);
			$result[] = $db->query("SELECT COUNT(id) FROM $table WHERE $last_update > ?", [round($summary[1])], true);
			$result[] = $db->query("SELECT COUNT(id) FROM $table WHERE $last_update > ?", [round($summary[2])], true);
			$table[0] = strtoupper($table[0]);
			$list .= "\n{$table}: " . number_format($result[0]['count'], 0, ',', '.') . ' | ' .
			number_format($result[1]['count'], 0, ',', '.') . ' | ' .
			number_format($result[2]['count'], 0, ',', '.');
			unset($result);
		}
		$menu[] = [
			[
				"text" => "🔄 Update",
				"callback_data" => "adminPanel-activity"
			],
			[
				"text" => "🗂 Database",
				"callback_data" => "adminPanel-database"
			]
		];
		$text = $bf->bold("👁‍🗨 Database Activity \n") . $list;
		if (!empty($callback_data)) {
			$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
			$bot->answerCallbackQuery($update['callback_query']['id']);
		} else {
			$bot->sendMessage($chat_id, $text, $menu);
		}
		die;
	} elseif ($command == "language_reload") {
		$db->rdel($db->rkeys("tr-MasterPoll*"));
		$bot->sendMessage($user_id, "Done");
		die;
	} elseif ($command == "languages_update") {
		$newjson = $langs->getTranslations();
		if ($newjson['ok']) {
			$newjson = $newjson['result'];
			foreach ($newjson as $lang => $strings) {
				ksort($strings);
				$okjson[explode("-", $lang)[0]] = $strings;
			}
			ksort($okjson);
			$bot->sendDocument($user_id, "./translations.json");
			file_put_contents("./translations.json", json_encode($okjson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
			$db->rdel($db->rkeys("tr-MasterPoll*"));
			$bot->sendMessage($user_id, $bf->bold("💾 Translations file updated!"));
		} else {
			$bot->sendMessage($user_id, $bf->bold("❌ Translations file not updated...\n") . $newjson['result']['response']);
		}
		die;
	}
}

?>
