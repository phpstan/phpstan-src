<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodAssertRule>
 */
class MethodAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		return new MethodAssertRule(new AssertRuleHelper($initializerExprTypeResolver));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/method-assert.php';
		$this->analyse([__DIR__ . '/data/method-assert.php'], [
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				10,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				19,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				28,
			],
			[
				'Asserted type int|string for $i with type int does not narrow down the type.',
				44,
			],
			[
				'Asserted type string for $i with type int can never happen.',
				51,
			],
			[
				'Assert references unknown parameter $j.',
				58,
			],
			[
				'Asserted negated type int for $i with type int can never happen.',
				65,
			],
			[
				'Asserted negated type string for $i with type int does not narrow down the type.',
				72,
			],
		]);
	}

	public function testBug10573(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10573.php'], []);
	}

	public function testBug10214(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10214.php'], []);
	}

}
