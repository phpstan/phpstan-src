<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RuleScopeCodecTest extends TestCase
{

	public function testNamesEachNodeClassOnce(): void
	{
		$encoded = RuleScopeCodec::encode(
			[
				'src/A.php' => ['PhpParser\Node\Stmt\Class_', 'PhpParser\Node\Expr\New_'],
				'src/B.php' => ['PhpParser\Node\Expr\New_'],
				'src/C.php' => [],
			],
			['App\Rules\NewRule' => ['PhpParser\Node\Expr\New_']],
		);

		$this->assertSame([
			'nodeClasses' => ['PhpParser\Node\Stmt\Class_', 'PhpParser\Node\Expr\New_'],
			'nodeTypes' => [
				'src/A.php' => [0, 1],
				'src/B.php' => [1],
				'src/C.php' => [],
			],
			'ruleNodeTypes' => ['App\Rules\NewRule' => ['PhpParser\Node\Expr\New_']],
		], $encoded);
	}

	public function testRoundTrip(): void
	{
		$nodeTypes = [
			'src/A.php' => ['PhpParser\Node\Stmt\Class_', 'PhpParser\Node\Expr\New_'],
			'src/B.php' => ['PhpParser\Node\Expr\New_', 'PHPStan\Node\InClassNode'],
			'src/C.php' => [],
		];
		$ruleNodeTypes = [
			'App\Rules\NewRule' => ['PhpParser\Node\Expr\New_'],
			'App\Rules\TwoNodesRule' => ['PhpParser\Node\Stmt\Class_', 'PHPStan\Node\InClassNode'],
		];

		$this->assertSame(
			['nodeTypes' => $nodeTypes, 'ruleNodeTypes' => $ruleNodeTypes],
			RuleScopeCodec::decode(RuleScopeCodec::encode($nodeTypes, $ruleNodeTypes)),
		);
	}

	public function testEmptyRoundTrip(): void
	{
		$this->assertSame(
			['nodeTypes' => [], 'ruleNodeTypes' => []],
			RuleScopeCodec::decode(RuleScopeCodec::encode([], [])),
		);
	}

	/**
	 * @return iterable<string, array{mixed}>
	 */
	public static function dataMalformed(): iterable
	{
		yield 'not an array' => [null];
		yield 'no class table' => [['nodeTypes' => [], 'ruleNodeTypes' => []]];
		yield 'no node types' => [['nodeClasses' => [], 'ruleNodeTypes' => []]];
		yield 'no rule node types' => [['nodeClasses' => [], 'nodeTypes' => []]];
		yield 'node types of a file are not a list' => [['nodeClasses' => ['Foo'], 'nodeTypes' => ['src/A.php' => 0], 'ruleNodeTypes' => []]];
		yield 'position past the class table' => [['nodeClasses' => ['Foo'], 'nodeTypes' => ['src/A.php' => [0, 1]], 'ruleNodeTypes' => []]];
		yield 'position is not an integer' => [['nodeClasses' => ['Foo'], 'nodeTypes' => ['src/A.php' => ['0']], 'ruleNodeTypes' => []]];
		yield 'class names in place of positions' => [['nodeTypes' => ['src/A.php' => ['Foo']], 'ruleNodeTypes' => []]];
	}

	/**
	 * @param mixed $ruleScope
	 */
	#[DataProvider('dataMalformed')]
	public function testMalformed($ruleScope): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The rule scope is malformed.');

		RuleScopeCodec::decode($ruleScope);
	}

}
