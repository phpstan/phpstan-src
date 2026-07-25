<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PhpParser\ErrorHandler;
use PhpParser\Node;
use PhpParser\Parser;
use PhpParser\Token;

final class CountingParser implements Parser
{

	public int $parseCount = 0;

	public function __construct(private Parser $parser)
	{
	}

	/**
	 * @return Node\Stmt[]|null
	 */
	#[Override]
	public function parse(string $code, ?ErrorHandler $errorHandler = null): ?array
	{
		$this->parseCount++;

		return $this->parser->parse($code, $errorHandler);
	}

	/**
	 * @return Token[]
	 */
	#[Override]
	public function getTokens(): array
	{
		return $this->parser->getTokens();
	}

}
