<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\ClassConst>
 */
final class MissingClassConstantTypehintRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Rules\MissingTypehintCheck $missingTypehintCheck;

	public function __construct(MissingTypehintCheck $missingTypehintCheck)
	{
		$this->missingTypehintCheck = $missingTypehintCheck;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$errors = [];
		foreach ($node->consts as $const) {
			$errors[] = $this->processSingleConstant($scope->getClassReflection(), $const->name->toString());
		}

		return $errors === [] ? [] : array_merge(...$errors);
	}

	/**
	 * @param string $constantName
	 * @return RuleError[]
	 */
	private function processSingleConstant(ClassReflection $classReflection, string $constantName): array
	{
		$constantReflection = $classReflection->getConstant($constantName);
		$constantType = $constantReflection->getValueType();

		$errors = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($constantType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s type has no value type specified in iterable type %s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$iterableTypeDescription
			))->tip(MissingTypehintCheck::TURN_OFF_MISSING_ITERABLE_VALUE_TYPE_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($constantType) as [$name, $genericTypeNames]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s with generic %s does not specify its types: %s',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$name,
				implode(', ', $genericTypeNames)
			))->tip(MissingTypehintCheck::TURN_OFF_NON_GENERIC_CHECK_TIP)->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($constantType) as $callableType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s type has no signature specified for %s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$callableType->describe(VerbosityLevel::typeOnly())
			))->build();
		}

		return $errors;
	}

}
