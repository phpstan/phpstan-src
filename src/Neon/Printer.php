<?php declare(strict_types = 1);

namespace PHPStan\Neon;

use DateTimeInterface;
use Nette\Neon\Node;
use Nette\Neon\Node\ArrayItemNode;
use Nette\Neon\Node\BlockArrayNode;
use Nette\Neon\Node\EntityChainNode;
use Nette\Neon\Node\EntityNode;
use Nette\Neon\Node\InlineArrayNode;
use Nette\Neon\Node\LiteralNode;
use Nette\Neon\Node\StringNode;
use Nette\Neon\Token;
use Nette\Neon\TokenStream;
use Nette\Utils\Strings;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\PhpDocParser\Printer\DiffElem;
use PHPStan\PhpDocParser\Printer\Differ;
use WeakMap;
use function array_slice;
use function array_values;
use function count;
use function get_class;
use function implode;
use function in_array;
use function ltrim;
use function spl_object_id;
use function str_contains;
use function strlen;
use function substr;

/**
 * Format-preserving printer of NEON syntax trees parsed by nette/neon,
 * inspired by the format-preserving printers of nikic/php-parser and phpstan/phpdoc-parser.
 *
 * A tree is changed by replacing its nodes with changed clones. A clone keeps the token positions of the node
 * it was cloned from and stands for it: what did not change in it is printed as it was written, with
 * its comments and whitespace. A node created with `new` has no token positions and is printed anew.
 * Items of lists are compared with the original ones, so they can be removed, added and reordered.
 *
 * nette/neon removes \r from the input before tokenizing it, so the printed code has \n line breaks.
 */
#[AutowiredService]
final class Printer
{

	private const DEFAULT_INDENT = "\t";

	private const DATE_FORMAT = 'Y-m-d H:i:s.u e';

	/**
	 * Delimits a reference to a range of tokens of the original code in the printed code,
	 * so that the unchanged code is copied once at the end, not by every node that contains it.
	 */
	private const COPY = "\x00";

	private const TRIVIA_TOKENS = [Token::Newline, Token::Whitespace, Token::Comment];

	/** @var WeakMap<TokenStream, array{code: string, offsets: list<int>, tokens: list<Token>}> */
	private WeakMap $streams;

	/** @var WeakMap<Node, array{originals: array<string, Node|false>, ends: array<int, int>, indent: string}> */
	private WeakMap $trees;

	private string $code = '';

	private bool $useCopies = true;

	/** @var list<int> byte offset of each token, and the length of the code at the end */
	private array $offsets = [];

	/** @var list<Token> */
	private array $tokens = [];

	/** @var array<string, Node|false> original nodes by class and position, false when ambiguous */
	private array $originals = [];

	/** @var array<int, int> effective end token of original nodes by spl_object_id() */
	private array $ends = [];

	private string $indentUnit = self::DEFAULT_INDENT;

	/** @var Differ<Node> */
	private Differ $differ;

	public function __construct()
	{
		$this->streams = new WeakMap();
		$this->trees = new WeakMap();
		$this->differ = new Differ(fn (Node $a, Node $b): bool => $a === $b || $this->findOriginal($b) === $a);
	}

	/**
	 * Prints $node, which is $originalNode or a changed copy of it.
	 * The original tree has to be parsed from $originalTokens, and stay unchanged.
	 */
	public function printFormatPreserving(Node $node, Node $originalNode, TokenStream $originalTokens): string
	{
		$pieces = Strings::split($this->printWithCopies($node, $originalNode, $originalTokens), '~' . self::COPY . '(\\d+)-(\\d+)' . self::COPY . '~');
		$result = '';
		for ($i = 0; $i < count($pieces); $i += 3) {
			$result .= $pieces[$i];
			if (!isset($pieces[$i + 2])) {
				continue;
			}

			$result .= $this->textRange((int) $pieces[$i + 1], (int) $pieces[$i + 2]);
		}

		return $result;
	}

