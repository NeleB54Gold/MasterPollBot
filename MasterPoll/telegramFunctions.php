<?php

class TelegramFunctions
{
	function __construct ($token) {
		$this->token = $token;
		$this->bot_id = explode(':', $token, 2)[0];
	}
	
	private function createMenu ($menu = [], $menutype = 'inline') {
		if ($menutype == 'reply') {
			return [
				'force_reply' => true,
				'selective' => true
			];
		} elseif ($menutype == 'hide') {
			return [
				'hide_keyboard' => true
			];
		} elseif ($menutype == 'inline') {
			return [
				'inline_keyboard' => $menu
			];
		} else {
			return [
				'keyboard' => $menu,
				'resize_keyboard' => true
			];
		}
	}
	
	public function createInlineResult ($type = 'article', $id, $title, $description = '', $message, $parse_mode = 'def', $disable_web_page_preview = true, $menu = [], $thumb = false) {
		if ($parse_mode == 'def') {
			$parse_mode = $this->getParseMode();
		} elseif (!in_array(strtolower($parse_mode), ['html', 'markdown', 'markdownv2'])) {
			$parse_mode = '';
		}
		$result = [
			'type' => $type,
			'id' => $id,
			'title' => $title,
			'description' => $description,
			'message_text' => $message,
			'parse_mode' => $parse_mode,
			'disable_web_page_preview' => $disable_web_page_preview
		];
		if (isset($menu) and is_array($menu) and !empty($menu)) {
			$result['reply_markup'] = $this->createMenu($menu);
		}
		if (isset($thumb) and !empty($thumb)) {
			$result['thumb_url'] = $thumb;
		}
		return $result;
	}
	
	private function getParseMode () {
		global $configs;
		if (isset($configs['parse_mode'])) {
			if (in_array(strtolower($configs['parse_mode']), ['html', 'markdown', 'markdownv2'])) {
				return strtolower($configs['parse_mode']);
			}
		}
		return '';
	}
	
	public function getID () {
		return $this->bot_id;
	}
	
	public function getMe () {
		global $db;
		
		if ($cache = $db->rget('bot-' . $this->bot_id . '-getMe')) {
			$bot = json_decode($cache, true);
		} else {
			$r = $this->request('getMe', [], true);
			if ($r['ok']) {
				$bot = $r['result'];
			} else {
				return $r;
			}
			$db->rset('bot-' . $this->bot_id . '-getMe', json_encode($bot), (60 * 60));
		}
		return $bot;
	}
	
	public function getUsername () {
		return $this->getMe()['username'];
	}
	
