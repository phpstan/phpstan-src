<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use LogicException;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php8;
use PhpParser\PhpVersion as PhpParserPhpVersion;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PHPStan\Testing\PHPStanTestCase;

class BatchReplacingNodeVisitorTest extends PHPStanTestCase
{

	public function testNonOverlappingSiblingFixesBothApply(): void
	{
		$code = <<<'PHP'
<?php
f($a, $b);
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$vars = $finder->findInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertCount(2, $vars);
		[$origA, $origB] = $vars;
		self::assertSame('a', $origA->name);
		self::assertSame('b', $origB->name);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origA,
				'callable' => static fn (Node $n): Node => self::wrap('A', $n),
			],
			[
				'node' => $origB,
				'callable' => static fn (Node $n): Node => self::wrap('B', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame('f(A($a), B($b));', $result);
		self::assertCount(2, $visitor->getAppliedOriginalNodes());
	}

	public function testNestedFixesAreAppliedRecursively(): void
	{
		$code = <<<'PHP'
<?php
outer($inner);
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$origOuter = $finder->findFirstInstanceOf($origStmts, Node\Expr\FuncCall::class);
		self::assertInstanceOf(Node\Expr\FuncCall::class, $origOuter);
		$origInner = $finder->findFirstInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertNotNull($origInner);
		self::assertSame('inner', $origInner->name);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origOuter,
				'callable' => static function (Node $n): Node {
					self::assertInstanceOf(Node\Expr\FuncCall::class, $n);
					return new Node\Expr\FuncCall(new Node\Name('wrap'), $n->getArgs());
				},
			],
			[
				'node' => $origInner,
				'callable' => static fn (Node $n): Node => self::wrap('mod', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame('wrap(mod($inner));', $result);
		self::assertCount(2, $visitor->getAppliedOriginalNodes());
	}

	public function testInnerFixIsSilentlySkippedWhenOuterFixDiscardsIt(): void
	{
		$code = <<<'PHP'
<?php
outer($inner);
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$origOuter = $finder->findFirstInstanceOf($origStmts, Node\Expr\FuncCall::class);
		self::assertInstanceOf(Node\Expr\FuncCall::class, $origOuter);
		$origInner = $finder->findFirstInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertInstanceOf(Node\Expr\Variable::class, $origInner);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origOuter,
				'callable' => static fn (Node $n): Node => new Node\Scalar\String_('replaced'),
			],
			[
				'node' => $origInner,
				'callable' => static fn (Node $n): Node => self::wrap('mod', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame("'replaced';", $result);
		$applied = $visitor->getAppliedOriginalNodes();
		self::assertCount(1, $applied);
		self::assertSame($origOuter, $applied[0]);
	}

	public function testIdempotentDuplicateFixesOnSameNodeApplyOnce(): void
	{
		$code = <<<'PHP'
<?php
$a;
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$origVar = $finder->findFirstInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertInstanceOf(Node\Expr\Variable::class, $origVar);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('safe', $n),
			],
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('safe', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame('safe($a);', $result);
		self::assertCount(1, $visitor->getAppliedOriginalNodes());
	}

	public function testConflictingFixesOnSameNodeAreSkipped(): void
	{
		$code = <<<'PHP'
<?php
$a;
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$origVar = $finder->findFirstInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertInstanceOf(Node\Expr\Variable::class, $origVar);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('A', $n),
			],
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('B', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame('$a;', $result);
		self::assertSame([], $visitor->getAppliedOriginalNodes());
	}

	public function testConflictedNodeStaysSkippedEvenWhenLaterFixAgrees(): void
	{
		$code = <<<'PHP'
<?php
$a;
PHP;

		[$origStmts, $cloneStmts] = $this->parseAndClone($code);

		$finder = new NodeFinder();
		$origVar = $finder->findFirstInstanceOf($origStmts, Node\Expr\Variable::class);
		self::assertInstanceOf(Node\Expr\Variable::class, $origVar);

		$visitor = new BatchReplacingNodeVisitor([
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('A', $n),
			],
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('B', $n),
			],
			[
				'node' => $origVar,
				'callable' => static fn (Node $n): Node => self::wrap('A', $n),
			],
		]);

		$result = $this->runVisitor($cloneStmts, $visitor);

		self::assertSame('$a;', $result);
		self::assertSame([], $visitor->getAppliedOriginalNodes());
	}

	/**
	 * @param non-empty-string $name
	 */
	private static function wrap(string $name, Node $expr): Node\Expr\FuncCall
	{
		if (!$expr instanceof Node\Expr) {
			throw new LogicException('wrap() expects an expression');
		}
		return new Node\Expr\FuncCall(new Node\Name($name), [new Node\Arg($expr)]);
	}

	/**
	 * @return array{0: Node\Stmt[], 1: Node[]}
	 */
	private function parseAndClone(string $code): array
	{
		$parser = new Php8(new Emulative(PhpParserPhpVersion::fromComponents(8, 5)));
		$origStmts = $parser->parse($code);
		self::assertNotNull($origStmts);

		$cloningTraverser = new NodeTraverser();
		$cloningTraverser->addVisitor(new CloningVisitor());
		$cloneStmts = $cloningTraverser->traverse($origStmts);

		return [$origStmts, $cloneStmts];
	}

	/**
	 * @param Node[] $stmts
	 */
	private function runVisitor(array $stmts, BatchReplacingNodeVisitor $visitor): string
	{
		$traverser = new NodeTraverser();
		$traverser->addVisitor($visitor);
		$result = $traverser->traverse($stmts);

		$printer = new StandardPrettyPrinter();
		return $printer->prettyPrint($result);
	}

}