	/**
	 * Prints $node like printFormatPreserving(), as the changes of the original code.
	 * Unchanged code is not copied, so the time depends on the size of the changes, not of the code.
	 *
	 * @return list<array{start: int, end: int, replacement: string}> byte offsets in the original code without \r,
	 *                                                                  the end is exclusive
	 */
	public function printChanges(Node $node, Node $originalNode, TokenStream $originalTokens): array
	{
		$pieces = Strings::split($this->printWithCopies($node, $originalNode, $originalTokens), '~' . self::COPY . '(\\d+)-(\\d+)' . self::COPY . '~');
		$changes = [];
		$offset = 0;
		$replacement = '';
		for ($i = 0; $i < count($pieces); $i += 3) {
			$replacement .= $pieces[$i];
			if (!isset($pieces[$i + 2])) {
				break;
			}

			$from = (int) $pieces[$i + 1];
			$to = (int) $pieces[$i + 2];
			if ($this->offsets[$from] < $offset) {
				// a moved node, the code before is already used
				$replacement .= $this->textRange($from, $to);
				continue;
			}

			$this->addChange($changes, $offset, $this->offsets[$from], $replacement);
			$offset = $this->offsets[$to + 1];
			$replacement = '';
		}
		$this->addChange($changes, $offset, strlen($this->code), $replacement);

		return $changes;
	}

	/**
	 * @param list<array{start: int, end: int, replacement: string}> $changes
	 */
	private function addChange(array &$changes, int $start, int $end, string $replacement): void
	{
		if ($end - $start === strlen($replacement) && substr($this->code, $start, $end - $start) === $replacement) {
			return;
		}

		$changes[] = ['start' => $start, 'end' => $end, 'replacement' => $replacement];
	}

	/**
	 * The printed code with unchanged ranges of the original code as references to its tokens,
	 * see copy(). Unless the code has the character of the references, then they are the code itself.
	 */
	private function printWithCopies(Node $node, Node $originalNode, TokenStream $originalTokens): string
	{
		$this->prepare($originalNode, $originalTokens);

		$start = $this->start($originalNode);
		$end = $this->end($originalNode);

		$result = $this->copy(0, $start - 1)
			. $this->p($node, $this->indentAt($start))
			. $this->copy($end + 1, count($this->tokens) - 1);

		$tree = $this->trees[$originalNode];
		$tree['ends'] = $this->ends;
		$this->trees[$originalNode] = $tree;

		return $result;
	}

	private function prepare(Node $originalNode, TokenStream $originalTokens): void
	{
		if (!isset($this->streams[$originalTokens])) {
			$tokens = array_values($originalTokens->getTokens());
			$offsets = [];
			$offset = 0;
			$code = '';
			foreach ($tokens as $token) {
				$offsets[] = $offset;
				$offset += strlen($token->value);
				$code .= $token->value;
			}
			$offsets[] = $offset;
			$this->streams[$originalTokens] = ['code' => $code, 'offsets' => $offsets, 'tokens' => $tokens];
		}

		['code' => $this->code, 'offsets' => $this->offsets, 'tokens' => $this->tokens] = $this->streams[$originalTokens];
		$this->useCopies = !str_contains($this->code, self::COPY);

		if (!isset($this->trees[$originalNode])) {
			$this->originals = [];
			$this->ends = [];
			$this->indentUnit = '';
			$this->indexOriginals($originalNode);
			$this->trees[$originalNode] = [
				'originals' => $this->originals,
				'ends' => [],
				'indent' => $this->indentUnit !== '' ? $this->indentUnit : self::DEFAULT_INDENT,
			];
		}

		$tree = $this->trees[$originalNode];
		$this->originals = $tree['originals'];
		$this->ends = $tree['ends'];
		$this->indentUnit = $tree['indent'];
	}

	private function indexOriginals(Node $node): void
	{
		$key = self::getKey($node);
		if ($key !== null) {
			$this->originals[$key] = isset($this->originals[$key]) ? false : $node;
		}
		if ($this->indentUnit === '' && $node instanceof BlockArrayNode && $node->indentation !== '' && $key !== null) {
			$this->indentUnit = $node->indentation;
		}

		foreach ($node as $child) {
			$this->indexOriginals($child);
		}
	}

