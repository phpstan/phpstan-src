<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Comparison\ConstantConditionInTraitHelper;
use PHPStan\Rules\Comparison\ConstantConditionInTraitRule;
use PHPStan\Rules\Comparison\PossiblyImpureTipHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CompositeRule>
 */
class ImpossibleInstanceOfRuleWithUncertainPhpDocTypesTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(
			self::createReflectionProvider(),
			checkNullables: true,
			checkThisOnly: false,
			checkUnionTypes: true,
			checkExplicitMixed: false,
			checkImplicitMixed: false,
			checkBenevolentUnionTypes: false,
			discoveringSymbolsTip: true,
		);

		// @phpstan-ignore argument.type
		return new CompositeRule([
			new ImpossibleInstanceOfRule(
				$ruleLevelHelper,
				new PossiblyImpureTipHelper(true),
				self::getContainer()->getByType(ConstantConditionInTraitHelper::class),
				treatPhpDocTypesAsCertain: false,
				reportAlwaysTrueInLastCondition: false,
				treatPhpDocTypesAsCertainTip: true,
			),
			new ConstantConditionInTraitRule(),
		]);
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
		return false;
	}

	public function testDoNotReportTypesFromPhpDocs(): void
	{
		$this->analyse([__DIR__ . '/data/impossible-instanceof-not-phpdoc.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				12,
			],
			[
				'Instanceof between stdClass and Exception will always evaluate to false.',
				15,
			],
			[
				'Instanceof between DateTimeInterface and DateTimeInterface will always evaluate to true.',
				27,
			],
			[
				'Instanceof between DateTimeInterface and ImpossibleInstanceofNotPhpDoc\SomeFinalClass will always evaluate to false.',
				30,
			],
		]);
	}

	public function testBug5333(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5333.php'], []);
	}

	public function testIfBranchesDoNotReportPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-if-branches-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				26,
				'Remove remaining cases below this one and this error will disappear too.',
			],
			[
				'Instanceof between UnreachableIfBranchesNotPhpDoc\Foo and UnreachableIfBranchesNotPhpDoc\Foo will always evaluate to true.',
				36,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testTernaryElseDoNotReportPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/../Comparison/data/unreachable-ternary-else-branch-not-phpdoc.php'], [
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				16,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				17,
			],
			[
				'Instanceof between UnreachableTernaryElseBranchNotPhpDoc\Foo and UnreachableTernaryElseBranchNotPhpDoc\Foo will always evaluate to true.',
				20,
			],
		]);
	}

	public function testBug4689(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4689.php'], []);
	}

	public function testBug13469(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13469.php'], [
			[
				sprintf('Instanceof between Bug13469\Foo and Stringable will always evaluate to %s.', PHP_VERSION_ID >= 80000 ? 'true' : 'false'),
				23,
			],
		]);
	}

	public function testBug5271(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-5271.php'], []);
	}

}
