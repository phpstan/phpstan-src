<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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

	public function dataBugWithoutGitHubIssue1(): array
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

	/**
	 * @dataProvider dataBugWithoutGitHubIssue1
	 */
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

}
