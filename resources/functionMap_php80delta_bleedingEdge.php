<?php // phpcs:ignoreFile

return [
	'new' => [
		'error_log' => ['bool', 'message'=>'string', 'message_type='=>'0|1|3|4', 'destination='=>'string', 'extra_headers='=>'string'],
		'filter_input' => ['mixed', 'type'=>'INPUT_GET|INPUT_POST|INPUT_COOKIE|INPUT_SERVER|INPUT_ENV', 'variable_name'=>'string', 'filter='=>'int', 'options='=>'array|int'],
		'filter_input_array' => ['array|false|null', 'type'=>'INPUT_GET|INPUT_POST|INPUT_COOKIE|INPUT_SERVER|INPUT_ENV', 'definition='=>'int|array', 'add_empty='=>'bool'],
		'hash_hkdf' => ['non-falsy-string', 'algo'=>'non-falsy-string', 'key'=>'string', 'length='=>'0|positive-int', 'info='=>'string', 'salt='=>'string'],
		'hash_pbkdf2' => ['non-empty-string', 'algo'=>'non-falsy-string', 'password'=>'string', 'salt'=>'string', 'iterations'=>'positive-int', 'length='=>'0|positive-int', 'raw_output='=>'bool'],
		'imagecreate' => ['__benevolent<GdImage|false>', 'width'=>'int<1, max>', 'height'=>'int<1, max>'],
		'imagecreatetruecolor' => ['__benevolent<GdImage|false>', 'width'=>'int<1, max>', 'height'=>'int<1, max>'],
		'mb_detect_order' => ['bool|list<non-falsy-string>', 'encoding_list='=>'non-empty-list<non-falsy-string>|non-falsy-string|null'],
	],
	'old' => [

	]
];
