<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NullsafeMethodCallRule>
 */
class NullsafeMethodCallRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafeMethodCallRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-method-call-rule.php'], [
			[
				'Using nullsafe method call on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

	public function testNullsafeVsScalar(): void
	{
		 $this->analyse([__DIR__ . '/../../Analyser/nsrt/nullsafe-vs-scalar.php'], []);
	}

	public function testBug8664(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-8664.php'], []);
	}

	public function testBug14150(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14150-nullsafe.php'], [
			[
				'Using nullsafe method call on non-nullable type $this(Bug14150NullsafeMethod\HelloWorld). Use -> instead.',
				21,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9293(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-9293.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6922b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6922b.php'], []);
	}

	public function testBug8523(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8523.php'], []);
	}

	public function testBug8523b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8523b.php'], []);
	}

	public function testBug8523c(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8523c.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug12222(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12222.php'], []);
	}

}
