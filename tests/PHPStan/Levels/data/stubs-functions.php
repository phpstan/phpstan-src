<?php

namespace StubsIntegrationTest\Functions;

function foo($i)
{
	return '';
}

function () {
	foo('test');
	$string = foo(1);
	foo($string);
};
