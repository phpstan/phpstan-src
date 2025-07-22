<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
use PHPStan\Parser\Parser;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPUnit\Framework\Attributes\DataProvider;
use function count;
use function get_class;
use function sprintf;

class ExpressionResultTest extends PHPStanTestCase
{

	public static function dataIsAlwaysTerminating(): array
	{
		return [
			[
				'sprintf("hello %s", "abc");',
				false,
			],
			[
				'isset($x);',
				false,
			],
			[
				'$x ? "def" : "abc";',
				false,
			],
			[
				'(string) $x;',
				false,
			],
			[
				'$x || exit();',
				false,
			],
			[
				'$x ?? exit();',
				false,
			],
			[
				'sprintf("hello %s", exit());',
				true,
			],
			[
				'(string) exit();',
				true,
			],
			[
				'!exit();',
				true,
			],
			[
				'eval(exit());',
				true,
			],
			[
				'empty(exit());',
				true,
			],
			[
				'isset(exit());',
				true,
			],
			[
				'$x ? "abc" : exit();',
				false,
			],
			[
				'$x ? exit() : "abc";',
				false,
			],
			[
				'fn() => yield (exit());',
				false,
			],
			[
				'@exit();',
				true,
			],
			[
				'$x && exit();',
				false,
			],
			[
				'exit() && $x;',
				true,
			],
			[
				'exit() || $x;',
				true,
			],
			[
				'exit() ?? $x;',
				true,
			],
			[
				'var_dump(1+exit());',
				true,
			],
			[
				'var_dump(1-exit());',
				true,
			],
			[
				'var_dump(1*exit());',
				true,
			],
			[
				'var_dump(1**exit());',
				true,
			],
			[
				'var_dump(1/exit());',
				true,
			],
			[
				'var_dump("a".exit());',
				true,
			],
			[
				'var_dump(exit()."a");',
				true,
			],
			[
				'array_push($arr, fn() => "exit");',
				false,
			],
			[
				'array_push($arr, function() { exit(); });',
				false,
			],
			[
				'array_push($arr, "exit");',
				false,
			],
			[
				'array_unshift($arr, "exit");',
				false,
			],
		];
	}

	#[DataProvider('dataIsAlwaysTerminating')]
	public function testIsAlwaysTerminating(
		string $code,
		bool $expectedIsAlwaysTerminating,
	): void
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');

		/** @var Stmt[] $stmts */
		$stmts = $parser->parseString(sprintf('<?php %s', $code));
		if (count($stmts) !== 1) {
			throw new ShouldNotHappenException('Expecting code which evaluates to a single statement, got: ' . count($stmts));
		}
		if (!$stmts[0] instanceof Stmt\Expression) {
			throw new ShouldNotHappenException('Expecting code contains a single statement expression, got: ' . get_class($stmts[0]));
		}
		$stmt = $stmts[0];
		$expr = $stmt->expr;

		/** @var NodeScopeResolver $nodeScopeResolver */
		$nodeScopeResolver = self::getContainer()->getByType(NodeScopeResolver::class);
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('test.php'))
			->assignVariable('x', new IntegerType(), new IntegerType(), TrinaryLogic::createYes())
			->assignVariable('arr', new ArrayType(new MixedType(), new MixedType()), new ArrayType(new MixedType(), new MixedType()), TrinaryLogic::createYes());

		$result = $nodeScopeResolver->processExprNode(
			$stmt,
			$expr,
			$scope,
			static function (): void {
			},
			ExpressionContext::createTopLevel(),
		);
		$this->assertSame($expectedIsAlwaysTerminating, $result->isAlwaysTerminating());
	}

}
