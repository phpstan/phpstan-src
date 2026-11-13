<?php

namespace TriggerErrorUserErrorPhpVersion;

if (PHP_VERSION_ID < 80400) {
	trigger_error('Something went wrong', E_USER_ERROR);
}

if (PHP_VERSION_ID >= 80400) {
	trigger_error('Something went wrong', E_USER_ERROR);
}
