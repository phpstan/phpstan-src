<?php declare(strict_types = 1);

namespace PHPStan\Analyser\StmtHandler;

use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\NoopNodeCallback;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\StatementContext;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

class ForeachHandlerTest extends PHPStanTestCase
{

	public static function dataConstantArrayConditionalHolders(): iterable
	{
		// no keys: both holder tables stay the empty array literal
		yield [new ConstantArrayType([], []), 0];
		yield [new ConstantArrayType([new ConstantStringType('a')], [new ConstantIntegerType(1)]), 1];
	}

	#[DataProvider('dataConstantArrayConditionalHolders')]
	public function testConstantArrayConditionalHoldersReachScopeOverride(ConstantArrayType $iterateeType, int $expectedHolders): void
	{
		$container = self::getContainer();
		$scope = self::createScopeFactory(self::createReflectionProvider(), $container->getService('typeSpecifier'))
			->create(ScopeContext::create(__FILE__))
			->assignVariable('a', $iterateeType, $iterateeType, TrinaryLogic::createYes());
		// the scope factory only creates MutatingScope: rebuild the scope as
		// the subclass from its constructor arguments
		$args = [];
		$reflection = new ReflectionClass(MutatingScope::class);
		foreach ($reflection->getMethod('__construct')->getParameters() as $parameter) {
			$args[$parameter->getName()] = $reflection->getProperty($parameter->getName())->getValue($scope);
		}
		$scope = new ConditionalExpressionsRecordingScope(...$args);

		$container->getByType(ForeachHandler::class)->processStmt(
			$container->getByType(NodeScopeResolver::class),
			new Foreach_(new Variable('a'), new Variable('v'), ['keyVar' => new Variable('k')]),
			$scope,
			new ExpressionResultStorage(),
			new NoopNodeCallback(),
			StatementContext::createDeep(),
		);

		$this->assertSame([
			['$v', $expectedHolders],
			['$a[$k]', $expectedHolders],
		], $scope->added);
	}

}
