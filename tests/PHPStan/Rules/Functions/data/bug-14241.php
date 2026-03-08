<?php declare(strict_types = 1);

namespace Bug14241;

function doFoo($_FILES): void {}

function doBar($_GET, $_POST): void {}

function doBaz($ok): void {}

class Foo
{
	public function doFoo($_SERVER): void {}

	public static function doBar($_SESSION): void {}
}

$f = function ($_COOKIE): void {};

$g = fn ($_REQUEST) => $_REQUEST;

function doQux($_ENV): void {}

function doQuux($GLOBALS): void {}
