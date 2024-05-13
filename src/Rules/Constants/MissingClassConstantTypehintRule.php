<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function implode;
use function sprintf;

/**
 * @implements Rule<Node\Stmt\ClassConst>
 */
final class MissingClassConstantTypehintRule implements Rule
{

	public function __construct(private MissingTypehintCheck $missingTypehintCheck)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassConst::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$scope->isInClass()) {
			throw new ShouldNotHappenException();
		}

		$errors = [];
		foreach ($node->consts as $const) {
			$constantName = $const->name->toString();
			$errors = array_merge($errors, $this->processSingleConstant($scope->getClassReflection(), $constantName));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleConstant(ClassReflection $classReflection, string $constantName): array
	{
		$constantReflection = $classReflection->getConstant($constantName);
		$constantType = $constantReflection->getPhpDocType();
		if ($constantType === null) {
			return [];
		}

		$errors = [];
		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($constantType) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s type has no value type specified in iterable type %s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($constantType) as [$name, $genericTypeNames]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s with generic %s does not specify its types: %s',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$name,
				implode(', ', $genericTypeNames),
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($constantType) as $callableType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Constant %s::%s type has no signature specified for %s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		return $errors;
	}

}
