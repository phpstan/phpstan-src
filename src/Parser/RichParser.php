<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Internal\TokenPolyfill;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use function strpos;
use function substr_count;
use const T_COMMENT;
use const T_DOC_COMMENT;

class RichParser implements Parser
{

	public const VISITOR_SERVICE_TAG = 'phpstan.parser.richParserNodeVisitor';

	public function __construct(
		private \PhpParser\Parser $parser,
		private Lexer $lexer,
		private NameResolver $nameResolver,
		private Container $container,
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
		$tokens = $this->lexer->getTokens();
		if ($errorHandler->hasErrors()) {
			throw new ParserErrorsException($errorHandler->getErrors(), null);
		}
		if ($nodes === null) {
			throw new ShouldNotHappenException();
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);

		foreach ($this->container->getServicesByTag(self::VISITOR_SERVICE_TAG) as $visitor) {
			$nodeTraverser->addVisitor($visitor);
		}

		/** @var array<Node\Stmt> */
		$nodes = $nodeTraverser->traverse($nodes);
		if (isset($nodes[0])) {
			$nodes[0]->setAttribute('linesToIgnore', $this->getLinesToIgnore($tokens));
		}

		return $nodes;
	}

	/**
	 * @param TokenPolyfill[] $tokens
	 * @return int[]
	 */
	private function getLinesToIgnore(array $tokens): array
	{
		$lines = [];
		foreach ($tokens as $token) {
			$type = $token->id;
			if ($type !== T_COMMENT && $type !== T_DOC_COMMENT) {
				continue;
			}

			$text = $token->text;
			$line = $token->line;
			if (strpos($text, '@phpstan-ignore-next-line') !== false) {
				$line++;
			} elseif (strpos($text, '@phpstan-ignore-line') === false) {
				continue;
			}

			$line += substr_count($text, "\n");

			$lines[] = $line;
		}

		return $lines;
	}

}
