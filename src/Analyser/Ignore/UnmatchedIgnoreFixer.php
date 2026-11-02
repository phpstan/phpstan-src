<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use PhpParser\Token;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Fixable\PhpDoc\PhpDocEditor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use function array_values;
use function count;
use function preg_match;
use function rtrim;
use function str_contains;
use function strlen;
use function strpos;
use function strrpos;
use function strspn;
use function substr;
use function substr_count;
use function trim;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_WHITESPACE;

/**
 * Removes inline ignores that no error matched. The comment that applies to a line is found
 * the same way RichParser finds the lines an ignore comment applies to.
 */
#[AutowiredService]
final class UnmatchedIgnoreFixer
{

	private const IGNORE_TAG = '@phpstan-ignore';

	public function __construct(private IgnoreLexer $ignoreLexer, private PhpDocEditor $phpDocEditor)
	{
	}

	/**
	 * Removes $identifier from the ignore comment with identifiers that applies to $line, or the whole ignore
	 * when it is the only identifier. Without $identifier, removes the ignore of $line without identifiers,
	 * the -line or -next-line variant. Plain comments are edited as text, PHPDocs with format-preserving printing
	 * so that their other tags are kept. Returns null when there is no such ignore, or when a plain comment has other contents.
	 */
	public function fix(string $code, int $line, ?string $identifier): ?string
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
			if (!str_contains($text, self::IGNORE_TAG)) {
				continue;
			}

			$endLine = $token->line + substr_count($text, "\n");
			$isNextLine = str_contains($text, '@phpstan-ignore-next-line');
			if ($isNextLine || str_contains($text, '@phpstan-ignore-line')) {
				if ($identifier !== null) {
					continue;
				}
				if ($token->id === T_DOC_COMMENT) {
					$newText = $this->removePhpDocLineTag($text, $token->line, $line);
					if ($newText === false) {
						continue;
					}

					return self::replaceComment($code, $token, $newText);
				}
				if ($endLine + ($isNextLine ? 1 : 0) !== $line) {
					continue;
				}
				if (preg_match('~^(?://|#|/\*)\s*@phpstan-ignore-(?:next-)?line\b~', $text) !== 1) {
					return null;
				}

				return self::replaceComment($code, $token, null);
			}

			if ($identifier === null) {
				continue;
			}

			if ($previous !== null && $previous->line === $token->line) {
				$ignoredLine = $token->line;
			} else {
				$ignoredLine = $endLine;
				$next = self::findNextCodeToken($tokens, $i);
				if ($next !== null && $next->line === $endLine + 1) {
					$ignoredLine = $next->line;
				}
			}
			if ($ignoredLine !== $line) {
				continue;
			}

			$newText = $token->id === T_DOC_COMMENT
				? $this->removePhpDocIdentifier($text, $identifier)
				: $this->removeCommentIdentifier($text, $identifier);
			if ($newText === false) {
				continue;
			}
			if ($newText === true) {
				return null;
			}

