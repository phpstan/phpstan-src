<?php

global $BAR;

function globalTest(string $BAR): void
{
	global $CONFIG;

	$localVar = true;

	return;
}
