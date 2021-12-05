<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler;

class PhpParserDecorator implements \PhpParser\Parser
{

	private \PHPStan\Parser\Parser $wrappedParser;

	public function __construct(\PHPStan\Parser\Parser $wrappedParser)
	{
		$this->wrappedParser = $wrappedParser;
	}

	/**
	 * @return \PhpParser\Node\Stmt[]
	 */
	public function parse(string $code, ?ErrorHandler $errorHandler = null): array
	{
		try {
			return $this->wrappedParser->parseString($code);
		} catch (\PHPStan\Parser\ParserErrorsException $e) {
			$message = $e->getMessage();
			if ($e->getParsedFile() !== null) {
				$message .= sprintf(' in file %s', $e->getParsedFile());
			}
			throw new \PhpParser\Error($message);
		}
	}

}
