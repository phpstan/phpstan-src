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
			[
				false,
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

	public function testBug11992(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-11992.php'], []);
	}

	public function testBug7531(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-7531.php'], [
			[
				'Unreachable statement - code above always terminates.',
				22,
			],
		]);
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

	#[RequiresPhp('>= 8.1')]
	public function testBug11909(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-11909.php'], [
			[
				'Unreachable statement - code above always terminates.',
				10,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13232a(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13232a.php'], [
			[
				'Unreachable statement - code above always terminates.',
				10,
			],
			[
				'Unreachable statement - code above always terminates.',
				17,
			],
			[
				'Unreachable statement - code above always terminates.',
				23,
			],
			[
				'Unreachable statement - code above always terminates.',
				32,
			],
			[
				'Unreachable statement - code above always terminates.',
				38,
			],
			[
				'Unreachable statement - code above always terminates.',
				44,
			],
			[
				'Unreachable statement - code above always terminates.',
				52,
			],
			[
				'Unreachable statement - code above always terminates.',
				61,
			],
			[
				'Unreachable statement - code above always terminates.',
				70,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13232b(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13232b.php'], [
			[
				'Unreachable statement - code above always terminates.',
				19,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13232c(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13232c.php'], [
			[
				'Unreachable statement - code above always terminates.',
				12,
			],
			[
				'Unreachable statement - code above always terminates.',
				20,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13232d(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13232d.php'], [
			[
				'Unreachable statement - code above always terminates.',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug13288(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13288.php'], []);
	}

	public function testBug13311(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13311.php'], []);
	}

	public function testBug13307(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13307.php'], []);
	}

	public function testBug13331(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-13331.php'], []);
	}

}
