<?php declare(strict_types = 1);

// Incorrect
$string = preg_replace_callback(
	'#pattern#',
	function(string $string): string {
		return $string;
	},
	'subject'
);
$string = preg_replace_callback(
	'#pattern#',
	function(string $string) {
		return $string;
	},
	'subject'
);
$string = preg_replace_callback(
	'#pattern#',
	function(array $matches) {},
	'subject'
);
$string = preg_replace_callback(
	'#pattern#',
	function() {},
	'subject'
);

// Correct
$string = preg_replace_callback(
	'#pattern#',
	function(array $matches): string {
		return $matches[0];
	},
	'subject'
);
$string = preg_replace_callback(
	'#pattern#',
	function(array $matches) {
		return $matches[0];
	},
	'subject'
);
$string = preg_replace_callback(
	'#pattern#',
	function() {
		return 'Hello';
	},
	'subject'
);
