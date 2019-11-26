<?php

function unsetInaccessible ()
{

	unset($notSetVariable);

	$scalar = 3;

	unset($scalar['a']);

	$singleDimArray = ['a' => 1];

	unset($singleDimArray['a']['b']);

	$multiDimArray = ['a' => ['b' => 1]];

	unset($multiDimArray['a']['b']['c'], $scalar, $singleDimArray['a']['b']);

}

/** @param iterable<int> $iterable */
function unsetOnMaybeIterable(iterable $iterable)
{
	unset($iterable['string']);
}

/** @param iterable<int, int> $iterable */
function unsetOnYesIterable(iterable $iterable)
{
	unset($iterable['string']);
}