	private static function getKey(Node $node): ?string
	{
		if ($node->startTokenPos === null || $node->endTokenPos === null) {
			return null;
		}

		return get_class($node) . ':' . $node->startTokenPos . ':' . $node->endTokenPos;
	}

	private function findOriginal(Node $node): ?Node
	{
		$key = self::getKey($node);
		if ($key === null) {
			return null;
		}

		$original = $this->originals[$key] ?? false;

		return $original === false ? null : $original;
	}

	/**
	 * @param string $indent indentation of the line the node starts on
	 */
	private function p(Node $node, string $indent): string
	{
		$original = $this->findOriginal($node);
		if ($original === null) {
			return $this->printNew($node, $indent);
		}
		if ($node === $original) {
			return $this->text($original);
		}

		if ($node instanceof LiteralNode && $original instanceof LiteralNode) {
			if (
				$node->value === $original->value
				|| (
					$node->value instanceof DateTimeInterface
					&& $original->value instanceof DateTimeInterface
					&& $node->value->format(self::DATE_FORMAT) === $original->value->format(self::DATE_FORMAT)
				)
			) {
				return $this->text($original);
			}

			return $this->printNew($node, $indent);
		}

		if ($node instanceof StringNode && $original instanceof StringNode) {
			return $node->value === $original->value ? $this->text($original) : $this->printNew($node, $indent);
		}

		if ($node instanceof ArrayItemNode && $original instanceof ArrayItemNode) {
			return $this->printItem($node, $original, $indent);
		}

		if ($node instanceof BlockArrayNode && $original instanceof BlockArrayNode) {
			return $this->printBlockItems($node, $original);
		}

		if ($node instanceof InlineArrayNode && $original instanceof InlineArrayNode) {
			if ($node->bracket !== $original->bracket) {
				return $this->printNew($node, $indent);
			}

			return $this->printInlineItems($node->items, $original->items, $original->startTokenPos ?? 0, $original->endTokenPos ?? 0, $indent);
		}

		if ($node instanceof EntityNode && $original instanceof EntityNode) {
			$open = $this->end($original->value) + 1;
			while (isset($this->tokens[$open]) && $this->tokens[$open]->type !== '(') {
				$open++;
			}

			return $this->copy($this->start($original), $this->start($original->value) - 1)
				. $this->p($node->value, $indent)
				. $this->copy($this->end($original->value) + 1, $open - 1)
				. $this->printInlineItems($node->attributes, $original->attributes, $open, $original->endTokenPos ?? 0, $indent);
		}

		if ($node instanceof EntityChainNode && $original instanceof EntityChainNode) {
			if (count($node->chain) !== count($original->chain)) {
				return $this->printNew($node, $indent);
			}

			$pairs = [];
			foreach (array_values($original->chain) as $i => $originalEntity) {
				$pairs[] = [$originalEntity, array_values($node->chain)[$i]];
			}

			return $this->splice($original, $pairs);
		}

		return $this->printNew($node, $indent);
	}

	private function printItem(ArrayItemNode $node, ArrayItemNode $original, string $indent): string
	{
		if (($node->key === null) !== ($original->key === null)) {
			return $this->printNew($node, $indent);
		}

		$head = $node->key !== null ? $this->p($node->key, $indent) . ':' : '-';
		$originalValue = $original->value;
		$value = $node->value;
		if ($this->isEmptyValue($originalValue)) {
			if ($value === $originalValue || $this->findOriginal($value) === $originalValue) {
				return $this->text($original);
			}

			return $head . $this->printValueAfterHead($value, $indent);
		}

		$isOriginalBlock = $originalValue instanceof BlockArrayNode;
		$isBlock = $value instanceof BlockArrayNode && count($value->items) > 0;
		if ($isOriginalBlock !== $isBlock) {
			return $head . $this->printValueAfterHead($value, $indent);
		}

		$pairs = [];
		if ($node->key !== null && $original->key !== null) {
			$pairs[] = [$original->key, $node->key];
		}
		$pairs[] = [$originalValue, $value];

		return $this->splice($original, $pairs);
	}

