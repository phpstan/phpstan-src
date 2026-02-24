<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Token;

/** @api */
interface Parser
{

	/**
	 * @param string $file path to a file to parse
	 * @return Node\Stmt[]
	 * @throws ParserErrorsException
	 */
	public function parseFile(string $file): array;

	/**
	 * @return Node\Stmt[]
	 * @throws ParserErrorsException
	 */
	public function parseString(string $sourceCode): array;

	/**
	 * Return tokens for the last parse.
	 *
	 * @return Token[]
	 */
	public function getTokens(): array;

}
