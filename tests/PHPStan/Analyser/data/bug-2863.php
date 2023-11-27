<?php

namespace Bug2863;

use function PHPStan\Testing\assertType;

$result = json_decode('{"a":5}');
assertType('0|1|2|3|4|5|6|7|8|9|10', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());

//
$result2 = json_decode('');
assertType('0|1|2|3|4|5|6|7|8|9|10', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());

//
$result3 = json_encode([]);
assertType('0|1|2|3|4|5|6|7|8|9|10', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());
