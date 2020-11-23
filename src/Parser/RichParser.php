<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PHPStan\File\FileReader;
use PHPStan\NodeVisitor\StatementOrderVisitor;

class RichParser implements Parser
{

	private \PhpParser\Parser $parser;

	private NameResolver $nameResolver;

	private NodeConnectingVisitor $nodeConnectingVisitor;

	private StatementOrderVisitor $statementOrderVisitor;

	public function __construct(
		\PhpParser\Parser $parser,
		NameResolver $nameResolver,
		NodeConnectingVisitor $nodeConnectingVisitor,
		StatementOrderVisitor $statementOrderVisitor
	)
	{
		$this->parser = $parser;
		$this->nameResolver = $nameResolver;
		$this->nodeConnectingVisitor = $nodeConnectingVisitor;
		$this->statementOrderVisitor = $statementOrderVisitor;
	}

	/**
	 * @param string $file path to a file to parse
	 * @return \PhpParser\Node\Stmt[]
	 */
	public function parseFile(string $file): array
	{
		try {
			return $this->parseString(FileReader::read($file));
		} catch (\PHPStan\Parser\ParserErrorsException $e) {
			throw new \PHPStan\Parser\ParserErrorsException($e->getErrors(), $file);
		}
	}

	/**
	 * @param string $sourceCode
	 * @return \PhpParser\Node\Stmt[]
	 */
	public function parseString(string $sourceCode): array
	{
		$errorHandler = new Collecting();
		$nodes = $this->parser->parse($sourceCode, $errorHandler);
		if ($errorHandler->hasErrors()) {
			throw new \PHPStan\Parser\ParserErrorsException($errorHandler->getErrors(), null);
		}
		if ($nodes === null) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);
		$nodeTraverser->addVisitor($this->nodeConnectingVisitor);
		$nodeTraverser->addVisitor($this->statementOrderVisitor);

		/** @var array<\PhpParser\Node\Stmt> */
		return $nodeTraverser->traverse($nodes);
	}

}
