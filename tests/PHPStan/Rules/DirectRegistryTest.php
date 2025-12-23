<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;

class DirectRegistryTest extends PHPStanTestCase
{

	public function testGetRules(): void
	{
		$rule = new DummyRule();

		$registry = new DirectRegistry([
			$rule,
		]);

		$rules = $registry->getRules(Node\Expr\FuncCall::class);
		self::assertCount(1, $rules);
		self::assertSame($rule, $rules[0]);

		self::assertCount(0, $registry->getRules(Node\Expr\MethodCall::class));
	}

	public function testGetRulesWithTwoDifferentInstances(): void
	{
		$fooRule = new UniversalRule(Node\Expr\FuncCall::class, static fn (Node\Expr\FuncCall $node, Scope $scope): array => [
			RuleErrorBuilder::message('Foo error')->identifier('tests.fooRule')->build(),
		]);
		$barRule = new UniversalRule(Node\Expr\FuncCall::class, static fn (Node\Expr\FuncCall $node, Scope $scope): array => [
			RuleErrorBuilder::message('Bar error')->identifier('tests.barRule')->build(),
		]);

		$registry = new DirectRegistry([
			$fooRule,
			$barRule,
		]);

		$rules = $registry->getRules(Node\Expr\FuncCall::class);
		self::assertCount(2, $rules);
		self::assertSame($fooRule, $rules[0]);
		self::assertSame($barRule, $rules[1]);

		self::assertCount(0, $registry->getRules(Node\Expr\MethodCall::class));
	}

}
