<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Php\ArrayColumnHelper;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;

/**
 * Reports `array_column()` calls reading a property that does not exist on the
 * objects contained in the source array.
 *
 * @implements Rule<FuncCall>
 */
final class ArrayColumnRule implements Rule
{

	public function __construct(
		private readonly ReflectionProvider $reflectionProvider,
		private readonly ArrayColumnHelper $arrayColumnHelper,
		private readonly bool $treatPhpDocTypesAsCertain,
		private readonly bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
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
		if ($functionReflection->getName() !== 'array_column') {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);

		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		if (count($args) < 2) {
			return [];
		}

		$arrayArg = $args[0]->value;
		$valueType = $scope->getType($arrayArg)->getIterableValueType();
		$nativeValueType = $scope->getNativeType($arrayArg)->getIterableValueType();

		$errors = [];
		foreach ($this->checkColumn($args[1]->value, $valueType, $nativeValueType, '#2 $column_key', $scope) as $error) {
			$errors[] = $error;
		}

		if (count($args) >= 3) {
			foreach ($this->checkColumn($args[2]->value, $valueType, $nativeValueType, '#3 $index_key', $scope) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkColumn(Node\Expr $columnExpr, Type $valueType, Type $nativeValueType, string $parameter, Scope $scope): array
	{
		$checkedValueType = $this->treatPhpDocTypesAsCertain ? $valueType : $nativeValueType;

		$columnType = $scope->getType($columnExpr);
		$missingProperties = $this->arrayColumnHelper->findMissingObjectProperties($checkedValueType, $columnType);
		if ($missingProperties === []) {
			return [];
		}

		$nativeMissingPropertyNames = [];
		foreach ($this->arrayColumnHelper->findMissingObjectProperties($nativeValueType, $columnType) as $nativeMissingProperty) {
			$nativeMissingPropertyNames[$nativeMissingProperty->getValue()] = true;
		}

		$errors = [];
		foreach ($missingProperties as $propertyNameType) {
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Parameter %s of function array_column expects a valid property name, %s given, but %s does not have such property.',
				$parameter,
				$propertyNameType->describe(VerbosityLevel::value()),
				$checkedValueType->describe(VerbosityLevel::typeOnly()),
			))->identifier('arrayColumn.property');

			if (
				$this->treatPhpDocTypesAsCertain
				&& $this->treatPhpDocTypesAsCertainTip
				&& !isset($nativeMissingPropertyNames[$propertyNameType->getValue()])
			) {
				$errorBuilder->treatPhpDocTypesAsCertainTip();
			}

			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

}
