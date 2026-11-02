<?php declare(strict_types = 1);

namespace PHPStan\Neon;

use Closure;
use Generator;
use Nette\Neon\Lexer;
use Nette\Neon\Neon;
use Nette\Neon\Node;
use Nette\Neon\Node\ArrayItemNode;
use Nette\Neon\Node\ArrayNode;
use Nette\Neon\Node\BlockArrayNode;
use Nette\Neon\Node\EntityNode;
use Nette\Neon\Node\LiteralNode;
use Nette\Neon\Node\StringNode;
use Nette\Neon\Parser;
use Nette\Neon\Traverser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function array_filter;
use function array_reverse;
use function array_values;
use function is_array;
use function substr_replace;

class PrinterTest extends TestCase
{

	/**
	 * @param callable(mixed): bool $predicate
	 * @return Closure(Node): null
	 */
	private static function removeItems(callable $predicate): Closure
	{
		return static function (Node $node) use ($predicate) {
			if ($node instanceof ArrayNode) {
				$node->items = array_values(array_filter($node->items, static fn (ArrayItemNode $item): bool => !$predicate($item->value->toValue())));
			}
			return null;
		};
	}

	/**
	 * @return Closure(Node): ?Node
	 */
	private static function replaceLiteral(mixed $old, mixed $new): Closure
	{
		return static fn (Node $node): ?Node => $node instanceof LiteralNode && $node->value === $old ? new LiteralNode($new) : null;
	}

	private static function item(?string $key, Node $value): ArrayItemNode
	{
		$item = new ArrayItemNode();
		$item->key = $key !== null ? new LiteralNode($key) : null;
		$item->value = $value;

		return $item;
	}

