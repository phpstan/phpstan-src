<?php declare(strict_types = 1);

namespace PregUnmatchedAsNullPhpVersions;

function needsString(string $s): void
{
}

function doFoo(string $s): void
{
	if (preg_match('/(a)(b)?/', $s, $matches, PREG_UNMATCHED_AS_NULL) === 1) {
		needsString($matches[2]);
	}
}
