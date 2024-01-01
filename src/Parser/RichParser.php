<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Token;
use PHPStan\Analyser\Ignore\IgnoreLexer;
use PHPStan\Analyser\Ignore\IgnoreParseException;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use function array_filter;
use function array_pop;
use function array_values;
use function count;
use function in_array;
use function str_contains;
use function strlen;
use function strpos;
use function substr;
use function substr_count;
use const ARRAY_FILTER_USE_KEY;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

class RichParser implements Parser
{

	public const VISITOR_SERVICE_TAG = 'phpstan.parser.richParserNodeVisitor';

	public function __construct(
		private \PhpParser\Parser $parser,
		private NameResolver $nameResolver,
		private Container $container,
		private IgnoreLexer $ignoreLexer,
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

		$tokens = $this->parser->getTokens();
		if ($errorHandler->hasErrors()) {
			throw new ParserErrorsException($errorHandler->getErrors(), null);
		}
		if ($nodes === null) {
			throw new ShouldNotHappenException();
		}

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);

		$traitCollectingVisitor = new TraitCollectingVisitor();
		$nodeTraverser->addVisitor($traitCollectingVisitor);

		foreach ($this->container->getServicesByTag(self::VISITOR_SERVICE_TAG) as $visitor) {
			$nodeTraverser->addVisitor($visitor);
		}

		/** @var array<Node\Stmt> */
		$nodes = $nodeTraverser->traverse($nodes);
		['lines' => $linesToIgnore, 'errors' => $ignoreParseErrors] = $this->getLinesToIgnore($tokens);
		if (isset($nodes[0])) {
			$nodes[0]->setAttribute('linesToIgnore', $linesToIgnore);
			if (count($ignoreParseErrors) > 0) {
				$nodes[0]->setAttribute('linesToIgnoreParseErrors', $ignoreParseErrors);
			}
		}

		foreach ($traitCollectingVisitor->traits as $trait) {
			$trait->setAttribute('linesToIgnore', array_filter($linesToIgnore, static fn (int $line): bool => $line >= $trait->getStartLine() && $line <= $trait->getEndLine(), ARRAY_FILTER_USE_KEY));
		}

