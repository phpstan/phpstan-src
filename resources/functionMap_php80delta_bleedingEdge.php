<?php // phpcs:ignoreFile

return [
	'new' => [
		'array_rand' => ['int|string|array<int,int>|array<int,string>', 'input'=>'non-empty-array', 'num_req'=>'positive-int'],
		'array_rand\'1' => ['int|string', 'input'=>'non-empty-array'],
		'DOMDocument::load' => ['bool', 'filename'=>'non-empty-string', 'options='=>'int'],
		'DOMDocument::loadHTML' => ['bool', 'source'=>'non-empty-string', 'options='=>'int'],
		'DOMDocument::loadHTMLFile' => ['bool', 'filename'=>'non-empty-string', 'options='=>'int'],
		'DOMDocument::loadXML' => ['bool', 'source'=>'non-empty-string', 'options='=>'int'],
		'DOMDocument::relaxNGValidate' => ['bool', 'filename'=>'non-empty-string'],
		'DOMDocument::relaxNGValidateSource' => ['bool', 'source'=>'non-empty-string'],
		'DOMDocument::save' => ['int|false', 'filename'=>'non-empty-string', 'options='=>'int'],
		'DOMDocument::saveHTMLFile' => ['int|false', 'filename'=>'non-empty-string'],
		'DOMDocument::schemaValidate' => ['bool', 'filename'=>'non-empty-string', 'flags='=>'int'],
		'DOMDocument::schemaValidateSource' => ['bool', 'source'=>'non-empty-string', 'flags='=>'int'],
	],
	'old' => [
		'array_rand' => ['int|string|array<int,int>|array<int,string>', 'input'=>'array', 'num_req'=>'int'],
		'array_rand\'1' => ['int|string', 'input'=>'array'],
		'DOMDocument::load' => ['bool', 'filename'=>'string', 'options='=>'int'],
		'DOMDocument::loadHTML' => ['bool', 'source'=>'string', 'options='=>'int'],
		'DOMDocument::loadHTMLFile' => ['bool', 'filename'=>'string', 'options='=>'int'],
		'DOMDocument::loadXML' => ['bool', 'source'=>'string', 'options='=>'int'],
		'DOMDocument::relaxNGValidate' => ['bool', 'filename'=>'string'],
		'DOMDocument::relaxNGValidateSource' => ['bool', 'source'=>'string'],
		'DOMDocument::save' => ['int|false', 'filename'=>'string', 'options='=>'int'],
		'DOMDocument::saveHTMLFile' => ['int|false', 'filename'=>'string'],
		'DOMDocument::schemaValidate' => ['bool', 'filename'=>'string', 'flags='=>'int'],
		'DOMDocument::schemaValidateSource' => ['bool', 'source'=>'string', 'flags='=>'int'],
	],
];
