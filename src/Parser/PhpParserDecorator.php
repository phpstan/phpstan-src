<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;

class PhpParserDecorator implements \PhpParser\Parser
{

	/** @var \PHPStan\Parser\Parser */
	private $wrappedParser;

	public function __construct(\PHPStan\Parser\Parser $wrappedParser)
	{
		$this->wrappedParser = $wrappedParser;
	}

	/**
	 * @param string $code
	 * @param \PhpParser\ErrorHandler|null $errorHandler
	 * @return \PhpParser\Node\Stmt[]
	 */
	public function parse(string $code, ?ErrorHandler $errorHandler = null): array
	{
		return $this->wrappedParser->parseString($code);
	}

}
