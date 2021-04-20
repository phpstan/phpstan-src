<?php

namespace Bug2863;

use function PHPStan\Testing\assertType;

$result = json_decode('{"a":5}');
assertType('int', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());

//
$result2 = json_decode('');
assertType('int', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());

//
$result3 = json_encode([]);
assertType('int', json_last_error());
assertType('string', json_last_error_msg());

if (json_last_error() !== JSON_ERROR_NONE || json_last_error_msg() !== 'No error') {
	throw new Exception(json_last_error_msg());
}

assertType('0', json_last_error());
assertType("'No error'", json_last_error_msg());
