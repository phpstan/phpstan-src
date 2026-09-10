<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt;
use PHPStan\Parser\Parser;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
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
			new ExpressionResultStorage(),
			static function (): void {
			},
			ExpressionContext::createTopLevel(),
		);
		$this->assertSame($expectedIsAlwaysTerminating, $result->isAlwaysTerminating());
	}

	public function testFinalizePreservesPreliminaryResult(): void
	{
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('test.php'));
		$expr = new Variable('value');
		$type = new IntegerType();
		$result = self::getContainer()->getByType(ExpressionResultFactory::class)->create(
			$scope,
			$scope,
			$expr,
			false,
			false,
			[],
			[],
			static fn (): IntegerType => $type,
			SpecifiedTypes::emptySpecifyCallback(),
		);
		$finalScope = $scope->assignVariable('value', $type, $type, TrinaryLogic::createYes());
		$throwPoints = [InternalThrowPoint::createImplicit($finalScope, $expr)];
		$flow = VariableFlow::read('value');
		$final = $result->finalize($finalScope, true, true, $throwPoints, [], $flow);

		$this->assertNotSame($result, $final);
		$this->assertSame($scope, $result->getScope());
		$this->assertFalse($result->hasYield());
		$this->assertFalse($result->isAlwaysTerminating());
		$this->assertSame([], $result->getThrowPoints());
		$this->assertNull($result->getVariableFlow());
		$this->assertSame($finalScope, $final->getScope());
		$this->assertTrue($final->hasYield());
		$this->assertTrue($final->isAlwaysTerminating());
		$this->assertSame($throwPoints, $final->getThrowPoints());
		$this->assertSame($flow, $final->getVariableFlow());
		$this->assertSame($type, $final->getType());
	}

	/** @return iterable<array{string, int}> */
	public static function dataCopiesPreserveMemoizedAnswers(): iterable
	{
		foreach (['finalize', 'withScope', 'atAskPosition', 'onNonNullabilityDevicedScopes'] as $method) {
			foreach ([0, 1, 2, 3] as $resolved) {
				yield [$method, $resolved];
			}
		}
	}

	#[DataProvider('dataCopiesPreserveMemoizedAnswers')]
	public function testCopiesPreserveMemoizedAnswers(string $method, int $resolved): void
	{
		$scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create('test.php'));
		$type = new IntegerType();
		$nativeType = new MixedType();
		$calls = new class {

			public int $types = 0;

			public int $narrowing = 0;

		};
		$result = self::getContainer()->getByType(ExpressionResultFactory::class)->create(
			$scope,
			$scope,
			new Int_(1),
			false,
			false,
			[],
			[],
			static function (bool $native) use ($calls, $type, $nativeType): Type {
				$calls->types++;
				return $native ? $nativeType : $type;
			},
			static function () use ($calls): SpecifiedTypes {
				$calls->narrowing++;
				return new SpecifiedTypes([], []);
			},
		);
		if (($resolved & 1) !== 0) {
			$this->assertSame($type, $result->getType());
		}
		if (($resolved & 2) !== 0) {
			$this->assertSame($nativeType, $result->getNativeType());
		}
		$context = TypeSpecifierContext::createTruthy();
		$specifiedTypes = $result->getSpecifiedTypes($context);
		$newScope = $scope->assignVariable('other', $type, $type, TrinaryLogic::createYes());
		switch ($method) {
			case 'finalize':
				$copy = $result->finalize($newScope, false, false, [], [], null);
				break;
			case 'withScope':
				$copy = $result->withScope($newScope);
				break;
			case 'atAskPosition':
				$copy = $result->atAskPosition($newScope);
				break;
			case 'onNonNullabilityDevicedScopes':
				$copy = $result->onNonNullabilityDevicedScopes($newScope, $newScope);
				break;
			default:
				throw new ShouldNotHappenException();
		}

		$this->assertNotSame($result, $copy);
		$this->assertSame($scope, $result->getScope());
		$this->assertSame($newScope, $copy->getScope());
		$this->assertSame($type, $copy->getType());
		$this->assertSame($nativeType, $copy->getNativeType());
		$this->assertSame($type, $copy->getKeepVoidType(false));
		$this->assertSame($nativeType, $copy->getKeepVoidType(true));
		$this->assertSame($specifiedTypes, $copy->getSpecifiedTypes($context));
		$this->assertSame(2, $calls->types);
		$this->assertSame(1, $calls->narrowing);
	}

}
