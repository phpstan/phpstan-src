<?php declare(strict_types = 1);

namespace PHPStan\Parser;

/** @api */
interface Parser
{

	/**
	 * @param string $file path to a file to parse
	 * @return \PhpParser\Node\Stmt[]
	 * @throws \PHPStan\Parser\ParserErrorsException
	 */
	public function parseFile(string $file): array;

	/**
	 * @return \PhpParser\Node\Stmt[]
	 * @throws \PHPStan\Parser\ParserErrorsException
	 */
	public function parseString(string $sourceCode): array;

}
