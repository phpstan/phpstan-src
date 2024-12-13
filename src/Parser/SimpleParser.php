<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;

final class SimpleParser implements Parser
{

	public function __construct(
		private \PhpParser\Parser $parser,
		private NameResolver $nameResolver,
		private VariadicMethodsVisitor $variadicMethodsVisitor,
		private VariadicFunctionsVisitor $variadicFunctionsVisitor,
		private PropertyHookNameVisitor $propertyHookNameVisitor,
	)
	{
	}

	/**
	 * @param string $file path to a file to parse
	 * @return Node\Stmt[]
	 */
	public function parseFile(string $file): array
	{
		try {
			return $this->parseString(FileReader::read($file));
		} catch (ParserErrorsException $e) {
			throw new ParserErrorsException($e->getErrors(), $file);
		}
	}

	/**
	 * @return Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		$errorHandler = new Collecting();
		$nodes = $this->parser->parse($sourceCode, $errorHandler);
		if ($errorHandler->hasErrors()) {
			throw new ParserErrorsException($errorHandler->getErrors(), null);
		}
		if ($nodes === null) {
			throw new ShouldNotHappenException();
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);
		$nodeTraverser->addVisitor($this->variadicMethodsVisitor);
		$nodeTraverser->addVisitor($this->variadicFunctionsVisitor);
		$nodeTraverser->addVisitor($this->propertyHookNameVisitor);

		/** @var array<Node\Stmt> */
		return $nodeTraverser->traverse($nodes);
	}

}
