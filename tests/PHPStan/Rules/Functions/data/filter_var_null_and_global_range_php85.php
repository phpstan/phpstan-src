<?php // lint >= 8.5

namespace FilterVarNullAndGlobalRangePhp85;

filter_var('foo@bar.test', FILTER_VALIDATE_EMAIL, FILTER_FLAG_GLOBAL_RANGE|FILTER_NULL_ON_FAILURE);

$flag = FILTER_NULL_ON_FAILURE|FILTER_FLAG_GLOBAL_RANGE;
filter_var(100, FILTER_VALIDATE_INT, $flag);

filter_var(
	'johndoe',
	FILTER_VALIDATE_REGEXP,
	['options' => ['regexp' => '/^[a-z]+$/'], 'flags' => FILTER_FLAG_GLOBAL_RANGE|FILTER_NULL_ON_FAILURE]
);
