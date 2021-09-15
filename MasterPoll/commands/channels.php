<?php

if ($chat['status'] == 'ban') {
	$bot->leaveChat($chat['id']);
	die;
}

if ($update_type == "callback_query") {
	$status = $db->rget("MP_{$bot->bot_id}-Chat_{$chat_id}");
	if (empty($status)) {
		$getBot = $bot->getChatMember($chat['id'], $bot->getID());
		if ($getBot['ok']) {
			if (!in_array($getBot['result']['status'], ['member', 'administrator'])) {
				$db->rset("MP_{$bot->bot_id}-Chat_{$chat_id}", 0, 60);
				$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('botNotChannelMember'), true);
				die;
			} else {
				$db->rset("MP_{$bot->bot_id}-Chat_{$chat_id}", 1, 60);
			}
		} else {
			$db->rset("MP_{$bot->bot_id}-Chat_{$chat_id}", 0, 60);
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('botNotChannelMember'), true);
			die;
		}
	} else {
		if ($status == 0) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('botNotChannelMember'), true);
			die;
		}
	}
} else {
	if (isset($update['message']['new_chat_member']) and $update['message']['new_chat_member']['id'] == $bot->getID()) {
		$db->rset("MP_{$bot->bot_id}-Chat_{$chat_id}", 1, 60);
	}
	die;
}

?>