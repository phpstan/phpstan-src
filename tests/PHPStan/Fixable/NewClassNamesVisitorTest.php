<?php declare(strict_types = 1);

namespace PHPStan\Fixable;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NewClassNamesVisitorTest extends TestCase
{

	private const CODE = <<<'PHP'
		<?php

		namespace HasWork;

		class Foo
		{

			/** @return int */
			#[\Override]
			public function doFoo(\Bar\Baz $baz): int
			{
				return strlen('');
			}

		}
		PHP;

	/**
	 * @return iterable<string, array{callable(ClassMethod): Node, bool}>
	 */
	public static function dataHasNewNames(): iterable
	{
		yield 'unchanged' => [
			static fn (ClassMethod $method): Node => $method,
			false,
		];

		yield 'removed attribute' => [
			static function (ClassMethod $method): Node {
				$method->attrGroups = [];
				return $method;
			},
			false,
		];

		yield 'added name' => [
			static function (ClassMethod $method): Node {
				$method->params[] = new Node\Param(new Node\Expr\Variable('qux'), type: new FullyQualified('Qux\Quux'));
				return $method;
			},
			true,
		];

		yield 'changed PHPDoc' => [
			static function (ClassMethod $method): Node {
				$method->setDocComment(new Doc('/** @return positive-int */'));
				return $method;
			},
			true,
		];

		yield 'new node with a PHPDoc' => [
			static function (ClassMethod $method): Node {
				$newMethod = new ClassMethod('doBar');
				$newMethod->setDocComment(new Doc('/** @return void */'));
				return $newMethod;
			},
			true,
		];
	}

	/**
	 * @param callable(ClassMethod): Node $fix
	 */
	#[DataProvider('dataHasNewNames')]
	public function testHasNewNames(callable $fix, bool $expected): void
	{
		$stmts = (new ParserFactory())->createForHostVersion()->parse(self::CODE);
		$this->assertNotNull($stmts);

		$newStmts = (new NodeTraverser(new CloningVisitor()))->traverse($stmts);
		$method = (new NodeFinder())->findFirstInstanceOf($newStmts, ClassMethod::class);
		$this->assertNotNull($method);

		$this->assertSame($expected, NewClassNamesVisitor::hasNewNames([$fix($method)]));
	}

}
