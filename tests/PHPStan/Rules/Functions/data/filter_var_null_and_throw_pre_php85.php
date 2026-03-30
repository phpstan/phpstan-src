<?php // lint < 8.5

namespace FilterVarNullAndThrowPrePhp85;

// On PHP < 8.5, FILTER_THROW_ON_FAILURE doesn't truly exist
// so combining it with FILTER_NULL_ON_FAILURE should not be an error
filter_var('foo@bar.test', FILTER_VALIDATE_EMAIL, FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE);

$flag = FILTER_NULL_ON_FAILURE|FILTER_THROW_ON_FAILURE;
filter_var(100, FILTER_VALIDATE_INT, $flag);

filter_var(
	'johndoe',
	FILTER_VALIDATE_REGEXP,
	['options' => ['regexp' => '/^[a-z]+$/'], 'flags' => FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE]
);
