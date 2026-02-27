<?php

namespace Bug5984;

interface LineScanner
{
	function isDone(): bool;
	/**
	 * @phpstan-impure
	 */
	function scanChar(string $char): bool;
	/**
	 * @phpstan-impure
	 */
	function readChar(): string;

	function getColumn(): int;
}

class Test
{
	public function minimumIndentation(LineScanner $scanner): ?int
	{
		if ($scanner->isDone() || $scanner->scanChar("\n")) {
			return null;
		}

		$min = $scanner->getColumn();

		while (!$scanner->isDone() && $scanner->readChar() !== "\n") {
		}

		return $min;
	}
}
