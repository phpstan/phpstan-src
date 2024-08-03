<?php

return [
	'includes' => [
		__DIR__ . '/../../conf/bleedingEdge.neon',
	],
	'parameters' => [
		'level' => '8',
		'paths' => [__DIR__ . '/src'],
		'excludePaths' => [
			'analyse' => [
				'src/test.php', // not absolute path - invalid in .php config
			],
		],
	],
];
