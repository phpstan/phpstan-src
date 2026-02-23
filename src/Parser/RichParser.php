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
use function array_map;
use function count;
use function implode;
use function in_array;
use function preg_match_all;
use function sprintf;
use function str_contains;
use function strlen;
use function strpos;
use function substr;
use function substr_count;
use const ARRAY_FILTER_USE_KEY;
use const PREG_OFFSET_CAPTURE;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

final class RichParser implements Parser
{

	public const VISITOR_SERVICE_TAG = 'phpstan.parser.richParserNodeVisitor';

	private const PHPDOC_TAG_REGEX = '(@(?:[a-z][a-z0-9-\\\\]+:)?[a-z][a-z0-9-\\\\]*+)';

	private const PHPDOC_DOCTRINE_TAG_REGEX = '(@[a-z_\\\\][a-z0-9_\:\\\\]*[a-z_][a-z0-9_]*)';

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

		$pipeTransformer = new NodeTraverser(new PipeTransformerVisitor());
		/** @var array<Node\Stmt> */
		$nodes = $pipeTransformer->traverse($nodes);

		$nodeTraverser = new NodeTraverser();
		$nodeTraverser->addVisitor($this->nameResolver);

		$traitCollectingVisitor = new TraitCollectingVisitor();
		$nodeTraverser->addVisitor($traitCollectingVisitor);

		foreach ($this->container->getServicesByTag(self::VISITOR_SERVICE_TAG) as $visitor) {
			$nodeTraverser->addVisitor($visitor);
		}

		/** @var array<Node\Stmt> */
		$nodes = $nodeTraverser->traverse($nodes);

		$reversePipeTransformer = new NodeTraverser(new ReversePipeTransformerVisitor());
		/** @var array<Node\Stmt> */
		$nodes = $reversePipeTransformer->traverse($nodes);

		['lines' => $linesToIgnore, 'errors' => $ignoreParseErrors] = $this->getLinesToIgnore($tokens);
		if (isset($nodes[0])) {
			$nodes[0]->setAttribute('linesToIgnore', $linesToIgnore);
			if (count($ignoreParseErrors) > 0) {
				$nodes[0]->setAttribute('linesToIgnoreParseErrors', $ignoreParseErrors);
			}
		}

