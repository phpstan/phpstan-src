<?php

namespace InternalClassesOverloadOffsetAccess\Php84;

function test1(\DOMNamedNodeMap $v): void
{
	if ($v['attribute_name']) {
		var_dump($v['attribute_name']);
	}
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test2(\Dom\NamedNodeMap $v): void
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

function test4(\Dom\NodeList $v): void
{
	//var_dump($v['attribute_name']);
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test5(\Dom\HTMLCollection $v): void
{
	if ($v['name']) {
		var_dump($v['name']);
	}
	if ($v[0]) {
		var_dump($v[0]);
	}
}

function test6(\Dom\DtdNamedNodeMap $v): void
{
	if ($v['name']) {
		var_dump($v['name']);
	}
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
