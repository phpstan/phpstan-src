<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * @extends RuleTestCase<UnreachableStatementRule>
 */
class UnreachableStatementRuleWithUncertainPhpDocTypesTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new UnreachableStatementRule();
	}

	public static function getAdditionalConfigFiles(): array
	{
		return array_merge(
			parent::getAdditionalConfigFiles(),
			[
				__DIR__ . '/../uncertain-phpdoc-types.neon',
			],
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public static function dataBugWithoutGitHubIssue1(): array
	{
		return [
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

	#[RequiresPhp('>= 8.1.0')]
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

	#[RequiresPhp('>= 8.1.0')]
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

	#[RequiresPhp('>= 8.1.0')]
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

	#[RequiresPhp('>= 8.1.0')]
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

	#[RequiresPhp('>= 8.1.0')]
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

	#[RequiresPhp('>= 8.1.0')]
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

	public function testBug15169(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-15169.php'], []);
	}

	public function testBug15169Analogous(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-15169b.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15169FirstClassCallables(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-15169c.php'], []);
	}

}