	/**
	 * Prints the original node with its children replaced.
	 *
	 * @param list<array{Node, Node}> $pairs original child and its replacement, in the order of the source
	 */
	private function splice(Node $original, array $pairs): string
	{
		$result = '';
		$pos = $this->start($original);
		foreach ($pairs as [$originalChild, $child]) {
			$childStart = $this->start($originalChild);
			$result .= $this->copy($pos, $childStart - 1) . $this->p($child, $this->indentAt($childStart));
			$pos = $this->end($originalChild) + 1;
		}

		return $result . $this->copy($pos, $this->end($original));
	}

	/**
	 * Every item of a block array is on its own lines. The comment at the end of the last line of an item
	 * belongs to the item, and so do the lines between the previous item and the item, like comments.
	 */
	private function printBlockItems(BlockArrayNode $node, BlockArrayNode $original): string
	{
		$items = array_values($node->items);
		if ($items === []) {
			return '[]';
		}

		$originalItems = array_values($original->items);
		$itemIndent = $this->getBlockItemIndent($originalItems);

		$result = '';
		$first = true;
		$runStart = null;
		$runEnd = null;
		foreach ($this->diff($originalItems, $items) as [$item, $i, $last]) {
			if ($i !== null) {
				$originalItem = $originalItems[$i];
				if ($item === $originalItem && $runEnd !== null && $runEnd === $i - 1) {
					// unchanged items next to each other are printed with the text between them in one go
					$runEnd = $last;
					continue;
				}

				$result .= $this->printBlockRun($originalItems, $runStart, $runEnd);
				$runStart = $runEnd = null;

				if ($first) {
					// the indentation of the first item is before the array, and empty lines above it are dropped
					$result .= ltrim($this->getBlockItemLead($original, $originalItems, $i, $itemIndent), " \t\n");
				} else {
					$result .= "\n" . $this->getBlockItemLead($original, $originalItems, $i, $itemIndent);
				}

				if ($item === $originalItem) {
					$runStart = $i;
					$runEnd = $last;
				} else {
					$result .= $this->p($item, $itemIndent) . $this->getBlockItemTrail($originalItems, $i);
				}
				$first = false;
				continue;
			}

			$result .= $this->printBlockRun($originalItems, $runStart, $runEnd);
			$runStart = $runEnd = null;
			if (!$first) {
				$result .= "\n" . $itemIndent;
			}
			$result .= $this->printListItem($item, $itemIndent, false);
			$first = false;
		}

		return $result . $this->printBlockRun($originalItems, $runStart, $runEnd);
	}

	/**
	 * @param list<Node> $originalItems
	 */
	private function printBlockRun(array $originalItems, ?int $runStart, ?int $runEnd): string
	{
		if ($runStart === null || $runEnd === null) {
			return '';
		}

		return $this->copy($originalItems[$runStart]->startTokenPos ?? 0, $this->end($originalItems[$runEnd]))
			. $this->getBlockItemTrail($originalItems, $runEnd);
	}

	/**
	 * The comment and whitespace after the item on its last line.
	 *
	 * @param list<Node> $originalItems
	 */
	private function getBlockItemTrail(array $originalItems, int $i): string
	{
		$start = $this->end($originalItems[$i]) + 1;

		return $this->textRange($start, $this->findLineBreak($start) - 1);
	}

	/**
	 * The lines before the item after the line break of the previous item, like empty lines and comments,
	 * with the indentation of the item.
	 *
	 * @param list<Node> $originalItems
	 * @param int<0, max> $i
	 */
	private function getBlockItemLead(BlockArrayNode $original, array $originalItems, int $i, string $itemIndent): string
	{
		if ($i === 0) {
			// the indentation of the comments above the first item is before the array
			return $itemIndent . $this->textRange($this->start($original), $this->start($originalItems[0]) - 1);
		}

		$lineBreak = $this->findLineBreak($this->end($originalItems[$i - 1]) + 1);

		// the lexer puts the empty lines into the token of the line break
		return substr($this->tokens[$lineBreak]->value, 1) . $this->textRange($lineBreak + 1, $this->start($originalItems[$i]) - 1);
	}

