<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Rules\Constants\OverrideAttributeOnClassConstantCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * Checks enum cases that override a constant from an implemented interface.
 *
 * @implements Rule<Node\Stmt\EnumCase>
 */
#[RegisteredRule(level: 0)]
final class OverridingEnumCaseRule implements Rule
{

	public function __construct(
		#[AutowiredParameter]
		private bool $checkPhpDocMethodSignatures,
		private OverrideAttributeOnClassConstantCheck $overrideAttributeCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return Node\Stmt\EnumCase::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $scope->getClassReflection();
		if ($classReflection === null || !$classReflection->isEnum()) {
			return [];
		}

		$caseName = $node->name->toString();
		$prototype = $this->overrideAttributeCheck->findPrototype($classReflection, $caseName);
		$errors = $this->overrideAttributeCheck->check($scope, $node, $caseName, $prototype);
		if ($prototype === null) {
			return $errors;
		}

		if ($prototype->isFinal()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum case %s::%s overrides final constant %s::%s.',
				$classReflection->getDisplayName(false),
				$caseName,
				$prototype->getDeclaringClass()->getDisplayName(false),
				$prototype->getName(),
			))->identifier('enum.caseOverridesFinal')->nonIgnorable()->build();
		}

		if (!$this->checkPhpDocMethodSignatures) {
			return $errors;
		}

		$prototypeNativeType = $prototype->getNativeType();
		if ($prototypeNativeType !== null) {
			// an enum case is an untyped constant, PHP rejects it even when the type accepts its value
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum case %s::%s is not compatible with native type %s of constant %s::%s.',
				$classReflection->getDisplayName(false),
				$caseName,
				$prototypeNativeType->describe(VerbosityLevel::typeOnly()),
				$prototype->getDeclaringClass()->getDisplayName(false),
				$prototype->getName(),
			))->identifier('enum.caseNativeType')->nonIgnorable()->build();

			return $errors;
		}

		if (
			$prototype->hasPhpDocType()
			&& !$prototype->getValueType()->isSuperTypeOf(new EnumCaseObjectType($classReflection->getName(), $caseName))->yes()
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Enum case %s::%s is not compatible with type %s of constant %s::%s.',
				$classReflection->getDisplayName(false),
				$caseName,
				$prototype->getValueType()->describe(VerbosityLevel::value()),
				$prototype->getDeclaringClass()->getDisplayName(false),
				$prototype->getName(),
			))->identifier('enum.caseType')->build();
		}

		return $errors;
	}

}
