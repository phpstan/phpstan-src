<?php // lint >= 8.0

namespace TriggerErrorUserError;

function doFoo(int $level, bool $b): void
{
	if ($b) {
		trigger_error('Something went wrong', E_USER_ERROR);
	}
	if ($b) {
		\trigger_error('Something went wrong', 256);
	}
	if ($b) {
		trigger_error(error_level: E_USER_ERROR, message: 'Something went wrong');
	}
	if ($b) {
		user_error('Something went wrong', E_USER_ERROR);
	}

	trigger_error('Something went wrong');
	trigger_error('Something went wrong', E_USER_WARNING);
	trigger_error('Something went wrong', E_USER_DEPRECATED);
	trigger_error('Something went wrong', $b ? E_USER_ERROR : E_USER_WARNING);
	trigger_error('Something went wrong', $level);
}
