<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Exit_;
use PhpParser\Node\Expr\FuncCall;
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
				'(fn() => exit())();', // immediately invoked function expression
				true,
			],
			[
				'register_shutdown_function(fn() => exit());',
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
				'call_user_func(fn() => exit());',
				false,
			],
			[
				'(function() { exit(); })();',
				true,
			],
			[
				'function () {};',
				false,
			],
			[
				'call_user_func(function() { exit(); });',
				false,
			],
			[
				'usort($arr, static function($a, $b):int { return $a <=> $b; });',
				false,
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
		$this->assertSame($expectedIsAlwaysTerminating, $this->processExpressionStatement($stmts[0])->isAlwaysTerminating());
	}

	public function testClosureTypeIsNotReusedForClosureWithRecycledObjectId(): void
	{
		/** @var Parser $parser */
		$parser = self::getContainer()->getService('currentPhpVersionRichParser');

		/** @var Stmt\Expression $generatorStmt */
		$generatorStmt = $parser->parseString('<?php fn() => yield (exit());')[0];
		$generatorArrowFunction = $generatorStmt->expr;
		$this->assertInstanceOf(ArrowFunction::class, $generatorArrowFunction);
		$this->assertFalse($this->processExpressionStatement($generatorStmt)->isAlwaysTerminating());

		// PHP hands the most recently freed object id to the next allocation - drop the
		// generator arrow function last, and build the next arrow function right after,
		// so it gets the same spl_object_id() unless something still holds the first one
		$exit = new Exit_();
		unset($generatorStmt);
		unset($generatorArrowFunction);
		$arrowFunction = new ArrowFunction(['expr' => $exit], ['isImmediatelyInvokedClosure' => true, 'immediatelyInvokedClosureArgs' => []]);

		$this->assertTrue($this->processExpressionStatement(new Stmt\Expression(new FuncCall($arrowFunction)))->isAlwaysTerminating());
	}

	private function processExpressionStatement(Stmt\Expression $stmt): ExpressionResult
	{
		/** @var NodeScopeResolver $nodeScopeResolver */
		$nodeScopeResolver = self::getContainer()->getByType(NodeScopeResolver::class);
		/** @var ScopeFactory $scopeFactory */
		$scopeFactory = self::getContainer()->getByType(ScopeFactory::class);
		$scope = $scopeFactory->create(ScopeContext::create('test.php'))
			->assignVariable('x', new IntegerType(), new IntegerType(), TrinaryLogic::createYes())
			->assignVariable('arr', new ArrayType(new MixedType(), new MixedType()), new ArrayType(new MixedType(), new MixedType()), TrinaryLogic::createYes());

		return $nodeScopeResolver->processExprNode(
			$stmt,
			$stmt->expr,
			$scope,
			new ExpressionResultStorage(),
			static function (): void {
			},
			ExpressionContext::createTopLevel(),
		);
	}

}
