<?php declare(strict_types = 1);

namespace Bug15013RuleTest;

function () {
	$string = 'App/Service::foo';

	[$first, $second] = explode(':::', $string);
};

function () {
	$string = 'App/Service::foo';

	[$first, $second] = explode('::', $string);
};
