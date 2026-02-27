<?php declare(strict_types=1);

namespace Bug6702;

interface LineScanner
{
	function isDone(): bool;

	function getColumn(): int;

	/**
	 * Reads the char at the current position and moves the cursor.
	 * @phpstan-impure
	 */
	function readChar(): string;

	function peekChar(int $offset = 0): string;

	function scanChar(string $char): bool;
}

function minimumIndentation(LineScanner $scanner): ?int
{
	while (!$scanner->isDone() && $scanner->readChar() !== "\n") {
	}

	if ($scanner->isDone()) {
		return $scanner->peekChar(-1) === "\n" ? -1 : null;
	}

	$min = null;
	while (!$scanner->isDone()) {
		// Consume the indentation
		while (!$scanner->isDone()) {
			$next = $scanner->peekChar();
			if ($next !== ' ' && $next !== "\t") {
				break;
			}
			$scanner->readChar();
		}

		if ($scanner->isDone() || $scanner->scanChar("\n")) {
			continue;
		}

		$min = $min === null ? $scanner->getColumn() : min($min, $scanner->getColumn());

		// Consume the rest of the line
		while (!$scanner->isDone() && $scanner->readChar() !== "\n") {
		}
	}

	return $min ?? -1;
}
