<?php

require("/home/NeleBot/MasterPoll/masterpoll.php");
$mp = new MasterPoll($bf);

if (isset($update['inline_query']['id'])) {
	$query = $update['inline_query']['query'];
	$user_id = $update['inline_query']['from']['id'];
	$results = [];

	$thumbs = [
		0 => [
			0 => "https://telegra.ph/file/ed125dd969f566e5864eb.png",
			1 => "https://telegra.ph/file/7c4be8cc4a54dd0e65904.png"
		],
		1 => [
			0 => "https://telegra.ph/file/32eb013b2575291f62bd6.png",
			1 => "https://telegra.ph/file/42fa736cc1a7755badb55.png"
		],
		2 => [
			0 => "https://telegra.ph/file/d08d7b4a174efe0c023ad.png",
			1 => "https://telegra.ph/file/c3b5880426908724aa25d.png"
		],
		3 => [
			0 => "https://telegra.ph/file/6b68666ea8efb9e1e748d.png",
			1 => "https://telegra.ph/file/2e348b666d6ef90ecb283.png"
		],
		4 => [
			0 => "https://telegra.ph/file/355dba88dd53b553708a6.png",
			1 => "https://telegra.ph/file/23b8f68899c2477118839.png"
		],
		5 => [
			0 => "https://telegra.ph/file/7c4be8cc4a54dd0e65904.png",
			1 => "https://telegra.ph/file/7c4be8cc4a54dd0e65904.png"
		],
		6 => [
			0 => "https://telegra.ph/file/355dba88dd53b553708a6.png",
			1 => "https://telegra.ph/file/23b8f68899c2477118839.png"
		]
	];
	
	if (is_numeric($query)) {
		$poll = $mp->getUserPoll($user_id, round(str_replace("-", '', $query)));
		if ($poll !== false and $poll['status'] === 2) {
			$message = $mp->createPollMessage($poll, ['inline']);
			$description = $mp->createInlineDescription($poll);
			$results = [
				$bot->createInlineResult(
					"article",
					"poll-{$poll['id']}",
					"{$poll['title']}",
					$description['text'],
					"{$message['text']}",
					'def',
					$message['disable_web_page_preview'],
					$message['reply_markup'],
					$thumbs[$poll['type']][$poll['anonymous']]
				)
			];
			$bot->answerInlineQuery($update['inline_query']['id'], $results);
			die;
		}
	} elseif (empty($query)) {
		$polls = $mp->getPollsList($user_id, 0, 50, " and status = 2");
		foreach ($polls as $poll) {
			if ($poll !== false and $poll['status'] === 2) {
				$poll = $mp->getPoll($poll['id']);
				$message = $mp->createPollMessage($poll, ['inline']);
				$description = $mp->createInlineDescription($poll);
				$results[] = $bot->createInlineResult(
					"article",
					"poll-{$poll['id']}",
					"{$poll['title']}",
					$description['text'],
					"{$message['text']}",
					'def',
					$message['disable_web_page_preview'],
					$message['reply_markup'],
					$thumbs[$poll['type']][$poll['anonymous']]
				);
			}
		}
		$bot->answerInlineQuery($update['inline_query']['id'], $results);
		die;
	} else {
		if (strpos($query, "share ") === 0 and is_numeric(explode(" ", $query, 2)[1])) {
			$poll = $db->query("SELECT * FROM polls WHERE id = ? LIMIT 1", [round(explode(" ", $query, 2)[1])], true);
			$poll['settings'] = json_decode($poll['settings'], true);
			if ($poll !== false and $poll['status'] === 2 and $poll['settings']['options']['1-0']) {
				$poll['choices'] = json_decode($poll['choices'], true);
				$poll['votes'] = $mp->getVotes($poll['id']);
				$message = $mp->createPollMessage($poll, ['inline']);
				$description = $mp->createInlineDescription($poll);
				$results[] = $bot->createInlineResult(
					"article",
					"poll-{$poll['id']}",
					"{$poll['title']}",
					$description['text'],
					"{$message['text']}",
					'def',
					$message['disable_web_page_preview'],
					$message['reply_markup'],
					$thumbs[$poll['type']][$poll['anonymous']]
				);
			}
			$bot->answerInlineQuery($update['inline_query']['id'], $results);
			die;
		} else {
			$polls = $db->query("SELECT * FROM polls WHERE owner_id = ? and status = ? and STRPOS(LOWER(title), ?) > 0 ORDER BY id DESC OFFSET ? LIMIT ?", [$user_id, 2, strtolower($query), 0, 50], 'fetch');
			foreach ($polls as $poll) {
				if ($poll !== false and $poll['status'] === 2) {
					$poll['settings'] = json_decode($poll['settings'], true);
					$poll['votes'] = $mp->getVotes($poll['id']);
					$poll['choices'] = json_decode($poll['choices'], true);
					$message = $mp->createPollMessage($poll, ['inline']);
					$description = $mp->createInlineDescription($poll);
					$results[] = $bot->createInlineResult(
						"article",
						"poll-{$poll['id']}",
						"{$poll['title']}",
						$description['text'],
						"{$message['text']}",
						'def',
						$message['disable_web_page_preview'],
						$message['reply_markup'],
						$thumbs[$poll['type']][$poll['anonymous']]
					);
				}
			}
			$bot->answerInlineQuery($update['inline_query']['id'], $results);
			die;
		}
	}
	$bot->answerInlineQuery($update['inline_query']['id']);
	die;
} elseif (isset($update['chosen_inline_result']['inline_message_id'])) {
	if (isset($update['chosen_inline_result']['inline_message_id']) and strpos($update['chosen_inline_result']['result_id'], "poll-") === 0) {
		$mp->addPollMessage(str_replace('poll-', '', $update['chosen_inline_result']['result_id']), $update['chosen_inline_result']['inline_message_id'], false, ['inline']);
	}
	die;
}

?>
