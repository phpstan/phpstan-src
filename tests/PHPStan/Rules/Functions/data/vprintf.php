<?php

namespace PrintfArrayParametersRuleTest;

function doFoo($message, array $arr) {
	vsprintf($message, 'foo'); // skip - format not a literal string

	vsprintf('%s', ['foo']); // ok
	vsprintf('%s %% %% %s', ['foo', 'bar']); // ok
	vsprintf('%s %s', ['foo']); // one parameter missing
	vsprintf('foo', ['foo']); // one parameter over
	vsprintf('foo %s', ['foo', 'bar']); // one parameter over
	vsprintf('%2$s %1$s %% %1$s %%%', ['one']); // one parameter missing
	vsprintf('%2$s %%'); // two parameters required
	vsprintf('%2$s %%', []); // two parameters required
	vsprintf('%2$s %1$s %1$s %s %s %s %s'); // four parameters required
	vsprintf('%2$s %1$s %% %s %s %s %s %%% %%%%', ['one', 'two', 'three', 'four']); // ok
	vsprintf("%'.9d %1$'.9d %0.3f %d %d %d", [123, 456]); // five parameters required

	vsprintf('%-4s', ['foo']); // ok
	vsprintf('%%s %s', ['foo', 'bar']); // one parameter over


	if (rand(0,1)) {
		$args = ['foo'];
	} else {
		$args = ['foo', 'bar'];
	}
	vsprintf('%-4s', $args); // one path with wrong number of args


	vprintf('%s', ['foo']); // ok
	vprintf('%s %% %% %s', ['foo', 'bar']); // ok
	vprintf('%s %s', ['foo']); // one parameter missing
	vprintf('abc'); // caught by CallToFunctionParametersRule
	vsprintf('abc', []); // ok
	vsprintf('abc', $arr); // ok

	if (rand(0,1)) {
		$format = '%s';
		$args = ['foo'];
	} else {
		$format = '%s%s';
		$args = ['foo', 'bar'];
	}
	vsprintf($format, $args); // ok

	if (rand(0,1)) {
		$format = '%s';
	} else {
		$format = '%s%s';
	}
	vsprintf($format, $arr); // need at least non-empty-array

	if (rand(0,1)) {
		$format = '%s';
	} else {
		$format = '%s%s';
	}
	if ($arr !== []) {
		vsprintf($format, $arr); // ok
	}

}
