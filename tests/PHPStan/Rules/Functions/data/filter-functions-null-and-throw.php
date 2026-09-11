<?php // lint >= 8.5

declare(strict_types = 1);

namespace FilterFunctionsNullAndThrow;

filter_var(value: 'foo@bar.test', options: FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE, filter: FILTER_VALIDATE_EMAIL);
filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE);
filter_input(type: INPUT_GET, options: FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE, var_name: 'foo', filter: FILTER_VALIDATE_INT);
filter_var_array([], ['foo' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE]]);
filter_input_array(INPUT_GET, ['foo' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE]]);

// the flags belong to different per-key specifications
filter_var_array([], [
	'foo' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_NULL_ON_FAILURE],
	'bar' => ['filter' => FILTER_VALIDATE_INT, 'flags' => FILTER_THROW_ON_FAILURE],
]);

// an integer $options is the filter id, it does not carry any flags
filter_var_array([], FILTER_VALIDATE_INT);
filter_input_array(INPUT_GET, FILTER_VALIDATE_INT);
