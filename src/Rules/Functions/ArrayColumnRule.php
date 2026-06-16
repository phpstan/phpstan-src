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

		// array_column() reads object properties (never ArrayAccess offsets), so
		// only check when the elements are definitely objects. Array elements use
		// offset access, scalars never have the member - leave those to other rules.
		if (!$checkedValueType->isObject()->yes()) {
			return [];
		}

		$columnType = $scope->getType($columnExpr);
		$propertyNames = $columnType->getConstantStrings();
		if ($propertyNames === []) {
			return [];
		}

		$errors = [];
		foreach ($propertyNames as $propertyNameType) {
			$propertyName = $propertyNameType->getValue();
			if (!$this->isPropertyMissing($checkedValueType, $propertyName)) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Parameter %s of function array_column expects a valid property name, %s given, but %s does not have such property.',
				$parameter,
				$propertyNameType->describe(VerbosityLevel::value()),
				$checkedValueType->describe(VerbosityLevel::typeOnly()),
			))->identifier('arrayColumn.property');

			if ($this->treatPhpDocTypesAsCertain && $this->treatPhpDocTypesAsCertainTip) {
				if (!$nativeValueType->isObject()->yes() || !$this->isPropertyMissing($nativeValueType, $propertyName)) {
					$errorBuilder->treatPhpDocTypesAsCertainTip();
				}
			}

			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

	private function isPropertyMissing(Type $valueType, string $propertyName): bool
	{
		$classReflections = $valueType->getObjectClassReflections();
		if ($classReflections === []) {
			return false;
		}

		foreach ($classReflections as $classReflection) {
			if ($classReflection->isEnum()) {
				return false;
			}
			if ($classReflection->hasInstanceProperty($propertyName)) {
				return false;
			}
			if ($classReflection->allowsDynamicProperties()) {
				return false;
			}
			if ($classReflection->hasNativeMethod('__isset') && $classReflection->hasNativeMethod('__get')) {
				return false;
			}
		}

		return true;
	}

}
