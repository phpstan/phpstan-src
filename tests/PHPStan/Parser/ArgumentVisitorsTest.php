<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Php\PhpVersion;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ArgumentVisitorsTest extends PHPStanTestCase
{

	/**
	 * @return iterable<array{class-string<NodeVisitorAbstract>, non-empty-string, non-empty-string, 0|1, string, bool}>
	 */
	public static function dataVisitors(): iterable
	{
		yield [ArrayFilterArgVisitor::class, 'array_filter', 'array', 0, ArrayFilterArgVisitor::ATTRIBUTE_NAME, false];
		yield [ArrayFindArgVisitor::class, 'array_find', 'array', 0, ArrayFindArgVisitor::ATTRIBUTE_NAME, false];
		yield [ArrayMapArgVisitor::class, 'array_map', 'callback', 0, ArrayMapArgVisitor::ATTRIBUTE_NAME, true];
		yield [ArrayWalkArgVisitor::class, 'array_walk', 'array', 0, ArrayWalkArgVisitor::ATTRIBUTE_NAME, false];
		yield [CurlSetOptArgVisitor::class, 'curl_setopt', 'handle', 0, CurlSetOptArgVisitor::ATTRIBUTE_NAME, false];
		yield [CurlSetOptArrayArgVisitor::class, 'curl_setopt_array', 'options', 1, CurlSetOptArrayArgVisitor::ATTRIBUTE_NAME, false];
	}

	/**
	 * @param class-string<NodeVisitorAbstract> $visitorClass
	 * @param non-empty-string $functionName
	 * @param non-empty-string $parameterName
	 * @param 0|1 $position
	 */
	#[DataProvider('dataVisitors')]
	public function testNamedArgumentsRespectPhpVersion(string $visitorClass, string $functionName, string $parameterName, int $position, string $attribute, bool $onValue): void
	{
		foreach ([70400 => $position, 80000 => 2] as $version => $expectedIndex) {
			$args = [
				new Arg(new Variable('a'), name: new Identifier('other')),
				new Arg(new Variable('b'), name: new Identifier('another')),
				new Arg(new Variable('c'), name: new Identifier($parameterName)),
			];
			$visitor = new $visitorClass(new PhpVersion($version));
			$visitor->enterNode(new FuncCall(new Name($functionName), $args));

			foreach ($args as $index => $arg) {
				$node = $onValue ? $arg->value : $arg;
				$this->assertSame($index === $expectedIndex, $node->hasAttribute($attribute));
			}
			if (!$onValue) {
				continue;
			}

			$this->assertArrayHasKey($expectedIndex, $args);
			$this->assertSame(
				$version < 80000 ? [$args[1], $args[2]] : [$args[0], $args[1]],
				$args[$expectedIndex]->value->getAttribute($attribute),
			);
		}
	}

}
