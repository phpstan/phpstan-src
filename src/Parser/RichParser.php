<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PHPStan\File\FileReader;
use PHPStan\NodeVisitor\StatementOrderVisitor;
use PHPStan\Php\PhpVersion;

class RichParser implements Parser
{

	private \PhpParser\Parser $parser;

	private Lexer $lexer;

	private NameResolver $nameResolver;

	private NodeConnectingVisitor $nodeConnectingVisitor;

	private StatementOrderVisitor $statementOrderVisitor;

	private NodeChildrenVisitor $nodeChildrenVisitor;

	private PhpVersion $phpVersion;

	public function __construct(
		\PhpParser\Parser $parser,
		Lexer $lexer,
		NameResolver $nameResolver,
		NodeConnectingVisitor $nodeConnectingVisitor,
		StatementOrderVisitor $statementOrderVisitor,
		NodeChildrenVisitor $nodeChildrenVisitor,
		PhpVersion $phpVersion
	)
	{
		$this->parser = $parser;
		$this->lexer = $lexer;
		$this->nameResolver = $nameResolver;
		$this->nodeConnectingVisitor = $nodeConnectingVisitor;
		$this->statementOrderVisitor = $statementOrderVisitor;
		$this->nodeChildrenVisitor = $nodeChildrenVisitor;
		$this->phpVersion = $phpVersion;
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
		$tokens = $this->lexer->getTokens();
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

		if ($this->phpVersion->requiresParenthesesForNestedTernaries()) {
			$nodeTraverser->addVisitor($this->nodeChildrenVisitor);
		}

		/** @var array<\PhpParser\Node\Stmt> */
		$nodes = $nodeTraverser->traverse($nodes);

		if (!$this->phpVersion->requiresParenthesesForNestedTernaries()) {
			return $nodes;
		}

		$tokensTraverser = new NodeTraverser();
		$tokensTraverser->addVisitor(new NodeTokensVisitor($tokens));

		/** @var array<\PhpParser\Node\Stmt> */
		return $tokensTraverser->traverse($nodes);
	}

}