		foreach ($traitCollectingVisitor->traits as $trait) {
			$preexisting = $trait->getAttribute('linesToIgnore', []);
			$filteredLinesToIgnore = array_filter($linesToIgnore, static fn (int $line): bool => $line >= $trait->getStartLine() && $line <= $trait->getEndLine(), ARRAY_FILTER_USE_KEY);
			foreach ($preexisting as $line => $ignores) {
				$filteredLinesToIgnore[$line] = $ignores;
			}
			$trait->setAttribute('linesToIgnore', $filteredLinesToIgnore);
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

			if ($type === T_DOC_COMMENT) {
				$lines += $this->getLinesToIgnoreForTokenByIgnoreComment($text, $line, '@phpstan-ignore-line', false);
				if ($isNextLine) {
					$pattern = sprintf('~%s~si', implode('|', [self::PHPDOC_TAG_REGEX, self::PHPDOC_DOCTRINE_TAG_REGEX]));
					$r = preg_match_all($pattern, $text, $pregMatches, PREG_OFFSET_CAPTURE);
					if ($r !== false) {
						$c = count($pregMatches[0]);
						if ($c > 0) {
							[$lastMatchTag, $lastMatchOffset] = $pregMatches[0][$c - 1];
							if ($lastMatchTag === '@phpstan-ignore-next-line') {
								// this will let us ignore errors outside of PHPDoc
								// and also cut off the PHPDoc text before the last tag
								$lineToIgnore = $line + 1 + substr_count($text, "\n");
								$lines[$lineToIgnore] = null;
								$text = substr($text, 0, $lastMatchOffset);
							}
						}
					}

					$lines += $this->getLinesToIgnoreForTokenByIgnoreComment($text, $line, '@phpstan-ignore-next-line', true);
				}

				if ($isNextLine || $isCurrentLine) {
					continue;
				}

			} else {
				if ($isNextLine) {
					$line++;
				}
				if ($isNextLine || $isCurrentLine) {
					$line += substr_count($token->text, "\n");

					$lines[$line] = null;
					continue;
				}
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
	 * @return array<int, null>
	 */
	private function getLinesToIgnoreForTokenByIgnoreComment(
		string $tokenText,
		int $tokenLine,
		string $ignoreComment,
		bool $ignoreNextLine,
	): array
	{
		$lines = [];
		$positionsOfIgnoreComment = [];
		$offset = 0;

		while (($pos = strpos($tokenText, $ignoreComment, $offset)) !== false) {
			$positionsOfIgnoreComment[] = $pos;
			$offset = $pos + 1;
		}

		foreach ($positionsOfIgnoreComment as $pos) {
			$line = $tokenLine + substr_count(substr($tokenText, 0, $pos), "\n") + ($ignoreNextLine ? 1 : 0);
			$lines[$line] = null;
		}

		return $lines;
	}

	/**
	 * @return non-empty-list<string>
	 * @throws IgnoreParseException
	 */
	private function parseIdentifiers(string $text, int $ignorePos): array
	{
		$text = substr($text, $ignorePos + strlen('@phpstan-ignore'));
		$originalTokens = $this->ignoreLexer->tokenize($text);
		$tokens = [];

		foreach ($originalTokens as $originalToken) {
			if ($originalToken[IgnoreLexer::TYPE_OFFSET] === IgnoreLexer::TOKEN_WHITESPACE) {
				continue;
			}
			$tokens[] = $originalToken;
		}

		$c = count($tokens);

		$identifiers = [];
		$openParenthesisCount = 0;
		$expected = [IgnoreLexer::TOKEN_IDENTIFIER];

		for ($i = 0; $i < $c; $i++) {
			$lastTokenTypeLabel = isset($tokenType) ? $this->ignoreLexer->getLabel($tokenType) : '@phpstan-ignore';
			[IgnoreLexer::VALUE_OFFSET => $content, IgnoreLexer::TYPE_OFFSET => $tokenType, IgnoreLexer::LINE_OFFSET => $tokenLine] = $tokens[$i];

			if ($expected !== null && !in_array($tokenType, $expected, true)) {
				$tokenTypeLabel = $this->ignoreLexer->getLabel($tokenType);
				$otherTokenContent = $tokenType === IgnoreLexer::TOKEN_OTHER ? sprintf(" '%s'", $content) : '';
				$expectedLabels = implode(' or ', array_map(fn ($token) => $this->ignoreLexer->getLabel($token), $expected));

				throw new IgnoreParseException(sprintf('Unexpected %s%s after %s, expected %s', $tokenTypeLabel, $otherTokenContent, $lastTokenTypeLabel, $expectedLabels), $tokenLine);
			}

			if ($tokenType === IgnoreLexer::TOKEN_OPEN_PARENTHESIS) {
				$openParenthesisCount++;
				$expected = null;
				continue;
			}

			if ($tokenType === IgnoreLexer::TOKEN_CLOSE_PARENTHESIS) {
				$openParenthesisCount--;
				if ($openParenthesisCount === 0) {
					$expected = [IgnoreLexer::TOKEN_COMMA, IgnoreLexer::TOKEN_END];
				}
				continue;
			}

			if ($openParenthesisCount > 0) {
				continue; // waiting for comment end
			}

			if ($tokenType === IgnoreLexer::TOKEN_IDENTIFIER) {
				$identifiers[] = $content;
				$expected = [IgnoreLexer::TOKEN_COMMA, IgnoreLexer::TOKEN_END, IgnoreLexer::TOKEN_OPEN_PARENTHESIS];
				continue;
			}

			if ($tokenType === IgnoreLexer::TOKEN_COMMA) {
				$expected = [IgnoreLexer::TOKEN_IDENTIFIER];
				continue;
			}
		}

		if ($openParenthesisCount > 0) {
			throw new IgnoreParseException('Unexpected end, unclosed opening parenthesis', $tokenLine ?? 1);
		}

		if (count($identifiers) === 0) {
			throw new IgnoreParseException('Missing identifier', 1);
		}

		return $identifiers;
	}

}
