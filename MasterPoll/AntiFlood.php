<?php

class AntiFlood {
	public $messages = 5;
	public $seconds = 1;
	public $bantime = 60;
	public $banned = 0;
	public $send_notice = 0;
	public $key = 'MasterPoll-AF-0';
	public $updates = [];
	
	public function __construct ($db, $id) {
		if (!$db) return;
		$this->rkey = 'MasterPoll-AF-' . $id;
		$this->rbkey = 'MasterPoll-AF-' . $id . '-ban';
		if ($db->rget($this->rbkey)) return $this->banned = 1;
		$db->rlistAdd($this->rkey, time());
		$db->redis->expire($this->rkey, 60);
		if (!empty($this->updates = $db->rgetList($this->rkey))) {
			foreach ($this->updates as $k => $time) {
				if ($time <= (time() - $this->seconds)) {
					unset($this->updates[$k]);
					$db->rListRem($this->rkey, $time, $k);
				}
			}
			if (count($this->updates) >= $this->messages) {
				$db->rset($this->rbkey, 1, round($this->bantime));
				# $db->ban($id); // [Database required] Only if you want to automatically perma-ban users with this AntiFlood System
				$db->rdel($this->rkey);
				$this->banned = 1;
				return $this->send_notice = 1;
			}
		}
	}	
}

?>