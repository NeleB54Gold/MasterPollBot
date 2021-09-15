<?php

class Languages
{
	public $user_language = 'en';
	public $redis_check = false;
	
	function __construct ($user_language, $report = []) {
		$this->report = $report;
		$this->user_language = $user_language;
	}
	
	public function getTranslate ($string, $args = [], $lang_u = 'def') {
		global $db;
		
		if (!$db->rget('tr-MasterPoll-status')) {
			$json = json_decode(file_get_contents('./translations.json'), true);
			$db->rset('tr-MasterPoll-status', true);
			$db->rdel($db->rkeys('tr-MasterPoll*'));
			$db->rset('tr-MasterPoll-status', true);
			foreach ($json as $lang => $strings) {
				foreach($strings as $stringn => $translation) {
					$db->rset('tr-MasterPoll-' . $lang . '-' . $stringn, $translation);
				}
			}
			
		}
		if ($lang_u == 'def') {
			$lang = $this->user_language;
		} else {
			$lang_u = strtolower($lang_u);
			$lang = $lang_u;
		}
		$string = str_replace(' ', '', $string);
		if ($t_string = $db->rget('tr-MasterPoll-' . $lang . '-' . $string) and $lang !== 'en') {
		} elseif ($t_string = $db->rget('tr-MasterPoll-en-' . $string)) {
		} else {
			$this->report->error('Language Warning: The string ' . $string . ' was not found in ' . $lang . ' language on translations file');
			$t_string = '🤖';
		}
		if (is_array($args) and $args) {
			$args = array_values($args);
			foreach(range(0, count($args) - 1) as $num) {
				$t_string = str_replace('[' . $num . ']', $args[$num], $t_string);
			}
		}
		return $t_string;
	}
	
	public function getTranslations () {
		global $configs;
		global $bot;
		
		date_default_timezone_set('GMT');
		$time = time();
		$args = [
			'api-key'		=> $configs['oneskyapp']['api-key'],
			'timestamp'		=> $time,
			'dev-hash'		=> md5($time . $configs['oneskyapp']['secret'])
		];
		$args['platform-id'] = $configs['oneskyapp']['platform-id'];
		$url = 'http://api.oneskyapp.com/2/string/output?' . http_build_query($args);
		if (!isset($this->curl)) $this->curl = curl_init();
		curl_setopt_array($this->curl, [
			CURLOPT_URL				=> $url,
			CURLOPT_POST			=> false,
			CURLOPT_TIMEOUT			=> 2,
			CURLOPT_RETURNTRANSFER	=> true
		]);
		$r = json_decode(curl_exec($this->curl), true);
		if (isset($r['translation'])) {
			if (isset($r['translation'][$configs['oneskyapp']['file_name']])) {
				return [
					'ok'		=> true,
					'result'	=> $r['translation'][$configs['oneskyapp']['file_name']]
				];
			} else {
				return [
					'ok'		=> false,
					'result'	=> ['response' => 'File name not exists']
				];
			}
		} else {
			return [
				'ok'		=> false,
				'result'	=> $r
			];
		}
	}
}

?>