	/**
	 * Position of the first line break from $pos, or the end of the code.
	 */
	private function findLineBreak(int $pos): int
	{
		while (isset($this->tokens[$pos]) && $this->tokens[$pos]->type !== Token::Newline) {
			$pos++;
		}

		return $pos;
	}

	/**
	 * @param list<Node> $originalItems
	 */
	private function getBlockItemIndent(array $originalItems): string
	{
		$first = $this->start($originalItems[0]);
		if ($this->isFirstOnLine($first)) {
			return $this->indentAt($first);
		}
		if (isset($originalItems[1])) {
			return $this->indentAt($this->start($originalItems[1]));
		}

		// a block opened on the line of a dash, like `- key: value`
		return $this->indentAt($first) . '  ';
	}

	/**
	 * Items of an inline array, like `[a, b]`, and attributes of an entity, like `Foo(a, b)`.
	 *
	 * @param array<Node> $items
	 * @param array<Node> $originalItems
	 * @param int $open position of the opening bracket
	 * @param int $close position of the closing bracket
	 */
	private function printInlineItems(array $items, array $originalItems, int $open, int $close, string $indent): string
	{
		$items = array_values($items);
		$originalItems = array_values($originalItems);
		$openText = $this->textRange($open, $open);
		$closeText = $this->textRange($close, $close);
		if ($items === []) {
			return $openText . $closeText;
		}
		if ($originalItems === []) {
			$printed = [];
			foreach ($items as $item) {
				$printed[] = $this->printListItem($item, $indent, true);
			}

			return $openText . implode(', ', $printed) . $closeText;
		}

		$defaultSeparator = isset($originalItems[1]) ? $this->getInlineSeparator($originalItems, 1) : ', ';

		$result = $this->textRange($open, $this->start($originalItems[0]) - 1);
		$first = true;
		$previous = null;
		$runStart = null;
		$runEnd = null;
		foreach ($this->diff($originalItems, $items) as [$item, $i, $last]) {
			if ($i !== null) {
				$originalItem = $originalItems[$i];
				if ($item === $originalItem && $runEnd !== null && $runEnd === $i - 1) {
					$runEnd = $last;
					$previous = $last;
					continue;
				}

				$result .= $this->printInlineRun($originalItems, $runStart, $runEnd);
				$runStart = $runEnd = null;
				if (!$first) {
					if ($previous !== null) {
						// the separator after an item has its trailing comment in a multi-line array
						$result .= $this->getInlineSeparator($originalItems, $previous + 1);
					} else {
						$result .= $i > 0 ? $this->getInlineSeparator($originalItems, $i) : $defaultSeparator;
					}
				}
				$previous = $i;
				if ($item === $originalItem) {
					$runStart = $i;
					$runEnd = $previous = $last;
				} else {
					$result .= $this->p($item, $indent);
				}
				$first = false;
				continue;
			}

			$result .= $this->printInlineRun($originalItems, $runStart, $runEnd);
			$runStart = $runEnd = null;
			if (!$first) {
				$result .= $defaultSeparator;
			}
			$result .= $this->printListItem($item, $indent, true);
			$previous = null;
			$first = false;
		}

		return $result
			. $this->printInlineRun($originalItems, $runStart, $runEnd)
			. $this->textRange($this->end($originalItems[count($originalItems) - 1]) + 1, $close);
	}

	/**
	 * @param list<Node> $originalItems
	 */
	private function printInlineRun(array $originalItems, ?int $runStart, ?int $runEnd): string
	{
		if ($runStart === null || $runEnd === null) {
			return '';
		}

		return $this->copy($this->start($originalItems[$runStart]), $this->end($originalItems[$runEnd]));
	}

	/**
	 * @param list<Node> $originalItems
	 */
	private function getInlineSeparator(array $originalItems, int $i): string
	{
		return $this->textRange($this->end($originalItems[$i - 1]) + 1, $this->start($originalItems[$i]) - 1);
	}

