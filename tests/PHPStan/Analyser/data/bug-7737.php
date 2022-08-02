<?php declare(strict_types = 1);

namespace Bug7737;

use function PHPStan\dumpType;

function () {
	$context = [
		'values' => [1, 2, 3],
	];
	foreach ($context['values'] as $context["_key"] => $context["value"]) {
		echo sprintf("Key: %s, Value: %s\n", $context["_key"], $context["value"]);
	}

	unset($context["_key"]);
};

function () {
	$context = [
		'values' => [1, 2, 3],
	];
	foreach ($context['values'] as $context["_key"] => $value) {
		echo sprintf("Key: %s, Value: %s\n", $context["_key"], $value);
	}

	unset($context["_key"]);
};
