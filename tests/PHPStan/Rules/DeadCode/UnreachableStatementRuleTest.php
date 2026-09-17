<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<UnreachableStatementRule>
 */
class UnreachableStatementRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new UnreachableStatementRule();
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/unreachable.php'], [
			[
				'Unreachable statement - code above always terminates.',
				12,
			],
			[
				'Unreachable statement - code above always terminates.',
				19,
			],
			[
				'Unreachable statement - code above always terminates.',
				30,
			],
			[
				'Unreachable statement - code above always terminates.',
				44,
			],
			[
				'Unreachable statement - code above always terminates.',
				58,
			],
			[
				'Unreachable statement - code above always terminates.',
				93,
			],
			[
				'Unreachable statement - code above always terminates.',
				157,
			],
		]);
	}

	public function testRuleTopLevel(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/unreachable-top-level.php'], [
			[
				'Unreachable statement - code above always terminates.',
				5,
			],
		]);
	}

	public static function dataBugWithoutGitHubIssue1(): array
	{
		return [
			[
				true,
			],
		];
	}

	#[DataProvider('dataBugWithoutGitHubIssue1')]
	public function testBugWithoutGitHubIssue1(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/bug-without-issue-1.php'], []);
	}

	public function testBug4070(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4070.php'], []);
	}

	public function testBug4070Two(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4070_2.php'], []);
	}

	public function testBug4076(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4076.php'], []);
	}

	public function testBug4535(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4535.php'], []);
	}

	public function testBug4346(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4346.php'], []);
	}

	public function testBug2913(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-2913.php'], []);
	}

	public function testBug4370(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4370.php'], []);
	}

	public function testBug7188(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-7188.php'], [
			[
				'Unreachable statement - code above always terminates.',
				22,
			],
		]);
	}

	public function testBug8620(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8620.php'], []);
	}

	public function testBug4002(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002.php'], []);
	}

	public function testBug4002Two(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002-2.php'], []);
	}

	public function testBug4002Three(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002-3.php'], [
			[
				'Unreachable statement - code above always terminates.',
				13,
			],
		]);
	}

	public function testBug4002Four(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002-4.php'], [
			[
				'Unreachable statement - code above always terminates.',
				9,
			],
		]);
	}

	public function testBug4002Class(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002_class.php'], []);
	}

	public function testBug4002Interface(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002_interface.php'], []);
	}

	public function testBug4002Trait(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-4002_trait.php'], []);
	}

	public function testBug8319(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8319.php'], []);
	}

	public function testBug8966(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8966.php'], [
			[
				'Unreachable statement - code above always terminates.',
				8,
			],
		]);
	}

	public function testBug11179(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-11179.php'], []);
	}

	public function testMultipleUnreachable(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/multiple_unreachable.php'], [
			[
				'Unreachable statement - code above always terminates.',
				14,
			],
		]);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testBug14328(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-14328.php'], [
			[
				'Unreachable statement - code above always terminates.',
				20,
			],
			[
				'Unreachable statement - code above always terminates.',
				26,
			],
			[
				'Unreachable statement - code above always terminates.',
				32,
			],
			[
				'Unreachable statement - code above always terminates.',
				38,
			],
		]);
	}

	public function testBug14369(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-14369.php'], [
			[
				'Unreachable statement - code above always terminates.',
				33,
			],
			[
				'Unreachable statement - code above always terminates.',
				40,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14582(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-14582.php'], []);
	}

	public function testBug11731(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-11731.php'], [
			[
				'Unreachable statement - code above always terminates.',
				9,
			],
			[
				'Unreachable statement - code above always terminates.',
				19,
			],
		]);
	}

	public function testBug15169TreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-15169.php'], [
			[
				'Unreachable statement - code above always terminates.',
				27,
			],
			[
				'Unreachable statement - code above always terminates.',
				36,
			],
			[
				'Unreachable statement - code above always terminates.',
				45,
			],
			[
				'Unreachable statement - code above always terminates.',
				54,
			],
			[
				'Unreachable statement - code above always terminates.',
				66,
			],
			[
				'Unreachable statement - code above always terminates.',
				76,
			],
		]);
	}

	public function testBug15169AnalogousTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-15169b.php'], [
			[
				'Unreachable statement - code above always terminates.',
				40,
			],
			[
				'Unreachable statement - code above always terminates.',
				49,
			],
			[
				'Unreachable statement - code above always terminates.',
				66,
			],
			[
				'Unreachable statement - code above always terminates.',
				75,
			],
			[
				'Unreachable statement - code above always terminates.',
				84,
			],
			[
				'Unreachable statement - code above always terminates.',
				93,
			],
			[
				'Unreachable statement - code above always terminates.',
				102,
			],
			[
				'Unreachable statement - code above always terminates.',
				127,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15169FirstClassCallablesTreatPhpDocTypesAsCertain(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-15169c.php'], [
			[
				'Unreachable statement - code above always terminates.',
				45,
			],
			[
				'Unreachable statement - code above always terminates.',
				55,
			],
			[
				'Unreachable statement - code above always terminates.',
				65,
			],
			[
				'Unreachable statement - code above always terminates.',
				75,
			],
		]);
	}

	public function testDynamicNameAlwaysTerminating(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/dynamic-name-always-terminating.php'], [
			[
				'Unreachable statement - code above always terminates.',
				23,
			],
			[
				'Unreachable statement - code above always terminates.',
				29,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testFirstClassCallableTerminatingVar(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/first-class-callable-terminating-var.php'], [
			[
				'Unreachable statement - code above always terminates.',
				19,
			],
		]);
	}

}
