<?php

require("./masterpoll.php");
$mp = new MasterPoll($bf);

if (isset($user) and time() >= ($user['last_update'] + (60 * 60))) {
	$user['last_update'] = time();
	if (isset($update['callback_query']['from'])) {
		$us = $update['callback_query']['from'];
	} elseif (isset($update['message']['from'])) {
		$us = $update['callback_query']['from'];
	}
	if (isset($us)) {
		$user['name'] = $us['first_name'];
		$user['surname'] = $us['last_name'];
		$user['username'] = $us['username'];
	}
	$db->query("UPDATE users SET name = ?, surname = ?, username = ?, last_update = ? WHERE id = ?", [$user['name'], $user['surname'], $user['username'], $user['last_update'], $user['id']], false);
}

function createTimeTemplate ($callback_data, $type = 0, $ctype = false) {
	global $db;
	global $langs;
	global $user;
	
	$menu = [];
	$time = $db->rget("MP-timeaction-{$user['id']}");
	if ($time <= time()) {
		$time = time() + 60;
	}
	$datetime = new DateTime('now', (new DateTimeZone($user['settings']['timezone'])));
	$datetime->setTimestamp($time);
	$timee = explode(", ", $user['settings']['date_format']);
	if (isset($timee[1])) {
		$form = $timee[1];
	} else {
		$form = $timee[0];
	}
	if ($type == 1) {
		if ($ctype == 1) {
			$menus = range(date("Y"), date("Y") + 5);
			$mcount = 0;
			$formenu = 3;
			foreach ($menus as $value) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = [
					'text' => round($value),
					"callback_data" => "{$callback_data}_1_1_{$value}"
				];
			}
		} elseif ($ctype == 2) {
			$menus = range(1, 12);
			$mcount = 0;
			$formenu = 6;
			foreach ($menus as $value) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = [
					'text' => round($value),
					"callback_data" => "{$callback_data}_1_2_{$value}"
				];
			}
		} elseif ($ctype == 3) {
			$menus = range(1, 31);
			$mcount = 0;
			$formenu = 5;
			foreach ($menus as $value) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = [
					'text' => round($value),
					"callback_data" => "{$callback_data}_1_3_{$value}"
				];
			}
		}
		$menu[] = [
			[
				"text" => $langs->getTranslate('yourDateTime'),
				"callback_data" => "{$callback_data}_0"
			]
		];
		$menu[] = [
			[
				"text" => date_format($datetime, "Y"),
				"callback_data" => "{$callback_data}_1_1"
			],
			[
				"text" => date_format($datetime, "m"),
				"callback_data" => "{$callback_data}_1_2"
			],
			[
				"text" => date_format($datetime, "d"),
				"callback_data" => "{$callback_data}_1_3"
			]
		];
	} elseif ($type == 2) {
		if ($ctype == 1) {
			$menus = range(0, 23);
			$mcount = 0;
			$formenu = 4;
			foreach ($menus as $value) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = [
					'text' => round($value),
					"callback_data" => "{$callback_data}_2_1_{$value}"
				];
			}
		} elseif ($ctype == 2) {
			$menus = range(0, 59);
			$mcount = 0;
			$formenu = 6;
			foreach ($menus as $value) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = [
					'text' => round($value),
					"callback_data" => "{$callback_data}_2_2_{$value}"
				];
			}
		}
		$menu[] = [
			[
				"text" => $langs->getTranslate('yourDateTime'),
				"callback_data" => "{$callback_data}_0"
			]
		];
		$menu[] = [
			[
				"text" => date_format($datetime, "H"),
				"callback_data" => "{$callback_data}_2_1"
			],
			[
				"text" => date_format($datetime, "i"),
				"callback_data" => "{$callback_data}_2_2"
			]
		];
	} else {
		$menu[] = [
			[
				"text" => date_format($datetime, explode(" ", $form)[0]),
				"callback_data" => "{$callback_data}_1"
			],
			[
				"text" => date_format($datetime, end(explode(" ", $user['settings']['date_format']))),
				"callback_data" => "{$callback_data}_2"
			]
		];
	}
	return $menu;
}

if ($update_type == "callback_query") {
	$user_id = $update['callback_query']['from']['id'];
	$chat_id = $update['callback_query']['message']['chat']['id'];
	$gcommand = $callback_data = $update['callback_query']['data'];
} else {
	$message = $update['message']['text'];
	if (isset($message)) {
		if ($message[0] == "/") {
			$gcommand = $command = substr($message, 1, strlen($message));
		}
	}
	$user_id = $update['message']['from']['id'];
	$chat_id = $update['message']['chat']['id'];
}

# Localization
if (isset($update['message']['location'])) {
	$tzs = DateTimeZone::listIdentifiers();
	foreach ($tzs as $tzid) {
		$timezone = new DateTimeZone($tzid);
		$location = $timezone->getLocation();
		$tz_lat   = $location['latitude'];
		$tz_long  = $location['longitude'];
		$theta = $update['message']['location']['longitude'] - $tz_long;
		$distance = (sin(deg2rad($update['message']['location']['latitude'])) * sin(deg2rad($tz_lat))) + (cos(deg2rad($update['message']['location']['latitude'])) * cos(deg2rad($tz_lat)) * cos(deg2rad($theta)));
		$distance = acos($distance);
		$distance = abs(rad2deg($distance));
		if (!$time_zone or $tz_distance > $distance) {
			$time_zone_name  = $tzid;
			$time_zone = $timezone;
			$tz_distance = $distance;
		}
	}
	$datetime = new DateTime();
	date_timezone_set($datetime, $time_zone);
	$user['settings']['timezone'] = $time_zone_name;
	$db->query("UPDATE users SET settings = ? WHERE id = ?", [json_encode($user['settings']), $user['id']], false);
	$bot->sendMessage($chat_id, $langs->getTranslate('timezoneSet', [$time_zone_name, date_format($datetime, $user['settings']['date_format'])]), ['hide'], 'def', false, false, 'hide');
	die;
}

# All global commands
if (isset($gcommand)) {
	if (in_array($gcommand, ["start", "start start"])) {
		$db->rdel("{$user_id}-MP-action");
		$last_poll = "";
		if ($id = $mp->getLastPollID($user['id'])) {
			$last_poll = $mp->lastPollMessage($user['id']);
		}
		$menu[] = [
			[
				'text' => $langs->getTranslate('createNewPollButton'),
				'callback_data' => "createPoll"
			]
		];
		$menu[] = [
			[
				'text' => $langs->getTranslate('settingsButton'),
				'callback_data' => "settings"
			],
			[
				'text' => $langs->getTranslate('pollListButton'),
				'callback_data' => "myPolls"
			]
		];
		$text = $langs->getTranslate('startMessage', [$last_poll]);
		if (!empty($callback_data)) {
			$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
			$bot->answerCallbackQuery($update['callback_query']['id']);
		} else {
			$bot->sendMessage($chat_id, $text, $menu);
		}
		die;
	} elseif (strpos($gcommand, 'cancel') === 0) {
		if ($db->rget("{$user_id}-MP-action")) {
			$db->rdel("{$user_id}-MP-action");
			$text = $langs->getTranslate('canceledThat');
		} else {
			$text = $langs->getTranslate('nothingToCancel');
		}
		if (isset($callback_data) and strpos($gcommand, 'cancel-') === 0) {
			$callback_data = str_replace('cancel-', '', $gcommand);
		} else {
			if ($command == 'cancel') {
				$bot->sendMessage($chat_id, $text, ['hide'], 'def', false, false, 'hide');
			}
			die;
		}
	}
}

