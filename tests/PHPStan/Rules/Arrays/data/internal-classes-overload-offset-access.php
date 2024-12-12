<?php

namespace InternalClassesOverloadOffsetAccess;

function test1(\DOMNamedNodeMap $v): void
{
	if ($v['attribute_name']) {
		var_dump($v['attribute_name']);
	}
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test3(\DOMNodeList $v): void
{
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test7(\PDORow $v): void
{
	if ($v['name']) {
		var_dump($v['name']);
	}
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test8(\ResourceBundle $v): void
{
	var_dump($v['name']);
	var_dump($v[0]);
}