	/**
	 * Kept items with their original index, and added items without it. Unchanged items at the start
	 * and at the end are common, like after removing one item from a long list. They are not left
	 * to the differ, and each of the two runs is one element with the index of its last item,
	 * so that the time does not grow with the length of the list.
	 *
	 * @param list<Node> $old
	 * @param list<Node> $new
	 * @return list<array{Node, int<0, max>, int<0, max>}|array{Node, null, null}>
	 */
	private function diff(array $old, array $new): array
	{
		$oldCount = count($old);
		$newCount = count($new);
		$minCount = $oldCount < $newCount ? $oldCount : $newCount;
		$startCount = 0;
		while ($startCount < $minCount && $old[$startCount] === $new[$startCount]) {
			$startCount++;
		}
		$endCount = 0;
		while ($endCount < $minCount - $startCount && $old[$oldCount - 1 - $endCount] === $new[$newCount - 1 - $endCount]) {
			$endCount++;
		}

		$result = [];
		if ($startCount > 0) {
			$result[] = [$new[0], 0, $startCount - 1];
		}
		$i = $startCount;
		$j = $startCount;
		foreach ($this->differ->diff(
			array_slice($old, $startCount, $oldCount - $startCount - $endCount),
			array_slice($new, $startCount, $newCount - $startCount - $endCount),
		) as $diffElem) {
			if ($diffElem->type === DiffElem::TYPE_REMOVE) {
				$i++;
				continue;
			}
			if ($diffElem->type === DiffElem::TYPE_ADD) {
				$result[] = [$new[$j], null, null];
			} else {
				$result[] = [$new[$j], $i, $i];
				$i++;
			}
			$j++;
		}
		if ($endCount > 0) {
			$result[] = [$new[$j], $i, $i + $endCount - 1];
		}

		return $result;
	}

	private function printListItem(Node $item, string $indent, bool $inline): string
	{
		if (!$item instanceof ArrayItemNode || $this->findOriginal($item) !== null) {
			return $this->p($item, $indent);
		}

		if ($inline) {
			return ($item->key !== null ? $this->p($item->key, $indent) . ': ' : '') . $this->p($item->value, $indent);
		}

		return ($item->key !== null ? $this->p($item->key, $indent) . ':' : '-') . $this->printValueAfterHead($item->value, $indent);
	}

	/**
	 * The value of a block item after its key and colon, or after its dash.
	 */
	private function printValueAfterHead(Node $value, string $indent): string
	{
		if ($value instanceof BlockArrayNode) {
			if (count($value->items) === 0) {
				return ' []';
			}

			$childIndent = $indent . ($value->indentation !== '' ? $value->indentation : $this->indentUnit);

			return "\n" . $childIndent . $this->p($value, $childIndent);
		}

		return ' ' . $this->p($value, $indent);
	}

	private function printNew(Node $node, string $indent): string
	{
		if ($node instanceof BlockArrayNode) {
			if (count($node->items) === 0) {
				return '[]';
			}

			$printed = [];
			foreach ($node->items as $item) {
				$printed[] = $this->printListItem($item, $indent, false);
			}

			return implode("\n" . $indent, $printed);
		}

		if ($node instanceof ArrayItemNode) {
			return $this->printListItem($node, $indent, false);
		}

		if ($node instanceof InlineArrayNode) {
			$printed = [];
			foreach ($node->items as $item) {
				$printed[] = $this->printListItem($item, $indent, true);
			}

			return $node->bracket . implode(', ', $printed) . (['[' => ']', '{' => '}', '(' => ')'][$node->bracket] ?? ']');
		}

		if ($node instanceof EntityNode) {
			$printed = [];
			foreach ($node->attributes as $item) {
				$printed[] = $this->printListItem($item, $indent, true);
			}

			return $this->p($node->value, $indent) . '(' . implode(', ', $printed) . ')';
		}

		if ($node instanceof EntityChainNode) {
			$result = '';
			foreach ($node->chain as $entity) {
				$result .= $this->p($entity, $indent);
			}

			return $result;
		}

		return $node->toString();
	}

	/**
	 * A key without a value, like `key:`, has a null value positioned at the token after the colon.
	 */
	private function isEmptyValue(Node $node): bool
	{
		return $node instanceof LiteralNode
			&& $node->value === null
			&& $node->startTokenPos !== null
			&& ($this->tokens[$node->startTokenPos]->type ?? null) !== Token::Literal;
	}

