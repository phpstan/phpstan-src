<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\NodeConnectingVisitor;
use PHPStan\File\FileReader;
use PHPStan\NodeVisitor\StatementOrderVisitor;
use PHPStan\ShouldNotHappenException;
use function is_string;
use function strpos;
use function substr_count;
use const T_COMMENT;
use const T_DOC_COMMENT;

class RichParser implements Parser
{

	private \PhpParser\Parser $parser;

	private Lexer $lexer;

	private NameResolver $nameResolver;

	private NodeConnectingVisitor $nodeConnectingVisitor;

	private StatementOrderVisitor $statementOrderVisitor;

	public function __construct(
		\PhpParser\Parser $parser,
		Lexer $lexer,
		NameResolver $nameResolver,
		NodeConnectingVisitor $nodeConnectingVisitor,
		StatementOrderVisitor $statementOrderVisitor
	)
	{
		$this->parser = $parser;
		$this->lexer = $lexer;
		$this->nameResolver = $nameResolver;
		$this->nodeConnectingVisitor = $nodeConnectingVisitor;
		$this->statementOrderVisitor = $statementOrderVisitor;
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
		$tokens = $this->lexer->getTokens();
		if ($errorHandler->hasErrors()) {
			throw new ParserErrorsException($errorHandler->getErrors(), null);
		}
		if ($nodes === null) {
			throw new ShouldNotHappenException();
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);
		$nodeTraverser->addVisitor($this->nodeConnectingVisitor);
		$nodeTraverser->addVisitor($this->statementOrderVisitor);

		/** @var array<Node\Stmt> */
		$nodes = $nodeTraverser->traverse($nodes);
		if (isset($nodes[0])) {
			$nodes[0]->setAttribute('linesToIgnore', $this->getLinesToIgnore($tokens));
		}

		return $nodes;
	}

	/**
	 * @param mixed[] $tokens
	 * @return int[]
	 */
	private function getLinesToIgnore(array $tokens): array
	{
		$lines = [];
		foreach ($tokens as $token) {
			if (is_string($token)) {
				continue;
			}

			$type = $token[0];
			if ($type !== T_COMMENT && $type !== T_DOC_COMMENT) {
				continue;
			}

			$text = $token[1];
			$line = $token[2];
			if (strpos($text, '@phpstan-ignore-next-line') !== false) {
				$line++;
			} elseif (strpos($text, '@phpstan-ignore-line') === false) {
				continue;
			}

			$line += substr_count($token[1], "\n");

			$lines[] = $line;
		}

		return $lines;
	}

}
