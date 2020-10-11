<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * @extends \PHPStan\Testing\RuleTestCase<IfConstantConditionRule>
 */
class IfConstantConditionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $treatPhpDocTypesAsCertain;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new IfConstantConditionRule(
			new ConstantConditionRuleHelper(
				new ImpossibleCheckTypeHelper(
					$this->createReflectionProvider(),
					$this->getTypeSpecifier(),
					[],
					$this->treatPhpDocTypesAsCertain
				),
				$this->treatPhpDocTypesAsCertain
			),
			$this->treatPhpDocTypesAsCertain
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	/**
	 * @return DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		return [
			new class implements DynamicFunctionReturnTypeExtension {

				public function isFunctionSupported(FunctionReflection $functionReflection): bool
				{
					return $functionReflection->getName() === 'always_true';
				}

				public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
				{
					return new ConstantBooleanType(true);
				}

			},
		];
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		require_once __DIR__ . '/data/function-definition.php';
		$this->analyse([__DIR__ . '/data/if-condition.php'], [
			[
				'If condition is always true.',
				40,
			],
			[
				'If condition is always false.',
				45,
			],
			[
				'If condition is always true.',
				96,
			],
			[
				'If condition is always true.',
				110,
			],
			[
				'If condition is always true.',
				113,
			],
			[
				'If condition is always true.',
				127,
			],
			[
				'If condition is always true.',
				287,
			],
			[
				'If condition is always false.',
				291,
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/if-condition-not-phpdoc.php'], [
			[
				'If condition is always true.',
				16,
			],
			[
				'If condition is always true.',
				20,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

}
