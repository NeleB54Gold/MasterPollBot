<?php

class MasterPollDatabase
{
	public $report = [];
	
	function __construct ($configs, $report = [], $api = []) {
		$this->report = $report;
		$this->api = $api;
		try {
			$this->PDO = new PDO('pgsql:host=' . $configs['database']['host'] . ';dbname=' . $configs['database']['name'], $configs['database']['user'], $configs['database']['password']);
		} catch (PDOException $e) {
			$this->report->error($e, 'PDOException');
			die;
		}
		try {
			$this->redis = new Redis();
			$this->redis->connect($configs['redis']['host'], $configs['redis']['port']);
			if ($configs['redis']['password'] !== false) $this->redis->auth($configs['redis']['password']);
			$this->redis->select($configs['redis']['database']);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			die;
		}
	}
	
	public function setupCheck ($tables = []) {
		foreach ($tables as $table) {
			if (in_array($table, ['users', 'bots', 'groups', 'channels', 'polls', 'choices', 'blacklists', 'crontabs'])) {
				$r = $this->query('SELECT COUNT(id) FROM ' . $table, false, true);
				if (!$r['error']) {
					$result[$table] = 'Exists';
				} else {
					if ($table == 'users') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS users (
							id				INT				PRIMARY KEY,
							name			VARCHAR(64)		NOT NULL,
							surname			VARCHAR(64),
							username		VARCHAR(32),
							lang			VARCHAR(16)		NOT NULL	DEFAULT \'en\',
							settings		JSON			NOT NULL	DEFAULT \'{}\',
							premium			BOOL			NOT NULL	DEFAULT \'0\',
							first_update	INT4			NOT NULL,
							last_update		INT4			NOT NULL,
							status			VARCHAR(16)		NOT NULL	DEFAULT \'active\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'bots') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS bots (
							id				INT				PRIMARY KEY,
							name			VARCHAR(64)		NOT NULL,
							username		VARCHAR(32),
							first_update	INT4			NOT NULL,
							last_update		INT4			NOT NULL,
							status			INT				NOT NULL	DEFAULT \'1\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'groups') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS groups (
							id				INT				PRIMARY KEY,
							title			VARCHAR(64)		NOT NULL,
							description		VARCHAR(256),
							username		VARCHAR(32),
							administrators	JSON			NOT NULL	DEFAULT \'[]\',
							permissions		JSON			NOT NULL	DEFAULT \'[]\',
							settings		JSON			NOT NULL	DEFAULT \'[]\',
							first_update	INT4			NOT NULL,
							last_update		INT4			NOT NULL,
							status			VARCHAR(32)		NOT NULL	DEFAULT \'active\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'channels') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS channels (
							id				INT				PRIMARY KEY,
							title			VARCHAR(64)		NOT NULL,
							description		VARCHAR(256),
							username		VARCHAR(32),
							administrators	JSON			NOT NULL	DEFAULT \'[]\',
							settings		JSON			NOT NULL	DEFAULT \'[]\',
							first_update	INT4			NOT NULL,
							last_update		INT4			NOT NULL,
							status			VARCHAR(32)		NOT NULL	DEFAULT \'active\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'polls') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS polls (
							id				SERIAL			PRIMARY KEY,
							poll_id			INT				NOT NULL,
							owner_id		INT				NOT NULL,
							status			INT				NOT NULL	DEFAULT \'0\',
							title			VARCHAR(512)	NOT NULL,
							description		VARCHAR(512),
							anonymous		BOOLEAN			NOT NULL	DEFAULT true,
							type			INT				NOT NULL,
							choices			JSON			NOT NULL	DEFAULT \'[]\',
							settings		JSON			NOT NULL	DEFAULT \'[]\',
							creation_date	INT				NOT NULL,
							last_update		INT				NOT NULL,
							messages		JSON			NOT NULL	DEFAULT \'[]\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'choices') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS choices (
							id				SERIAL		PRIMARY KEY,
							poll_id			INT			NOT NULL,
							choice_id		INT			NOT NULL,
							user_id			INT			NOT NULL,
							cache_name		VARCHAR(128),
							comment			TEXT,
							choice_date		INT			NOT NULL);');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'blacklists') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS blacklists (
							id				SERIAL			PRIMARY KEY,
							user_id			INT				NOT NULL,
							poll_id			INT				DEFAULT 0,
							users			JSONB			NOT NULL	DEFAULT \'{}\');');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} elseif ($table == 'crontabs') {
						$q = $this->query('CREATE TABLE IF NOT EXISTS crontabs (
							id				SERIAL			PRIMARY KEY,
							time			INT				NOT NULL,
							type			INT				NOT NULL,
							user_id			INT				NOT NULL,
							poll_id			INT				NOT NULL,
							chat_id			NUMERIC,
							bot_id			INT				NOT NULL);');
						if ($q['error']) {
							$result[$table] = 'Error creating this table';
						} else {
							$result[$table] = 'Created';
						}
					} else {
						$result[$table] = 'Table not supported';
					}
				}
			} else {
				$result[$table] = 'Not exists';
			}
		}
		return $result;
	}
	
	public function redisPing () {
		try {
			$result = $this->redis->ping();
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rget ($key) {
		try {
			$result = $this->redis->get($key);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rset ($key, $value, $time = 'no') {
		try {
			if ($time == 'no' or $time == 0) {
				$result = $this->redis->set($key, $value, (60 * 60));
			} else {
				$result = $this->redis->set($key, $value, $time);
			}
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rdel ($key) {
		try {
			$result = $this->redis->del($key);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rkeys ($key) {
		try {
			$result = $this->redis->keys($key);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rlistAdd ($key, $value) {
		try {
			$result = $this->redis->lpush($key, $value);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rlget ($key, $offset = 0, $limit = 50) {
		try {
			$result = $this->redis->lrange($key, $offset, $limit);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rgetList ($key, $offset = 0, $limit = 50) {
		try {
			$result = $this->redis->lrange($key, $offset, $limit);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return $result;
	}
	
	public function rListRem ($key, $value = 0, $count = 50) {
		try {
			return $this->redis->lRem($key, $value, $count);
		} catch (Exception $e) {
			$this->report->error($e, 'Redis');
			$result = false;
		}
		return 0;
	}
	
	public function query ($query, $args = [], $return = true) {
		try {
			$q = $this->PDO->prepare($query);
			if (!$q) {
				$err = $this->PDO->errorInfo();
				$this->report->error($err, 'PDOError');
				return ['error' => $err];
			}
			if (is_array($args) and !empty($args)) {
				$q->execute($args);
			} else {
				$q->execute();
			}
			$err = $q->errorInfo();
			if ($err[0] !== '00000') {
				$this->report->error($err, 'PDOError');
				return ['error' => $err];
			}
			if ($return === false) {
				return true;
			} elseif ($return === true) {
				return $q->fetch(\PDO::FETCH_ASSOC);
			} else {
				return $q->fetchAll();
			}
		} catch (PDOException $e) {
			$this->report->error($err, 'PDOException');
			die;
		}
	}

	public function getUser ($user) {
		if (isset($user['id']) or isset($user['username'])) {
			if (isset($user['id'])) {
				if ($user['id'] <= 0 or $user['id'] >= 2147483647) {
					return ['error' => 'This id is out of range'];
				}
				$q = $this->query('SELECT * FROM users WHERE id = ?', [round($user['id'])], true);
			} else {
				if (strlen($user['username']) > 32) {
					return ['error' => 'This username has too many characters'];
				}
				$q = $this->query('SELECT * FROM users WHERE LOWER(username) = LOWER(?)', [$user['username']], true);
			}
			if (!isset($q['id']) and $user['first_name']) {
				if (!isset($user['language_code'])) $user['language_code'] = 'en';
				$args = [
					$user['id'],
					$user['first_name'],
					$user['last_name'],
					$user['username'],
					$user['language_code'],
					time(),
					time()
				];
				$this->query('INSERT INTO users (id, name, surname, username, lang, first_update, last_update) VALUES (?,?,?,?,?,?,?)', $args);
				$q = $this->query('SELECT * FROM users WHERE id = ?', [$user['id']], true);
				if (!isset($q['id'])) {
					return ['error' => 'Error to load user into MasterPollDatabase'];
				}
			}
			$af = new AntiFlood($this, $q['id']);
			if ($af->banned) die;
			if ($user['first_name'] !== $q['name'] or $user['last_name'] !== $q['surname'] or $user['username'] !== $q['username']) {
				$q['name'] = $user['first_name'];
				$q['surname'] = $user['last_name'];
				$q['username'] = $user['username'];
				$this->query('UPDATE users SET name = ?, surname = ? , username = ? WHERE id = ?', [$q['name'], $q['surname'], $q['username'], $q['id']]);
			}
			$q['settings'] = json_decode($q['settings'], true);
			if (!is_array($q['settings'])) $q['settings'] = [];
			if (!isset($q['settings']['timezone'])) {
				$q['settings']['timezone'] = 'UTC';
			}
			if (!isset($q['settings']['date_format'])) {
				$q['settings']['date_format'] = 'D, d/m/Y H:i';
			}
			return $q;
		} else {
			return ['error' => 'There is no id or username in $user in getUser()'];
		}
	}

	public function getGroup ($chat) {if (isset($chat['id']) or isset($chat['username'])) {
			if (!isset($chat['type']) and !isset($chat['id']) and !isset($chat['username'])) {
				return ['error' => 'No informations inserted...'];
			} elseif (isset($chat['id'])) {
				if ($chat['id'] > 0) {
					return ['error' => 'This id is out of range -0'];
				}
				$q = $this->query('SELECT * FROM groups WHERE id = ?', [round($chat['id'])], true);
			}
			if (!isset($q['id']) and isset($chat['username'])) {
				if (strlen($chat['username']) > 32) {
					return ['error' => 'This username has too many characters'];
				}
				$q = $this->query('SELECT * FROM groups WHERE username = ?', [$chat['username']], true);
			}
			if (!isset($q['id']) and isset($chat['title'])) {
				$args = [
					$chat['id'],
					$chat['title'],
					$chat['description'],
					$chat['username'],
					time(),
					time()
				];
				$i = $this->query('INSERT INTO groups (id, title, description, username, first_update, last_update) VALUES (?,?,?,?,?,?)', $args);
				$q = $this->query('SELECT * FROM groups WHERE id = ?', [$chat['id']], true);
				if (!isset($q['id'])) {
					$this->report->error('Error to load group into MasterPollDatabase: ' . json_encode($i), 'Database');
					return ['error' => 'Error to load group into MasterPollDatabase'];
				}
			} elseif (!isset($q['id']) and !isset($chat['title'])) {
				return ['error' => 'Chat not found'];
			}
			$q['permissions'] = json_decode($q['permissions'], true);
			$q['administrators'] = json_decode($q['administrators'], true);
			if (empty($q['administrators']) or empty($q['permissions']) or $q['last_update'] <= (time() - 60 * 60 * 24)) {
				$q['permissions'] = [];
				$chat = $this->api->getChat($q['id']);
				if ($chat['ok']) {
					$q['title'] = $chat['result']['title'];
					$q['description'] = $chat['result']['description'];
					$q['username'] = $chat['result']['username'];
					$q['permissions'] = $chat['result']['permissions'];
				}
				$q['administrators'] = [];
				$admins = $this->api->getChatAdministrators($q['id']);
				if ($admins['ok']) {
					$q['administrators'] = $admins['result'];
				}
				$q['last_update'] = time();
				$this->query('UPDATE groups SET title = ?, description = ?, username = ?, permissions = ?, administrators = ?, last_update = ? WHERE id = ?', [$q['title'], $q['description'], $q['username'], json_encode($q['permissions']), json_encode($q['administrators']), $q['last_update'], $q['id']]);
			}
			return $q;
		} else {
			return ['error' => 'There is no id or username in $chat in getGroup()'];
		}
	}

	public function getChannel ($chat) {if (isset($chat['id']) or isset($chat['username'])) {
			if (!isset($chat['type']) and !isset($chat['id']) and !isset($chat['username'])) {
				return ['error' => 'No informations inserted...'];
			} elseif (isset($chat['id'])) {
				if ($chat['id'] > 0) {
					return ['error' => 'This id is out of range -0'];
				}
				$q = $this->query('SELECT * FROM channels WHERE id = ?', [round($chat['id'])], true);
			}
			if (!isset($q['id']) and isset($chat['username'])) {
				if (strlen($chat['username']) > 32) {
					return ['error' => 'This username has too many characters'];
				}
				$q = $this->query('SELECT * FROM channels WHERE username = ?', [$chat['username']], true);
			}
			if (!isset($q['id']) and isset($chat['title'])) {
				$args = [
					$chat['id'],
					$chat['title'],
					$chat['description'],
					$chat['username'],
					time(),
					time()
				];
				$i = $this->query('INSERT INTO channels (id, title, description, username, first_update, last_update) VALUES (?,?,?,?,?,?)', $args);
				$q = $this->query('SELECT * FROM channels WHERE id = ?', [$chat['id']], true);
				if (!isset($q['id'])) {
					$this->report->error('Error to load channel into MasterPollDatabase: ' . json_encode($i), 'Database');
					return ['error' => 'Error to load channel into MasterPollDatabase'];
				}
			} elseif (!isset($q['id']) and !isset($chat['title'])) {
				return ['error' => 'Chat not found'];
			}
			$q['administrators'] = json_decode($q['administrators'], true);
			if (!isset($q['administrators']) or empty($q['administrators']) or $q['last_update'] <= (time() - 60 * 60 * 24)) {
				$q['administrators'] = [];
				$admins = $this->api->getChatAdministrators($q['id']);
				if ($admins['ok']) {
					$q['administrators'] = $admins['result'];
				}
				$q['last_update'] = time();
				$this->query('UPDATE channels SET administrators = ?, last_update = ? WHERE id = ?', [json_encode($q['administrators']), $q['last_update'], $q['id']]);
			}
			return $q;
		} else {
			return ['error' => 'There is no id or username in $chat in getChannel()'];
		}
	}

	public function getChatsByAdmin ($user_id, $limit = 10) {
		$chats = [];
		if (!is_numeric($user_id)) {
			return $chats;
		}
		$groups = $this->query("SELECT title, id FROM groups WHERE administrators @> '[{\"user\":{\"id\": {$user_id}}}]' LIMIT ?", [round($limit) / 2], 'fetch');
		if (isset($groups[0]['id'])) {
			$chats = array_merge($chats, $groups);
		}
		$channels = $this->query("SELECT title, id FROM channels WHERE administrators @> '[{\"user\":{\"id\": {$user_id}}}]' LIMIT ?", [round($limit) / 2], 'fetch');
		if (isset($channels[0]['id'])) {
			$chats = array_merge($chats, $channels);
		}
		return $chats;
	}

	public function isBanned ($user_id, $owner_id, $poll_id = 0) {
		$q = $this->query('SELECT users->? as ban FROM blacklists WHERE user_id = ? and (poll_id = 0 or poll_id = ?)', [$user_id, $owner_id, $poll_id], 'fetch');
		if (
		(isset($q[0]['ban']) and ($q[0]['ban'] === 0 or $q[0]['ban'] >= time()))
		or
		(isset($q[1]) and ($q[1]['ban'] === 0 or $q[1]['ban'] >= time()))
		) {
			return true;
		} else {
			return false;
		}
	}

	public function getLanguage ($user_id) {
		if (is_numeric($user_id)) {
			if (isset($user_id)) {
				if ($user_id > 0 and $user_id < 2147483647) {
					$q = $this->query('SELECT * FROM users WHERE id = ?', [round($user_id)], true);
				}
			}
			if (isset($q['id']) and $q['lang']) {
				return $q['lang'];
			}
		}
		return 'en';
	}
}

?>
