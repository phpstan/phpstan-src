<?php

namespace JsonValidateParams;

function doFoo() {
	$x = json_validate('{ "test": { "foo": "bar" } }', 0, 0); // invalid depth
	$x = json_validate('{ "test": { "foo": "bar" } }', 100, JSON_BIGINT_AS_STRING); // invalid flags

	$x = json_validate('{ "test": { "foo": "bar" } }');
	$x = json_validate('{ "test": { "foo": "bar" } }', 100, 0);
	$x = json_validate('{ "test": { "foo": "bar" } }', 100, JSON_INVALID_UTF8_IGNORE);

}