	public static function dataPrintFormatPreserving(): Generator
	{
		$noChange = static fn (Node $node) => null;

		yield 'unchanged' => [
			"# header\nparameters:\n\tlevel: 8 # max\n\tpaths: [src,  tests]\n\n\tfoo: Foo( 1 , 2 )\n",
			$noChange,
			"# header\nparameters:\n\tlevel: 8 # max\n\tpaths: [src,  tests]\n\n\tfoo: Foo( 1 , 2 )\n",
		];

		$entries = "parameters:\n\tignoreErrors:\n"
			. "\t\t# first\n\t\t-\n\t\t\tmessage: '#^A$#'\n\t\t\tpath: A.php\n"
			. "\n\t\t# second\n\t\t-\n\t\t\tmessage: '#^B$#' # b\n\t\t\tpath: B.php\n"
			. "\t\t-\n\t\t\tmessage: '#^C$#'\n\t\t\tpath: C.php\n"
			. "\tlevel: 8\n";
		$isEntry = static fn (string $message) => static fn (mixed $value): bool => is_array($value) && ($value['message'] ?? null) === $message;

		yield 'first block item with the comments above it' => [
			$entries,
			self::removeItems($isEntry('#^A$#')),
			"parameters:\n\tignoreErrors:\n"
			. "\t\t# second\n\t\t-\n\t\t\tmessage: '#^B$#' # b\n\t\t\tpath: B.php\n"
			. "\t\t-\n\t\t\tmessage: '#^C$#'\n\t\t\tpath: C.php\n"
			. "\tlevel: 8\n",
		];

		yield 'middle block item with the lines above it' => [
			$entries,
			self::removeItems($isEntry('#^B$#')),
			"parameters:\n\tignoreErrors:\n"
			. "\t\t# first\n\t\t-\n\t\t\tmessage: '#^A$#'\n\t\t\tpath: A.php\n"
			. "\t\t-\n\t\t\tmessage: '#^C$#'\n\t\t\tpath: C.php\n"
			. "\tlevel: 8\n",
		];

		yield 'last block item' => [
			$entries,
			self::removeItems($isEntry('#^C$#')),
			"parameters:\n\tignoreErrors:\n"
			. "\t\t# first\n\t\t-\n\t\t\tmessage: '#^A$#'\n\t\t\tpath: A.php\n"
			. "\n\t\t# second\n\t\t-\n\t\t\tmessage: '#^B$#' # b\n\t\t\tpath: B.php\n"
			. "\tlevel: 8\n",
		];

		yield 'all block items' => [
			$entries,
			self::removeItems(static fn (mixed $value): bool => is_array($value) && isset($value['message'])),
			"parameters:\n\tignoreErrors: []\n\tlevel: 8\n",
		];

		yield 'item of a block list in a block opened on the line of a dash' => [
			"- messages:\n    - a\n    - b # b\n  path: A.php\n",
			self::removeItems(static fn (mixed $value): bool => $value === 'b'),
			"- messages:\n    - a\n  path: A.php\n",
		];

		yield 'scalar with a trailing comment' => [
			"count: 2 # two\nlevel: 2\n",
			self::replaceLiteral(2, 1),
			"count: 1 # two\nlevel: 1\n",
		];

		yield 'first inline item' => [
			"paths: [a,  b, c] # paths\n",
			self::removeItems(static fn (mixed $value): bool => $value === 'a'),
			"paths: [b, c] # paths\n",
		];

		yield 'last inline item' => [
			"paths: [a,  b, c] # paths\n",
			self::removeItems(static fn (mixed $value): bool => $value === 'c'),
			"paths: [a,  b] # paths\n",
		];

		yield 'item of a multi-line inline list' => [
			"paths: [\n\ta, # a\n\tb,\n\tc,\n]\n",
			self::removeItems(static fn (mixed $value): bool => $value === 'b'),
			"paths: [\n\ta, # a\n\tc,\n]\n",
		];

		yield 'entity attribute' => [
			"services:\n\t- Foo( 1 , 2 )\n",
			static function (Node $node) {
				if ($node instanceof EntityNode) {
					$node->attributes = [$node->attributes[0]];
				}
				return null;
			},
			"services:\n\t- Foo( 1 )\n",
		];

		yield 'new block item with the indentation of the others' => [
			"paths:\n    - a\nlevel: 8\n",
			static function (Node $node) {
				if ($node instanceof BlockArrayNode && $node->items[0]->key === null) {
					$node->items[] = self::item(null, new StringNode('b c'));
				}
				return null;
			},
			"paths:\n    - a\n    - 'b c'\nlevel: 8\n",
		];

		yield 'new block value of an empty key' => [
			"parameters:\n\tpaths:\n\tlevel: 8\n",
			static function (Node $node) {
				if ($node instanceof ArrayItemNode && $node->key instanceof LiteralNode && $node->key->value === 'paths') {
					$list = new BlockArrayNode();
					$list->items = [self::item(null, new LiteralNode('a')), self::item(null, new LiteralNode('b'))];
					$node->value = $list;
				}
				return null;
			},
			"parameters:\n\tpaths:\n\t\t- a\n\t\t- b\n\tlevel: 8\n",
		];
	}

	/**
	 * @param Closure(Node): ?Node $change called when leaving each node of a copy of the parsed tree
	 */
	#[DataProvider('dataPrintFormatPreserving')]
	public function testPrintFormatPreserving(string $code, Closure $change, string $expected): void
	{
		$tokens = (new Lexer())->tokenize($code);
		$originalNode = (new Parser())->parse($tokens);
		$node = (new Traverser())->traverse($originalNode, static fn (Node $node): Node => clone $node, $change);

		$printer = new Printer();
		$printed = $printer->printFormatPreserving($node, $originalNode, $tokens);
		$this->assertSame($expected, $printed);
		$this->assertEquals($node->toValue(), Neon::decode($printed));

		$changed = $code;
		foreach (array_reverse($printer->printChanges($node, $originalNode, $tokens)) as $change) {
			$changed = substr_replace($changed, $change['replacement'], $change['start'], $change['end'] - $change['start']);
		}
		$this->assertSame($expected, $changed);

		// the original tree prints the original code
		$this->assertSame($code, (new Printer())->printFormatPreserving($originalNode, $originalNode, $tokens));
	}

}
