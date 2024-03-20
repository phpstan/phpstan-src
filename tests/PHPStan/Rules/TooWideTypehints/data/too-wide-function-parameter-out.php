<?php

namespace TooWideFunctionParameterOut;

function doFoo(?string &$p): void
{

}

function doBar(?string &$p): void
{
	$p = 'foo';
}

/**
 * @param-out string|null $p
 */
function doBaz(?string &$p): void
{
	$p = 'foo';
}

function doLorem(?string &$p): void
{
	$p = 'foo';
}

function ipCheckData(string $host, ?\stdClass &$ipdata): bool
{
	$ipdata = null;

	// See if this is an ip address
	if (!filter_var($host, FILTER_VALIDATE_IP)) {
		return false;
	}

	$ipdata = new \stdClass;

	return true;
}

/**
 * @param int $flags
 * @param 10 $out
 *
 * @param-out ($flags is 2 ? 20 : 10) $out
 */
function bug10699(int $flags, &$out): void
{

}
