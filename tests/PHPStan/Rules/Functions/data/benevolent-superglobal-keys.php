<?php

declare(strict_types=1);

namespace CallFunctionsBenevolentSuperglobalKeys;

function benevolentKeysOfSuperglobalsString(): void
{
	foreach ($GLOBALS as $k => $v) {
		trim($k);
	}

	foreach ($_SERVER as $k => $v) {
		trim($k);
	}

	foreach ($_GET as $k => $v) {
		trim($k);
	}

	foreach ($_POST as $k => $v) {
		trim($k);
	}

	foreach ($_FILES as $k => $v) {
		trim($k);
	}

	foreach ($_COOKIE as $k => $v) {
		trim($k);
	}

	foreach ($_SESSION as $k => $v) {
		trim($k);
	}

	foreach ($_REQUEST as $k => $v) {
		trim($k);
	}

	foreach ($_ENV as $k => $v) {
		trim($k);
	}
}

function benevolentKeysOfSuperglobalsInt(): void
{
	foreach ($GLOBALS as $k => $v) {
		acceptInt($k);
	}

	foreach ($_SERVER as $k => $v) {
		acceptInt($k);
	}

	foreach ($_GET as $k => $v) {
		acceptInt($k);
	}

	foreach ($_POST as $k => $v) {
		acceptInt($k);
	}

	foreach ($_FILES as $k => $v) {
		acceptInt($k);
	}

	foreach ($_COOKIE as $k => $v) {
		acceptInt($k);
	}

	foreach ($_SESSION as $k => $v) {
		acceptInt($k);
	}

	foreach ($_REQUEST as $k => $v) {
		acceptInt($k);
	}

	foreach ($_ENV as $k => $v) {
		acceptInt($k);
	}
}

function acceptInt(int $i): void
{
}
