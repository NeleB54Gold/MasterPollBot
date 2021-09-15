<?php

if ($update_type == "callback_query") {
	$user_id = $update['callback_query']['from']['id'];
	if ($update['callback_query']['inline_message_id']) {
		$message_id = $update['callback_query']['inline_message_id'];
	} else {
		$chat_id = $update['callback_query']['message']['chat']['id'];
		$message_id = $update['callback_query']['message']['message_id'];
	}
	$gcommand = $callback_data = $update['callback_query']['data'];
} else {
	$message = $update['message']['text'];
	$message_id = $update['message']['message_id'];
	if (isset($message)) {
		if ($message[0] == "/") {
			$gcommand = $command = substr($message, 1, strlen($message));
		}
	}
	$user_id = $update['message']['from']['id'];
	$chat_id = $update['message']['chat']['id'];
}

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

if (strpos($callback_data, "v_") === 0) {
	require("./masterpoll.php");
	$mp = new MasterPoll($bf);
	$e = explode("_", $callback_data);
	$poll = $mp->getPoll($e[1], ['*', 'votes']);
	$view = false;
	$url = false;
	if ($db->isBanned($user_id, $poll['owner_id'], $poll['id'])) {
		# Check if the user is banned
		$cbtext = $langs->getTranslate('voteNotAllowed');
		$view = true;
	} elseif ($poll['status'] !== 2) {
		# Check if the poll is open
		$cbtext = $langs->getTranslate('tryVotePollClosed');
		$view = true;
	} elseif (isset($poll['settings']['options']['0-5']) and round(count($poll['votes'])) >= $poll['settings']['options']['0-5']) {
		# Check if the poll is full
		$cbtext = $langs->getTranslate('votesLimitCallback');
		$view = true;
	} elseif ($poll['type'] == 0) { // Vote
		$choice_id = $e[2];
		if (!isset($e[2]) or !isset($poll['choices'][$choice_id])) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'), true);
			die;
		}
		$thereis = $mp->votePoll($poll['id'], $user['id'], $user['name'], $choice_id);
		if ($thereis) {
			$cbtext = $langs->getTranslate('callbackVotedFor', [$poll['choices'][$e[2]]]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBack', [$poll['choices'][$e[2]]]);
			$view = true;
		}
	} elseif ($poll['type'] == 1) { // Doodle
		$choice_id = $e[2];
		if (!isset($e[2]) or !isset($poll['choices'][$choice_id])) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'), true);
			die;
		}
		$thereis = $mp->voteDoodle($poll['id'], $user['id'], $user['name'], $choice_id);
		if ($thereis) {
			$cbtext = $langs->getTranslate('callbackVotedFor', [$poll['choices'][$e[2]]]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBack', [$poll['choices'][$e[2]]]);
			$view = true;
		}
	} elseif ($poll['type'] == 2) { // Limited Doodle
		$choice_id = $e[2];
		if (!isset($e[2]) or !isset($poll['choices'][$choice_id])) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'), true);
			die;
		}
		$thereis = $mp->voteLimitedDoodle($poll['id'], $user['id'], $user['name'], $choice_id);
		if (strpos($thereis, 'overLimit') === 0) {
			$myc = str_replace('overLimit', '', $thereis);
			$cbtext = $langs->getTranslate('limitedDoodleYouCanChoose', [$poll['settings']['limitedDoodleLimit'], $myc]);
			$view = true;
		} elseif ($thereis) {
			$cbtext = $langs->getTranslate('callbackVotedFor', [$poll['choices'][$e[2]]]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBack', [$poll['choices'][$e[2]]]);
			$view = true;
		}
	} elseif ($poll['type'] == 3) { // Board
		# Redirect to the comment command
		$cbtext = false;
		$url = "https://t.me/" . $bot->getUsername() . "?start=comment_" . round($poll['id']);
	} elseif ($poll['type'] == 4) { // Rating
		$choice_id = $e[2] - 1;
		if (!isset($e[2]) or !isset($poll['choices'][$choice_id])) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'), true);
			die;
		}
		$thereis = $mp->voteRating($poll['id'], $user['id'], $user['name'], $choice_id);
		if ($thereis) {
			$cbtext = $langs->getTranslate('callbackVotedFor', [$poll['choices'][$choice_id]]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBack', [$poll['choices'][$choice_id]]);
			$view = true;
		}
	} elseif ($poll['type'] == 5) { // Participation
		# Participate or remove your participation
		$thereis = $mp->addParticipation($e[1], $user['id'], $user['name']);
		if ($thereis) {
			$cbtext = $langs->getTranslate('callbackParticipateIn', [$poll['title']]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBackParticipation', [$poll['title']]);
			$view = true;
		}
	} elseif ($poll['type'] == 6) { // Quiz
		$choice_id = $e[2];
		if (!isset($e[2]) or !isset($poll['choices'][$choice_id])) {
			$bot->answerCallbackQuery($update['callback_query']['id'], $langs->getTranslate('generalError'), true);
			die;
		}
		$thereis = $mp->votePoll($poll['id'], $user['id'], $user['name'], $choice_id);
		if ($thereis) {
			$cbtext = $langs->getTranslate('callbackVotedFor', [$poll['choices'][$e[2]]]);
		} else {
			$cbtext = $langs->getTranslate('callbackTookBack', [$poll['choices'][$e[2]]]);
			$view = true;
		}
	} else {
		$cbtext = $langs->getTranslate('generalError');
	}
	$bot->answerCallbackQuery($update['callback_query']['id'], $cbtext, $view, $url);
	$poll = $mp->getPoll($e[1], ['*', 'votes']);
	$message = $mp->createPollMessage($poll);
	$bot->editMessageText($chat_id, $message['text'], $message['reply_markup'], $message_id, 'def', $message['disable_web_page_preview']);
	if (isset($chat_id)) {
		$mp->updatePollMessages($poll, ['message_id' => $message_id, 'chat_id' => $chat_id]);
	} else {
		$mp->updatePollMessages($poll, ['message_id' => $message_id]);
	}
	die;
} elseif (strpos($callback_data, "share_") === 0) {
	$e = explode("_", $callback_data);
	$url = "https://t.me/" . $bot->getUsername() . "?start=share_{$e[1]}";
	$bot->answerCallbackQuery($update['callback_query']['id'], '', true, $url);
	die;
} elseif (strpos($callback_data, "append_") === 0) {
	$e = explode("_", $callback_data);
	$url = "https://t.me/" . $bot->getUsername() . "?start=append_{$e[1]}";
	$bot->answerCallbackQuery($update['callback_query']['id'], '', true, $url);
	die;
} else {
	$bot->answerCallbackQuery($update['callback_query']['id'], "Unknown action...", true);
}

die;

?>
