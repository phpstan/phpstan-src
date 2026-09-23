<?php // lint >= 8.5

namespace FilterVarNullAndThrowPhpVersion;

function (string $s): void {
	if (PHP_VERSION_ID >= 80500) {
		filter_var($s, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE);
	} else {
		filter_var($s, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE|FILTER_NULL_ON_FAILURE);
	}
};
