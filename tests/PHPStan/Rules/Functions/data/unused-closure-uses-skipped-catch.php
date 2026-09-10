<?php

namespace UnusedClosureUsesSkippedCatch;

interface Driver
{
	/** @throws \LogicException */
	public function click(string $xpath): void;
}

function click(Driver $driver, string $xpath): void
{
	$exception = null;
	$unused = null;
	$callback = function () use ($driver, $xpath, &$exception, &$unused) {
		try {
			$driver->click($xpath);
			return true;
		} catch (\RuntimeException $caught) {
			$exception = $caught;
			return null;
		}
	};
	if ($callback() !== true) {
		throw $exception;
	}
}
