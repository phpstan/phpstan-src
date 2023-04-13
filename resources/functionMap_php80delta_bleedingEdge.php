<?php // phpcs:ignoreFile

return [
	'new' => [
		'filter_input' => ['mixed', 'type'=>'INPUT_GET|INPUT_POST|INPUT_COOKIE|INPUT_SERVER|INPUT_ENV', 'variable_name'=>'string', 'filter='=>'int', 'options='=>'array|int'],
		'filter_input_array' => ['array|false|null', 'type'=>'INPUT_GET|INPUT_POST|INPUT_COOKIE|INPUT_SERVER|INPUT_ENV', 'definition='=>'int|array', 'add_empty='=>'bool'],
	],
	'old' => [

	]
];
