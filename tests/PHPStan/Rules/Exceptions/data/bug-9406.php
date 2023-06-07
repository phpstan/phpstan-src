<?php

namespace Bug9406;

function foo(): void
{
	if ($_POST['foo'] === null) {
		throw new \InvalidArgumentException('Foo');
	}
}


function app(): void
{
	try {
		foo();

		if ($_POST['bar'] === null) {
			throw new \RuntimeException('Bar');
		}
	} catch (\RuntimeException|\InvalidArgumentException $e) {

	}
}

function app2(): void
{
	try {
		foo();

		if ($_POST['bar'] === null) {
			throw new \RuntimeException('Bar');
		}
	} catch (\InvalidArgumentException $e) {

	}
}
