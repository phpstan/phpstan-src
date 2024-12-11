<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<DefinedVariableRule>
 */
class DefinedVariableRuleTest extends RuleTestCase
{

	private bool $cliArgumentsVariablesRegistered;

	private bool $checkMaybeUndefinedVariables;

	private bool $polluteScopeWithLoopInitialAssignments;

	private bool $polluteScopeWithAlwaysIterableForeach;

	protected function getRule(): Rule
	{
		return new DefinedVariableRule(
			$this->cliArgumentsVariablesRegistered,
			$this->checkMaybeUndefinedVariables,
		);
	}

	protected function shouldPolluteScopeWithLoopInitialAssignments(): bool
	{
		return $this->polluteScopeWithLoopInitialAssignments;
	}

	protected function shouldPolluteScopeWithAlwaysIterableForeach(): bool
	{
		return $this->polluteScopeWithAlwaysIterableForeach;
	}

	public function testDefinedVariables(): void
	{
		require_once __DIR__ . '/data/defined-variables-definition.php';
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables.php'], [
			[
				'Undefined variable: $definedLater',
				5,
			],
			[
				'Variable $definedInIfOnly might not be defined.',
				10,
			],
			[
				'Variable $definedInCases might not be defined.',
				21,
			],
			[
				'Undefined variable: $fooParameterBeforeDeclaration',
				29,
			],
			[
				'Undefined variable: $parseStrParameter',
				34,
			],
			[
				'Undefined variable: $parseStrParameter',
				36,
			],
			[
				'Undefined variable: $foo',
				39,
			],
			[
				'Undefined variable: $willBeUnset',
				44,
			],
			[
				'Undefined variable: $mustAlreadyExistWhenDividing',
				50,
			],
			[
				'Undefined variable: $arrayDoesNotExist',
				57,
			],
			[
				'Undefined variable: $undefinedVariable',
				59,
			],
			[
				'Undefined variable: $this',
				96,
			],
			[
				'Undefined variable: $this',
				99,
			],
			[
				'Undefined variable: $variableInEmpty',
				145,
			],
			[
				'Undefined variable: $negatedVariableInEmpty',
				152,
			],
			[
				'Undefined variable: $variableInEmpty',
				155,
			],
			[
				'Undefined variable: $negatedVariableInEmpty',
				156,
			],
			[
				'Undefined variable: $variableInIsset',
				159,
			],
			[
				'Undefined variable: $anotherVariableInIsset',
				159,
			],
			[
				'Undefined variable: $variableInIsset',
				161,
			],
			[
				'Undefined variable: $anotherVariableInIsset',
				161,
			],
			[
				'Undefined variable: $http_response_header',
				185,
			],
			[
				'Undefined variable: $http_response_header',
				191,
			],
			[
				'Undefined variable: $assignedInKey',
				203,
			],
			[
				'Undefined variable: $assignedInKey',
				204,
			],
			[
				'Variable $forI might not be defined.',
				250,
			],
			[
				'Variable $forJ might not be defined.',
				251,
			],
			[
				'Variable $variableAvailableInAllCatches might not be defined.',
				266,
			],
			[
				'Variable $variableDefinedOnlyInOneCatch might not be defined.',
				267,
			],
			[
				'Undefined variable: $variableInBitwiseAndAssign',
				277,
			],
			[
				'Variable $mightBeUndefinedInDoWhile might not be defined.',
				282,
			],
			[
				'Undefined variable: $variableInSecondCase',
				290,
			],
			[
				'Variable $variableInSecondCase might not be defined.',
				293,
			],
			[
				'Undefined variable: $variableAssignedInSecondCase',
				300,
			],
			[
				'Variable $variableInFallthroughCase might not be defined.',
				302,
			],
			[
				'Variable $variableFromDefaultFirst might not be defined.',
				312,
			],
			[
				'Undefined variable: $undefinedVariableInForeach',
				315,
			],
			[
				'Variable $anotherForLoopVariable might not be defined.',
				328,
			],
			[
				'Variable $maybeDefinedInTernary might not be defined.',
				351,
			],
			[
				'Variable $anotherMaybeDefinedInTernary might not be defined.',
				354,
			],
			[
				'Variable $whileVariableUsedAndThenDefined might not be defined.',
				356,
			],
			[
				'Variable $forVariableUsedAndThenDefined might not be defined.',
				360,
			],
			[
				'Variable $unknownVariablePassedToReset might not be defined.',
				368,
			],
			[
				'Variable $unknownVariablePassedToReset might not be defined.',
				369,
			],
			[
				'Variable $variableInAssign might not be defined.',
				384,
			],
			[
				'Variable $undefinedArrayIndex might not be defined.',
				409,
			],
			[
				'Variable $anotherUndefinedArrayIndex might not be defined.',
				409,
			],
			[
				'Variable $str might not be defined.',
				423,
			],
			[
				'Variable $str might not be defined.',
				428,
			],
		]);
	}

	public function testDefinedVariablesInClosures(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-closures.php'], [
			[
				'Undefined variable: $this',
				14,
			],
		]);
	}

	public function testDefinedVariablesInShortArrayDestructuringSyntax(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-array-destructuring-short-syntax.php'], [
			[
				'Undefined variable: $f',
				11,
			],
			[
				'Undefined variable: $f',
				14,
			],
			[
				'Undefined variable: $var3',
				32,
			],
		]);
	}

	public function testCliArgumentsVariablesNotRegistered(): void
	{
		$this->cliArgumentsVariablesRegistered = false;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/cli-arguments-variables.php'], [
			[
				'Variable $argc might not be defined.',
				3,
			],
			[
				'Undefined variable: $argc',
				5,
			],
		]);
	}

	public function testCliArgumentsVariablesRegistered(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/cli-arguments-variables.php'], [
			[
				'Undefined variable: $argc',
				5,
			],
		]);
	}

	public function dataLoopInitialAssignments(): array
	{
		return [
			[
				false,
				false,
				[],
			],
			[
				false,
				true,
				[
					[
						'Variable $i might not be defined.',
						7,
					],
					[
						'Variable $whileVar might not be defined.',
						13,
					],
				],
			],
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
	 * @dataProvider dataLoopInitialAssignments
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testLoopInitialAssignments(
		bool $polluteScopeWithLoopInitialAssignments,
		bool $checkMaybeUndefinedVariables,
		array $expectedErrors,
	): void
	{
		$this->cliArgumentsVariablesRegistered = false;
		$this->polluteScopeWithLoopInitialAssignments = $polluteScopeWithLoopInitialAssignments;
		$this->checkMaybeUndefinedVariables = $checkMaybeUndefinedVariables;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/loop-initial-assignments.php'], $expectedErrors);
	}

	public function testDefineVariablesInClass(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/define-variables-class.php'], []);
	}

	public function testDeadBranches(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/dead-branches.php'], [
			[
				'Undefined variable: $test',
				21,
			],
			[
				'Undefined variable: $test',
				33,
			],
			[
				'Undefined variable: $test',
				55,
			],
			[
				'Undefined variable: $test',
				66,
			],
			[
				'Undefined variable: $test',
				94,
			],
		]);
	}

	public function testForeach(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/foreach.php'], [
			[
				'Variable $val might not be defined.',
				9,
			],
			[
				'Variable $test might not be defined.',
				10,
			],
			[
				'Undefined variable: $val',
				46,
			],
			[
				'Undefined variable: $test',
				47,
			],
			[
				'Variable $val might not be defined.',
				62,
			],
			[
				'Variable $test might not be defined.',
				63,
			],
			[
				'Undefined variable: $val',
				200,
			],
			[
				'Undefined variable: $test',
				201,
			],
			[
				'Undefined variable: $val',
				216,
			],
			[
				'Undefined variable: $test',
				217,
			],
			[
				'Variable $val might not be defined.',
				246,
			],
			[
				'Variable $test might not be defined.',
				247,
			],
		]);
	}

	public function dataForeachPolluteScopeWithAlwaysIterableForeach(): array
	{
		return [
			[
				true,
				[
					[
						'Undefined variable: $key',
						8,
					],
					[
						'Undefined variable: $val',
						9,
					],
					[
						'Undefined variable: $test',
						10,
					],
					[
						'Variable $test might not be defined.',
						34,
					],
					[
						'Variable $key might not be defined.',
						47,
					],
					[
						'Variable $test might not be defined.',
						48,
					],
					[
						'Variable $key might not be defined.',
						61,
					],
					[
						'Variable $test might not be defined.',
						62,
					],
				],
			],
			[
				false,
				[
					[
						'Undefined variable: $key',
						8,
					],
					[
						'Undefined variable: $val',
						9,
					],
					[
						'Undefined variable: $test',
						10,
					],
					[
						'Variable $key might not be defined.',
						19,
					],
					[
						'Variable $val might not be defined.',
						20,
					],
					[
						'Variable $test might not be defined.',
						21,
					],
					[
						'Variable $key might not be defined.',
						32,
					],
					[
						'Variable $val might not be defined.',
						33,
					],
					[
						'Variable $test might not be defined.',
						34,
					],
					[
						'Variable $key might not be defined.',
						47,
					],
					[
						'Variable $test might not be defined.',
						48,
					],
					[
						'Variable $key might not be defined.',
						61,
					],
					[
						'Variable $test might not be defined.',
						62,
					],
					[
						'Variable $key might not be defined.',
						75,
					],
					[
						'Variable $test might not be defined.',
						76,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataForeachPolluteScopeWithAlwaysIterableForeach
	 *
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testForeachPolluteScopeWithAlwaysIterableForeach(bool $polluteScopeWithAlwaysIterableForeach, array $errors): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = $polluteScopeWithAlwaysIterableForeach;
		$this->analyse([__DIR__ . '/data/foreach-always-iterable.php'], $errors);
	}

	public function testBooleanOperatorsTruthyFalsey(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/boolean-op-truthy-falsey.php'], [
			[
				'Variable $matches might not be defined.',
				9,
			],
			[
				'Variable $matches might not be defined.',
				15,
			],
		]);
	}

	public function testArrowFunctions(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-arrow-functions.php'], [
			[
				'Undefined variable: $a',
				10,
			],
			[
				'Undefined variable: $this',
				19,
			],
		]);
	}

	public function testCoalesceAssign(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-coalesce-assign.php'], [
			[
				'Undefined variable: $b',
				16,
			],
		]);
	}

	public function testBug2748(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-2748.php'], [
			[
				'Undefined variable: $foo',
				10,
			],
			[
				'Undefined variable: $foo',
				15,
			],
		]);
	}

	public function testGlobalVariables(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/global-variables.php'], []);
	}

	public function testRootScopeMaybeDefined(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = false;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/root-scope-maybe.php'], []);
	}

	public function testRootScopeMaybeDefinedCheck(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/root-scope-maybe.php'], [
			[
				'Variable $maybe might not be defined.',
				3,
			],
			[
				'Variable $this might not be defined.',
				5,
			],
		]);
	}

	public function testFormerThisVariableRule(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/this.php'], [
			[
				'Undefined variable: $this',
				16,
			],
			[
				'Undefined variable: $this',
				20,
			],
			[
				'Undefined variable: $this',
				26,
			],
			[
				'Undefined variable: $this',
				38,
			],
		]);
	}

	public function testClosureUse(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-anonymous-function-use.php'], [
			[
				'Variable $bar might not be defined.',
				5,
			],
			[
				'Variable $wrongErrorHandler might not be defined.',
				22,
			],
			[
				'Variable $onlyInIf might not be defined.',
				23,
			],
			[
				'Variable $forI might not be defined.',
				24,
			],
			[
				'Variable $forJ might not be defined.',
				25,
			],
			[
				'Variable $anotherVariableFromForCond might not be defined.',
				26,
			],
		]);
	}

	public function testNullsafeIsset(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/variable-nullsafe-isset.php'], []);
	}

	public function testBug1306(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-1306.php'], []);
	}

	public function testBug3515(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-3515.php'], [
			[
				'Undefined variable: $anArray',
				19,
			],
			[
				'Undefined variable: $anArray',
				20,
			],
		]);
	}

	public function testBug4412(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-4412.php'], [
			[
				'Undefined variable: $a',
				17,
			],
		]);
	}

	public function testBug3283(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-3283.php'], []);
	}

	public function testFirstClassCallables(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/first-class-callables.php'], [
			[
				'Undefined variable: $foo',
				10,
			],
			[
				'Undefined variable: $foo',
				11,
			],
			[
				'Undefined variable: $foo',
				29,
			],
			[
				'Undefined variable: $foo',
				30,
			],
			[
				'Undefined variable: $foo',
				48,
			],
			[
				'Undefined variable: $foo',
				49,
			],
		]);
	}

	public function testBug6112(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-6112.php'], []);
	}

	public function testBug3601(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-3601.php'], []);
	}

	public function testBug1016(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-1016.php'], []);
	}

	public function testBug1016b(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-1016b.php'], []);
	}

	public function testBug8142(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-8142.php'], []);
	}

	public function testBug5401(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-5401.php'], []);
	}

	public function testBug8212(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-8212.php'], []);
	}

	public function testBug4173(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-4173.php'], [
			[
				'Variable $value might not be defined.', // could be fixed
				30,
			],
		]);
	}

	public function testBug5805(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-5805.php'], []);
	}

	public function testBug8467c(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = false;
		$this->analyse([__DIR__ . '/data/bug-8467c.php'], [
			[
				'Variable $v might not be defined.',
				16,
			],
			[
				'Variable $v might not be defined.',
				18,
			],
		]);
	}

	public function testBug393(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = false;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-393.php'], []);
	}

	public function testBug9474(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-9474.php'], []);
	}

	public function testEnum(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/defined-variables-enum.php'], []);
	}

	public function testBug5326(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-5326.php'], []);
	}

	public function testBug5266(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-5266.php'], []);
	}

	public function testIsStringNarrowsCertainty(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
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

	public function testDiscussion10252(): void
	{
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/discussion-10252.php'], []);
	}

	public function testBug10418(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-10418.php'], []);
	}

	public function testPassByReferenceIntoNotNullable(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
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
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
		$this->analyse([__DIR__ . '/data/bug-10228.php'], []);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->cliArgumentsVariablesRegistered = true;
		$this->polluteScopeWithLoopInitialAssignments = true;
		$this->checkMaybeUndefinedVariables = true;
		$this->polluteScopeWithAlwaysIterableForeach = true;
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

}
