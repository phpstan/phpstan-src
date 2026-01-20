<?php // phpcs:ignoreFile

return [
	'new' => [
		'php_uname' => ['string', 'mode='=>'"a"|"s"|"n"|"r"|"v"|"m"'],
		'SplFileInfo::__construct' => ['void', 'filename'=>'non-empty-string'],
		'SplFileInfo::__toString' => ['non-empty-string'],
		'SplFileInfo::getBasename' => ['non-empty-string', 'suffix='=>'string'],
		'SplFileInfo::getFilename' => ['non-empty-string'],
		'SplFileInfo::getPathname' => ['non-empty-string'],
		'SplFileInfo::getRealPath' => ['non-empty-string|false'],
	],
	'old' => [
		'php_uname' => ['string', 'mode='=>'string'],
	],
];
