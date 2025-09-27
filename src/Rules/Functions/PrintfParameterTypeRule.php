<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\BooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function count;
use function sprintf;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class PrintfParameterTypeRule implements Rule
{

	private const FORMAT_ARGUMENT_POSITIONS = [
		'printf' => 0,
		'sprintf' => 0,
		'fprintf' => 1,
	];
	private const MINIMUM_NUMBER_OF_ARGUMENTS = [
		'printf' => 1,
		'sprintf' => 1,
		'fprintf' => 2,
	];

	public function __construct(
		private PrintfHelper $printfHelper,
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private bool $checkStrictPrintfPlaceholderTypes,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Expr\FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$name = $functionReflection->getName();
		if (!array_key_exists($name, self::FORMAT_ARGUMENT_POSITIONS)) {
			return [];
		}

		$formatArgumentPosition = self::FORMAT_ARGUMENT_POSITIONS[$name];

		$args = $node->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return [];
			}
		}
		$argsCount = count($args);
		if ($argsCount < self::MINIMUM_NUMBER_OF_ARGUMENTS[$name]) {
			return []; // caught by CallToFunctionParametersRule
		}

		$formatArgType = $scope->getType($args[$formatArgumentPosition]->value);
		$formatArgTypeStrings = $formatArgType->getConstantStrings();

		// Let's start simple for now.
		if (count($formatArgTypeStrings) !== 1) {
			return [];
		}

		$formatString = $formatArgTypeStrings[0];
		$format = $formatString->getValue();
		$placeholderMap = $this->printfHelper->getPrintfPlaceholders($format);
		$errors = [];
		$typeAllowedByCallToFunctionParametersRule = TypeCombinator::union(
			new StringAlwaysAcceptingObjectWithToStringType(),
			new IntegerType(),
			new FloatType(),
			new BooleanType(),
			new NullType(),
		);
		// Type on the left can go to the type on the right, but not vice versa.
		$allowedTypeNameMap = $this->checkStrictPrintfPlaceholderTypes
			? [
				'strict-int' => 'int',
				'int' => 'int',
				'float' => 'float',
				'string' => 'string',
				'mixed' => 'string',
			]
			: [
				'strict-int' => 'int',
				'int' => 'castable to int',
				'float' => 'castable to float',
				// These are here just for completeness. They won't be used because, these types are already enforced by
				// CallToFunctionParametersRule.
				'string' => 'castable to string',
				'mixed' => 'castable to string',
			];

		for ($i = $formatArgumentPosition + 1, $j = 0; $i < $argsCount; $i++, $j++) {
			// Some arguments may be skipped entirely.
			foreach ($placeholderMap[$j] ?? [] as $placeholder) {
				$argType = $this->ruleLevelHelper->findTypeToCheck(
					$scope,
					$args[$i]->value,
					'',
					fn (Type $t) => $placeholder->doesArgumentTypeMatchPlaceholder($t, $this->checkStrictPrintfPlaceholderTypes),
				)->getType();

				if ($argType instanceof ErrorType || $placeholder->doesArgumentTypeMatchPlaceholder($argType, $this->checkStrictPrintfPlaceholderTypes)) {
					continue;
				}

				// This is already reported by CallToFunctionParametersRule
				if (
					!$this->ruleLevelHelper->accepts(
						$typeAllowedByCallToFunctionParametersRule,
						$argType,
						$scope->isDeclareStrictTypes(),
					)->result
				) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Parameter #%d of function %s is expected to be %s by placeholder #%d (%s), %s given.',
						$i + 1,
						$name,
						$allowedTypeNameMap[$placeholder->acceptingType],
						$placeholder->placeholderNumber,
						$placeholder->label,
						$argType->describe(VerbosityLevel::typeOnly()),
					),
				)->identifier('argument.type')->build();
			}
		}

		return $errors;
	}

}
