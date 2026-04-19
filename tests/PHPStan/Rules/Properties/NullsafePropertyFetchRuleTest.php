<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NullsafePropertyFetchRule>
 */
class NullsafePropertyFetchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NullsafePropertyFetchRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch-rule.php'], [
			[
				'Using nullsafe property access on non-nullable type Exception. Use -> instead.',
				16,
			],
		]);
	}

	public function testBug6020(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6020.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7109(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7109.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug5172(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-5172.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug7980(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/data/bug-7980.php'], []);
	}

	public function testBug8517(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-8517.php'], []);
	}

	public function testBug14150(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14150-nullsafe.php'], [
			[
				'Using nullsafe property access on non-nullable type $this(Bug14150NullsafeProperty\HelloWorld). Use -> instead.',
				20,
			],
			[
				'Using nullsafe property access on non-nullable type $this(Bug14150NullsafeProperty\HelloWorld). Use -> instead.',
				27,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9105(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-9105.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug6922(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6922.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug14493(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14493.php'], []);
	}

}
