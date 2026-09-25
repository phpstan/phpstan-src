<?php declare(strict_types = 1);

$pattern = "/^(?:
	(?P<foo1>foo[a-z]+)
	(?:-(?P<bar1>[0-9]+))?
	|
	(?P<foo2>foo[0-9]+)
	(?:-(?P<bar2>[a-z]+))?
)$/ix";
if (preg_match($pattern, "foobar", $matches, \PREG_UNMATCHED_AS_NULL)) {
	$foo = $matches["foo1"] ?? $matches["foo2"] ?? "";
	$bar = $matches["bar1"] ?? $matches["bar2"] ?? "";
}