		return $nodes;
	}

	/**
	 * @param Token[] $tokens
	 * @return array{lines: array<int, non-empty-list<string>|null>, errors: array<int, non-empty-list<string>>}
	 */
	private function getLinesToIgnore(array $tokens): array
	{
		$lines = [];
		$previousToken = null;
		$pendingToken = null;
		$errors = [];
		foreach ($tokens as $token) {
			$type = $token->id;
			$line = $token->line;
			if ($type !== T_COMMENT && $type !== T_DOC_COMMENT) {
				if ($type !== T_WHITESPACE) {
					if ($pendingToken !== null) {
						[$pendingText, $pendingIgnorePos, $tokenLine, $pendingLine] = $pendingToken;

						try {
							$identifiers = $this->parseIdentifiers($pendingText, $pendingIgnorePos);
						} catch (IgnoreParseException $e) {
							$errors[] = [$tokenLine + $e->getPhpDocLine(), $e->getMessage()];
							$pendingToken = null;
							continue;
						}

						if ($line !== $pendingLine + 1) {
							$lineToAdd = $pendingLine;
						} else {
							$lineToAdd = $line;
						}

						foreach ($identifiers as $identifier) {
							$lines[$lineToAdd][] = $identifier;
						}

						$pendingToken = null;
					}
					$previousToken = $token;
				}
				continue;
			}

			$text = $token->text;
			$isNextLine = str_contains($text, '@phpstan-ignore-next-line');
			$isCurrentLine = str_contains($text, '@phpstan-ignore-line');
			if ($isNextLine) {
				$line++;
			}
			if ($isNextLine || $isCurrentLine) {
				$line += substr_count($token->text, "\n");

				$lines[$line] = null;
				continue;
			}

			$ignorePos = strpos($text, '@phpstan-ignore');
			if ($ignorePos === false) {
				continue;
			}

			$ignoreLine = substr_count(substr($text, 0, $ignorePos), "\n") - 1;

			if ($previousToken !== null && $previousToken->line === $line) {
				try {
					foreach ($this->parseIdentifiers($text, $ignorePos) as $identifier) {
						$lines[$line][] = $identifier;
					}
				} catch (IgnoreParseException $e) {
					$errors[] = [$token->line + $e->getPhpDocLine() + $ignoreLine, $e->getMessage()];
				}

				continue;
			}

			$line += substr_count($token->text, "\n");
			$pendingToken = [$text, $ignorePos, $token->line + $ignoreLine, $line];
		}

		if ($pendingToken !== null) {
			[$pendingText, $pendingIgnorePos, $tokenLine, $pendingLine] = $pendingToken;

			try {
				foreach ($this->parseIdentifiers($pendingText, $pendingIgnorePos) as $identifier) {
					$lines[$pendingLine][] = $identifier;
				}
			} catch (IgnoreParseException $e) {
				$errors[] = [$tokenLine + $e->getPhpDocLine(), $e->getMessage()];
			}
		}

		$processedErrors = [];
		foreach ($errors as [$line, $message]) {
			$processedErrors[$line][] = $message;
		}

		return [
			'lines' => $lines,
			'errors' => $processedErrors,
		];
	}

	/**
	 * @return non-empty-list<string>
	 * @throws IgnoreParseException
	 */
	private function parseIdentifiers(string $text, int $ignorePos): array
	{
		$text = substr($text, $ignorePos + strlen('@phpstan-ignore'));
		$tokens = $this->ignoreLexer->tokenize($text);
		$tokens = array_values(array_filter($tokens, static fn (array $token) => !in_array($token[IgnoreLexer::TYPE_OFFSET], [IgnoreLexer::TOKEN_WHITESPACE, IgnoreLexer::TOKEN_EOL], true)));
		$c = count($tokens);

		$identifiers = [];
		$depth = 0;
		$parenthesisStack = [];
		for ($i = 0; $i < $c; $i++) {
			[IgnoreLexer::VALUE_OFFSET => $content, IgnoreLexer::TYPE_OFFSET => $tokenType, IgnoreLexer::LINE_OFFSET => $tokenLine] = $tokens[$i];
			if ($tokenType === IgnoreLexer::TOKEN_IDENTIFIER && $depth === 0) {
				$identifiers[] = $content;
				if (isset($tokens[$i + 1])) {
					if ($tokens[$i + 1][IgnoreLexer::TYPE_OFFSET] === IgnoreLexer::TOKEN_COMMA) {
						$i++;
					}
				}
				continue;
			}
			if ($i === 0) {
				throw new IgnoreParseException('First token is not an identifier', $tokenLine);
			}
			if ($tokenType === IgnoreLexer::TOKEN_COMMA) {
				throw new IgnoreParseException('Unexpected comma (,)', $tokenLine);
			}
			if ($tokenType === IgnoreLexer::TOKEN_CLOSE_PARENTHESIS) {
				if ($depth < 1) {
					throw new IgnoreParseException('Closing parenthesis ")" before opening parenthesis "("', $tokenLine);
				}

				$depth--;
				array_pop($parenthesisStack);
				if ($depth === 0) {
					break;
				}
			}
			if ($tokenType !== IgnoreLexer::TOKEN_OPEN_PARENTHESIS) {
				continue;
			}

			$depth++;
			$parenthesisStack[] = $tokenLine;
		}

		if (count($parenthesisStack) > 0) {
			throw new IgnoreParseException('Unclosed opening parenthesis "(" without closing parenthesis ")"', $parenthesisStack[count($parenthesisStack) - 1]);
		}

		if (count($identifiers) === 0) {
			throw new IgnoreParseException('Missing identifier', 1);
		}

		return $identifiers;
	}

}
