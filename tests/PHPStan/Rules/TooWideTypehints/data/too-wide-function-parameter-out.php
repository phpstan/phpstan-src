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
