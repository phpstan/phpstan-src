<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\TrinaryLogic;
use PHPStan\Type\IntegerType;
use PHPStan\Type\TypeCombinator;

class NonNullabilityHelperTest extends PHPStanTestCase
{

	public function testActiveEnsuresAreResetPerFile(): void
	{
		$helper = self::getContainer()->getByType(NonNullabilityHelper::class);

		$reflectionProvider = self::createReflectionProvider();
		$scopeFactory = self::createScopeFactory($reflectionProvider, self::getContainer()->getService('typeSpecifier'));
		$nullableInt = TypeCombinator::addNull(new IntegerType());
		$scope = $scopeFactory->create(ScopeContext::create('file.php'))
			->assignVariable('a', $nullableInt, $nullableInt, TrinaryLogic::createYes());
		$expr = new Variable('a');

		$helper->ensureShallowNonNullability($scope, $scope, $expr);
		$this->assertNotNull($helper->getActiveEnsuredOriginalType($expr, false));

		// an internal error escaping between ensure and revert must not leak the
		// stale frame into the next file's analysis
		$helper->resetFileAnalysisState();
		$this->assertNull($helper->getActiveEnsuredOriginalType($expr, false));
	}

}
