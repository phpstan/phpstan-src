<?php declare(strict_types = 1);

namespace PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

class RegistryTest extends PHPStanTestCase
{

	public function testGetCollectors(): void
	{
		$collector = new DummyCollector();

		$registry = new Registry([
			$collector,
		]);

		$collectors = $registry->getCollectors(Node\Expr\FuncCall::class);
		$this->assertCount(1, $collectors);
		$this->assertSame($collector, $collectors[0]);

		$this->assertCount(0, $registry->getCollectors(Node\Expr\MethodCall::class));
	}

	public function testGetCollectorsWithTwoDifferentInstances(): void
	{
		$fooCollector = new UniversalCollector(Node\Expr\FuncCall::class, static fn (Node\Expr\FuncCall $node, Scope $scope): array => ['Foo error']);
		$barCollector = new UniversalCollector(Node\Expr\FuncCall::class, static fn (Node\Expr\FuncCall $node, Scope $scope): array => ['Bar error']);

		$registry = new Registry([
			$fooCollector,
			$barCollector,
		]);

		$collectors = $registry->getCollectors(Node\Expr\FuncCall::class);
		$this->assertCount(2, $collectors);
		$this->assertSame($fooCollector, $collectors[0]);
		$this->assertSame($barCollector, $collectors[1]);

		$this->assertCount(0, $registry->getCollectors(Node\Expr\MethodCall::class));
	}

}
