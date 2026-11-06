<?php declare(strict_types = 1);

namespace SuperglobalAssignType;

function conformingWrites(string $string): void
{
	$_GET['order'] = 'DESC';
	$_POST['nested']['key'] = $string;
	$_COOKIE['name'] .= 'suffix';
	unset($_GET['posted']);
	parse_str('a=b', $_GET);
	$_ENV['FOO'] = 'bar';
	$_SERVER['REQUEST_URI'] = $string;
	unset($_SERVER['PATH_INFO']);
}

/**
 * @param list<string> $strings
 * @param list<int> $ints
 */
function nonConformingWrites(array $strings, array $ints, int $int): void
{
	$_GET['message'] = $int;
	$_POST['ids'] = $strings;
	$_POST['ids'] = $ints;
	$_ENV['FOO'] = 1;
	$_SERVER['SERVER_PORT'] = $int;
}

function reportedOnce(int $int, string $string): void
{
	$_POST['link_id'] = $int;
	$_POST['comment_parent'] = $int;
	$_POST['title'] = $string;
	unset($_POST['posted']);
}

/**
 * @param list<string> $strings
 */
function foreachTarget(array $strings): void
{
	foreach ($strings as $_GET) {
	}
}

function untypedSuperglobals(int $int): void
{
	$GLOBALS['foo'] = $int;
	$_SESSION['user'] = $int;
}
