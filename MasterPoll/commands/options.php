<?php

$options = [
	0	=> [
		'optionName'	=> "votes",
		'compatibility'	=> [
			'types'			=> range(0, 6),
			'anonymous'		=> [0, 1]
		],
		'subOptions'	=> [
			0	=> [
				'optionName'	=> 'appendable',
				'type'			=> 'bool',
				'compatibility'	=> [
					'types'			=> range(0, 2)
				]
			],
			1	=> [
				'optionName'	=> 'sorted',
				'type'			=> 'bool',
				'compatibility'	=> [
					'types'			=> range(0, 2)
				]
			],
			2	=> [
				'optionName'	=> 'hideVoters',
				'type'			=> 'bool',
				'compatibility'	=> [
					'types'			=> [0, 1, 2, 3, 5, 6],
					'anonymous'		=> [0]
				]
			],
			3	=> [
				'optionName'	=> 'closingTime',
				'type'			=> 'time',
				'premiumOnly'	=> 1
			],
			4	=> [
				'optionName'	=> 'openingTime',
				'type'			=> 'time',
				'premiumOnly'	=> 1
			],
			5	=> [
				'optionName'	=> 'votersLimit',
				'type'			=> 'numeric'
			],
			6	=> [
				'optionName'	=> 'resetVotes'
			],
			7	=> [
				'optionName'	=> 'createGraph',
				'compatibility'	=> [
					'types'			=> [0, 1, 2, 4, 6]
				]
			],
			8	=> [
				'optionName'	=> 'turnAnonymous',
				'compatibility'	=> [
					'types'			=> [0, 1, 2, 3, 4, 6],
					'anonymous'		=> [0]
				]
			]
		]
	],
	1	=> [
		'optionName'	=> "general",
		'compatibility'	=> [
			'types'			=> range(0, 6),
			'anonymous'		=> [0, 1]
		],
		'subOptions'	=> [
			0	=> [
				'optionName'	=> 'shareable',
				'type'			=> 'bool'
			],
			1	=> [
				'optionName'	=> 'webPagePreview',
				'type'			=> 'bool'
			],
			2	=> [
				'optionName'	=> 'webDocumentPreview',
				'type'			=> 'text'
			],
			3	=> [
				'optionName'	=> 'administrators',
				'type'			=> 'array'
			],
			4	=> [
				'optionName'	=> 'editTitleOrDescription'
			],
			5	=> [
				'optionName'	=> 'clone'
			],
			6	=> [
				'optionName'	=> 'export',
				'formats'		=> ['json', 'yaml', 'xml']
			]
		]
	],
	2	=> [
		'optionName'	=> "style",
		'compatibility'	=> [
			'types'			=> [0, 1, 2, 4],
			'anonymous'		=> [0, 1]
		],
		'subOptions'	=> [
			0	=> [
				'optionName'	=> 'buttons',
				'type'			=> 'numeric'
			],
			1	=> [
				'optionName'	=> 'message',
				'type'			=> 'numeric',
				'compatibility'	=> [
					'types'			=> range(0, 2)
				]
			],
			2	=> [
				'optionName'	=> 'progressBar',
				'type'			=> 'numeric',
				'compatibility'	=> [
					'types'			=> range(0, 2)
				]
			]
		]
	],
	3	=> [
		'optionName'	=> "moderation",
		'compatibility'	=> [
			'types'			=> range(0, 3),
			'anonymous'		=> [0, 1]
		],
		'subOptions'	=> [
			0	=> [
				'optionName'	=> 'notification',
				'type'			=> 'bool'
			],
			1	=> [
				'optionName'	=> 'antiSpam',
				'type'			=> 'bool',
				'premiumOnly'	=> 1
			],
			2	=> [
				'optionName'	=> 'moderate'
			],
			3	=> [
				'optionName'	=> 'forbiddenWords',
				'type'			=> 'array',
				'premiumOnly'	=> 1
			]
		]
	]
];

?>