	public function request ($method, $args = [], $response = false) {
		if (!isset($this->curl)) $this->curl = curl_init();
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> 'https://api.telegram.org/bot' . $this->token . '/' . $method,
			CURLOPT_POST			=> 1,
			CURLOPT_POSTFIELDS		=> $args,
			CURLOPT_TIMEOUT			=> 2,
			CURLOPT_RETURNTRANSFER	=> $response
		]);
		return json_decode(curl_exec($this->curl), true);
	}
	
	public function sendMessage ($chat_id, $text, $menu = [], $parse_mode = 'def', $disable_web_page_preview = true, $response = false, $menutype = 'inline') {
		if ($parse_mode == 'def') {
			$parse_mode = $this->getParseMode();
		} elseif (!in_array(strtolower($parse_mode), ['html', 'markdown', 'markdownv2'])) {
			$parse_mode = '';
		}
		$args = [
			'chat_id'	=> $chat_id,
			'text'		=> $text,
			'parse_mode'=> $parse_mode
		];
		if (!empty($menu) and is_array($menu)) {
			$args['reply_markup'] = json_encode($this->createMenu($menu, $menutype));
		}
		if ($disable_web_page_preview) {
			$args['disable_web_page_preview'] = true;
		}
		return $this->request('sendMessage', $args, $response);
	}
	
	public function sendDocument ($chat_id, $file, $caption = false, $menu = [], $parse_mode = 'def', $response = false, $menutype = 'inline') {
		if ($parse_mode == 'def') {
			$parse_mode = $this->getParseMode();
		} elseif (!in_array(strtolower($parse_mode), ['html', 'markdown', 'markdownv2'])) {
			$parse_mode = '';
		}
		// Local file
		if (strpos($file, 'http') === false) {
			if (file_exists($file)) {
				$e = explode('.', $file);
				$ex = $e[count($e) - 1];
				$file = curl_file_create($file);
			}
		}
		$args = [
			'chat_id'	=> $chat_id,
			'document'	=> $file,
			'parse_mode'=> $parse_mode
		];
		if (!empty($caption) and is_string($caption)) {
			$args['caption'] = $caption;
		}
		if (!empty($menu) and is_array($menu)) {
			$args['reply_markup'] = json_encode($this->createMenu($menu, $menutype));
		}
		return $this->request('sendDocument', $args, $response);
	}
	
	public function editMessageText ($chat_id, $text, $menu = [], $message_id, $parse_mode = 'def', $disable_web_page_preview = true, $response = false) {
		if ($parse_mode == 'def') {
			$parse_mode = $this->getParseMode();
		} elseif (!in_array(strtolower($parse_mode), ['html', 'markdown', 'markdownv2'])) {
			$parse_mode = '';
		}
		if (is_numeric($message_id)) {
			$args = [
				'chat_id'		=> $chat_id,
				'message_id'	=> $message_id
			];
		} else {
			$args = [
				'inline_message_id'	=> $message_id
			];
		}
		$args['text'] = $text;
		$args['parse_mode'] = $parse_mode;
		if (!empty($menu) and is_array($menu)) {
			$args['reply_markup'] = json_encode($this->createMenu($menu, 'inline'));
		}
		if ($disable_web_page_preview) {
			$args['disable_web_page_preview'] = true;
		}
		return $this->request('editMessageText', $args, $response);
	}
	
	public function forwardMessage ($chat_id, $from_chat_id, $message_id, $response = false) {
		$args = [
			'chat_id'		=> $chat_id,
			'from_chat_id'	=> $from_chat_id,
			'message_id'	=> $message_id
		];
		return $this->request('forwardMessage', $args, $response);
	}
	
	public function deleteMessage ($chat_id, $message_id, $response = false) {
		$args = [
			'chat_id'		=> $chat_id,
			'message_id'	=> $message_id
		];
		return $this->request('deleteMessage', $args, $response);
	}
	
	public function getChat ($chat_id) {
		$args = [
			'chat_id'		=> $chat_id
		];
		return $this->request('getChat', $args, true);
	}
	
	public function getChatAdministrators ($chat_id) {
		$args = [
			'chat_id'		=> $chat_id
		];
		return $this->request('getChatAdministrators', $args, true);
	}
	
	public function getChatMember ($chat_id, $user_id) {
		$args = [
			'chat_id'		=> $chat_id,
			'user_id'		=> $user_id
		];
		return $this->request('getChatMember', $args, true);
	}
	
	public function getFile ($file_id) {
		$args = [
			'file_id'		=> $file_id
		];
		$r = $this->request('getFile', $args, true);
		if ($r['ok']) {
			$prefix = 'https://api.telegram.org/file/bot' . $this->token . '/';
			return $prefix . $r['result']['file_path'];
		}
		return false;
	}
	
	public function uploadMedia ($file_id, $type = 'video') {
		global $bot;
		if (!$file_id) return false;
		
		$file = $this->getFile($file_id);
		if (!$file) return false;
		$time = time();
		if ($type == 'photo') {
			$args = ['file' => curl_file_create($file, 'image/jpeg')];
		} else {
			$args = ['file' => curl_file_create($file, 'video/mp4')];
		}
		$this->curl = curl_init();
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> 'https://telegra.ph/upload',
			CURLOPT_POST			=> 1,
			CURLOPT_POSTFIELDS		=> $args,
			CURLOPT_TIMEOUT			=> 2,
			CURLOPT_RETURNTRANSFER	=> 1
		]);
		$res = curl_exec($this->curl);
		$res = json_decode($res, true);
		if (isset($res[0]['src'])) {
			return 'https://telegra.ph' . $res[0]['src'];
		}
		return false;
	}
	
	public function leaveChat ($chat_id, $response = false) {
		$args = [
			'chat_id'		=> $chat_id
		];
		return $this->request('leaveChat', $args, $response);
	}
	
	public function answerCallbackQuery ($callback_query_id, $text = false, $alert = false, $url = false, $response = false) {
		$args = [
			'callback_query_id'		=> $callback_query_id
		];
		if ($text) {
			$args['text'] = $text;
			if ($alert) {
				$args['show_alert'] = true;
			} else {
				$args['show_alert'] = false;
			}
		} elseif ($url) {
			$args['url'] = $url;
		}
		return $this->request('answerCallbackQuery', $args, $response);
	}
	
	public function answerInlineQuery ($query_id, $results = [], $start_p = 'def', $response = false) {
		global $langs;
		
		if ($start_p === 'def') {
			$start_p = $langs->getTranslate('createNewPollButton');
		}
		$args = [
			'inline_query_id' => $query_id,
			'results' => json_encode($results),
			'cache_time' => 5,
			'switch_pm_text' => $start_p,
			'switch_pm_parameter' => 'start'
		];
		return $this->request('answerInlineQuery', $args, $response);
	}
}

?>