# All callback-only answers
if (isset($callback_data)) {
	if (strpos($callback_data, "createPoll") === 0) {
		$cbtext = false;
		$db->rset("{$user_id}-MP-action", 'createpoll');
		if ($db->rget("{$user_id}-anon")) {
			$emo = [
				'anonymous' => "✅",
				'personal' => "☑️"
			];
			$pollAnonymous = true;
		} else {
			$emo = [
				'anonymous' => "☑️",
				'personal' => "✅"
			];
			$pollAnonymous = false;
		}
		$pollType = $db->rget("{$user_id}-ptype");
		$db->rdel("{$user_id}-ptitle");
		$db->rdel("{$user_id}-pdesc");
		$db->rdel("{$user_id}-pchoices");
		$db->rdel("{$user_id}-pchoicesDone");
		if (!is_numeric($pollType)) {
			$db->rset("{$user_id}-ptype", 0);
			$pollType = 0;
		}
		if (strpos($callback_data, "createPoll-") === 0) {
			$ex = explode("-", $callback_data);
			$setting = $ex[1];
		}
		if ($setting == "anonymous") {
			if ($ex[2]) {
				$emo = [
					'anonymous' => "✅",
					'personal' => "☑️"
				];
				$pollAnonymous = true;
				$db->rset("{$user_id}-anon", true);
			} else {
				$emo = [
					'anonymous' => "☑️",
					'personal' => "✅"
				];
				$pollAnonymous = false;
				$db->rset("{$user_id}-anon", false);
			}
			$types = $mp->getTypes();
			$ptype_name = $types[$pollType];
			$ptype_name[0] = strtolower($ptype_name[0]);
			$ptype_name = str_replace(" ", '', $ptype_name);
			$ptname = $langs->getTranslate($ptype_name);
			$ptname[0] = strtoupper($ptname[0]);
			$settings = $bf->bold("  ℹ️ " . $langs->getTranslate('pollType') . ":") . " {$ptname}";
			$settings .= $bf->bold("\n  👁‍🗨 " . $langs->getTranslate('pollAnonymous') . ":") . " {$emo['anonymous']}";
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('changePollTypeButton'),
					'callback_data'	=> "createPoll-type"
				]
			];
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('personalButton', [$emo['personal']]),
					'callback_data'	=> "createPoll-anonymous-0"
				],
				[
					'text'			=> $langs->getTranslate('anonymousButton', [$emo['anonymous']]),
					'callback_data'	=> "createPoll-anonymous-1"
				]
			];
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('mainMenuButton'),
					'callback_data'	=> "start"
				]
			];
			$text = $langs->getTranslate('createNewPoll', [$settings]);
		} elseif ($setting == "type") {
			$types = $mp->getTypes();
			if (isset($ex[2])) {
				if (isset($types[$ex[2]])) {
					$pollType = $ex[2];
					$db->rset("{$user_id}-ptype", $ex[2]);
				} else {
					$cbtext = "Type of poll not found...";
				}
			}
			$text = $langs->getTranslate('pollTypeSelection') . "\n";
			foreach ($types as $type_id => $type_name) {
				if ($type_id == $db->rget("{$user_id}-ptype")) {
					$emot = "✅";
				} else {
					$emot = "☑️";
				}
				$type_name[0] = strtolower($type_name[0]);
				$type_name = str_replace(" ", '', $type_name);
				$name = $langs->getTranslate($type_name) . " $emot";
				$name[0] = strtoupper($name[0]);
				$type_name[0] = strtoupper($type_name[0]);
				$menus[] = [
					'text' => $name,
					'callback_data' => "createPoll-type-$type_id"
				];
				$text .= PHP_EOL . $bf->bold($name) . " - " . $bf->italic($langs->getTranslate('pollType' . $type_name . 'Description')) . "\n";
			}
			if (count($menus) <= 4) {
				$formenu = 1;
			} else {
				$formenu = 2;
			}
			$mcount = 0;
			foreach ($menus as $mmenu) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = $mmenu;
			}
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('backButton'),
					'callback_data'	=> "createPoll"
				]
			];
		} else {
			$types = $mp->getTypes();
			$ptype_name = $types[$pollType];
			$ptype_name[0] = strtolower($ptype_name[0]);
			$ptype_name = str_replace(" ", '', $ptype_name);
			$ptname = $langs->getTranslate($ptype_name);
			$ptname[0] = strtoupper($ptname[0]);
			$settings = $bf->bold("  ℹ️ " . $langs->getTranslate('pollType') . ":") . " {$ptname}";
			$settings .= $bf->bold("\n  👁‍🗨 " . $langs->getTranslate('pollAnonymous') . ":") . " {$emo['anonymous']}";
			$text = $langs->getTranslate('createNewPoll', [$settings]);
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('changePollTypeButton'),
					'callback_data'	=> "createPoll-type"
				]
			];
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('personalButton', [$emo['personal']]),
					'callback_data'	=> "createPoll-anonymous-0"
				],
				[
					'text'			=> $langs->getTranslate('anonymousButton', [$emo['anonymous']]),
					'callback_data'	=> "createPoll-anonymous-1"
				]
			];
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('mainMenuButton'),
					'callback_data'	=> "start"
				]
			];
		}
		$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		die;
	} elseif (strpos($callback_data, "settings") === 0) {
		$cbtext = false;
		if (strpos($callback_data, "settings-") === 0) {
			$ex = explode("-", $callback_data);
			$setting = $ex[1];
		}
		if ($setting == "changeTimezone") {
			$datetime = new DateTime();
			date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
			$text = $bf->bold($langs->getTranslate('changeTimeZoneButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionChangeTimeZone')) . 
			PHP_EOL . date_format($datetime, $user['settings']['date_format']) . " - " . $bf->code($user['settings']['timezone']);
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('changeTimeZoneButton'),
					'callback_data'	=> "setTimeZone"
				],
				[
					'text'			=> $langs->getTranslate('changeDateFormatButton'),
					'callback_data'	=> "setDateFormat"
				]
			];
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('backButton'),
					'callback_data'	=> "settings"
				]
			];
		} elseif ($setting == "changeLanguage") {
			$languages = json_decode(file_get_contents("./languages.json"), true);
			if (isset($ex[2])) {
				if (isset($languages[$ex[2]])) {
					$user['lang'] = $ex[2];
					$db->query("UPDATE users SET lang = ? WHERE id = ?", [$ex[2], $user_id], false);
					$langs = new Languages($user['lang']);
				} else {
					$cbtext = "Language not found...";
				}
			}
			$text = $bf->bold($langs->getTranslate('changeLanguageButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionChangeLanguage')) .
			"\n→ ✅ " . $languages[$user['lang']]['ename'];
			foreach ($languages as $lang_code => $language) {
				if ($language['flag']) {
					$language['ename'] = "{$language['flag']} {$language['ename']}";
				}
				if ($lang_code == $user['lang']) {
					$language['ename'] = "{$language['flag']} {$language['name']} ✅";
				}
				$menus[] = [
					'text' => $language['ename'],
					'callback_data' => "settings-changeLanguage-$lang_code"
				];
			}
			if (count($menus) <= 6) {
				$formenu = 1;
			} else {
				$formenu = 2;
			}
			$mcount = 0;
			foreach ($menus as $mmenu) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = $mmenu;
			}
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('backButton'),
					'callback_data'	=> "settings"
				]
			];
		} elseif ($setting == "manageBlacklist") {
			$text = $bf->bold($langs->getTranslate('blacklistButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionBannedUsers')) . "\n";
			$bl = $db->query("SELECT users FROM blacklists WHERE user_id = ? and poll_id = ? LIMIT 1", [$user['id'], 0]);
			if (!empty($bl)) {
				foreach ($bl as $user => $time) {
					if ($time !== 0 or $time < time()) {
						unset($bl[$user]);
					}
				}
			}
			if (!empty($bl['users'])) {
				$bl['users'] = json_decode($bl['users'], true);
				$datetime = new DateTime('now', (new DateTimeZone($user['settings']['timezone'])));
				foreach ($bl['users'] as $banus => $bantime) {
					$banned = $db->query("SELECT id, name, surname FROM users WHERE id = ?", [$banus], true);
					if ($bantime) {
						$bantime = date($user['settings']['date_format'], $bantime);
					} else {
						$bantime = "♾";
					}
					$text .= PHP_EOL . $bf->tag($banned['id'], $banned['name'], $banned['surname']) . ": " . $bantime;
				}
			}
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('blacklistAddButton'),
					'callback_data'	=> "bl-add"
				]
			];
			if (!empty($bl['users'])) {
				$menu[count($menu) - 1][] = [
					'text'			=> $langs->getTranslate('blacklistRemoveButton'),
					'callback_data'	=> "bl-remove"
				];
				$menu[] = [
					[
						'text'			=> $langs->getTranslate('blacklistRemoveAllButton'),
						'callback_data'	=> "bl-removeAll"
					]
				];
			}
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('backButton'),
					'callback_data'	=> "settings"
				]
			];
		} else {
			if ($setting == 'changeTheme') {
				if (!isset($user['settings']['theme'])) {
					if (isset($user['settings']['theme']) and $user['settings']['theme'] == "light") {
						$user['settings']['theme'] = "dark";
					} else {
						$user['settings']['theme'] = "light";
					}
					$db->query("UPDATE users SET settings = settings::jsonb || ?::jsonb WHERE id = ?", ['{"theme":"' . $user['settings']['theme'] . '"}', $user_id]);
				} else {
					if (isset($user['settings']['theme']) and $user['settings']['theme'] == "light") {
						$user['settings']['theme'] = "dark";
					} else {
						$user['settings']['theme'] = "light";
					}
					$db->query("UPDATE users SET settings = settings::jsonb || jsonb_set(
					settings::jsonb, 
					'{theme}'::text[], 
					'\"{$user['settings']['theme']}\"'::jsonb
					)::jsonb WHERE id = ?", [$user_id]);
				}
			}
			if ($user['settings']['theme'] == "light") {
				$theme['emoji'] = "☀️";
			} else {
				$theme['emoji'] = "🌑";
			}
			$text = $bf->bold($langs->getTranslate('settings')) . 
			"\n\n" . $bf->bold($langs->getTranslate('changeTimeZoneButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionChangeTimeZone')) . 
			"\n\n" . $bf->bold($langs->getTranslate('changeLanguageButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionChangeLanguage')) . 
			"\n\n" . $bf->bold($langs->getTranslate('blacklistButton')) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionBannedUsers')) . 
			"\n\n" . $bf->bold($langs->getTranslate('changeThemeButton', [$theme['emoji']])) . 
			PHP_EOL . $bf->italic($langs->getTranslate('settingsDescriptionChangeTheme'));
			$menus = [
				[
					'text'			=> $langs->getTranslate('changeTimeZoneButton'),
					'callback_data'	=> "settings-changeTimezone"
				],
				[
					'text'			=> $langs->getTranslate('changeLanguageButton'),
					'callback_data'	=> "settings-changeLanguage"
				],
				[
					'text'			=> $langs->getTranslate('blacklistButton'),
					'callback_data'	=> "settings-manageBlacklist"
				],
				[
					'text'			=> $langs->getTranslate('changeThemeButton', [$theme['emoji']]),
					'callback_data'	=> "settings-changeTheme"
				]
			];
			if (count($menus) <= 3) {
				$formenu = 1;
			} elseif (count($menus) > 3 and count($menus) <= 6) {
				$formenu = 2;
			} else {
				$formenu = 3;
			}
			$mcount = 0;
			foreach ($menus as $mmenu) {
				if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
					$mcount += 1;
				}
				$menu[$mcount][] = $mmenu;
			}
			$menu[] = [
				[
					'text'			=> $langs->getTranslate('mainMenuButton'),
					'callback_data'	=> "start"
				]
			];
		}
		$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		die;
	} elseif (strpos($callback_data, "myPolls") === 0) {
		$cbtext = false;
		if (strpos($callback_data, "myPolls-") === 0) {
			$e = explode("-", $callback_data);
			$page = $e[1];
			if ($page >= 0) {
				$prevpage = $page - 1;
			}
			if ($e[2]) {
				$menusetting = true;
			} else {
				$menusetting = false;
			}
		} else {
			$page = 1;
		}
		$text = $bf->bold($langs->getTranslate('listYourPolls')) . "\n";
		$polls = $mp->getPollsList($user_id, (($page * 5) - 5), 6);
		if (!empty($polls)) {
			if (count($polls) == 6) {
				$nextpage = $page + 1;
				unset($polls[end(array_keys($polls))]);
			}
			$emojiNum = "️⃣";
			$types = $mp->getTypes();
			foreach ($polls as $poll) {
				$num += 1;
				$number = $num . $emojiNum;
				$t_type = $mp->getAnonymous($poll['anonymous']) . $types[$poll['type']];
				$text .= "\n$number " . $bf->bold($poll['title']) . "\n  " .
				$bf->italic($langs->getTranslate('pollType') . ":") . " " . $langs->getTranslate('inlineDescription' . $t_type) . 
				"\n  " . $bf->italic("ID:") . " /{$poll['poll_id']}";
				if ($menusetting) {
					if (isset($menu[0])) {
						$menunum = (count($menu) + 1);
						if (!$menunum) {
							$menunum = 1;
						}
						$menu[$menunum][] = [
							"text" => $number,
							"callback_data" => "myPoll_{$poll['id']}"
						];
						if ($poll['status'] == 2) {
							$menu[$menunum][] = [
								"text" => $langs->getTranslate('publish'),
								"switch_inline_query_current_chat" => "{$poll['id']}"
							];
						}
						if ($poll['status'] == 2) {
							$menu[$menunum][] = [
								"text" => $langs->getTranslate('pollClose'),
								"callback_data" => "myPoll_{$poll['id']}_2"
							];
						} else {
							$menu[$menunum][] = [
								"text" => $langs->getTranslate('pollReopen'),
								"callback_data" => "myPoll_{$poll['id']}_3"
							];
						}
						$menu[$menunum][] = [
							"text" => $langs->getTranslate('pollDelete'),
							"callback_data" => "myPoll_{$poll['id']}_1"
						];
					} else {
						$menu[][] = [
							"text" => $number,
							"callback_data" => "myPoll_{$poll['id']}"
						];
						if ($poll['status'] == 2) {
							$menu[0][] = [
								"text" => $langs->getTranslate('publish'),
								"switch_inline_query_current_chat" => "{$poll['id']}"
							];
						}
						if ($poll['status'] == 2) {
							$menu[0][] = [
								"text" => $langs->getTranslate('pollClose'),
								"callback_data" => "myPoll_{$poll['id']}_2"
							];
						} else {
							$menu[0][] = [
								"text" => $langs->getTranslate('pollReopen'),
								"callback_data" => "myPoll_{$poll['id']}_3"
							];
						}
						$menu[0][] = [
							"text" => $langs->getTranslate('pollDelete'),
							"callback_data" => "myPoll_{$poll['id']}_1"
						];
					}
				} else {
					if (isset($menu)) {
						$menu[count($menu) - 1][] = [
							"text"			=> $number,
							"callback_data"	=> "myPoll_{$poll['id']}"
						];
					} else {
						$menu[][] = [
							"text"			=> $number,
							"callback_data"	=> "myPoll_{$poll['id']}"
						];
					}
				}
			}
			$poll_exists = true;
		}
		if (isset($menu)) $menu = array_values($menu);
		if ($prevpage) {
			$page_manager[] = [
				'text' => "⬅️",
				'callback_data'	=> "myPolls-" . $prevpage . "-$menusetting"
			];
		}
		if ($nextpage) {
			$page_manager[] = [
				'text' => "➡️",
				'callback_data'	=> "myPolls-" . $nextpage . "-$menusetting"
			];
		}
		if (isset($page_manager)) $menu[] = $page_manager;
		$menu[][] = [
			'text' => $langs->getTranslate('mainMenuButton'),
			'callback_data'	=> "start"
		];
		if (isset($poll_exists)) {
			$menu[count($menu) - 1][] = [
				'text'			=> $langs->getTranslate('deleteAllButton'),
				'callback_data'	=> "deleteAllPolls"
			];
			if ($menusetting) {
				$menu[count($menu) - 1][] = [
					'text'			=> $langs->getTranslate('pollListShowSettings'),
					'callback_data'	=> "myPolls-{$page}-0"
				];
			} else {
				$menu[count($menu) - 1][] = [
					'text'			=> $langs->getTranslate('pollListShowSettings'),
					'callback_data'	=> "myPolls-{$page}-1"
				];
			}
		} else {
			$text .= PHP_EOL . $langs->getTranslate('listEmpty');
		}
		$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		die;
	} elseif (strpos($callback_data, "myPoll") === 0) {
		$cbtext = false;
		$db->rdel("{$user_id}-MP-action");
		if (strpos($callback_data, "myPoll_") === 0) {
			$e = explode("_", $callback_data);
		} else {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'));
			die;
		}
		$poll = $mp->getPoll($e[1], ['*', 'votes']);
		if ($poll['status'] !== 0 and isset($e[2])) {
			if ($e[2] == 1) {
				# Delete button
				if ($e[3] == 'confirm') {
					$mp->deletePoll($poll['id']);
					$poll = $mp->getPoll($e[1], ['*', 'votes']);
					$text = $langs->getTranslate('pollDeleted');
					$bot->editMessageText($chat_id, $text, false, $update['callback_query']['message']['message_id']);
					$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
					$mp->updatePollMessages($poll);
					die;
				} else {
					$menu[] = [
						[
							"text" => $langs->getTranslate('yesSure'),
							"callback_data" => "myPoll_{$e[1]}_{$e[2]}_confirm"
						],
						[
							"text" => $langs->getTranslate('no'),
							"callback_data" => "myPoll_{$e[1]}"
						]
					];
					$text = $langs->getTranslate('seriouslyWannaDeleteThePoll', [htmlspecialchars($poll['title'])]);
					$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
					$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
					die;
				}
			} elseif ($e[2] == 2) {
				# Close button
				$r = $mp->closePoll($poll['id']);
				$poll = $mp->getPoll($e[1], ['*', 'votes']);
				$update_poll = true;
			} elseif ($e[2] == 3) {
				# Reopen button
				$mp->reopenPoll($poll['id']);
				$poll = $mp->getPoll($e[1], ['*', 'votes']);
				$update_poll = true;
			} elseif ($e[2] == 4) {
				# Refresh button
				$update_poll = true;
			} elseif ($e[2] == 5) {
				# Send to button
				$db->rset("{$user_id}-MP-action", "sendPollToChat_{$poll['id']}");
				if (isset($e[3])) {
					$db->rdel("{$user_id}-MP-action");
					if ($e[3] == 0) {
						if (isset($e[4])) {
							$db->query("DELETE FROM crontabs WHERE id = ?", [$e[4]], false);
						}
						$text = $bf->bold("📆 " . $langs->getTranslate('pollSendScheduledPosts')) . "\n";
						$crontabs = $db->query("SELECT * FROM crontabs WHERE bot_id = ? and user_id = ? and poll_id = ? and type = ? ORDER BY time ASC", [$bot->getID(), $user_id, $poll['id'], 3], 'fetch');
						$num = 0;
						if (!empty($crontabs)) {
							$datetime = new DateTime();
							date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
							foreach ($crontabs as $cron) {
								$num++;
								$chat = $db->getGroup(['id' => $cron['chat_id']]);
								if (!isset($chat['title'])) $chat = $db->getChannel(['id' => $cron['chat_id']]);
								$datetime->setTimestamp($cron['time']);
								$text .= PHP_EOL . date_format($datetime, $user['settings']['date_format']) . " ➡️ " . $bf->bold($chat['title']);
								$menu[] = [
									[
										'text' => "$num",
										'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
									],
									[
										'text' => "🗑 " . $langs->getTranslate('pollDelete'),
										'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$cron['id']}"
									]
								];
							}
						}
						$menu[][] = [
							'text' => $langs->getTranslate('backButton'),
							'callback_data' => "myPoll_{$e[1]}_{$e[2]}"
						];
						if (!isset($back)) {
							if ($menu) {
								$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
								$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
							} else {
								$bot->answerCallbackQuery($update['callback_query']['id'], $text, true);
							}
							die;
						}
					}
					$chat = $db->getGroup(['id' => round($e[3])]);
					if (!isset($chat['id'])) {
						$chat = $db->getChannel(['id' => round($e[3])]);
					}
					if (isset($chat['id'])) {
						$getBotMember = $bot->getChatMember($chat['id'], $bot->getID());
						if ($getBotMember['ok']) {
							if (in_array($getBotMember['result']['status'], ['administrator', 'member'])) {
								$getMember = $bot->getChatMember($chat['id'], $user_id);
								if ($getMember['ok']) {
									if (in_array($getMember['result']['status'], ['administrator', 'creator'])) {
										if (isset($e[4]) and in_array($e[4], [0, 1])) {
											if ($e[4] == 0) {
												$message = $mp->createPollMessage($poll);
												$send = $bot->sendMessage($chat['id'], $message['text'], $message['reply_markup'], 'def', $message['disable_web_page_prevew'], true);
												if ($send['ok']) {
													$mp->addPollMessage($poll['id'], $send['result']['message_id'], $chat['id']);
													$back = true;
												} else {
													$text = $langs->getTranslate('telegramError', [$send['description']]);
													$menu[][] = [
														'text' => $langs->getTranslate('backButton'),
														'callback_data' => "myPoll_{$e[1]}_5_{$e[3]}"
													];
												}
											} elseif ($e[4] == 1) {
												$time = $db->rget("MP-timeaction-{$user_id}");
												if ($time <= time()) $time = time() + 60;
												if (isset($e[5]) and $e[5] == 3) {
													$db->rdel("MP-timeaction-{$user_id}");
													$datetime = new DateTime();
													date_timezone_set($datetime, (new DateTimeZone("UTC")));
													$datetime->setTimestamp($time);
													$db->query("INSERT INTO crontabs (time, type, user_id, poll_id, chat_id, bot_id) VALUES (?,?,?,?,?,?)", [$datetime->getTimeStamp(), 3, $user_id, $poll['id'], $chat['id'], $bot->getID()], false);
													$text = "✅ " . $langs->getTranslate('done');
													$menu[][] = [
														'text' => $langs->getTranslate('backButton'),
														'callback_data' => "myPoll_{$e[1]}_5"
													];
												} else {
													if (isset($e[7])) {
														$datetime = new DateTime('now', (new DateTimeZone("UTC")));
														$datetime->setTimestamp($time);
														$time_p = explode("-", date_format($datetime, "Y-m-d-H-i"));
														if ($e[5] == 1) {
															if ($e[6] == 1) {
																$time_p[0] = $e[7];
															} elseif ($e[6] == 2) {
																$time_p[1] = $e[7];
															} elseif ($e[6] == 3) {
																$time_p[2] = $e[7];
															}
														} elseif ($e[5] == 2) {
															if ($e[6] == 1) {
																$time_p[3] = $e[7];
															} elseif ($e[6] == 2) {
																$time_p[4] = $e[7];
															}
														}
														if (!isset($e[5])) $is_set = "_{$e[4]}";
														if (isset($e[5]) and !isset($e[6])) $is_set = "_{$e[5]}";
														$datetime->setTimestamp(mktime($time_p[3], $time_p[4], 0, $time_p[1], $time_p[2], $time_p[0]));
														$db->rset("MP-timeaction-{$user_id}", $datetime->getTimestamp());
													}
													$menu = createTimeTemplate("myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}", $e[5], $e[6]);
													$menu[][] = [
														'text' => $langs->getTranslate('schedulePostConfirm'),
														'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_3"
													];
													$menu[][] = [
														'text' => $langs->getTranslate('backButton'),
														'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}" . $is_set
													];
													$text = $bf->bold("📆 " . $langs->getTranslate('pollSendSchedule')) . "\n";
													$text .= $langs->getTranslate('choosePollScheduleTime', [htmlspecialchars($chat['title'])]);
												}
											}
										} else {
											$menu[] = [
												[
													'text' => "📩 " . $langs->getTranslate('pollSendNow'),
													'callback_data' => "myPoll_{$e[1]}_5_{$chat['id']}_0"
												],
												[
													'text' => "📆 " . $langs->getTranslate('pollSendSchedule'),
													'callback_data' => "myPoll_{$e[1]}_5_{$chat['id']}_1"
												]
											];
											$menu[][] = [
												'text' => $langs->getTranslate('backButton'),
												'callback_data' => "myPoll_{$e[1]}_5"
											];
											$text = $langs->getTranslate('pollSendTime', [htmlspecialchars($chat['title'])]);
										}
									} else {
										$text = "🚫 " . $langs->getTranslate('chatInaccessible');
										$menu[][] = [
											'text' => $langs->getTranslate('backButton'),
											'callback_data' => "myPoll_{$e[1]}_5"
										];
									}
								} else {
									$text = "🚫 " . $langs->getTranslate('chatInaccessible');
									$menu[][] = [
										'text' => $langs->getTranslate('backButton'),
										'callback_data' => "myPoll_{$e[1]}_5"
									];
								}
							} else {
								$text = "🚫 " . $langs->getTranslate('chatInaccessible');
								$menu[][] = [
									'text' => $langs->getTranslate('backButton'),
									'callback_data' => "myPoll_{$e[1]}_5"
								];
							}
						} else {
							$text = "🚫 " . $langs->getTranslate('chatInaccessible');
							$menu[][] = [
								'text' => $langs->getTranslate('backButton'),
								'callback_data' => "myPoll_{$e[1]}_5"
							];
						}
					} else {
						$text = $langs->getTranslate('chatNotFound');
					}
					if (!isset($back)) {
						if ($menu) {
							$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
							$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
						} else {
							$bot->answerCallbackQuery($update['callback_query']['id'], $text, true);
						}
						die;
					}
				} else {
					$text = "📨 " . $bf->bold($langs->getTranslate('pollSendChat'));
					$text .= PHP_EOL . $bf->italic($langs->getTranslate('pollSendChatID'));
					$menu[][] = [
						"text" => "📆 " . $langs->getTranslate('pollSendScheduledPosts'),
						"callback_data" => "myPoll_{$e[1]}_5_0"
					];
					$chats = $db->getChatsByAdmin($user_id, 6);
					if (!empty($chats)) {
						foreach ($chats as $chat) {
							$menus[] = [
								'text' => " {$chat['title']} ",
								'callback_data' => "myPoll_{$e[1]}_5_{$chat['id']}"
							];
						}
						if (count($menus) <= 3) {
							$formenu = 1;
						} else {
							$formenu = 2;
						}
						$mcount = 1;
						foreach ($menus as $mmenu) {
							if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
								$mcount += 1;
							}
							$menu[$mcount][] = $mmenu;
						}
					}
					$menu[][] = [
						"text" => $langs->getTranslate('backButton'),
						"callback_data" => "myPoll_{$e[1]}"
					];
					$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
					$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
					die;
				}
			} elseif ($e[2] == 6) {
				# Options
				$options = $mp->getOptions();
				if (isset($e[3])) {
					if (array_key_exists($e[3], $options) and !empty($options[$e[3]])) {
						if (!in_array(round($poll['type']), $options[$e[3]]['compatibility']['types']) or !in_array($poll['anonymous'], $options[$e[3]]['compatibility']['anonymous'])) {
							$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'));							die;
						}
						if (isset($e[4])) {
							$cbview = true;
							if (!array_key_exists($e[4], $options[$e[3]]['subOptions'])) {
								$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'));
								die;
							}
							if ($options[$e[3]]['subOptions'][$e[4]]['type'] == 'bool') {
								$poll = $mp->setPollOption($poll, $e[3], $e[4]);
							} elseif ($options[$e[3]]['subOptions'][$e[4]]['type'] == 'time')  {
								$options[$e[3]]['subOptions'][$e[4]]['optionName'][0] = strtoupper($options[$e[3]]['subOptions'][$e[4]]['optionName']);
								$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
								$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description');
								$time = $db->rget("MP-timeaction-{$user_id}");
								if ($time <= time()) $time = time() + 60;
								if ($e[4] == "3") {
									$crontype = 2;
								} elseif ($e[4] == "4") {
									$crontype = 1;
								} else {
									die;
								}
								if (isset($e[5]) and $e[5] == 3) {
									$db->rdel("MP-timeaction-{$user_id}");
									$datetime = new DateTime();
									date_timezone_set($datetime, (new DateTimeZone("UTC")));
									$datetime->setTimestamp($time);
									$db->query("DELETE FROM crontabs WHERE type = ? and poll_id = ? and bot_id = ?", [$crontype, $poll['id'], $bot->getID()], false);
									$db->query("INSERT INTO crontabs (time, type, user_id, poll_id, bot_id) VALUES (?,?,?,?,?)", [$datetime->getTimeStamp(), $crontype, $user_id, $poll['id'], $bot->getID()], false);
									$menu = [];
									$text = "✅ " . $langs->getTranslate('done');
								} else {
									if ($e[5] == 'delete') {
										$db->query("DELETE FROM crontabs WHERE id = ?", [$e[6]], false);
									} elseif (isset($e[7])) {
										$datetime = new DateTime('now', (new DateTimeZone("UTC")));
										$datetime->setTimestamp($time);
										$time_p = explode("-", date_format($datetime, "Y-m-d-H-i"));
										if ($e[5] == 1) {
											if ($e[6] == 1) {
												$time_p[0] = $e[7];
											} elseif ($e[6] == 2) {
												$time_p[1] = $e[7];
											} elseif ($e[6] == 3) {
												$time_p[2] = $e[7];
											}
										} elseif ($e[5] == 2) {
											if ($e[6] == 1) {
												$time_p[3] = $e[7];
											} elseif ($e[6] == 2) {
												$time_p[4] = $e[7];
											}
										}
										if (!isset($e[5])) $is_set = "_{$e[4]}";
										if (isset($e[5]) and !isset($e[6])) $is_set = "_{$e[5]}";
										$datetime->setTimestamp(mktime($time_p[3], $time_p[4], 0, $time_p[1], $time_p[2], $time_p[0]));
										$db->rset("MP-timeaction-{$user_id}", $datetime->getTimestamp());
									}
									$menu = createTimeTemplate("myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}", $e[5], $e[6]);
									$menu[][] = [
										'text' => $langs->getTranslate('confirmButton'),
										'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_3"
									];
									$cron = $db->query("SELECT id FROM crontabs WHERE poll_id = ? and type = ? LIMIT 1", [$poll['id'], $crontype], true);
									if (isset($cron['id'])) {
										$menu[][] = [
											'text' => "🗑 " . $langs->getTranslate('pollDelete'),
											'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_delete_{$cron['id']}"
										];
									}
								}
								if (isset($text)) {
									if (isset($menu)) {
										$menu[][] = [
											"text" => $langs->getTranslate('backButton'),
											"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
										];
										$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
										$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
									} else {
										$bot->answerCallbackQuery($update['callback_query']['id'], $text, $cbview);
									}
									die;
								}
							} else {
								$options[$e[3]]['subOptions'][$e[4]]['optionName'][0] = strtoupper($options[$e[3]]['subOptions'][$e[4]]['optionName']);
								$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
								if ($e[3] == 0) {
									if ($e[4] == 5) {
										$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('optionsVotersLimit'));
										$text .= PHP_EOL . $langs->getTranslate('optionsVotersLimitDescription');
										if ($e[5] == "set") {
											$db->rset("{$user_id}-MP-action", "settings-{$poll['id']}-{$e[3]}-{$e[4]}", 60);
											$menu = [];
											$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('optionsVotersLimit'));
											if ($poll['votes']) {
												$votes = count($poll['votes']);
											} else {
												$votes = 0;
											}
											$text .= PHP_EOL . $langs->getTranslate('numericInputRequest', [$votes, $configs['limits']['pollVoters']]);
										} elseif (isset($poll['settings']['options']['0-5']) and $e[5] !== "del") {
											$text .= PHP_EOL . $langs->getTranslate('votersLimit', [$poll['settings']['options']['0-5']]);
											$menu[] = [
												[
													"text" => $langs->getTranslate('votersLimitSetButton'),
													"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_set"
												],
												[
													"text" => $langs->getTranslate('votersLimitUnsetButton'),
													"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_del"
												]
											];
										} else {
											if ($e[5] == "del") {
												unset($poll['settings']['options']['0-5']);
												$poll = $mp->setPollOption($poll, 0, 5);
											}
											$menu[] = [
												[
													"text" => $langs->getTranslate('votersLimitSetButton'),
													"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_set"
												]
											];
										}
									} elseif ($e[4] == 6) {
										if (isset($poll['votes']) and is_array($poll['votes']) and !empty($poll['votes'])) {
											if (isset($e[5])) {
												$ok = $db->query("DELETE FROM choices WHERE poll_id = ?", [$poll['id']], true);
												$poll['votes'] = [];
												unset($text);
											} else {
												$text .= PHP_EOL . $langs->getTranslate('resetVotes');
												$menu[] = [
													[
														"text" => $langs->getTranslate('yesSure'),
														"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
													],
													[
														"text" => $langs->getTranslate('no'),
														"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
													]
												];
											}
										} else {
											$cbtext = $langs->getTranslate('resetVotesEmpty');
											unset($text);
										}
									} elseif ($e[4] == 7) {
										if (isset($e[5])) {
											$select[$e[5]] = "🔘 ";
											if ($e[5] == 1) {
												foreach ($poll['choices'] as $choiceID => $choice) {
													$labels[] = $choice;
													if (isset($poll['votes']) and is_array($poll['votes'])) {
														$cvotes = 0;
														foreach ($poll['votes'] as $vote) {
															if ($choiceID == $vote['choice_id']) $cvotes++;
														}
													} else {
														$cvotes = 0;
													}
													$datasets[] = [
														'label'			=> "$choice",
														'data'			=> [$cvotes],
														'fill'			=> false,
														'borderColor'	=> 'blue'
													];
												}
												$chartargs = [
													'type'		=> 'bar',
													'data'		=> [
														'datasets'	=> $datasets
													]
												];
											} elseif ($e[5] == 2) {
												foreach ($poll['choices'] as $choiceID => $choice) {
													$labels[] = "$choice";
													if (isset($poll['votes']) and is_array($poll['votes'])) {
														$cvotes = 0;
														foreach ($poll['votes'] as $vote) {
															if ($choiceID == $vote['choice_id']) $cvotes++;
														}
													} else {
														$cvotes = 0;
													}
													$datasets[0]['data'][] = $cvotes;
												}
												$chartargs = [
													'type'	=> 'pie',
													'data'	=> [
														'labels'	=> $labels,
														'datasets'	=> $datasets
													]
												];
											}
											if ($chartargs) {
												$req = [
													'c'			=> json_encode($chartargs),
													'format'	=> "png",
													'width'		=> 500,
													'height'	=> 300
												];
												$url = 'https://quickchart.io/chart?' . http_build_query($req);
												$text .= "<a href='" . htmlspecialchars($url) . "'>&#8203;</a>";
												$dislink = false;
											}
										}
										$text .= PHP_EOL . $langs->getTranslate('optionsCreateGraphDescription');
										$menu[] = [
											[
												"text" => $select[1] . $langs->getTranslate('optionsGraphBar'),
												"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
											],
											[
												"text" => $select[2] . $langs->getTranslate('optionsGraphPie'),
												"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_2"
											]
										];
										$menu[] = [
											[
												"text" => "🔄 " . $langs->getTranslate('refreshButton'),
												"callback_data" => $callback_data
											]
										];
									} elseif ($e[4] == 8) {
										if (!$poll['anonymous']) {
											if (isset($e[5])) {
												$db->query("UPDATE polls SET anonymous = ? WHERE id = ?", [1, $poll['id']], false);
												$poll['anonymous'] = true;
												unset($text);
											} else {
												$text .= PHP_EOL . $langs->getTranslate('turnAnonymous');
												$menu[] = [
													[
														"text" => $langs->getTranslate('turnAnonymousSure'),
														"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
													],
													[
														"text" => $langs->getTranslate('no'),
														"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
													]
												];
											}
										}
									}
								} elseif ($e[3] == 1) {
									if ($e[4] == 2) {
										if ($e[5]) {
											unset($poll['settings']['options']['1-2']);
											$poll = $mp->setPollOption($poll, 1, 2);
										}
										$db->rset("{$user_id}-MP-action", "settings-{$poll['id']}-{$e[3]}-{$e[4]}", 60);
										$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
										$text .= PHP_EOL . $langs->getTranslate('attachWebDocumentPreview');
										$menu = [];
										if (isset($poll['settings']['options']['1-2'])) {
											$menu[][] = [
												'text' => $langs->getTranslate('removeWebDocumentPreview'),
												'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
											];
										}
									} elseif ($e[4] == 3) {
										$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'), true);
										die;
									} elseif ($e[4] == 4) {
										if (!empty($poll['votes'])) {
											$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('pollMustBeEmpty'));
											die;
										}
										if (isset($e[6])) {
											$db->query("UPDATE polls SET description = ? WHERE id = ?", ['', $poll['id']], false);
											unset($poll['description']);
											unset($e[5]);
										}
										if (isset($e[5])) {
											$db->rset("{$user_id}-MP-action", "settings-{$poll['id']}-{$e[3]}-{$e[4]}-{$e[5]}", 60);
											$menu = [];
											$etype = [
												"Title",
												"Description"
											];
											$text = $langs->getTranslate('editPoll' . $etype[$e[5]]);
											$e[3] = "{$e[3]}_{$e[4]}";
										} else {
											$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
											$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description') . "\n";
											$text .= PHP_EOL . $mp->getTypeEmoji($poll['type']) . " " . $bf->code($poll['title']);
											if ($poll['description']) $text .= $bf->code($poll['description']);
											$menu[] = [
												[
													'text' => $langs->getTranslate('title'),
													'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_0"
												],
												[
													'text' => $langs->getTranslate('description'),
													'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
												]
											];
											if ($poll['description']) {
												$menu[][] = [
													'text' => $langs->getTranslate('removeDescription'),
													'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1_1"
												];
											}
										}
									} elseif ($e[4] == 5) {
										$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
										$text .= PHP_EOL . $langs->getTranslate('cloneOptions');
										$render = [
											1	=> "❌"
										];
										if ($db->rget("MP-cloneOptions")) {
											$render[1] = "✅";
											$pollsettings = $poll['settings'];
										} else {
											$pollsettings = [];
										}
										if (isset($e[5]) and $e[5] == 0) {	// Clone poll
											if ($pollType == 0) {
												# Vote
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], $poll['choices'], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 1) {
												# Doodle
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], $poll['choices'], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 2) {
												# Limited Doodle
												$pollsettings['limitedDoodleLimit'] = $poll['settings']['limitedDoodleLimit'];
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], $poll['choices'], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 3) {
												# Board
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], [], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 4) {
												# Rating
												$choices = range(1, $message);
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], $poll['choices'], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 5) {
												# Participation
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], [], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} elseif ($pollType == 6) {
												# Quiz
												$pollsettings['quizResponse'] = $poll['settings']['quizResponse'];
												$poll = $mp->createPoll($user_id, $poll['title'], $poll['description'], $poll['type'], $poll['anonymous'], $poll['choices'], $pollsettings);
												$cbtext = $langs->getTranslate('finishedPollCreation');
												$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
												$db->rdel("{$user_id}-MP-action");
											} else {
												# Unknown or unsupported
												die;
											}
											$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
											if ($pollMessage['text']) {
												$polltext = $pollMessage['text'];
											} else {
												$polltext = $langs->getTranslate('generalError');
											}
											if (isset($pollMessage['reply_markup'])) {
												$pollmenu = $pollMessage['reply_markup'];
											}
											if (!isset($pollMessage['disable_web_page_prevew'])) {
												$pollMessage['disable_web_page_prevew'] = true;
											}
											$bot->sendMessage($chat_id, $polltext, $pollmenu, 'def', false, $pollMessage['disable_web_page_prevew']);
											if (isset($menu)) {
												$menu[][] = [
													"text" => $langs->getTranslate('backButton'),
													"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
												];
												$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
											}
											die;
										} elseif ($e[5] == 1) {				// Clone options
											if ($db->rget("MP-cloneOptions")) {
												$db->rdel("MP-cloneOptions");
												$render[1] = "❌";
											} else {
												$db->rset("MP-cloneOptions", true, 15);
												$render[1] = "✅";
											}
										}
										$menu[][] = [
											'text' => "{$render[1]}" . $langs->getTranslate('pollOptions'),
											'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_1"
										];
										$menu[][] = [
											'text' => $mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']),
											'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_0"
										];
									} elseif ($e[4] == 6) {
										if (isset($e[5]) and isset($options[1]['subOptions'][6]['formats']) and in_array($e[5], $options[1]['subOptions'][6]['formats'])) {
											if ($e[5] == 'json') {
												$filename = "poll-{$poll['id']}.json";
												file_put_contents($filename, json_encode($mp->createFile($poll['id']), JSON_PRETTY_PRINT));
												if (file_exists($filename)) {
													$datetime = new DateTime();
													date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
													$ok = $bot->sendDocument($chat_id, $filename, $langs->getTranslate('exportedFile', [htmlspecialchars($poll['title']), date_format($datetime, $user['settings']['date_format']), $bot->getUsername()]), [], 'def', true);
													if ($ok['ok']) {
														$text = $langs->getTranslate('exportedSuccessfully');
														$cbview = false;
													} else {
														$text = $langs->getTranslate('exportFail');
													}
												} else {
													$text = $langs->getTranslate('exportFail');
												}
											} elseif ($e[5] == 'yaml' and function_exists("yaml_emit")) {
												$filename = "poll-{$poll['id']}.yaml";
												file_put_contents($filename, yaml_emit($mp->createFile($poll['id'])));
												if (file_exists($filename)) {
													$datetime = new DateTime();
													date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
													$ok = $bot->sendDocument($chat_id, $filename, $langs->getTranslate('exportedFile', [htmlspecialchars($poll['title']), date_format($datetime, $user['settings']['date_format']), $bot->getUsername()]), [], 'def', true);
													if ($ok['ok']) {
														$text = $langs->getTranslate('exportedSuccessfully');
														$cbview = false;
													} else {
														$text = $langs->getTranslate('exportFail');
													}
												} else {
													$text = $langs->getTranslate('exportFail');
												}
											} elseif ($e[5] == 'xml' and class_exists("SimpleXMLElement")) {
												$filename = "poll-{$poll['id']}.xml";
												function array_to_xml($data, $xml_data) {
													foreach($data as $key => $value) {
														if(is_array($value)) {
															if(is_numeric($key)){
																$key = 'item'.$key;
															}
															$subnode = $xml_data->addChild($key);
															array_to_xml($value, $subnode);
														} else {
															$xml_data->addChild("$key", htmlspecialchars("$value"));
														}
													}
												}
												$xml_data = new SimpleXMLElement('<poll/>');
												array_to_xml($mp->createFile($poll['id']), $xml_data);
												$xml_data->asXML($filename);
												if (file_exists($filename)) {
													$datetime = new DateTime();
													date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
													$ok = $bot->sendDocument($chat_id, $filename, $langs->getTranslate('exportedFile', [htmlspecialchars($poll['title']), date_format($datetime, $user['settings']['date_format']), $bot->getUsername()]), [], 'def', true);
													if ($ok['ok']) {
														$text = $langs->getTranslate('exportedSuccessfully');
														$cbview = false;
													} else {
														$text = $langs->getTranslate('exportFail');
													}
												} else {
													$text = $langs->getTranslate('exportFail');
												}
											} elseif ($e[5] == 'csv' and function_exists("fputcsv")) {
												$filename = "poll-{$poll['id']}.csv";
												$data = $mp->createFile($poll['id']);
												$outstream = fopen($filename, 'x+');
												fputcsv($outstream, $data, ',', '"');
												rewind($outstream);
												$csv = fgets($outstream);
												fclose($outstream);
												if (file_exists($filename)) {
													$datetime = new DateTime();
													date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
													$ok = $bot->sendDocument($chat_id, $filename, $langs->getTranslate('exportedFile', [htmlspecialchars($poll['title']), date_format($datetime, $user['settings']['date_format']), $bot->getUsername()]), [], 'def', true);
													if ($ok['ok']) {
														$text = $langs->getTranslate('exportedSuccessfully');
														$cbview = false;
													} else {
														$text = $langs->getTranslate('exportFail');
													}
												} else {
													$text = $langs->getTranslate('exportFail');
												}
											} else {
												$text = $langs->getTranslate('generalError');
											}
											if (file_exists($filename)) unlink($filename);
										} else {
											$menu = [];
											$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('optionsExport'));
											$text .= PHP_EOL . $langs->getTranslate('exportFormat');
											if (isset($options[1]['subOptions'][6]['formats']) and !empty($options[1]['subOptions'][6]['formats'])) {
												foreach ($options[1]['subOptions'][6]['formats'] as $fileType) {
													$menus[] = [
														'text' => strtoupper($fileType),
														'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_" . $fileType
													];
												}
												if (!empty($menus)) {
													if (count($menus) <= 3) {
														$formenu = 1;
													} else {
														$formenu = 2;
													}
													if (is_array($menu)) {
														$mcount = count($menu);
													} else {
														$mcount = 0;
													}
													foreach ($menus as $mmenu) {
														if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
															$mcount += 1;
														}
														$menu[$mcount][] = $mmenu;
													}
												}
											}
										}
									}
								} elseif ($e[3] == 2) {
									if ($e[4] == 0) {
										if (isset($e[5])) {
											$e[5] = round($e[5]);
											$mp->setPollOption($poll, 2, 0, $e[5]);
											$poll['settings']['options']['2-0'] = $e[5];
										}
										if (!isset($poll['settings']['options']['2-0'])) {
											$poll['settings']['options']['2-0'] = 0;
										}
										$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description');
										$menu[] = [];
										$select[$poll['settings']['options']['2-0']] = "🔘 ";
										foreach (range(0, 2) as $num) {
											$menu[0][] = [
												'text' => $select[$num] . $mp->getButtons($langs->getTranslate('title'), 50, $num),
												'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_{$num}"
											];
										}
									} elseif ($e[4] == 1) {
										if (isset($e[5])) {
											$mp->setPollOption($poll, 2, 1, $e[5]);
											$poll['settings']['options']['2-1'] = $e[5];
										}
										if (!isset($poll['settings']['options']['2-1'])) {
											$poll['settings']['options']['2-1'] = 0;
										}
										$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description');
										$menu[] = [];
										$select[$poll['settings']['options']['2-1']] = "🔘 ";
										foreach ([0, 1] as $num) {
											$menu[0][] = [
												'text' => $select[$num] . $mp->getChoiceText($langs->getTranslate('title'), 10, $num),
												'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_{$num}"
											];
										}
									} elseif ($e[4] == 2) {
										if (isset($e[5])) {
											if ($e[5] == 'no') {
												$mp->setPollOption($poll, 2, 2);
												unset($poll['settings']['options']['2-2']);
											} else {
												$mp->setPollOption($poll, 2, 2, $e[5]);
												$poll['settings']['options']['2-2'] = $e[5];
											}
										}
										$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description');
										$menu[] = [];
										$select[$poll['settings']['options']['2-2']] = "🔘 ";
										foreach ([0, 1] as $num) {
											$menu[0][] = [
												'text' => $select[$num] . $mp->createBars(10, $num),
												'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_{$num}"
											];
										}
										if (isset($poll['settings']['options']['2-2'])) {
											$menu[][] = [
												'text' => $langs->getTranslate('optionsNoBars'),
												'callback_data' => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}_{$e[4]}_no"
											];
										}
									} else {
										$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'), true);
										die;
									}
								} elseif ($e[3] == 3) {
									if ($e[4] == 2) {
										if (in_array($poll['type'], range(0, 2)) and count($poll['choices']) <= 2) {
											$text = $langs->getTranslate('pollOptionsFew');
										} else {
											$text = $bf->bold($mp->getOptionEmoji($e[3], $e[4]) . " " . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName']));
											$text .= PHP_EOL . $langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description') . "\n";
											$menu[][] = [
												'text' => "🔄 " . $langs->getTranslate('refreshButton'),
												'callback_data' => $callback_data
											];
											if (in_array($poll['type'], range(0, 2))) {
												if (isset($poll['votes']) and !empty($poll['votes'])) {
													foreach ($poll['votes'] as $vote) {
														$cvotes[$vote['choice_id']]++;
														$avotes[$vote['choice_id']][] = [
															'id'	=> $vote['user_id'],
															'name'	=> $vote['cache_name']
														];
													}
												}
												foreach ($poll['choices'] as $choiceID => $choice) {
													$text .= PHP_EOL . $bf->bold($choice) . "\n/delete_{$poll['id']}_{$choiceID}\n";
													if (isset($avotes[$choiceID]) and !empty($avotes[$choiceID])) {
														for ($i = 0; $i < 10; $i ++) {
															if (isset($avotes[$choiceID][$i])) $text .= "- " . htmlspecialchars($avotes[$choiceID][$i]['name']) . "\n";
														}
													}
												}
											} elseif ($poll['type'] == 3) {
												foreach ($poll['votes'] as $vote) {
													if (!$poll['anonymous']) {
														$vuser = "👁‍🗨 " . htmlspecialchars($vote['cache_name']) . " [" . $bf->code($vote['user_id']) . "]\n";
													}
													$text .= "\n$vuser" . $bf->bold($vote['comment']) . "\n/delete_{$poll['id']}_{$vote['id']}\n";
												}
											}
										}
									} elseif ($e[4] == 3) {
										if (isset($e[5])) {
											if ($e[5] == 0) {
												$menu = [];
												$text .= PHP_EOL . $langs->getTranslate('forbiddenWordsAdd', [htmlspecialchars($poll['title'])]);
											} elseif ($e[5] == 1) {
												$menu = [];
												$text .= PHP_EOL . $langs->getTranslate('forbiddenWordsRemove', [htmlspecialchars($poll['title'])]);
											} else {
												$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'));
												die;
											}
											$db->rset("{$user_id}-MP-action", "settings-{$e[1]}-{$e[3]}-{$e[4]}-{$e[5]}", 60);
										} else {
											$menu[] = [
												[
													'text' => $langs->getTranslate('forbiddenWordsAddButton'),
													'callback_data' => $callback_data . "_0"
												],
												[
													'text' => $langs->getTranslate('forbiddenWordsRemoveButton'),
													'callback_data' => $callback_data . "_1"
												]
											];
											$text .= PHP_EOL . $bf->italic($langs->getTranslate('options' . $options[$e[3]]['subOptions'][$e[4]]['optionName'] . 'Description')) . "\n";
											if (isset($poll['settings']['options']['3-3']) and !empty($poll['settings']['options']['3-3'])) {
												$text .= PHP_EOL . $list;
											} else {
												$text .= PHP_EOL . $langs->getTranslate('listEmpty');
											}
										}
									}
								} else {
									$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('operationNotAvailable'));
									die;
								}
								if (isset($text)) {
									if (isset($menu)) {
										$menu[][] = [
											"text" => $langs->getTranslate('backButton'),
											"callback_data" => "myPoll_{$e[1]}_{$e[2]}_{$e[3]}"
										];
										if ($dislink !== false) $dislink = true;
										$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id'], 'def', false);
										$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
									} else {
										$bot->answerCallbackQuery($update['callback_query']['id'], $text, $cbview);
									}
									die;
								}
							}
						}
						$options[$e[3]]['optionName'][0] = strtoupper($options[$e[3]]['optionName'][0]);
						$text = $bf->bold($mp->getOptionEmoji($e[3]) . " " . $langs->getTranslate('optionsCategory' . $options[$e[3]]['optionName']));
						$text .= "\n";
						foreach ($options[$e[3]]['subOptions'] as $optionID => $option) {
							if (!isset($option['compatibility']['types'])) {
								$option['compatibility']['types'] = $options[$e[3]]['compatibility']['types'];
							}
							if (!isset($option['compatibility']['anonymous'])) {
								$option['compatibility']['anonymous'] = $options[$e[3]]['compatibility']['anonymous'];
							}
							if (in_array($poll['type'], $option['compatibility']['types']) and in_array($poll['anonymous'], $option['compatibility']['anonymous'])) {
								$option['optionName'][0] = strtoupper($option['optionName'][0]);
								$s_emo = $mp->getOptionStatus($poll, $e[3], $optionID);
								$m_emo = $mp->getOptionEmoji($e[3], $optionID);
								if (!$m_emo) $m_emo = $s_emo;
								$text .= "\n🔸 " . $bf->bold($langs->getTranslate('options' . $option['optionName']) . ": ") . $s_emo;
								$text .= PHP_EOL . $bf->italic($langs->getTranslate('options' . $option['optionName'] . "Description"));
								$menus[] = [
									'text' => "$m_emo " . $langs->getTranslate('options' . $option['optionName']),
									'callback_data' => "myPoll_{$e[1]}_6_{$e[3]}_{$optionID}"
								];
							}
						}
						if (!empty($menus)) {
							if (count($menus) <= 3) {
								$formenu = 1;
							} else {
								$formenu = 2;
							}
							if (is_array($menu)) {
								$mcount = count($menu);
							} else {
								$mcount = 0;
							}
							foreach ($menus as $mmenu) {
								if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
									$mcount += 1;
								}
								$menu[$mcount][] = $mmenu;
							}
						}
						$menu[][] = [
							"text" => $langs->getTranslate('backButton'),
							"callback_data" => "myPoll_{$e[1]}_6"
						];
					} else {
						$text = $langs->getTranslate('operationNotAvailable');
					}
				} else {
					$text = $bf->bold("⚙️ " . $langs->getTranslate('pollSettingsWelcome'));
					$text .= "\n";
					$text .= "\n🔸 " . $bf->bold($langs->getTranslate('title') . ": ") . htmlspecialchars($poll['title']);
					if ($poll['description']) $text .= "\n🔸 " . $bf->bold($langs->getTranslate('description') . ": ") . htmlspecialchars($poll['description']);
					if ($poll['votes']) {
						$votes = count($poll['votes']);
					} else {
						$votes = 0;
					}
					if ($poll['voters']) {
						$voters = count($poll['voters']);
					} else {
						$voters = $votes;
					}
					$text .= "\n🔸 " . $bf->bold($langs->getTranslate('votes') . "/" . $langs->getTranslate('voters') . ": ") . "{$votes}/{$voters}";
					$trType = $mp->getTypes()[$poll['type']];
					$trType[0] = strtoupper($trType[0]);
					$text .= "\n🔸 " . $bf->bold($langs->getTranslate('pollType')) . ": " . $trType;
					foreach ($options as $optionID => $option) {
						if (in_array($poll['type'], $option['compatibility']['types']) and in_array($poll['anonymous'], $option['compatibility']['anonymous'])) {
							$option['optionName'][0] = strtoupper($option['optionName'][0]);
							$menus[] = [
								"text" => $mp->getOptionEmoji($optionID) . " " . $langs->getTranslate('optionsCategory' . $option['optionName']),
								"callback_data" => "myPoll_{$e[1]}_6_{$optionID}"
							];
						}
					}
					if (count($menus) < 4) {
						$formenu = 1;
					} else {
						$formenu = 2;
					}
					$mcount = 0;
					foreach ($menus as $mmenu) {
						if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
							$mcount += 1;
						}
						$menu[$mcount][] = $mmenu;
					}
					$menu[][] = [
						"text" => $langs->getTranslate('backButton'),
						"callback_data" => "myPoll_{$e[1]}_4"
					];
				}
				if (isset($text)) {
					if (isset($menu)) {
						$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
						$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
					} else {
						$bot->answerCallbackQuery($update['callback_query']['id'], $text);
					}
					die;
				}
			}
		}
		$message = [];
		$message['reply_markup'] = false;
		if (!is_numeric($poll['status'])) {
			$message['text'] = $langs->getTranslate('pollDoesntExist');
		} elseif ($poll['status'] !== 0) {
			$message = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
		} else {
			$message['text'] = $langs->getTranslate('pollDeleted');
		}
		$bot->editMessageText($chat_id, $message['text'], $message['reply_markup'], $update['callback_query']['message']['message_id'], 'def', $message['disable_web_page_preview']);
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		if (isset($poll['messages']) and is_array($poll['messages'])) {
			/* if (!empty($poll['messages'])) {
				foreach ($poll['messages'] as $message) {
					if ($message['message_id'] == $update['callback_query']['message']['message_id'] and $message['chat_id'] == $chat_id) {
						$isok = true;
					}
				}
			}
			if (!$isok) {
				$mp->addPollMessage($poll['id'], $update['callback_query']['message']['message_id'], $chat_id, ['language' => $user['lang'], 'isPollOwner' => true]);
			}*/
			if ($update_poll) $mp->updatePollMessages($poll);
		}
		die;
	} elseif ($callback_data == "setTimeZone") {
		$bot->deleteMessage($chat_id, $update['callback_query']['message']['message_id']);
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		$menu[] = [
			[
				'text' => $langs->getTranslate('setTimeZone'),
				'request_location' => true
			]
		];
		$menu[] = [
			[
				'text' => "/cancel"
			]
		];
		$db->rset("{$user_id}-MP-action", 'setTimeZone', 60);
		$bot->sendMessage($chat_id, $langs->getTranslate('setTimeZone'), $menu, 'def', false, false, 'keyboard');
		die;
	} elseif (strpos($callback_data, "setDateFormat") === 0) {
		$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
		$text = $bf->bold($langs->getTranslate('setDateFormat'));
		$datetime = new DateTime();
		date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
		if (strpos($callback_data, "setDateFormat-") === 0) {
			if (!isset($user['settings']['date_format'])) {
				$user['settings']['date_format'] = str_replace("setDateFormat-", '', $callback_data);
				$db->query("UPDATE users SET settings = settings::jsonb || ?::jsonb WHERE id = ?", ['{"date_format":"' . $user['settings']['date_format'] . '"}', $user_id]);
			} else {
				$user['settings']['date_format'] = str_replace("setDateFormat-", '', $callback_data);
				$db->query("UPDATE users SET settings = settings::jsonb || jsonb_set(
				settings::jsonb, 
				'{date_format}'::text[], 
				'\"{$user['settings']['date_format']}\"'::jsonb
				)::jsonb WHERE id = ?", [$user_id]);
			}
		}
		$text .= PHP_EOL . $bf->italic(date_format($datetime, $user['settings']['date_format']));
		/* 
			Use the php datetime format
			Check in the official website
			https://www.php.net/manual/en/datetime.format.php
		*/
		$date_formats = ["D, d/m/Y H:i", "d/m/Y H:i", "m/d/Y H:i", "y/d/m H:i", "Y/m/d H:i"];
		foreach ($date_formats as $format) {
			if ($format == $user['settings']['date_format']) {
				$tformat = "✅ " . $format;
			} else {
				$tformat = $format;
			}
			$menus[] = [
				'text' => date_format($datetime, $tformat),
				'callback_data' => "setDateFormat-{$format}"
			];
		}
		if (count($menus) <= 4) {
			$formenu = 1;
		} else {
			$formenu = 2;
		}
		$mcount = 0;
		foreach ($menus as $mmenu) {
			if (isset($menu[$mcount]) and count($menu[$mcount]) >= $formenu) {
				$mcount += 1;
			}
			$menu[$mcount][] = $mmenu;
		}
		$menu[] = [
			[
				'text' => $langs->getTranslate('backButton'),
				'callback_data' => "settings-changeTimezone"
			]
		];
		$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
		die;
	} elseif (strpos($callback_data, "bl-") === 0) {
		$bot->answerCallbackQuery($update['callback_query']['id']);
		$e = explode("-", $callback_data);
		if ($e[1] == "add") {
			$db->rset("{$user_id}-MP-action", 'bl-add', 60);
			$text = $langs->getTranslate('blacklistAdd');
		} elseif ($e[1] == "remove") {
			$db->rset("{$user_id}-MP-action", 'bl-remove', 60);
			$bl = $db->query("SELECT users FROM blacklists WHERE user_id = ? and poll_id = ? LIMIT 1", [$user['id'], 0]);
			foreach ($bl as $user => $time) {
				if ($time !== 0 or $time < time()) {
					unset($bl[$user]);
				}
			}
			$text = $langs->getTranslate('blacklistRemove');
			if (!empty($bl['users'])) {
				$bl['users'] = json_decode($bl['users'], true);
				if (is_numeric($e[2]) and isset($bl['users'][$e[2]])) {
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || '{}'::jsonb WHERE user_id = ? and poll_id = ?", [$e[2], $user['id'], 0]);
					unset($bl['users'][$e[2]]);
				}
				foreach ($bl['users'] as $banus => $bantime) {
					$banned = $db->query("SELECT id, name, surname FROM users WHERE id = ?", [$banus], true);
					$name = $banned['name'] . " " . $banned['surname'];
					$menu[] = [
						[
							"text" => $name,
							"callback_data" => "name"
						],
						[
							"text" => $langs->getTranslate('blacklistRemoveButton'),
							"callback_data" => "bl-remove-{$banned['id']}"
						]
					];
				}
			}
		} elseif ($e[1] == "removeAll") {
			$db->rset("{$user_id}-MP-action", 'bl-removeAll', 60);
			$text = $bf->bold($langs->getTranslate('blacklistRemoveAll'));
			$menu[] = [
				[
					'text' => $langs->getTranslate('yesSure'),
					'callback_data' => "bl-removeAllSure"
				],
				[
					'text' => $langs->getTranslate('no'),
					'callback_data' => "cancel-settings-manageBlacklist"
				]
			];
		} elseif ($e[1] == "removeAllSure") {
			$db->rdel("{$user_id}-MP-action", 'bl-removeAll');
			$db->query("UPDATE blacklists SET users = ? WHERE user_id = ? and poll_id = ?", ['{}', $user['id'], 0]);
			$text = $bf->bold($langs->getTranslate('blacklistAllRemoved'));
		} else {
			$text = "Unknown action...";
		}
		$menu[] = [
			[
				'text' => $langs->getTranslate('backButton'),
				'callback_data' => "cancel-settings-manageBlacklist"
			]
		];
		$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
		die;
	} elseif (strpos($callback_data, "deleteAllPolls") === 0) {
		$e = explode("-", $callback_data);
		$polls = $mp->getPollsList($user_id, 0, 1);
		if (empty($polls)) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('listEmpty'));
		} else {
			$poll_exists = true;
			if ($e[1] == "yes") {
				$text = $langs->getTranslate('seriouslySureDeleteEverything');
				$menu[] = [
					[
						"text"			=> $langs->getTranslate('yesSure'),
						"callback_data"	=> "deleteAllPolls-sure"
					],
					[
						"text"			=> $langs->getTranslate('no'),
						"callback_data"	=> "deleteAllPolls-no"
					]
				];
			} elseif ($e[1] == "sure") {
				$mp->deleteAllPolls($user_id);
				$text = $langs->getTranslate('deletedEverything');
			} elseif ($e[1] == "no") {
				$text = $bf->italic($langs->getTranslate('sadlyEverythingThere'));
			} else {
				$text = $langs->getTranslate('seriouslyDeleteEverything');
				$menu[] = [
					[
						"text"			=> $langs->getTranslate('yesSure'),
						"callback_data"	=> "deleteAllPolls-yes"
					],
					[
						"text"			=> $langs->getTranslate('no'),
						"callback_data"	=> "deleteAllPolls-no"
					]
				];
			}
			if (!isset($menu)) {
				$menu[] = [
					[
						'text' => $langs->getTranslate('backButton'),
						'callback_data' => "myPolls"
					]
				];
			}
			$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id']);
			$bot->answerCallbackQuery($update['callback_query']['id']);
		}
		die;
	}
}