	/**
	 * The first token of the original node. A block array starts with the comment lines right above its first item,
	 * they belong to the item like the lines between two items belong to the second one.
	 */
	private function start(Node $original): int
	{
		$start = $original->startTokenPos ?? 0;
		if (!$original instanceof BlockArrayNode || $original->items === [] || !$this->isFirstOnLine($start)) {
			return $start;
		}

		$pos = $start - 1;
		if ($pos >= 0 && $this->tokens[$pos]->type === Token::Whitespace) {
			$pos--;
		}
		// an empty line separates the comments above it, like a header of the file
		while ($pos > 0 && $this->tokens[$pos]->type === Token::Newline && $this->tokens[$pos]->value === "\n") {
			$commentPos = $pos - 1;
			if ($this->tokens[$commentPos]->type !== Token::Comment || !$this->isFirstOnLine($commentPos)) {
				break;
			}

			$start = $commentPos;
			$pos = $commentPos - 1;
			if ($pos < 0 || $this->tokens[$pos]->type !== Token::Whitespace) {
				continue;
			}

			$pos--;
		}

		return $start;
	}

	/**
	 * The last token of the original node. A node ending with an empty value ends before it,
	 * its end position is after it. A block array ends at the end of its last line.
	 */
	private function end(Node $original): int
	{
		$id = spl_object_id($original);
		if (isset($this->ends[$id])) {
			return $this->ends[$id];
		}

		if ($this->isEmptyValue($original)) {
			$end = $this->start($original) - 1;
		} elseif ($original instanceof ArrayItemNode) {
			if ($this->isEmptyValue($original->value)) {
				$end = $this->start($original->value) - 1;
				while ($end > 0 && in_array($this->tokens[$end]->type, self::TRIVIA_TOKENS, true)) {
					$end--;
				}
			} else {
				$end = $this->end($original->value);
			}
		} elseif ($original instanceof BlockArrayNode && $original->items !== []) {
			// the comment on the last line belongs to the last item, not to what contains the array
			$items = array_values($original->items);
			$end = $this->findLineBreak($this->end($items[count($items) - 1]) + 1) - 1;
		} elseif ($original instanceof EntityChainNode && $original->chain !== []) {
			$chain = array_values($original->chain);
			$end = $this->end($chain[count($chain) - 1]);
		} else {
			$end = $original->endTokenPos ?? 0;
		}

		return $this->ends[$id] = $end;
	}

	/**
	 * The unchanged original node, see copy().
	 */
	private function text(Node $original): string
	{
		return $this->copy($this->start($original), $this->end($original));
	}

	/**
	 * Unchanged code of the tokens from $from to $to, inclusive, to be put into the printed code as it is.
	 * The text is not there yet, use textRange() to look at it.
	 */
	private function copy(int $from, int $to): string
	{
		if ($to < $from) {
			return '';
		}
		if (!$this->useCopies) {
			return $this->textRange($from, $to);
		}

		return self::COPY . $from . '-' . $to . self::COPY;
	}

	/**
	 * Code of the tokens from $from to $to, inclusive.
	 */
	private function textRange(int $from, int $to): string
	{
		if ($to < $from) {
			return '';
		}

		return substr($this->code, $this->offsets[$from], $this->offsets[$to + 1] - $this->offsets[$from]);
	}

	private function isFirstOnLine(int $pos): bool
	{
		$pos--;
		if ($pos >= 0 && $this->tokens[$pos]->type === Token::Whitespace) {
			$pos--;
		}

		return $pos < 0 || $this->tokens[$pos]->type === Token::Newline;
	}

	/**
	 * Indentation of the line of the token.
	 */
	private function indentAt(int $pos): string
	{
		$pos--;
		while ($pos >= 0 && $this->tokens[$pos]->type !== Token::Newline) {
			$pos--;
		}

		$lineStart = $this->tokens[$pos + 1] ?? null;
		if ($lineStart === null || $lineStart->type !== Token::Whitespace) {
			return '';
		}

		return $lineStart->value;
	}

}
