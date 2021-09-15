<?php

class MasterPoll
{
	public $standard = [
		'pollTypes' => [
			0 => 'Vote',
			1 => 'Doodle',
			2 => 'Limited Doodle',
			3 => 'Board',
			4 => 'Rating',
			5 => 'Participation',
			6 => 'Quiz'
		],
		'pollAnonymous' => [
			0 => "Personal",
			1 => "Anonymous"
		],
		'pollStatus' => [
			0 => "Deleted",
			1 => "Closed",
			2 => "Open"
		]
	];
	
	function __construct ($bf) {
		$this->bf = $bf;
	}

	# Get Status text by ID
	public function getStatus () {
		return $this->standard['pollStatus'];
	}

	# Get Poll Type text by ID
	public function getTypes () {
		return $this->standard['pollTypes'];
	}

	# Get Available Options text by ID
	public function getOptions () {
		require_once('./commands/options.php');
		return $this->options = $options;
	}

	# Get Option Status
	public function getOptionStatus ($poll, $optionID, $subOptionID = false) {
		global $db;
		global $user;
		global $langs;
		
		if ($optionID == 2) {
			if ($subOptionID == 0) {
				return $this->getButtons($langs->getTranslate('title'), 50, $poll['settings']['options']["$optionID-$subOptionID"]);
			} elseif ($subOptionID == 1) {
				//return $this->createBars(10, $poll['settings']['options']["$optionID-$subOptionID"]);
			} elseif ($subOptionID == 2) {
				return $this->createBars(10, $poll['settings']['options']["$optionID-$subOptionID"]);
			}
		} elseif (isset($poll['settings']['options'])) {
			if (in_array($this->options[$optionID]['subOptions'][$subOptionID]['type'], ['bool', 'text'])) {
				if ($poll['settings']['options']["$optionID-$subOptionID"]) {
					return "✅";
				} else {
					return "❌";
				}
			} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'time') {
				if ($optionID == 0 and in_array($subOptionID, [3, 4])) {
					$crontype = [
						3 => 2,
						4 => 1
					];
				} else {
					return "❌";
				}
				$datetime = new DateTime('now', (new DateTimeZone($user['settings']['timezone'])));
				$cron = $db->query("SELECT time FROM crontabs WHERE poll_id = ? and type = ? LIMIT 1", [$poll['id'], $crontype[$subOptionID]], true);
				if (isset($cron['time'])) {
					$datetime->setTimestamp($cron['time']);
					return "⏳ " . date_format($datetime, $user['settings']['date_format']);
				} else {
					return "❌";
				}
			} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'numeric') {
				if (isset($poll['settings']['options']["$optionID-$subOptionID"])) {
					return $poll['settings']['options']["$optionID-$subOptionID"];
				} else {
					return "❌";
				}
			}
		}
		if ($emoji = $this->getOptionEmoji($optionID, $subOptionID)) return $emoji;
		return "❌";
	}

	# Get poll Anonymous status text by ID
	public function getAnonymous ($anonymous) {
		if ($anonymous) {
			return 'Anonymous';
		} else {
			return 'Personal';
		}
	}

	# Get polls List by user ID
	public function getPollsList ($owner, $offset = 0, $length = 5, $onlyopen = '') {
		global $db;
		
		if (is_numeric($owner)) {
			$result = $db->query('SELECT id, poll_id, status, title, type, anonymous FROM polls WHERE status != ? and owner_id = ?' . $onlyopen . ' ORDER BY id DESC OFFSET ? LIMIT ?', [0, $owner, $offset, $length], 'fetch');
			if (!isset($result[0]['id'])) return [];
			return $result;
		} else {
			return [];
		}
	}

	# Get poll by ID
	public function getPoll ($id, $show = ['*']) {
		global $db;

		if (in_array('*', $show)) {
			# Get All info about this poll
			$elements = "*";
		} else {
			# Get only essential info about this poll (faster)
			$rows = array_diff($show, ['votes']);
			foreach ($rows as $element) {
				if (isset($elements)) {
					$elements .= ", $element";
				} else {
					$elements = $element;
				}
			}
		}
		$poll = $db->query("SELECT $elements FROM polls WHERE id = ? LIMIT 1", [$id], true);
		if (!isset($poll['id'])) {
			return false;
		}
		if (in_array('votes', $show)) {
			$poll['votes'] = $this->getVotes($poll['id']);
		}
		if (isset($poll['choices'])) $poll['choices'] = json_decode($poll['choices'], true);
		if (isset($poll['settings'])) $poll['settings'] = json_decode($poll['settings'], true);
		if (isset($poll['messages'])) $poll['messages'] = json_decode($poll['messages'], true);
		return $poll;
	}
	
	# Get Poll Votes by the poll ID
	public function getVotes ($id, $show = ['*']) {
		global $db;
		
		if (in_array('*', $show)) {
			# Get All info about this poll
			$elements = "*";
		} else {
			# Get only essential info about this poll (faster)
			foreach ($show as $element) {
				if (isset($elements)) {
					$elements .= ", $element";
				} else {
					$elements = $element;
				}
			}
		}
		$choices = $db->query("SELECT $elements FROM choices WHERE poll_id = ? ORDER BY choice_date DESC LIMIT 100", [$id], 'fetch');
		return $choices;
	}

	# Get Poll Voters List by the poll ID
	public function getVoters ($id, $select = 'def') {
		global $db;
		
		if ($select === 'def') {
			$select = "";
		} else {
			$select = " and choice_id = " . round($select);
		}
		$choices = $db->query("SELECT cache_name FROM choices WHERE id = ?$select", [$poll['id']], 'fetch');
		if (is_array($choices) and !empty($choices)) {
			$message['text'] = "";
			foreach ($choices as $choice) {
				$message['text'] .= "\n{$choice['cache_name']}";
			}
		} else {
			$message['text'] = "There is no voters...";
		}
		return $message;
	}

	# Get Poll ID by the user ID and private poll ID
	public function getUserPoll ($owner, $poll_id) {
		global $db;
		
		if (!is_numeric($owner)) {
			return false;
		} elseif (!is_numeric($poll_id)) {
			return false;
		}
		$q = $db->query("SELECT id FROM polls WHERE owner_id = ? and poll_id = ? LIMIT 1", [$owner, $poll_id], true);
		if (!isset($q['id'])) {
			return false;
		}
		return $this->getPoll($q['id'], ['*', 'votes']);
	}

	# Get Last Poll ID by the user ID
	public function getLastPollID ($owner, $status = 0) {
		global $db;
		
		if (is_numeric($owner)) {
			$result = $db->query("SELECT poll_id FROM polls WHERE status != ? and owner_id = ? ORDER BY poll_id DESC LIMIT 1", [$status, $owner], true);
			if (isset($result['poll_id'])) {
				return $result['poll_id'];
			} else {
				return 0;
			}
		} else {
			return false;
		}
	}

	# Get Poll Type Emoji by the type ID
	public function getTypeEmoji ($type) {
		if (!is_numeric($type)) return "🔤";
		$typesEmoji = [
			0 => "📊",
			1 => "📊",
			2 => "📊",
			3 => "📝",
			4 => "⭐️",
			5 => "🗳",
			6 => "🎲"
		];
		$emoji = $typesEmoji[round($type)];
		if (is_null($emoji)) return $typesEmoji[0];
		return $emoji;
	}

	# Get Option Emoji by the type option ID
	public function getOptionEmoji ($optionID, $subOptionID = false) {
		if (!is_numeric($optionID)) return "🔤";
		$optionsEmoji = [
			0 => [
				'emoji'	=> "👍🏻",
				0 => ['emoji' => false],
				1 => ['emoji' => false],
				2 => ['emoji' => false],
				3 => ['emoji' => "⏰"],
				4 => ['emoji' => "⏰"],
				5 => ['emoji' => "📃"],
				6 => ['emoji' => "🔄"],
				7 => ['emoji' => "🖼"],
				8 => ['emoji' => "👁‍🗨"]
			],
			1 => [
				'emoji'	=> "🛎",
				0 => ['emoji' => false],
				1 => ['emoji' => false],
				2 => ['emoji' => "🖇"],
				3 => ['emoji' => false],
				4 => ['emoji' => "✍️"],
				5 => ['emoji' => "📑"],
				6 => ['emoji' => "📤"]
			],
			2 => [
				'emoji'	=> "📲",
				0 => ['emoji' => "🔢"],
				1 => ['emoji' => "💬"],
				2 => ['emoji' => "📊"]
			],
			3 => [
				'emoji' => "⛔️",
				0 => ['emoji' => false],
				1 => ['emoji' => false],
				2 => ['emoji' => "🔨"],
				3 => ['emoji' => "#️⃣"],
			],
		];
		if ($subOptionID !== false) {
			$emoji = $optionsEmoji[round($optionID)][$subOptionID]['emoji'];
		} else {
			$emoji = $optionsEmoji[round($optionID)]['emoji'];
		}
		if (is_null($emoji)) return $optionsEmoji[0]['emoji'];
		return $emoji;
	}

	# Set poll options
	public function setPollOption ($poll, $optionID, $subOptionID, $value = null) {
		global $db;
		global $bot;
		
		if (!isset($poll['id'])) {
			return $poll;
		} elseif (!isset($poll['settings']['options'])) {
			$db->query("UPDATE polls SET settings = settings::jsonb || ?::jsonb WHERE id = ?", ['{"options":{}}', $poll['id']]);
			$poll['settings']['options'] = [];
		}
		if (!is_array($this->options)) {
			$this->getOptions();
		}
		if ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'bool') {
			if (isset($poll['settings']['options']["$optionID-$subOptionID"]) and !$value) {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_strip_nulls(jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'null'
				))::jsonb WHERE id = ?", [$poll['id']]);
				unset($poll['settings']['options']["$optionID-$subOptionID"]);
			} else {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'true'
				)::jsonb WHERE id = ?", [$poll['id']]);
				$poll['settings']['options']["$optionID-$subOptionID"] = true;
			}
		} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'time') {
			if ($value and is_numeric($value) and $value > time()) {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'true'
				)::jsonb WHERE id = ?", [$poll['id']]);
				$poll['settings']['options']["$optionID-$subOptionID"] = $value;
			} else {
				if (isset($poll['settings']['options']["$optionID-$subOptionID"])) {
					$db->query("UPDATE polls SET settings = settings::jsonb || 
					jsonb_strip_nulls(jsonb_set(
						settings::jsonb,
						'{options,$optionID-$subOptionID}',
						'null'
					))::jsonb WHERE id = ?", [$poll['id']]);
					unset($poll['settings']['options']["$optionID-$subOptionID"]);
				}
			}
		} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'array') {
			if (is_array($value)) {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'" . json_encode(json_encode($value)) . "'
				)::jsonb WHERE id = ?", [$poll['id']]);
				$poll['settings']['options']["$optionID-$subOptionID"] = $value;
			} else {
				if (isset($poll['settings']['options']["$optionID-$subOptionID"])) {
					$db->query("UPDATE polls SET settings = settings::jsonb || 
					jsonb_strip_nulls(jsonb_set(
						settings::jsonb,
						'{options,$optionID-$subOptionID}',
						'null'
					))::jsonb WHERE id = ?", [$poll['id']]);
					unset($poll['settings']['options']["$optionID-$subOptionID"]);
				}
			}
		} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'numeric') {
			if (is_numeric($value) and !is_null($value)) {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'$value'
				)::jsonb WHERE id = ?", [$poll['id']]);
				$poll['settings']['options']["$optionID-$subOptionID"] = $value;
			} else {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_strip_nulls(jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'null'
				))::jsonb WHERE id = ?", [$poll['id']]);
				unset($poll['settings']['options']["$optionID-$subOptionID"]);
			}
		} elseif ($this->options[$optionID]['subOptions'][$subOptionID]['type'] == 'text') {
			if (!is_null($value)) {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'\"$value\"'
				)::jsonb WHERE id = ?", [$poll['id']]);
				$poll['settings']['options']["$optionID-$subOptionID"] = $value;
			} else {
				$db->query("UPDATE polls SET settings = settings::jsonb || 
				jsonb_strip_nulls(jsonb_set(
					settings::jsonb,
					'{options,$optionID-$subOptionID}',
					'null'
				))::jsonb WHERE id = ?", [$poll['id']]);
				unset($poll['settings']['options']["$optionID-$subOptionID"]);
			}
		}
		return $poll;
	}

	# Set user vote on Vote type
	public function votePoll ($id, $user_id, $cache_name, $choice_id) {
		global $db;
		
		$poll = $db->query("SELECT status FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choice = $db->query("SELECT * FROM choices WHERE poll_id = ? and user_id = ? LIMIT 1", [$id, $user_id], true);
		if (isset($choice['poll_id']) and $choice['choice_id'] == $choice_id) {
			$db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], false);
			return false;
		} else {
			if (isset($choice['id'])) $db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], false);
			$db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date) VALUES (?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time()], false);
			return true;
		}
	}

	# Set user vote on Doodle type
	public function voteDoodle ($id, $user_id, $cache_name, $choice_id) {
		global $db;
		
		$poll = $db->query("SELECT status FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choice = $db->query("SELECT * FROM choices WHERE poll_id = ? and choice_id = ? and user_id = ?", [$id, $choice_id, $user_id], true);
		if (!isset($choice['poll_id'])) {
			$db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date) VALUES (?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time()], false);
			return true;
		} else {
			$db->query("DELETE FROM choices WHERE poll_id = ? and choice_id = ? and user_id = ?", [$id, $choice_id, $user_id], false);
			return false;
		}
	}

	# Set user vote on Limited Doodle type
	public function voteLimitedDoodle ($id, $user_id, $cache_name, $choice_id) {
		global $db;
		
		$poll = $db->query("SELECT status, settings FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choices = $db->query("SELECT * FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], 'fetch');
		foreach ($choices as $choice) {
			if (isset($choice['poll_id']) and $choice['choice_id'] == $choice_id) {
				$db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ? and choice_id = ?", [$id, $user_id, $choice_id], false);
				return false;
			}
		}
		$poll['settings'] = json_decode($poll['settings'], true);
		if ($poll['settings']['limitedDoodleLimit']) {
			if (empty($choices) or $poll['settings']['limitedDoodleLimit'] > count($choices)) {
				$db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date) VALUES (?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time()], false);
				return true;
			} else {
				return 'overLimit' . count($choices);
			}
		} else {
			return false;
		}
	}

	# Set user comment on Board type
	public function addBoardComment ($id, $user_id, $cache_name, $comment = false) {
		global $configs;
		global $db;
		
		$poll = $db->query("SELECT status FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choice_id = 0;
		$db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], false);
		if ($comment !== false and !empty($comment)) {
			$db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date, comment) VALUES (?,?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time(), $comment], false);
		}
		return true;
	}

	# Set user vote on Rating type
	public function voteRating ($id, $user_id, $cache_name, $choice_id) {
		global $db;
		
		$poll = $db->query("SELECT status, settings FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choice = $db->query("SELECT * FROM choices WHERE poll_id = ? and user_id = ? LIMIT 1", [$id, $user_id], true);
		if (isset($choice['id']) and $choice['choice_id'] == $choice_id) {
			$db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], false);
			return false;
		} else {
			if (isset($choice['id'])) $db->query("DELETE FROM choices WHERE poll_id = ? and user_id = ?", [$id, $user_id], false);
			$q = $db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date) VALUES (?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time()], false);
			return true;
		}
	}

	# Set user participation
	public function addParticipation ($id, $user_id, $cache_name) {
		global $db;
		
		$poll = $db->query("SELECT status FROM polls WHERE id = ?", [$id], true);
		if ($poll['status'] !== 2) {
			return [
				'error_code' => 403, 
				'error_description' => "PollNotOpen"
			];
		}
		$choice_id = 0;
		$choice = $db->query("SELECT * FROM choices WHERE poll_id = ? and choice_id = ? and user_id = ?", [$id, $choice_id, $user_id], true);
		if (!isset($choice['id'])) {
			$db->query("INSERT INTO choices (poll_id, choice_id, user_id, cache_name, choice_date) VALUES (?,?,?,?,?)", [$id, $choice_id, $user_id, $cache_name, time()], false);
			return true;
		} else {
			$db->query("DELETE FROM choices WHERE poll_id = ? and choice_id = ? and user_id = ?", [$id, $choice_id, $user_id], false);
			return false;
		}
	}

	# Create a poll
	public function createPoll ($owner, $title, $description = false, $type = 0, $anonymous = true, $choices = [], $settings = []) {
		global $db;
		
		if (!is_numeric($owner)) {
			return false;
		} elseif (!is_numeric($type)) {
			return false;
		} elseif (!in_array($type, array_keys($this->getTypes()))) {
			return false;
		} elseif (is_bool($anonymous)) {
			if ($anonymous) {
				$anonymous = 't';
			} else {
				$anonymous = 'f';
			}
		} elseif (!is_array($choices)) {
			return false;
		} elseif (!is_array($settings)) {
			return false;
		}
		$poll_id = $this->getLastPollID($owner, 9999) + 1;
		if ($type == 2 and !isset($settings['limitedDoodleLimit'])) {
			return false;
		} elseif ($type == 6 and !isset($settings['quizResponse'])) {
			return false;
		}
		if (empty($settings)) {
			$settings = '{}';
		} else {
			$settings = json_encode($settings);
		}
		$db->query("INSERT INTO polls (poll_id, owner_id, status, title, description, type, anonymous, choices, settings, creation_date, last_update) VALUES (?,?,?,?,?,?,?,?,?,?,?)", [$poll_id, $owner, 2, $title, $description, $type, $anonymous, json_encode($choices), $settings, time(), time()]);
		return $this->getUserPoll($owner, $poll_id);
	}

	# Create a poll file
	public function createFile ($id) {
		global $db;
		global $bot;
		global $user;
		
		if (!is_numeric($id)) {
			return false;
		}
		$poll = $this->getPoll($id, ['*', 'votes']);
		$array = [
			'bot'	=> $bot->getUsername(),
			'title' => $poll['title']
		];
		if ($poll['description']) $array['description'] = $poll['description'];
		$array['type'] = $this->getTypes()[$poll['type']];
		$array['anonymous'] = $this->getAnonymous($poll['anonymous']);
		$array['votes'] = [];
		$datetime = new DateTime();
		date_timezone_set($datetime, (new DateTimeZone($user['settings']['timezone'])));
		if ($poll['anonymous']) {
			if (in_array($poll['type'], [0, 1, 2, 4, 6])) {
				foreach ($poll['choices'] as $choiceID => $choice) {
					$array['votes'][$choice] = [];
					foreach ($poll['votes'] as $vote) {
						if ($vote['choice_id'] == $choiceID) {
							$datetime->setTimestamp($vote['choice_date']);
							$array['votes'][$choice][] = [
								'date'	=> date_format($datetime, $user['settings']['date_format'])
							];
						}
					}
				}
			} elseif ($poll['type'] == 3) {
				foreach ($poll['votes'] as $vote) {
					$datetime->setTimestamp($vote['choice_date']);
					$array['votes'][] = [
						'comment'	=> $vote['comment'],
						'date'		=> date_format($datetime, $user['settings']['date_format'])
					];
				}
			}
		} else {
			if (in_array($poll['type'], [0, 1, 2, 4, 6])) {
				foreach ($poll['choices'] as $choiceID => $choice) {
					$array['votes'][$choice] = [];
					foreach ($poll['votes'] as $vote) {
						if ($vote['choice_id'] == $choiceID) {
							$tusername = '';
							$tuser = $db->query('SELECT username FROM users WHERE id = ?', [$vote['user_id']]);
							if ($tuser['username']) $tusername = $tuser['username'];
							$datetime->setTimestamp($vote['choice_date']);
							$array['votes'][$choice][] = [
								'id'	=> $vote['user_id'],
								'name'	=> $vote['cache_name'],
								'username'	=> $tusername,
								'date'	=> date_format($datetime, $user['settings']['date_format'])
							];
						}
					}
				}
			} elseif ($poll['type'] == 3) {
				foreach ($poll['votes'] as $vote) {
					$tusername = '';
					$tuser = $db->query('SELECT username FROM users WHERE id = ?', [$vote['user_id']]);
					if ($tuser['username']) $tusername = $tuser['username'];
					$datetime->setTimestamp($vote['choice_date']);
					$array['votes'][] = [
						'id'		=> $vote['user_id'],
						'name'		=> $vote['cache_name'],
						'username'	=> $tusername,
						'comment'	=> $vote['comment'],
						'date'		=> date_format($datetime, $user['settings']['date_format'])
					];
				}
			} elseif ($poll['type'] == 5) {
				foreach ($poll['votes'] as $vote) {
					$tusername = '';
					$tuser = $db->query('SELECT username FROM users WHERE id = ?', [$vote['user_id']]);
					if ($tuser['username']) $tusername = $tuser['username'];
					$datetime->setTimestamp($vote['choice_date']);
					$array['votes'][] = [
						'id'		=> $vote['user_id'],
						'name'		=> $vote['cache_name'],
						'username'	=> $tusername,
						'date'		=> date_format($datetime, $user['settings']['date_format'])
					];
				}
			}
		}
		return $array;
	}

	# Get the poll message
	public function createPollMessage ($poll, $options = []) {
		global $db;
		global $langs;
		global $bot;
		
		$message['ok'] = false;
		if (!isset($poll['id'])) {
			$message['error_code'] = 404;
			$message['error_description'] = "The variable \$poll['id'] was not found";
		} elseif (!is_numeric($poll['id'])) {
			$message['error_code'] = 402;
			$message['error_description'] = "The variable \$poll['id'] must be a numeric value";
		} elseif (!isset($poll['poll_id'])) {
			$message['error_code'] = 404;
			$message['error_description'] = "The variable \$poll['poll_id'] was not found";
		} elseif (!is_numeric($poll['poll_id'])) {
			$message['error_code'] = 402;
			$message['error_description'] = "The variable \$poll['poll_id'] must be a numeric value";
		} elseif (!isset($poll['owner_id'])) {
			$message['error_code'] = 404;
			$message['error_description'] = "The variable \$poll['owner_id'] was not found";
		} elseif (!is_numeric($poll['owner_id'])) {
			$message['error_code'] = 402;
			$message['error_description'] = "The variable \$poll['owner_id'] must be a numeric value";
		} else {
			$message['ok'] = true;
			$message['text'] = "";
			if (isset($poll['settings']['options']['1-2'])) {
				$message['disable_web_page_preview'] = false;
				$message['text'] .= "<a href='" . $poll['settings']['options']['1-2'] . "'>&#8203;</>";
			} elseif (isset($poll['settings']['options']['1-1'])) {
				$message['disable_web_page_preview'] = false;
			} else {
				$message['disable_web_page_preview'] = true;
			}
			$message['text'] .= $this->getTypeEmoji($poll['type']) . " " . $this->bf->bold($poll['title']);
			if ($poll['description']) $message['text'] .= "\n" . $this->bf->textspecialchars($poll['description']);
			$message['text'] .= "\n";
			if (isset($options['language'])) {
				$lang = $options['language'];
			} elseif (isset($poll['settings']['language'])) {
				$lang = $poll['settings']['language'];
			} else {
				$lang = $db->getLanguage($poll['owner_id']);
			}
			if ($poll['type'] == 0) {
				# Vote
				if ($poll['anonymous'] or $poll['settings']['options']['0-2']) {
					foreach ($poll['choices'] as $choiceID => $choice) {
						$cvotes[$choiceID] = 0;
						$perc[$choiceID] = 0;
					}
					if (isset($poll['votes']) and !empty($poll['votes'])) {
						foreach ($poll['votes'] as $vote) {
							$cvotes[$vote['choice_id']]++;
						}
					}
					if (!empty($poll['votes'])) {
						$total = count($poll['votes']);
						foreach ($cvotes as $choiceID => $votes) {
							$perc[$choiceID] = round($votes / $total * 100);
						}
					}
					if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
						asort($cvotes, SORT_NUMERIC);
						$cvotes = array_reverse($cvotes, true);
					}
					foreach ($cvotes as $choiceID => $votes) {
						$choice = $this->getChoiceText($poll['choices'][$choiceID], $votes, $poll['settings']['options']['2-1']);
						$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
						if (isset($poll['settings']['options']['2-2']) and $perc[$poll['choices'][$choiceID]]) $message['text'] .= $this->createBars($perc[$poll['choices'][$choiceID]], $poll['settings']['options']['2-2']) . "\n";
					}
				} else {
					foreach ($poll['choices'] as $choiceID => $choice) {
						$cvotes[$choiceID] = 0;
						$perc[$choiceID] = 0;
					}
					if (isset($poll['votes']) and !empty($poll['votes'])) {
						foreach ($poll['votes'] as $vote) {
							$cvotes[$vote['choice_id']]++;
							$avotes[$vote['choice_id']][] = [
								'id'	=> $vote['user_id'],
								'name'	=> $vote['cache_name']
							];
						}
					}
					if (!empty($poll['votes'])) {
						$total = count($poll['votes']);
						foreach ($cvotes as $choiceID => $votes) {
							$perc[$choiceID] = round($votes / $total * 100);
						}
					}
					if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
						asort($cvotes, SORT_NUMERIC);
						$cvotes = array_reverse($cvotes, true);
					}
					foreach ($cvotes as $choiceID => $votes) {
						$choice = $this->getChoiceText($poll['choices'][$choiceID], $cvotes[$choiceID], $poll['settings']['options']['2-1']);
						$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
						if (isset($poll['settings']['options']['2-2']) and $perc[$choiceID]) $message['text'] .= $this->createBars($perc[$choiceID], $poll['settings']['options']['2-2']) . "\n";
						if (isset($avotes[$choiceID]) and !empty($avotes[$choiceID])) {
							for ($i = 0; $i < 10; $i ++) {
								if (isset($avotes[$choiceID][$i])) $message['text'] .= "- " . htmlspecialchars($avotes[$choiceID][$i]['name']) . "\n";
							}
						}
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$choiceID = 0;
							foreach ($poll['choices'] as $choiceID => $choice) {
								$message['reply_markup'][] = [
									[
										'text'			=> $this->getButtons($choice, $perc[$choiceID], $poll['settings']['options']['2-0']),
										'callback_data'	=> "v_{$poll['id']}_{$choiceID}"
									]
								];
								$choiceID += 1;
							}
						}
					}
				}
			} elseif ($poll['type'] == 1) {
				# Doodle
				if (isset($poll['choices']) and !empty($poll['choices'])) {
					if ($poll['anonymous'] or $poll['settings']['options']['0-2']) {
						foreach ($poll['choices'] as $choiceID => $choice) {
							$cvotes[$choiceID] = 0;
							$perc[$choiceID] = 0;
						}
						if (isset($poll['votes']) and !empty($poll['votes'])) {
							foreach ($poll['votes'] as $vote) {
								$cvotes[$vote['choice_id']]++;
							}
						}
						if (!empty($poll['votes'])) {
							$total = count($poll['votes']);
							foreach ($cvotes as $choiceID => $votes) {
								$perc[$choiceID] = round($votes / $total * 100);
							}
						}
						if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
							asort($cvotes, SORT_NUMERIC);
							$cvotes = array_reverse($cvotes, true);
						}
						foreach ($cvotes as $choiceID => $votes) {
							$choice = $this->getChoiceText($poll['choices'][$choiceID], $votes, $poll['settings']['options']['2-1']);
							$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
						}
					} else {
						foreach ($poll['choices'] as $choiceID => $choice) {
							$cvotes[$choiceID] = 0;
							$perc[$choiceID] = 0;
						}
						if (isset($poll['votes']) and !empty($poll['votes'])) {
							foreach ($poll['votes'] as $vote) {
								$cvotes[$vote['choice_id']]++;
								$avotes[$vote['choice_id']][] = [
									'id'	=> $vote['user_id'],
									'name'	=> $vote['cache_name']
								];
							}
						}
						if (!empty($poll['votes'])) {
							$total = count($poll['votes']);
							foreach ($cvotes as $choiceID => $votes) {
								$perc[$choiceID] = round($votes / $total * 100);
							}
						}
						if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
							asort($cvotes, SORT_NUMERIC);
							$cvotes = array_reverse($cvotes, true);
						}
						foreach ($cvotes as $choiceID => $votes) {
							$choice = $this->getChoiceText($poll['choices'][$choiceID], $cvotes[$choiceID], $poll['settings']['options']['2-1']);
							$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
							if (isset($poll['settings']['options']['2-2']) and $perc[$choiceID]) $message['text'] .= $this->createBars($perc[$choiceID], $poll['settings']['options']['2-2']) . "\n";
							if (isset($avotes[$choiceID]) and !empty($avotes[$choiceID])) {
								for ($i = 0; $i < 10; $i ++) {
									if (isset($avotes[$choiceID][$i])) $message['text'] .= "- " . htmlspecialchars($avotes[$choiceID][$i]['name']) . "\n";
								}
							}
						}
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$choiceID = 0;
							foreach ($poll['choices'] as $choiceID => $choice) {
								$message['reply_markup'][] = [
									[
										'text'			=> $this->getButtons($choice, $perc[$choiceID], $poll['settings']['options']['2-0']),
										'callback_data'	=> "v_{$poll['id']}_{$choiceID}"
									]
								];
								$choiceID += 1;
							}
						}
					}
				}
			} elseif ($poll['type'] == 2) {
				# Limited Doodle
				if (isset($poll['choices']) and !empty($poll['choices'])) {
					if ($poll['anonymous'] or $poll['settings']['options']['0-2']) {
						foreach ($poll['choices'] as $choiceID => $choice) {
							$cvotes[$choiceID] = 0;
							$perc[$choiceID] = 0;
						}
						if (isset($poll['votes']) and !empty($poll['votes'])) {
							foreach ($poll['votes'] as $vote) {
								$cvotes[$vote['choice_id']]++;
							}
						}
						if (!empty($poll['votes'])) {
							$total = count($poll['votes']);
							foreach ($cvotes as $choiceID => $votes) {
								$perc[$choiceID] = round($votes / $total * 100);
							}
						}
						if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
							asort($cvotes, SORT_NUMERIC);
							$cvotes = array_reverse($cvotes, true);
						}
						foreach ($cvotes as $choiceID => $votes) {
							$choice = $this->getChoiceText($poll['choices'][$choiceID], $cvotes[$choiceID], $poll['settings']['options']['2-1']);
							$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
							if (isset($poll['settings']['options']['2-2']) and $perc[$choiceID]) $message['text'] .= $this->createBars($perc[$choiceID], $poll['settings']['options']['2-2']) . "\n";
						}
					} else {
						foreach ($poll['choices'] as $choiceID => $choice) {
							$cvotes[$choiceID] = 0;
							$perc[$choiceID] = 0;
						}
						if (isset($poll['votes']) and !empty($poll['votes'])) {
							foreach ($poll['votes'] as $vote) {
								$cvotes[$vote['choice_id']]++;
								$avotes[$vote['choice_id']][] = [
									'id'	=> $vote['user_id'],
									'name'	=> $vote['cache_name']
								];
							}
						}
						if (!empty($poll['votes'])) {
							$total = count($poll['votes']);
							foreach ($cvotes as $choiceID => $votes) {
								$perc[$choiceID] = round($votes / $total * 100);
							}
						}
						if ($poll['settings']['options']['0-1'] and isset($poll['votes']) and !empty($poll['votes'])) {
							asort($cvotes, SORT_NUMERIC);
							$cvotes = array_reverse($cvotes, true);
						}
						foreach ($cvotes as $choiceID => $votes) {
							$choice = $this->getChoiceText($poll['choices'][$choiceID], $cvotes[$choiceID], $poll['settings']['options']['2-1']);
							$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
							if (isset($poll['settings']['options']['2-2']) and $perc[$choiceID]) $message['text'] .= $this->createBars($perc[$choiceID], $poll['settings']['options']['2-2']) . "\n";
							if (isset($avotes[$choiceID]) and !empty($avotes[$choiceID])) {
								for ($i = 0; $i < 10; $i ++) {
									if (isset($avotes[$choiceID][$i])) $message['text'] .= "- " . htmlspecialchars($avotes[$choiceID][$i]['name']) . "\n";
								}
							}
						}
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$choiceID = 0;
							foreach ($poll['choices'] as $choiceID => $choice) {
								$message['reply_markup'][] = [
									[
										'text'			=> $this->getButtons($choice, $perc[$choiceID], $poll['settings']['options']['2-0']),
										'callback_data'	=> "v_{$poll['id']}_{$choiceID}"
									]
								];
								$choiceID += 1;
							}
						}
					}
				}
			} elseif ($poll['type'] == 3) {
				# Board
				if (isset($poll['votes']) and !empty($poll['votes'])) {
					if ($poll['anonymous'] or $poll['settings']['options']['0-2']) {
						foreach ($poll['votes'] as $vote) {
							$message['text'] .= "\n👤 " . htmlspecialchars($vote['comment']) . "\n";
						}
					} else {
						foreach ($poll['votes'] as $vote) {
							$message['text'] .= "\n" . $this->bf->bold("{$vote['cache_name']}:") . " " . htmlspecialchars($vote['comment']) . "\n";
						}
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$message['reply_markup'][] = [
								[
									'text'			=> $langs->getTranslate('buttonComment', false, $lang),
									'callback_data'	=> "v_{$poll['id']}_0"
								]
							];
						}
					}
				}
			} elseif ($poll['type'] == 4) {
				# Rating
				if (isset($poll['choices']) and !empty($poll['choices'])) {
					if (isset($poll['votes']) and !empty($poll['votes'])) {
						$all = 0;
						foreach ($poll['votes'] as $vote) {
							$all += $vote['choice_id'] + 1;
						}
						$message['text'] .= "\n" . round(($all / count($poll['votes'])), 2) . "/" . end($poll['choices']) . "\n";
						if (!$poll['anonymous'] and !$poll['settings']['options']['0-2']) {
							foreach ($poll['votes'] as $vote) {
								$message['text'] .= "\n" . htmlspecialchars($vote['cache_name']) . ": " . ($vote['choice_id'] + 1);
							}
							$message['text'] .= "\n";
						}
					} else {
						$message['text'] .= "\n0/" . end($poll['choices']) . "\n";
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							foreach ($poll['choices'] as $choice) {
								$message['reply_markup'][] = [
									[
										'text'			=> $choice,
										'callback_data'	=> "v_{$poll['id']}_{$choice}"
									]
								];
							}
						}
					}
				}
			} elseif ($poll['type'] == 5)  {
				# Participation
				if (isset($poll['votes']) and !empty($poll['votes']) and !$poll['anonymous'] and !$poll['settings']['options']['0-2']) {
					foreach ($poll['votes'] as $vote) {
						$message['text'] .= "\n- " . htmlspecialchars($vote['cache_name']);
					}
					$message['text'] .= "\n";
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$message['reply_markup'][] = [
								[
									'text'			=> $langs->getTranslate('buttonParticipate', false, $lang),
									'callback_data'	=> "v_{$poll['id']}_0"
								]
							];
						}
					}
				}
			} elseif ($poll['type'] == 6) {
				# Quiz
				if (isset($poll['choices']) and !empty($poll['choices'])) {
					if ($poll['status'] == 2) {
						foreach ($poll['choices'] as $choiceID => $choice) {
							$message['text'] .= "\n" . $this->bf->bold($choice) . "\n";
						}
					} else {
						if (isset($poll['votes']) and !empty($poll['votes'])) {
							foreach ($poll['votes'] as $vote) {
								$cvotes[$vote['choice_id']][] = [
									'id'	=> $vote['user_id'],
									'name'	=> $vote['cache_name']
								];
							}
						}
						foreach ($poll['choices'] as $choiceID => $choice) {
							if ($choiceID == $poll['settings']['quizResponse']) {
								$message['text'] .= "\n✅ " . $this->bf->bold($choice) . "\n";
								if (!$poll['anonymous'] and !$poll['settings']['options']['0-2']) {
									if (isset($cvotes[$choiceID]) and !empty($cvotes[$choiceID])) {
										for ($i = 0; $i < 10; $i ++) {
											if (isset($cvotes[$choiceID][$i])) $message['text'] .= "- " . $this->bf->bold($cvotes[$choiceID][$i]['name']) . "\n";
										}
									}
								}
							} else {
								$message['text'] .= "\n❌ " . $this->bf->italic($choice) . "\n";
								if (!$poll['anonymous'] and !$poll['settings']['options']['0-2']) {
									if (isset($cvotes[$choiceID]) and !empty($cvotes[$choiceID])) {
										for ($i = 0; $i < 10; $i ++) {
											if (isset($cvotes[$choiceID][$i])) $message['text'] .= "- " . $this->bf->italic($cvotes[$choiceID][$i]['name']) . "\n";
										}
									}
								}
							}
							
							
						}
					}
				}
				if ($poll['status'] !== 0) {
					if ($options['isPollOwner']) {
						if ($poll['status'] == 2) {
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('publish', false, $lang),
								'switch_inline_query'	=> $poll['poll_id']
							];
							$message['reply_markup'][0][] = [
								'text'					=> $langs->getTranslate('pollSendChat', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_5"
							];
							$message['reply_markup'][1][] = [
								'text'					=> $langs->getTranslate('refreshButton', false, $lang),
								'callback_data'			=> "myPoll_{$poll['id']}_4"
							];
						} else {
							$message['reply_markup'] = [[], []];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollOptions', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_6"
						];
						if ($poll['status'] == 2) {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollClose', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_2"
							];
						} else {
							$message['reply_markup'][2][] = [
								'text'				=> $langs->getTranslate('pollReopen', false, $lang),
								'callback_data'		=> "myPoll_{$poll['id']}_3"
							];
						}
						$message['reply_markup'][2][] = [
							'text'					=> $langs->getTranslate('pollDelete', false, $lang),
							'callback_data'			=> "myPoll_{$poll['id']}_1"
						];
					} elseif ($options['isPollAdmin']) {
						
					} else {
						if ($poll['status'] == 2) {
							$choiceID = 0;
							foreach ($poll['choices'] as $choice) {
								$message['reply_markup'][] = [
									[
										'text'			=> $choice,
										'callback_data'	=> "v_{$poll['id']}_{$choiceID}"
									]
								];
								$choiceID += 1;
							}
						}
					}
				}
			}
			if ($poll['settings']['options']['0-0'] and $poll['status'] == 2 and !$options['isPollOwner']) {
				$message['reply_markup'][][] = [
					'text' => $langs->getTranslate('appendButton'),
					'callback_data' => "append_{$poll['id']}"
				];
			}
			if ($poll['settings']['options']['1-0'] and $poll['status'] == 2 and !$options['isPollOwner']) {
				$message['reply_markup'][][] = [
					'text' => $langs->getTranslate('shareButton'),
					'callback_data' => "share_{$poll['id']}"
				];
			}
			if ($poll['settings']['options']['0-5']) {
				$limited = "Limited";
			} else {
				$limited = "";
			}
			if (!in_array($poll['type'], [1, 2]) and isset($poll['votes']) and is_array($poll['votes']) and !empty($poll['votes'])) {
				$votes_count = round(count($poll['votes']));
			} elseif (in_array($poll['type'], [1, 2]) and isset($poll['votes']) and is_array($poll['votes']) and !empty($poll['votes'])) {
				foreach ($poll['votes'] as $votes) {
					if (!$already_seen[$votes['user_id']]) {
						$votes_count += 1;
						$already_seen[$votes['user_id']] = 1;
					}
				}
			} else {
				$votes_count = 0;
			}
			if ($votes_count === 0) {
				$message['text'] .= "\n" . $langs->getTranslate('renderer' . $limited . 'ZeroVotedSoFar', [$votes_count, $poll['settings']['options']['0-5']], $lang);
			} elseif ($votes_count === 1) {
				$message['text'] .= "\n" . $langs->getTranslate('renderer' . $limited . 'SingleVotedSoFar', [$votes_count, $poll['settings']['options']['0-5']], $lang);
			} else {
				$message['text'] .= "\n" . $langs->getTranslate('renderer' . $limited . 'MultiVotedSoFar', [$votes_count, $poll['settings']['options']['0-5']], $lang);
			}
			$trType = $this->getTypes()[$poll['type']];
			$trType[0] = strtoupper($trType[0]);
			$message['text'] .= "\n📖 " . $this->bf->italic($langs->getTranslate('inlineDescription' . $this->getAnonymous($poll['anonymous']) . $trType, false, $lang));
			if ($poll['status'] == 1) {
				$message['text'] .= "\n\n" . $langs->getTranslate('pollClosed', false, $lang);
			} elseif ($poll['status'] == 0) {
				$message['text'] .= "\n\n" . $langs->getTranslate('pollDeleted', false, $lang);
			}
		}
		return $message;
	}

	# Get the inline poll description
	public function createInlineDescription ($poll) {
		global $db;
		global $langs;
		
		$message['text'] = $langs->getTranslate('generalError');
		if (!isset($poll['id'])) {
		} elseif (!is_numeric($poll['id'])) {
		} elseif (!isset($poll['poll_id'])) {
		} elseif (!is_numeric($poll['poll_id'])) {
		} elseif (!isset($poll['owner_id'])) {
		} elseif (!is_numeric($poll['owner_id'])) {
		} else {
			if ($poll['status'] === 0) {
				return $message;
			}
			$trType = $this->getTypes()[$poll['type']];
			$trType[0] = strtoupper($trType[0]);
			if ($poll['anonymous']) {
				$pollTypeAnon = $langs->getTranslate('inlineDescriptionAnonymous' . $trType);
			} else {
				$pollTypeAnon = $langs->getTranslate('inlineDescriptionPersonal' . $trType);
			}
			if (isset($poll['votes']) and is_array($poll['votes']) and !empty($poll['votes'])) {
				$votes_count = round(count($poll['votes']));
			} else {
				$q = $db->query("SELECT COUNT(*) FROM choices WHERE id = ?", [$poll['id']], true);
				if (isset($q['count'])) {
					$votes_count = $q['count'];
				} else {
					$votes_count = 0;
				}
			}
			$message['text'] = $langs->getTranslate('inlineDescriptionFirstLine', [$pollTypeAnon, $votes_count]);
		}
		return $message;
	}

	# Get buttons style by type
	public function getButtons ($choice, $p = 0, $type = 0) {
		if ($type == 0) {
			return "{$choice} - {$p}%";
		} elseif ($type == 1) {
			return "{$p}% - {$choice}";
		} else {
			return $choice;
		}
	}

	# Get choices style by type
	public function getChoiceText ($choice, $v = 0, $type = 0) {
		if ($type == 0) {
			return "{$choice} [" . round($v) . "]";
		} elseif ($type == 1) {
			return $choice;
		} else {
			return "{$choice} [" . round($v) . "]";
		}
	}

	# Get Emoji Bars by percentage and type
	public function createBars ($p = 0, $type = 0) {
		if ($p == "NAN") $p = 0;
		if ($type === 0) {
			if ($p <= 10) {
				return "👍";
			} elseif ($p >=11 && $p <= 20) {
				return "👍👍";
			} elseif ($p >= 21 && $p <= 30) {
				return "👍👍👍";
			} elseif ($p >= 31 && $p <= 40) {
				return "👍👍👍👍";
			} elseif ($p >= 41 && $p <= 50) {
				return "👍👍👍👍👍";
			} elseif ($p >= 51 && $p <= 60) {
				return "👍👍👍👍👍👍";
			} elseif ($p >= 61 && $p <= 70) {
				return "👍👍👍👍👍👍👍";
			} elseif ($p >= 71 && $p <= 80) {
				return "👍👍👍👍👍👍👍👍";
			} elseif ($p >= 81 && $p <= 90) {
				return "👍👍👍👍👍👍👍👍👍";
			} elseif ($p >= 91 && $p <= 100) {
				return "👍👍👍👍👍👍👍👍👍👍";
			} else {
				return "👍👍👍👍👍👍👍👍👍👍";
			}
		} elseif ($type == 1) {
			if ($p == 0) {
				return "○○○○○○○○○○";
			} elseif ($p <= 10) {
				return "●○○○○○○○○○";
			} elseif ($p >=11 && $p <= 20) {
				return "●●○○○○○○○○";
			} elseif ($p >= 21 && $p <= 30) {
				return "●●●○○○○○○○";
			} elseif ($p >= 31 && $p <= 40) {
				return "●●●●○○○○○○";
			} elseif ($p >= 41 && $p <= 50) {
				return "●●●●●○○○○○";
			} elseif ($p >= 51 && $p <= 60) {
				return "●●●●●●○○○○";
			} elseif ($p >= 61 && $p <= 70) {
				return "●●●●●●●○○○";
			} elseif ($p >= 71 && $p <= 80) {
				return "●●●●●●●●○○";
			} elseif ($p >= 81 && $p <= 90) {
				return "●●●●●●●●●○";
			} elseif ($p >= 91 && $p <= 100) {
				return "●●●●●●●●●●";
			} else {
				return "●●●●●●●●●●";
			}
		} else {
			return "❌";
		}
	}

	# Get the last poll message by user ID
	public function lastPollMessage ($owner) {
		global $db;
		global $langs;
		
		if (is_numeric($owner)) {
			$id = $this->getLastPollID($owner);
			$poll = $this->getUserPoll($owner, $id);
			$t_type = $this->getAnonymous($poll['anonymous']) . $this->getTypes()[$poll['type']];
			return "\n" . $langs->getTranslate('lastPollCreated') . ": /{$poll['poll_id']}" . 
			"\n ✍🏻 " . $this->bf->bold($poll['title']) .
			"\n ℹ️ " . $langs->getTranslate('pollType') . ": " . $langs->getTranslate('inlineDescription' . $t_type) . "\n";
		} else {
			return "";
		}
	}

	# Add choice to polls
	public function addPollChoice ($id, $choice) {
		global $db;
		
		return $db->query("UPDATE polls SET choices = choices || ? WHERE id = ?", ["[\"$choice\"]", $id], true);
	}

	# Add message ID to the poll
	public function addPollMessage ($id, $message_id, $chat_id = false, $tags = []) {
		global $db;
		
		if (!is_numeric($id)) {
			return false;
		} elseif (empty($message_id)) {
			return false;
		} else {
			$message = [
				'time'			=> time(),
				'message_id'	=> $message_id
			];
			if (isset($chat_id) and is_numeric($chat_id)) $message['chat_id'] = $chat_id;
			if (isset($tags) and !empty($tags)) $message['tags'] = $tags;
			return $db->query("UPDATE polls SET messages = messages::jsonb - '{}' || ?::jsonb WHERE id = ?", [json_encode($message), $id], false);
		}
	}

	# Update poll messages
	public function updatePollMessages ($poll, $noupdate = []) {
		global $db;
		global $bot;
		
		if (!isset($poll['id'])) {
			return false;
		} elseif (!is_numeric($poll['id'])) {
			return false;
		} elseif (!isset($poll['poll_id'])) {
			return false;
		} elseif (!is_numeric($poll['poll_id'])) {
			return false;
		} elseif (!isset($poll['owner_id'])) {
			return false;
		} elseif (!is_numeric($poll['owner_id'])) {
			return false;
		} else {
			$rup = rand(0, 1999);
			$db->rset('MPPollUpdate_' . $poll['id'], $rup, 10);
			$updates = [];
			$poll_message = $this->createPollMessage($poll);
			$finish = false;
			foreach ($poll['messages'] as $message) {
				if ($finish or round($db->rget('MPPollUpdate_' . $poll['id'])) !== round($rup)) {
					$finish = true;
					$updates[] = "Update overwrited.";
				} else {
					if (!isset($message['chat_id']) or empty($message['chat_id'])) $message['chat_id'] = false;
					if ($noupdate['message_id'] !== $message['message_id']) {
						$updates[] = $bot->editMessageText($message['chat_id'], $poll_message['text'], $poll_message['reply_markup'], $message['message_id'], 'def', $poll_message['disable_web_page_preview']);
					}
				}
			}
			return $updates;
		}
	}

	# Close poll votes
	public function closePoll ($id) {
		global $db;

		if (!is_numeric($id)) {
			return false;
		} else {
			return $db->query("UPDATE polls SET status = ? WHERE id = ? and status != ?", [1, $id, 0], false);
		}
	}
	
	# Reopen poll votes
	public function reopenPoll ($id) {
		global $db;

		if (!is_numeric($id)) {
			return false;
		} else {
			return $db->query("UPDATE polls SET status = ? WHERE id = ? and status != ?", [2, $id, 0], false);
		}
	}
	
	# Delete all polls by user ID
	public function deleteAllPolls ($owner) {
		global $db;
		
		if (!is_numeric($owner)) {
			return false;
		} else {
			return $db->query("UPDATE polls SET status = ? WHERE owner_id = ?", [0, $owner], false);
		}
	}

	# Delete poll by user ID and poll ID
	public function deletePoll ($id) {
		global $db;
		
		if (!is_numeric($id)) {
			return false;
		} else {
			return $db->query("UPDATE polls SET status = ? WHERE id = ?", [0, $id], false);
		}
	}
}

?>