# All commands-only answers
if (isset($command)) {
	if (strpos($command, "start share_") === 0) {
		# Share polls
		$e = explode("_", str_replace("start ", '', $command));
		$poll = $mp->getPoll($e[1]);
		if ($db->isBanned($user_id, $poll['owner_id'], $poll['id'])) {
			$bot->sendMessage($chat_id, bold($langs->getTranslate('voteNotAllowed')));
		} elseif ($poll['status'] !== 2) {
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} elseif ($poll['settings']['options']['1-0']) {
			$menu[][] = [
				'text' => $langs->getTranslate('shareButton'),
				'switch_inline_query' => "share {$poll['id']}"
			];
			$bot->sendMessage($chat_id, $bf->bold($langs->getTranslate('shareButton')) . PHP_EOL . $langs->getTranslate('shareComment', [$bf->bold($poll['title'])]), $menu);
		} else {
			$bot->sendMessage($chat_id, $langs->getTranslate('generalError'));
		}
		die;
	} elseif (strpos($command, "start append_") === 0) {
		# Append options
		$e = explode("_", str_replace("start ", '', $command));
		$poll = $mp->getPoll($e[1]);
		if ($db->isBanned($user_id, $poll['owner_id'], $poll['id'])) {
			$bot->sendMessage($chat_id, bold($langs->getTranslate('voteNotAllowed')));
		} elseif ($poll['status'] !== 2) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} elseif (count($poll['choices']) >= $configs['limits']['pollChoices']) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} else {
			$db->rset("{$user_id}-MP-action", "append-{$poll['id']}", (60 * 5));
			$bot->sendMessage($chat_id, $langs->getTranslate('appendSendMe', [htmlspecialchars($poll['title']), $configs['limits']['pollChoice']]));
		}
		die;
	} elseif (strpos($command, "start comment_") === 0) {
		# Board comment
		$e = explode("_", str_replace("start ", '', $command));
		$poll = $mp->getPoll($e[1]);
		if ($db->isBanned($user_id, $poll['owner_id'], $poll['id'])) {
			$mp->addBoardComment($poll['id'], $user['id'], $user['name']);
			$bot->sendMessage($chat_id, bold($langs->getTranslate('voteNotAllowed')));
		} elseif ($poll['status'] !== 2) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} else {
			$db->rset("{$user_id}-MP-action", "comment-{$poll['id']}", (60 * 5));
			$bot->sendMessage($chat_id, $langs->getTranslate('boardComment', [htmlspecialchars($poll['title']), $configs['limits']['boardComments']]));
			$mp->addBoardComment($poll['id'], $user['id'], $user['name']);
		}
		die;
	} elseif (strpos($command, "delete_") === 0) {
		$e = explode("_", $command);
		if (isset($e[1]) and isset($e[2])) {
			$poll = $mp->getPoll($e[1], ['*', 'votes']);
			if (!$poll['status']) {
				$bot->sendMessage($chat_id, $langs->getTranslate('pollDeleted'));
				die;
			} elseif (in_array($poll['type'], range(0, 3))) {
				if ($poll['type'] == 3) {
					$choice = $db->query("SELECT * FROM choices WHERE id = ?", [$e[2]], true);
					if ($choice['poll_id'] == $e[1]) {
						$db->query("DELETE FROM choices WHERE id = ?", [$e[2]], false);
						$text = $langs->getTranslate('boardCommentDeleted', [htmlspecialchars($choice['comment']), htmlspecialchars($poll['title'])]);
						$poll['votes'] = $mp->getVotes($e[1]);
					} else {
						$text = $langs->getTranslate('boardCommentNotFound');
					}
				} else {
					if (isset($poll['choices'][$e[2]])) {
						if (count($poll['choices']) <= 2) {
							$text = $langs->getTranslate('pollOptionsFew');
						} else {
							$text = $langs->getTranslate('pollOptionDeleted', [htmlspecialchars($poll['choices'][$e[2]]), htmlspecialchars($poll['title'])]);
							unset($poll['choices'][$e[2]]);
							$db->query("UPDATE polls SET choices = ? WHERE id = ?", [json_encode($poll['choices']), $e[1]], false);
							$db->query("DELETE FROM choices WHERE poll_id = ? and choice_id = ?", [$e[1], $e[2]], false);
						}
					} else {
						$text = $langs->getTranslate('pollOptionNotFound');
					}
				}
				$bot->sendMessage($chat_id, $text);
				$mp->updatePollMessages($poll);
				die;
			}
		}
	} elseif (strpos($command, "ban ") === 0) {
		# Ban users in the general Blacklist
		$e = explode(" ", str_replace("@", '', str_replace("ban ", '', $command)), 2);
		$id = $e[0];
		if (is_numeric($id)) {
			$banned = $db->getUser(['id' => round($id)]);
		} else {
			$banned = $db->getUser(['username' => $id]);
		}
		if (isset($banned['id']) and $banned['id'] == $user_id) {
			$text = "🥺 " . $langs->getTranslate('blacklistAddMe');
		} elseif (isset($banned['name'])) {
			if (strpos($e[1], "for ") === 0) {
				$datetime = new DateTime();
				$strtime = strtotime(str_replace("for ", '', $e[1]));
				if (is_numeric($strtime) and $strtime >= time()) {
					$datetime->setTimestamp($strtime + $bantime);
					if (!isset($db->query("SELECT id FROM blacklists WHERE poll_id = ? and user_id = ? LIMIT 1", [0, $user['id']])['id'])) {
						$db->query("INSERT INTO blacklists (user_id) VALUES (?)", [$user['id']]);
					}
					$dateban = $datetime->getTimestamp();
					if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
						$text = $langs->getTranslate('blacklistAlreadyAdded');
					} else {
						$db->query("UPDATE blacklists SET users = users::jsonb - ? || ? WHERE user_id = ? and poll_id = ?", [$banned['id'], json_encode([$banned['id'] => $dateban]), $user['id'], 0]);
						date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
						$bandatetime = date_format($datetime, $user['settings']['date_format']);
						$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
						$text = $langs->getTranslate('blacklistAddUser', [$tag, $bandatetime]);
					}
				} else {
					$bandatetime = date($user['settings']['date_format'], $datetime->getTimestamp() + $bantime);
					$text = $langs->getTranslate('blacklistAddInvalidTime', [$bandatetime]);
				}
			} else {
				if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
					$text = $langs->getTranslate('blacklistAlreadyAdded');
				} else {
					$bandatetime = $langs->getTranslate('indefinitelyTime');
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || ? WHERE user_id = ? and poll_id = ?", [$banned['id'], json_encode([$banned['id'] => 0]), $user['id'], 0]);
					$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
					$text = $langs->getTranslate('blacklistAddUser', [$tag, $bandatetime]);
				}
			}
		} else {
			$text = $langs->getTranslate('userNotFound');
		}
		$bot->sendMessage($chat_id, $text);
		die;
	} elseif (strpos($command, "unban ") === 0) {
		# Unban users in the general Blacklist
		$id = str_replace("@", '', str_replace("unban ", '', $command));
		if (is_numeric($id)) {
			$banned = $db->getUser(['id' => round($id)]);
		} else {
			$banned = $db->getUser(['username' => $id]);
		}
		if (isset($banned['name'])) {
			if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
				$db->query("UPDATE blacklists SET users = users::jsonb - ? || '{}'::jsonb WHERE user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0]);
				$bandatetime = date($user['settings']['date_format'], $dateban);
				$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
				$text = $langs->getTranslate('blacklistRemoved', [$tag]);
			} else {
				$text = $langs->getTranslate('blacklistNotAdded');
			}
		} else {
			$text = $langs->getTranslate('userNotFound');
		}
		$bot->sendMessage($chat_id, $text);
		die;
	} elseif ($command == "list") {
		# List all user polls
		$polls = $mp->getPollsList($user_id, 0, 512);
		$text = $langs->getTranslate('listYourPolls');
		if (!empty($polls)) {
			foreach ($polls as $poll) {
				if (strlen($poll['title']) > 20) $poll['title'] = substr($poll['title'], 0, 17) . "...";
				$text .= "\n\n" . $mp->getTypeEmoji($poll['type']) . " " . $bf->bold($poll['title']) . "\n -> /{$poll['poll_id']}";
			}
		} else {
			$text .= "\n\n" . $langs->getTranslate('listEmpty');
		}
		$bot->sendMessage($chat_id, $text);
		die;
	} elseif (is_numeric($command)) {
		# Poll command with user's ID (Example: /45)
		$poll = $mp->getUserPoll($user_id, $command);
		$message = [];
		$message['reply_markup'] = false;
		if (!is_numeric($poll['status'])) {
			$message['text'] = $langs->getTranslate('pollDoesntExist');
		} elseif ($poll['status'] !== 0) {
			$polloptions = ['language' => $user['lang'], 'isPollOwner' => true];
			$message = $mp->createPollMessage($poll, $polloptions);
		} else {
			$message['text'] = $langs->getTranslate('pollDeleted');
		}
		$send = $bot->sendMessage($chat_id, $message['text'], $message['reply_markup'], 'def', $message['disable_web_page_prevew'], true);
		die;
	} else {
		$bot->sendMessage($chat_id, $langs->getTranslate('unrecognizedCommand'));
		die;
	}
}

