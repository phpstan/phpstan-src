<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PhpParser\Token;
use PHPStan\DependencyInjection\AutowiredService;
use function count;
use function implode;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function strlen;
use function strpos;
use function substr;
use function substr_count;
use function trim;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

/**
 * Replaces the -line or -next-line variant of the ignore comment with an ignore of the identifiers
 * of the errors reported on the ignored line. The text after the tag becomes the comment of every identifier.
 */
#[AutowiredService]
final class LineIgnoreFixer
{

	public function __construct(private UnmatchedIgnoreFixer $unmatchedIgnoreFixer)
	{
	}

	/**
	 * @param non-empty-list<string> $identifiers identifiers of the errors reported on $line, one for each error that one ignore has to match
	 */
	public function fix(string $code, int $line, array $identifiers): ?string
	{
		$tokens = Token::tokenize($code);
		$previous = null;
		foreach ($tokens as $i => $token) {
			if ($token->id !== T_COMMENT && $token->id !== T_DOC_COMMENT) {
				if ($token->id !== T_WHITESPACE) {
					$previous = $token;
				}
				continue;
			}

			$text = $token->text;
			$isNextLine = str_contains($text, '@phpstan-ignore-next-line');
			if (!$isNextLine && !str_contains($text, '@phpstan-ignore-line')) {
				continue;
			}

			$tag = $isNextLine ? '@phpstan-ignore-next-line' : '@phpstan-ignore-line';
			$tagPos = (int) strpos($text, $tag);
			$endLine = $token->line + substr_count($text, "\n");
			$closing = str_ends_with($text, '*/') ? '*/' : '';
			$rest = substr($text, $tagPos + strlen($tag), strlen($text) - $tagPos - strlen($tag) - strlen($closing));

			// the same lines RichParser ignores for the tag
			if ($token->id === T_DOC_COMMENT) {
				$ignoredLine = $token->line + substr_count(substr($text, 0, $tagPos), "\n");
				if ($isNextLine) {
					$ignoredLine = self::hasPhpDocTag($rest) ? $ignoredLine + 1 : $endLine + 1;
				}
			} else {
				$ignoredLine = $endLine + ($isNextLine ? 1 : 0);
			}
			if ($ignoredLine !== $line) {
				continue;
			}

			// other ignores, or tags after the identifiers, would not parse
			if (substr_count($text, '@phpstan-ignore') !== 1 || self::hasPhpDocTag($rest)) {
				return null;
			}

			$comment = trim((string) preg_replace('~\s*\n\s*(?:\*(?!/)\s*)?~', ' ', $rest));
			if (!self::hasBalancedParentheses($comment)) {
				return null;
			}

			$ignore = self::createIgnore($identifiers, $comment);

			// the same line RichParser ignores for the ignore with identifiers
			$isAfterCode = $previous !== null && $previous->line === $token->line;
			if ($isAfterCode) {
				$newIgnoredLine = $token->line;
			} else {
				$newIgnoredLine = $endLine;
				$next = self::findNextCodeToken($tokens, $i);
				if ($next !== null && $next->line === $endLine + 1) {
					$newIgnoredLine = $next->line;
				}
			}

			if ($newIgnoredLine === $line) {
				preg_match('~\s*$~', $rest, $matches);
				$newText = substr($text, 0, $tagPos) . $ignore . ($matches[0] ?? '') . $closing;

				return substr($code, 0, $token->pos) . $newText . substr($code, $token->pos + strlen($text));
			}

			if (!$isNextLine || !$isAfterCode) {
				return null;
			}

			// an ignore with identifiers after code ignores its own line, so it moves to the end of the next line
			$newCode = $this->unmatchedIgnoreFixer->fix($code, $line, null);
			if ($newCode === null) {
				return null;
			}

			return self::appendToLine($newCode, $line - substr_count($code, "\n") + substr_count($newCode, "\n"), $ignore);
		}

		return null;
	}

	/**
	 * @param non-empty-list<string> $identifiers
	 */
	private static function createIgnore(array $identifiers, string $comment): string
	{
		if ($comment !== '') {
			foreach ($identifiers as $k => $identifier) {
				$identifiers[$k] = sprintf('%s (%s)', $identifier, $comment);
			}
		}

		return '@phpstan-ignore ' . implode(', ', $identifiers);
	}

	/**
	 * Appends a comment with the ignore to the end of the line, which then ignores the line like
	 * a comment after code. A comment at the end of the line gets the ignore appended to it.
	 */
	private static function appendToLine(string $code, int $line, string $ignore): ?string
	{
		$tokens = Token::tokenize($code);
		$lastCode = null;
		foreach ($tokens as $i => $token) {
			$isComment = $token->id === T_COMMENT || $token->id === T_DOC_COMMENT;
			if (!$isComment && $token->id !== T_WHITESPACE) {
				$lastCode = $token;
			}
			if ($token->line !== $line) {
				continue;
			}
			if ($isComment && str_contains($token->text, '@phpstan-ignore')) {
				return null;
			}
			if ($token->id !== T_WHITESPACE || !str_contains($token->text, "\n")) {
				continue;
			}

			// the line has to end with code, or with a comment that starts on the line after code
			if ($lastCode === null || $lastCode->line !== $line || $tokens[$i - 1]->line !== $line) {
				return null;
			}

			return substr($code, 0, $token->pos) . ' // ' . $ignore . substr($code, $token->pos);
		}

		return null;
	}

	/**
	 * Like the tag regexes in RichParser, which decide whether the -next-line tag is the last tag of a PHPDoc.
	 */
	private static function hasPhpDocTag(string $text): bool
	{
		return preg_match('~@[a-z_\\\\]~i', $text) === 1;
	}

	private static function hasBalancedParentheses(string $text): bool
	{
		$depth = 0;
		for ($i = 0; $i < strlen($text); $i++) {
			if ($text[$i] === '(') {
				$depth++;
			} elseif ($text[$i] === ')') {
				$depth--;
				if ($depth < 0) {
					return false;
				}
			}
		}

		return $depth === 0;
	}

	/**
	 * @param Token[] $tokens
	 */
	private static function findNextCodeToken(array $tokens, int $i): ?Token
	{
		for ($j = $i + 1; $j < count($tokens); $j++) {
			$id = $tokens[$j]->id;
			if ($id !== T_WHITESPACE && $id !== T_COMMENT && $id !== T_DOC_COMMENT) {
				return $tokens[$j];
			}
		}

		return null;
	}

}
