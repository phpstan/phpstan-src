<?php // phpcs:ignoreFile

return [
	'new' => [
		'array_rand' => ['int|string|array<int,int>|array<int,string>', 'input'=>'non-empty-array', 'num_req'=>'positive-int'],
		'array_rand\'1' => ['int|string', 'input'=>'non-empty-array'],
	],
	'old' => [
		'array_rand' => ['int|string|array<int,int>|array<int,string>', 'input'=>'array', 'num_req'=>'int'],
		'array_rand\'1' => ['int|string', 'input'=>'array'],
	],
];
