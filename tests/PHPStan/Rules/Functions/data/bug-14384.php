<?php declare(strict_types = 1);

namespace Bug14384;

$canCall = function_exists('some_totally_nonexistent_function_14384');

if ($canCall) {
	some_totally_nonexistent_function_14384();
}