			return self::replaceComment($code, $token, $newText);
		}

		return null;
	}

	/**
	 * @return string|bool|null new text of the comment, null when the whole comment should be removed,
	 *                          false when the identifier is not there, true when the comment has other contents
	 */
	private function removeCommentIdentifier(string $text, string $identifier): string|bool|null
	{
		$listStart = (int) strpos($text, self::IGNORE_TAG) + strlen(self::IGNORE_TAG);
		$result = $this->removeIdentifierFromList(substr($text, $listStart), $identifier);
		if ($result === false) {
			return false;
		}

		[$newList, $listEnd] = $result;
		if ($newList !== null) {
			return substr($text, 0, $listStart) . $newList;
		}

		$isOnlyIgnore = preg_match('~^(?://|#|/\*)\s*$~', substr($text, 0, $listStart - strlen(self::IGNORE_TAG))) === 1
			&& preg_match('~^\s*(?:\*/)?$~', substr($text, $listStart + $listEnd)) === 1;

		return $isOnlyIgnore ? null : true;
	}

	/**
	 * @return string|false|null new text of the PHPDoc, null when the whole PHPDoc should be removed,
	 *                           false when the identifier is not there
	 */
	private function removePhpDocIdentifier(string $text, string $identifier): string|false|null
	{
		$found = false;
		$newText = $this->phpDocEditor->editText($text, function (Node $node) use ($identifier, &$found): Node {
			if (!$node instanceof PhpDocNode) {
				return $node;
			}

			foreach ($node->children as $k => $child) {
				if (!$child instanceof PhpDocTagNode || $child->name !== self::IGNORE_TAG) {
					continue;
				}

				// only the first ignore of a PHPDoc applies
				if ($child->value instanceof GenericTagValueNode) {
					$result = $this->removeIdentifierFromList($child->value->value, $identifier);
					if ($result !== false) {
						$found = true;
						if ($result[0] === null) {
							unset($node->children[$k]);
							$node->children = array_values($node->children);
						} else {
							$child->value->value = $result[0];
						}
					}
				}
				break;
			}

			return $node;
		});

		return $found ? $newText : false;
	}

	/**
	 * Removes the -line or -next-line tag of a PHPDoc that ignores $line, the same lines RichParser ignores for them.
	 *
	 * @return string|false|null new text of the PHPDoc, null when the whole PHPDoc should be removed,
	 *                           false when no such tag ignores $line
	 */
	private function removePhpDocLineTag(string $text, int $startLine, int $line): string|false|null
	{
		$endLine = $startLine + substr_count($text, "\n");
		$found = false;
		$newText = $this->phpDocEditor->editText($text, static function (Node $node) use ($text, $startLine, $endLine, $line, &$found): Node {
			if (!$node instanceof PhpDocNode) {
				return $node;
			}

			$offsets = [];
			$candidates = [];
			$lastTagKey = null;
			foreach ($node->children as $k => $child) {
				if (!$child instanceof PhpDocTagNode) {
					continue;
				}

				$lastTagKey = $k;
				if ($child->name !== '@phpstan-ignore-line' && $child->name !== '@phpstan-ignore-next-line') {
					continue;
				}

				$offset = strpos($text, $child->name, $offsets[$child->name] ?? 0);
				if ($offset === false) {
					return $node;
				}
				$offsets[$child->name] = $offset + 1;

				$candidates[$k] = $startLine + substr_count(substr($text, 0, $offset), "\n") + ($child->name === '@phpstan-ignore-next-line' ? 1 : 0);
			}

			foreach ($candidates as $k => $ignoredLine) {
				// the last tag of a PHPDoc ignores the line after it
				$child = $node->children[$k];
				if ($k === $lastTagKey && $child instanceof PhpDocTagNode && $child->name === '@phpstan-ignore-next-line') {
					$ignoredLine = $endLine + 1;
				}
				if ($ignoredLine !== $line) {
					continue;
				}

				$found = true;
				unset($node->children[$k]);
				$node->children = array_values($node->children);
				break;
			}

			return $node;
		});

		return $found ? $newText : false;
	}

	/**
	 * @return array{string|null, int}|false new list, null when the identifier was the only one, and where the list ends;
	 *                                       false when the identifier is not there
	 */
	private function removeIdentifierFromList(string $list, string $identifier): array|false
	{
		$offset = 0;
		$depth = 0;
		$items = [];
		$item = null;
		foreach ($this->ignoreLexer->tokenize($list) as [$value, $type]) {
			if ($depth === 0 && $type === IgnoreLexer::TOKEN_END) {
				break;
			}

			$end = $offset + strlen($value);
			if ($type === IgnoreLexer::TOKEN_OPEN_PARENTHESIS) {
				$depth++;
			} elseif ($type === IgnoreLexer::TOKEN_CLOSE_PARENTHESIS) {
				$depth--;
				if ($depth === 0 && $item !== null) {
					// the comment in parentheses belongs to the identifier
					$item['end'] = $end;
				}
			} elseif ($depth === 0 && $type === IgnoreLexer::TOKEN_IDENTIFIER) {
				if ($item !== null) {
					$items[] = $item;
				}
				$item = ['name' => $value, 'start' => $offset, 'end' => $end];
			}
			$offset = $end;
		}
		if ($item !== null) {
			$items[] = $item;
		}

		foreach ($items as $k => $item) {
			if ($item['name'] !== $identifier) {
				continue;
			}

			if (count($items) === 1) {
				return [null, $offset];
			}

			// with the comma and the whitespace next to it
			if ($k < count($items) - 1) {
				return [substr($list, 0, $item['start']) . substr($list, $items[$k + 1]['start']), $offset];
			}

			return [substr($list, 0, $items[$k - 1]['end']) . substr($list, $item['end']), $offset];
		}

		return false;
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

	/**
	 * Replaces the comment with $newText. Without $newText, removes the line of a comment that is alone on it,
	 * otherwise the comment and the whitespace that separates it from the code.
	 */
	private static function replaceComment(string $code, Token $token, ?string $newText): string
	{
		$start = $token->pos;
		$end = $start + strlen($token->text);
		if ($newText !== null) {
			return substr($code, 0, $start) . $newText . substr($code, $end);
		}

		$lineStartPos = strrpos(substr($code, 0, $start), "\n");
		$lineStart = $lineStartPos === false ? 0 : $lineStartPos + 1;
		$lineEnd = strpos($code, "\n", $end);
		$before = substr($code, $lineStart, $start - $lineStart);
		$after = substr($code, $end, ($lineEnd === false ? strlen($code) : $lineEnd) - $end);

		if (trim($before) === '' && trim($after) === '') {
			return substr($code, 0, $lineStart) . ($lineEnd === false ? '' : substr($code, $lineEnd + 1));
		}

		if (trim($before) !== '') {
			$start = $lineStart + strlen(rtrim($before, " \t"));
		} else {
			$end += strspn($after, " \t");
		}

		return substr($code, 0, $start) . substr($code, $end);
	}

}
