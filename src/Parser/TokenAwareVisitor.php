<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Token;

/**
 * A node visitor that is aware of the parser tokens.
 *
 * @api
 */
interface TokenAwareVisitor
{

	/**
	 * @param Token[] $tokens
	 */
	public function setTokens(array $tokens): void;

}
