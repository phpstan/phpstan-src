<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<DefinedVariableRule>
 */
class DefinedVariableRuleTest extends RuleTestCase
{

	private bool $cliArgumentsVariablesRegistered;

	private bool $checkMaybeUndefinedVariables;

	protected function getRule(): Rule
	{
		return new DefinedVariableRule(
			$this->cliArgumentsVariablesRegistered,
			$this->checkMaybeUndefinedVariables,
		);
	}

	public static function dataLoopInitialAssignments(): array
	{
		return [
			[
				true,
				false,
				[],
			],
			[
				true,
				true,
				[],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	#[DataProvider('dataLoopInitialAssignments')]
	public function testLoopInitialAssignments(
		bool $polluteScopeWithLoopInitialAssignments,
		bool $checkMaybeUndefinedVariables,
		array $expectedErrors,
	): void
	{
		$this->cliArgumentsVariablesRegistered = false;
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
		$this->analyse([__DIR__ . '/data/loop-initial-assignments.php'], $expectedErrors);
	}

	public function testBug3601(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-3601.php'], []);
	}

	public function testBug1016(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-1016.php'], []);
	}

	public function testBug1016b(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-1016b.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug8142(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-8142.php'], []);
	}

	public function testBug8212(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-8212.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9474(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-9474.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testEnum(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/defined-variables-enum.php'], []);
	}

	public function testBug5326(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-5326.php'], []);
	}

	public function testBug5266(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-5266.php'], []);
	}

	public function testIsStringNarrowsCertainty(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/isstring-certainty.php'], [
			[
				'Variable $a might not be defined.',
				11,
			],
			[
				'Undefined variable: $a',
				19,
			],
		]);
	}

	public function testBug12364(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-12364.php'], [
			[
				'Variable $z might not be defined.',
				20,
			],
			[
				'Variable $z might not be defined.',
				23,
			],
		]);
	}

	public function testDiscussion10252(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/discussion-10252.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug10418(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-10418.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPassByReferenceIntoNotNullable(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/pass-by-reference-into-not-nullable.php'], [
			[
				'Undefined variable: $three',
				32,
			],
		]);
	}

	public function testBug10228(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-10228.php'], []);
	}

	public function testBug9426(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-9426.php'], []);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHooks(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/property-hooks.php'], [
			[
				'Undefined variable: $val',
				16,
			],
			[
				'Undefined variable: $value',
				28,
			],
			[
				'Undefined variable: $val',
				43,
			],
			[
				'Undefined variable: $value',
				51,
			],
		]);
	}

	public function testDynamicAccess(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/dynamic-access.php'], [
			[
				'Undefined variable: $bar',
				15,
			],
			[
				'Undefined variable: $bar',
				18,
			],
			[
				'Undefined variable: $buz',
				18,
			],
			[
				'Undefined variable: $bar',
				38,
			],
			[
				'Undefined variable: $foo',
				41,
			],
			[
				'Undefined variable: $buz',
				44,
			],
			[
				'Undefined variable: $foo',
				45,
			],
			[
				'Undefined variable: $bar',
				46,
			],
			[
				'Undefined variable: $buz',
				49,
			],
			[
				'Variable $foo might not be defined.',
				50,
			],
			[
				'Variable $bar might not be defined.',
				51,
			],
		]);
	}

	public function testBug8719(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-8719.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13353(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-13353.php'], []);
	}

	public function testBug13694(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-13694.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug5191(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-5191.php'], [
			[
				'Variable $pow might not be defined.',
				23,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug10909(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-10909.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug13981(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-13981.php'], [
			[
				'Undefined variable: $baseDir',
				34,
			],
			[
				'Variable $baseDir might not be defined.',
				46,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug7705(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-7705.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug12944(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-12944.php'], []);
	}

	#[DataProvider('dataBug11545')]
	public function testBug11545(bool $polluteScopeWithLoopInitialAssignments): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$errors = [];
		if (!$polluteScopeWithLoopInitialAssignments) {
			$errors[] = [
				'Variable $result might not be defined.',
				24,
			];
		}

		$this->analyse([__DIR__ . '/data/bug-11545.php'], $errors);
	}

	/** @return iterable<array{bool}> */
	public static function dataBug11545(): iterable
	{
		yield [true];
	}

	public function testBug13920(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;

		$this->analyse([__DIR__ . '/data/bug-13920.php'], []);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug6833(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-6833.php'], [
			[
				'Variable $file might not be defined.',
				69,
			],
			[
				'Variable $file might not be defined.',
				70,
			],
			[
				'Variable $file might not be defined.',
				96,
			],
			[
				'Variable $file might not be defined.',
				97,
			],
			[
				'Variable $file might not be defined.',
				134,
			],
			[
				'Variable $file might not be defined.',
				159,
			],
			[
				'Variable $file might not be defined.',
				160,
			],
			[
				'Variable $file might not be defined.',
				172,
			],
			[
				'Variable $file might not be defined.',
				173,
			],
			[
				'Variable $file might not be defined.',
				205,
			],
			[
				'Variable $file might not be defined.',
				206,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug9392(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-9392.php'], []);
	}

	public function testBug14418(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->analyse([__DIR__ . '/data/bug-14418.php'], []);
	}

}
