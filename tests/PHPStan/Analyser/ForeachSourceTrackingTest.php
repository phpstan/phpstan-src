<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use Override;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\Group;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;

#[Group('foreach-tracking')]
class ForeachSourceTrackingTest extends PHPStanTestCase
{

	private ReflectionProvider $reflectionProvider;

	private ScopeFactory $scopeFactory;

	#[Override]
	protected function setUp(): void
	{
		$this->reflectionProvider = self::createReflectionProvider();
		$this->scopeFactory = self::createScopeFactory($this->reflectionProvider, self::getContainer()->getByType(TypeSpecifier::class));
	}

	public function testGetForeachSourcesReturnsArray(): void
	{
		$scope = $this->scopeFactory->create(ScopeContext::create('test.php'));

		$foreachSources = $scope->getForeachSources();
		$this->assertIsArray($foreachSources);
		$this->assertEmpty($foreachSources);
	}

	public function testEnterForeachCreatesTracking(): void
	{
		$originalScope = $this->scopeFactory->create(ScopeContext::create('test.php'));

		$arrayExpr = new Variable('array');
		$scope = $originalScope->enterForeach($originalScope, $arrayExpr, 'value', null, false);

		$foreachSources = $scope->getForeachSources();
		$this->assertIsArray($foreachSources);
		$this->assertArrayHasKey('value', $foreachSources);

		$tracking = $foreachSources['value'];
		$this->assertInstanceOf(ForeachSourceTracking::class, $tracking);
		$this->assertSame('value', $tracking->valueVarName);
		$this->assertSame($arrayExpr, $tracking->arrayExpr);
	}

	public function testExitForeachRemovesTracking(): void
	{
		$originalScope = $this->scopeFactory->create(ScopeContext::create('test.php'));

		$arrayExpr = new Variable('array');
		$scope = $originalScope->enterForeach($originalScope, $arrayExpr, 'value', null, false);
		$this->assertArrayHasKey('value', $scope->getForeachSources());

		$scope = $scope->exitForeach('value');
		$this->assertArrayNotHasKey('value', $scope->getForeachSources());
	}

}