# All message action
if ($action = $db->rget("{$user_id}-MP-action")) {
	if (strpos($action, "append-") === 0) {
		$poll = $mp->getPoll(str_replace("append-", '', $action), ['id', 'owner_id', 'poll_id', 'status', 'title', 'settings', 'choices']);
		if ($poll['status'] !== 2 or !$poll['settings']['options']['0-0']) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} elseif (count($poll['choices']) >= $configs['limits']['pollChoices']) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} else {
			if (strlen($message) <= $configs['limits']['pollChoice']) {
				if (in_array($message, $poll['choices'])) {
					$bot->sendMessage($chat_id, $langs->getTranslate('optionAlreadyExists'));
				} else {
					if ($poll['settings']['options']['3-1'] and isset($update['message']['entities'])) {
						foreach ($update['message']['entities'] as $entity) {
							if ($entity['type'] == "url" or $entity['type'] == "mention") {
								$bot->sendMessage($chat_id, $bf->bold($langs->getTranslate('antiSpamDetection')));
								die;
							}
						}
					}
					$db->rdel("{$user_id}-MP-action");
					$t = $mp->addPollChoice($poll['id'], $message);
					$bot->sendMessage($chat_id, $langs->getTranslate('addOptionSuccess', [htmlspecialchars($message), htmlspecialchars($poll['title'])]));
					if ($poll['settings']['options']['3-0']) {
						if ($poll['anonymous']) {
							$from = "👤";
						} else {
							$from = $user['name'];
						}
						$amenu[][] = [
							'text' => $mp->getOptionEmoji(3,2) . " " . $langs->getTranslate('optionsModerate'),
							'callback_data' => "myPoll_{$poll['id']}_6_3_2"
						];
						if ($user_id !== $poll['owner_id']) $bot->sendMessage($poll['owner_id'], $langs->getTranslate('notificationAppend', [htmlspecialchars($poll['title']), $poll['poll_id'], htmlspecialchars($from), htmlspecialchars($message)]), $amenu);
					}
					$poll = $mp->getPoll($poll['id'], ['*', 'votes']);
					$mp->updatePollMessages($poll);
				}
			} else {
				$bot->sendMessage($chat_id, $langs->getTranslate('tooManyCharacters', [strlen($message), $configs['limits']['pollChoice']]));
			}
		}
		die;
	} elseif (strpos($action, "comment-") === 0) {
		$poll = $mp->getPoll(str_replace("comment-", '', $action), ['*']);
		if ($poll['status'] !== 2) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollClosed'));
		} else {
			if (strlen($message) <= $configs['limits']['boardComments']) {
				if ($poll['settings']['options']['3-3']) {
					$poll['settings']['options']['3-3'] = json_decode($poll['settings']['options']['3-3'], true);
					foreach ($poll['settings']['options']['3-3'] as $word) {
						if (strpos($message, $word) !== false) {
							$bot->sendMessage($chat_id, $bf->bold($langs->getTranslate('forbiddenWordDetection')));
							die;
						}
					}
				}
				if ($poll['settings']['options']['3-1'] and isset($update['message']['entities'])) {
					foreach ($update['message']['entities'] as $entity) {
						if ($entity['type'] == "url" or $entity['type'] == "mention") {
							$bot->sendMessage($chat_id, $bf->bold($langs->getTranslate('antiSpamDetection')));
							die;
						}
					}
				}
				$db->rdel("{$user_id}-MP-action");
				$mp->addBoardComment($poll['id'], $user['id'], $user['name'], $message);
				$bot->sendMessage($chat_id, $langs->getTranslate('boardSuccess', [htmlspecialchars($message), htmlspecialchars($poll['title'])]));
				if ($poll['settings']['options']['3-0']) {
					if ($poll['anonymous']) {
						$from = "👤";
					} else {
						$from = $user['name'];
					}
					if ($user_id !== $poll['owner_id']) $bot->sendMessage($poll['owner_id'], $langs->getTranslate('notificationBoard', [htmlspecialchars($poll['title']), $poll['poll_id'], htmlspecialchars($from), htmlspecialchars($message)]));
				}
				$poll = $mp->getPoll($poll['id'], ['*', 'votes']);
				$mp->updatePollMessages($poll);
			} else {
				$bot->sendMessage($chat_id, $langs->getTranslate('tooManyCharacters', [strlen($message), $configs['limits']['boardComments']]));
			}
		}
		die;
	} elseif ($action == "createpoll") {
		$rpty = $db->rget("{$user_id}-ptype");
		if (key_exists($rpty, $mp->getTypes())) {
			if ($db->rget("{$user_id}-anon")) {
				$pollAnonymous = true;
			} else {
				$pollAnonymous = false;
			}
			$pollType = $db->rget("{$user_id}-ptype");
			if (!is_numeric($pollType)) {
				$db->rset("{$user_id}-ptype", 0);
				$pollType = 0;
			} elseif ($pollType == 5) {
				$pollAnonymous = false;
			}
			$title = $db->rget("{$user_id}-ptitle");
			$description = $db->rget("{$user_id}-pdesc");
			$choices = $db->rgetList("{$user_id}-pchoices", 0, 25);
			$choices = array_reverse($choices);
			if (in_array($pollType, [2, 6])) {
				$choicesDone = $db->rget("{$user_id}-pchoicesDone");
			}
			if (!$title) {
				$strlen = strlen($message);
				$limit = $configs['limits']['pollTitle'];
				if (!is_numeric($strlen)) {
					$text = $langs->getTranslate('tooManyCharacters', [0, $limit]);
					$die = true;
				} elseif ($strlen <= 0 or $strlen > $limit) {
					$text = $langs->getTranslate('tooManyCharacters', [$strlen, $limit]);
					$die = true;
				} else {
					$text = $langs->getTranslate('creatingPoll', [htmlspecialchars($message)]);
					$db->rset("{$user_id}-ptitle", $message);
					$menu[] = [
						[
							"text" => $langs->getTranslate('skipThisStep'),
							"callback_data" => "skip"
						]
					];
					$die = true;
				}
			} elseif (!$description) {
				if ($callback_data == "skip") {
					$description = false;
					$db->rset("{$user_id}-pdesc", 'MasterPollNull');
					if (in_array($pollType, [0, 1, 2, 6])) {
						$die = true;
						$text = $langs->getTranslate('creatingPollWannaAddDescription', [$title]);
						$menu[][] = [
							'text' => $langs->getTranslate('addDescription'),
							'callback_data' => "not-skip"
						];
					} elseif (in_array($pollType, [4])) {
						$die = true;
						$text = $langs->getTranslate('ratingEnterMaxValue');
					}
				} elseif ($callback_data == "not-skip") {
					$die = true;
					$db->rdel("{$user_id}-pdesc");
					$text = $langs->getTranslate('creatingPoll', [$title]);
					$menu[][] = [
						'text' => $langs->getTranslate('skipThisStep'),
						'callback_data' => "skip"
					];
				} elseif ($message) {
					$strlen = strlen($message);
					$limit = $configs['limits']['pollDescription'];
					if (!is_numeric($strlen)) {
						$text = $langs->getTranslate('tooManyCharacters', [0, $limit]);
						$die = true;
					} elseif ($strlen <= 0 or $strlen > $limit) {
						$text = $langs->getTranslate('tooManyCharacters', [$strlen, $limit]);
						$die = true;
					} else {
						if ($pollType == 3) {
							$ttype = "Board";
						} elseif ($pollType == 4) {
							$ttype = "Rating";
						} elseif ($pollType == 5) {
							$ttype = "Participation";
						} else {
							$ttype = "Poll";
						}
						$description = $message;
						$text = $langs->getTranslate('create' . $ttype, [htmlspecialchars($message)]);
						if (in_array($pollType, [0, 1, 2, 4, 6])) {
							$db->rset("{$user_id}-pdesc", $message);
							if (in_array($pollType, [4])) {
								$text = $langs->getTranslate('ratingEnterMaxValue');
							}
							$die = true;
						}
					}
				} else {
					die;
				}
			} elseif (in_array($pollType, [0, 1, 2, 6]) and !$choicesDone) {
				if ($callback_data == "done") {
					if (empty($choices) or count($choices) <= 1) {
						$die = true;
						$cbtext = $langs->getTranslate('needMoreThenOneOption');
					} elseif (in_array($pollType, [2])) {
						$die = true;
						$db->rset("{$user_id}-pchoicesDone", true);
						$text = $langs->getTranslate('limitedDoodleEnterMaxVotes');
					} elseif (in_array($pollType, [6])) {
						$die = true;
						$db->rset("{$user_id}-pchoicesDone", true);
						$choiceID = 0;
						foreach ($choices as $choice) {
							$menu[][] = [
								'text' => $choice,
								'callback_data' => "quizSelect{$choiceID}"
							];
							$choiceID += 1;
						}
						$text = $langs->getTranslate('quizEnterRightResponse');
					}
				} elseif ($message) {
					$strlen = strlen($message);
					$limit = $configs['limits']['pollChoice'];
					$die = true;
					if (!is_numeric($strlen)) {
						$text = $langs->getTranslate('tooManyCharacters', [0, $limit]);
					} elseif ($strlen <= 0 or $strlen > $limit) {
						$text = $langs->getTranslate('tooManyCharacters', [$strlen, $limit]);
					} else {
						$menu[][] = [
							'text' => "💾 " . $langs->getTranslate('done'),
							'callback_data' => "done"
						];
						if (count($choices) >= $configs['limits']['pollChoices']) {
							$choices[] = $message;
							$die = false;
							unset($menu);
						} elseif (!in_array($message, $choices)) {
							$db->rlistAdd("{$user_id}-pchoices", $message);
							$text = $langs->getTranslate('addedToPoll', [htmlspecialchars($message), htmlspecialchars($title)]);
						} else {
							$text = $langs->getTranslate('alreadyDefined', [htmlspecialchars($message)]);
						}
					}
				} else {
					die;
				}
			} elseif (in_array($pollType, [2])) {
				if (isset($message)) {
					$die = true;
					$limit = count($choices);
					if (is_numeric($message)) {
						$message = round(str_replace('-', '', $message));
						if ($message and $message > 0 and $limit > $message) {
							$die = false;
						} else {
							$text = $langs->getTranslate('numericInputRequest', [0, $limit]);
						}
					} else {
						$text = $langs->getTranslate('numericInputRequest', [0, $limit]);
					}
				} else {
					die;
				}
			} elseif (in_array($pollType, [4])) {
				if (isset($message)) {
					$die = true;
					$limit = $configs['limits']['ratingMaxLimit'] + 1;
					if (is_numeric($message)) {
						$message = round(str_replace('-', '', $message));
						if ($message and $message > 1 and $limit > $message) {
							$die = false;
						} else {
							$text = $langs->getTranslate('numericInputRequest', [1, $limit]);
						}
					} else {
						$text = $langs->getTranslate('numericInputRequest', [1, $limit]);
					}
				} else {
					die;
				}
			} elseif (in_array($pollType, [6])) {
				if (strpos($callback_data, 'quizSelect') === 0) {
					$choice = round(str_replace('quizSelect', '', $callback_data));
				} elseif (isset($message)) {
					$die = true;
					$text = $langs->getTranslate('quizEnterRightResponse');
					$choiceID = 0;
					foreach ($choices as $choice) {
						$menu[][] = [
							'text' => $choice,
							'callback_data' => "quizSelect{$choiceID}"
						];
						$choiceID += 1;
					}
				} else {
					die;
				}
			}
			if (!$die) {
				$pollMessage['disable_web_page_prevew'] = true;
				if ($description == "MasterPollNull") unset($description);
				if ($pollType == 0) {
					# Vote
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous, $choices);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 1) {
					# Doodle
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous, $choices);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 2) {
					# Limited Doodle
					$settings = ['limitedDoodleLimit' => $message];
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous, $choices, $settings);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 3) {
					# Board
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 4) {
					# Rating
					$choices = range(1, $message);
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous, $choices);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 5) {
					# Participation
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} elseif ($pollType == 6) {
					# Quiz
					$settings = ['quizResponse' => $choice];
					$poll = $mp->createPoll($user_id, $title, $description, $pollType, $pollAnonymous, $choices, $settings);
					$cbtext = $langs->getTranslate('finishedPollCreation');
					$pollMessage = $mp->createPollMessage($poll, ['language' => $user['lang'], 'isPollOwner' => true]);
					$db->rdel("{$user_id}-MP-action");
				} else {
					# Unknown or unsupported
					die;
				}
				if ($pollMessage['text']) {
					$text = $pollMessage['text'];
				} else {
					$text = $langs->getTranslate('generalError');
				}
				if (isset($pollMessage['reply_markup'])) {
					$menu = $pollMessage['reply_markup'];
				}
				if (!isset($pollMessage['disable_web_page_prevew'])) {
					$pollMessage['disable_web_page_prevew'] = true;
				}
				$fw = true;
			}
			if ($callback_data) {
				if ($fw) {
					$bot->deleteMessage($chat_id, $update['callback_query']['message']['message_id']);
					$bot->sendMessage($chat_id, $text, $menu, 'def', $pollMessage['disable_web_page_prevew']);
				} else {
					$bot->editMessageText($chat_id, $text, $menu, $update['callback_query']['message']['message_id'], 'def', $pollMessage['disable_web_page_prevew']);
				}
				$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext);
			} else {
				$bot->sendMessage($chat_id, $text, $menu, 'def', $pollMessage['disable_web_page_prevew']);
			}
		} else {
			$bot->sendMessage($chat_id, $bf->bold($langs->getTranslate('generalError')));
		}
		die;
	} elseif (strpos($action, "settings-") === 0) {
		$e = explode("-", $action);
		$poll = $mp->getPoll($e[1], ['*', 'votes']);
		if (!$poll['status']) {
			$db->rdel("{$user_id}-MP-action");
			$bot->sendMessage($chat_id, $langs->getTranslate('pollDeleted'));
		} elseif ($e[2] == 0 and $e[3] == 5) {
			if ($poll['votes']) {
				$votes = count($poll['votes']);
			} else {
				$votes = 0;
			}
			if (is_numeric($message) and $message < $configs['limits']['pollVoters'] and $message > $votes) {
				$db->rdel("{$user_id}-MP-action");
				$mp->setPollOption($poll, 0, 5, round($message));
				$menu[][] = [
					'text' => $langs->getTranslate('backButton'),
					'callback_data' => "myPoll_{$e[1]}_6_0_5"
				];
				$bot->sendMessage($chat_id, "✅ " . $langs->getTranslate('done'), $menu);
				$poll = $mp->getPoll($poll['id'], ['*', 'votes']);
				$mp->updatePollMessages($poll);
			} else {
				$bot->sendMessage($chat_id, $langs->getTranslate('numericInputRequest', [$votes, $configs['limits']['pollVoters']]));
			}
		} elseif ($e[2] == 1 and $e[3] == 2) {
			if ($update['message']['photo']) {
				foreach ($update['message']['photo'] as $photo) {
					if ($photo['file_size'] < 5242880) {
						$that = $photo['file_id'];
					}
				}
				$url = $bot->uploadMedia($that, 'photo');
			} elseif ($update['message']['video']) {
				if ($update['message']['animation']['file_size'] < 5242880) {
					$url = $bot->uploadMedia($update['message']['video']['file_id'], 'video');
				}
			} elseif ($update['message']['animation']) {
				if ($update['message']['animation']['file_size'] < 5242880) {
					$url = $bot->uploadMedia($update['message']['animation']['file_id'], 'video');
				}
			} elseif ($message and isset($update['message']['entities'])) {
				if ($update['message']['entities'][0]['type'] == 'url' and $update['message']['entities'][0]['offset'] === 0) {
					$url = substr($message, 0, $update['message']['entities'][0]['length']);
				}
			}
			if ($url) {
				$db->rdel("{$user_id}-MP-action");
				$poll = $mp->setPollOption($poll, 1, 1, true);
				$poll = $mp->setPollOption($poll, 1, 2, $url);
				$menu[][] = [
					'text' => $langs->getTranslate('backButton'),
					'callback_data' => "myPoll_{$e[1]}_6_1"
				];
				$text = "✅ " . $langs->getTranslate('done');
			} else {
				$text = $langs->getTranslate('attachWebDocumentPreview');
			}
			$bot->sendMessage($chat_id, $text, $menu);
		} elseif ($e[2] == 1 and $e[3] == 4) {
			$etype = [
				"Title",
				"Description"
			];
			$limit = $configs['limits']['poll' . $etype[$e[4]]];
			$strlen = strlen($message);
			if (!is_numeric($strlen)) {
				$text = $langs->getTranslate('tooManyCharacters', [0, $limit]);
			} elseif ($strlen <= 0 or $strlen > $limit) {
				$text = $langs->getTranslate('tooManyCharacters', [$strlen, $limit]);
			} else {
				$db->query("UPDATE polls SET " . strtolower($etype[$e[4]]) . " = ? WHERE id = ?", [$message, $poll['id']]);
				$text = "✅ " . $langs->getTranslate('done');
				$menu[][] = [
					'text' => $langs->getTranslate('backButton'),
					'callback_data' => "myPoll_{$e[1]}_6_1_4"
				];
			}
			$bot->sendMessage($chat_id, $text, $menu);
		} elseif ($e[2] == 3 and $e[3] == 3) {
			$limit = $configs['limits']['pollChoice'];
			$strlen = strlen($message);
			if (!is_numeric($strlen)) {
				$text = $langs->getTranslate('tooManyCharacters', [0, $limit]);
			} elseif ($strlen <= 0 or $strlen > $limit) {
				$text = $langs->getTranslate('tooManyCharacters', [$strlen, $limit]);
			} else {
				$menu[][] = [
					'text' => "💾 " . $langs->getTranslate('done'),
					'callback_data' => "myPoll_{$e[1]}_6_3_3"
				];
				$word = strtolower($message);
				$poll['settings']['options']['3-3'] = json_decode($poll['settings']['options']['3-3'], true);
				if ($e[4]) {
					if (!empty($poll['settings']['options']['3-3']) and in_array($word, $poll['settings']['options']['3-3'])) {
						$text = $langs->getTranslate('forbiddenWordsRemoved');
						$poll['settings']['options']['3-3'] = array_diff($poll['settings']['options']['3-3'], [$word]);
					} else {
						$text = $langs->getTranslate('forbiddenWordsAlreadyRemoved');
					}
				} else {
					if (!is_array($poll['settings']['options']['3-3']) or !in_array($word, $poll['settings']['options']['3-3'])) {
						$text = $langs->getTranslate('forbiddenWordsAdded');
						$poll['settings']['options']['3-3'][] = $word;
					} else {
						$text = $langs->getTranslate('forbiddenWordsAlreadyAdded');
					}
				}
				$poll = $mp->setPollOption($poll, 3, 3, $poll['settings']['options']['3-3']);
			}
			$bot->sendMessage($chat_id, $text, $menu);
		}
		die;
	} elseif ($action == "bl-add") {
		if (!isset($db->query("SELECT id FROM blacklists WHERE poll_id = ? and user_id = ? LIMIT 1", [0, $user['id']])['id'])) {
			$db->query("INSERT INTO blacklists (user_id) VALUES (?)", [$user['id']]);
		}
		if ($update['message']['forward_sender_name']) {
			$text = $langs->getTranslate('forwardNamePrivate');
		} elseif ($update['message']['forward_from']['id']) {
			$banned = $db->getUser($update['message']['forward_from']);
			if (isset($banned['id']) and $banned['id'] == $user_id) {
				$text = "🥺 " . $langs->getTranslate('blacklistAddMe');
			} elseif ($banned['id']) {
				if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
					$text = $langs->getTranslate('blacklistAlreadyAdded');
				} else {
					$bandatetime = $langs->getTranslate('indefinitelyTime');
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || ? WHERE user_id = ? and poll_id = ?", [$banned['id'], json_encode([$banned['id'] => 0]), $user['id'], 0]);
					$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
					$text = $langs->getTranslate('blacklistAddUser', [$tag, $bandatetime]);
					$menu[] = [
						[
							"text" => $langs->getTranslate('backButton'),
							"callback_data" => "cancel-settings-manageBlacklist"
						]
					];
				}
			} else {
				$text = $langs->getTranslate('userNotFound');
			}
		} else {
			if (is_numeric($message)) {
				$banned = $db->getUser(['id' => round($message)]);
			} else {
				$banned = $db->getUser(['username' => str_replace("@", '', $message)]);
			}
			if (isset($banned['id']) and $banned['id'] == $user_id) {
				$text = "🥺 " . $langs->getTranslate('blacklistAddMe');
			} elseif ($banned['id']) {
				if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
					$text = $langs->getTranslate('blacklistAlreadyAdded');
				} else {
					$bandatetime = $langs->getTranslate('indefinitelyTime');
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || ? WHERE user_id = ? and poll_id = ?", [$banned['id'], json_encode([$banned['id'] => 0]), $user['id'], 0]);
					$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
					$text = $langs->getTranslate('blacklistAddUser', [$tag, $bandatetime]);
					$menu[] = [
						[
							"text" => $langs->getTranslate('backButton'),
							"callback_data" => "cancel-settings-manageBlacklist"
						]
					];
				}
			} else {
				$text = $langs->getTranslate('userNotFound');
			}
		}
		$bot->sendMessage($chat_id, $text, $menu);
		die;
	} elseif ($action == "bl-remove") {
		if ($update['message']['forward_sender_name']) {
			$text = $langs->getTranslate('forwardNamePrivate');
		} elseif ($update['message']['forward_from']['id']) {
			$banned = $db->getUser($update['message']['forward_from']);
			if ($banned['id']) {
				if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || '{}'::jsonb WHERE user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0]);
					$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
					$text = $langs->getTranslate('blacklistRemoved', [$tag]);
					$menu[] = [
						[
							"text" => $langs->getTranslate('backButton'),
							"callback_data" => "cancel-settings-manageBlacklist"
						]
					];
				} else {
					$text = $langs->getTranslate('blacklistNotAdded');
				}
			} else {
				$text = $langs->getTranslate('userNotFound');
			}
		} else {
			if (is_numeric($message)) {
				$banned = $db->getUser(['id' => round($message)]);
			} else {
				$banned = $db->getUser(['username' => str_replace("@", '', $message)]);
			}
			if ($banned['id']) {
				if ($r = $db->query("SELECT * FROM blacklists WHERE users::jsonb->? NOTNULL and user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0])) {
					$db->query("UPDATE blacklists SET users = users::jsonb - ? || '{}'::jsonb WHERE user_id = ? and poll_id = ?", [$banned['id'], $user['id'], 0]);
					$tag = $bf->tag($banned['id'], $banned['name'], $banned['surname']);
					$text = $langs->getTranslate('blacklistRemoved', [$tag]);
					$menu[] = [
						[
							"text" => $langs->getTranslate('backButton'),
							"callback_data" => "cancel-settings-manageBlacklist"
						]
					];
				} else {
					$text = $langs->getTranslate('blacklistNotAdded');
				}
			} else {
				$text = $langs->getTranslate('userNotFound');
			}
		}
		$bot->sendMessage($chat_id, $text, $menu);
		die;
	} elseif (strpos($action, "sendPollToChat_") === 0) {
		$e = explode("_", $action);
		$id = $e[1];
		if ($update['message']['forward_from_chat']['id']) {
			if ($update['message']['forward_from_chat']['type'] == "channel") {
				$chat = $db->getChannel($update['message']['forward_from_chat']);
			} elseif (in_array($update['message']['forward_from_chat']['type'], ['group', 'supergroup'])) {
				$chat = $db->getGroup($update['message']['forward_from_chat']);
			}
		} else {
			$message = str_replace("@", '', $message);
			$chat = $db->getGroup(['id' => round($message), 'username' => $message]);
			if (!isset($chat['id'])) {
				$chat = $db->getChannel(['id' => round($message), 'username' => $message]);
			}
		}
		if (isset($chat['id'])) {
			$getBotMember = $bot->getChatMember($chat['id'], $bot->getID());
			if ($getBotMember['ok']) {
				if (in_array($getBotMember['result']['status'], ['administrator', 'member'])) {
					$getMember = $bot->getChatMember($chat['id'], $user_id);
					if ($getMember['ok']) {
						if (in_array($getMember['result']['status'], ['administrator', 'creator'])) {
							$menu[] = [
								[
									'text' => "📩 " . $langs->getTranslate('pollSendNow'),
									'callback_data' => "myPoll_{$id}_5_{$chat['id']}_0"
								],
								[
									'text' => "📆 " . $langs->getTranslate('pollSendSchedule'),
									'callback_data' => "myPoll_{$id}_5_{$chat['id']}_1"
								]
							];
							$menu[][] = [
								'text' => $langs->getTranslate('backButton'),
								'callback_data' => "myPoll_{$id}_5"
							];
							$text = $langs->getTranslate('pollSendTime', [htmlspecialchars($chat['title'])]);
						} else {
							$text = "🚫 " . $langs->getTranslate('chatInaccessible');
						}
					} else {
						$text = "🚫 " . $langs->getTranslate('chatInaccessible');
					}
				} else {
					$text = "🚫 " . $langs->getTranslate('chatInaccessible');
				}
			} else {
				$text = "🚫 " . $langs->getTranslate('chatInaccessible');
			}
		} else {
			$text = $langs->getTranslate('chatNotFound');
		}
		$bot->sendMessage($chat_id, $text, $menu);
		die;
	}
}

# Unrecognized message type
if (isset($callback_data)) {
	if ($callback_data == 'done') {
		$cbtext = $langs->getTranslate('noPollsToFinish');
	}
	$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext, true);
} elseif (isset($message)) {
	$bot->sendMessage($chat_id, $langs->getTranslate('wannaCreate'));
} else {
	die;
}

?>
