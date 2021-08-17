<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<Node\Stmt\ClassConst>
 */
class IncompatibleClassConstantPhpDocTypeRule implements Rule
{

	private \PHPStan\Rules\Generics\GenericObjectTypeCheck $genericObjectTypeCheck;

	private UnresolvableTypeHelper $unresolvableTypeHelper;

	public function __construct(
		GenericObjectTypeCheck $genericObjectTypeCheck,
		UnresolvableTypeHelper $unresolvableTypeHelper
	)
	{
		$this->genericObjectTypeCheck = $genericObjectTypeCheck;
		$this->unresolvableTypeHelper = $unresolvableTypeHelper;
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
			$constantName = $const->name->toString();
			$errors = array_merge($errors, $this->processSingleConstant($scope->getClassReflection(), $constantName));
		}

		return $errors;
	}

	/**
	 * @param string $constantName
	 * @return RuleError[]
	 */
	private function processSingleConstant(ClassReflection $classReflection, string $constantName): array
	{
		$constantReflection = $classReflection->getConstant($constantName);
		if (!$constantReflection instanceof ClassConstantReflection) {
			return [];
		}

		if (!$constantReflection->hasPhpDocType()) {
			return [];
		}

		$phpDocType = $constantReflection->getValueType();

		$errors = [];
		if (
			$this->unresolvableTypeHelper->containsUnresolvableType($phpDocType)
		) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @var for constant %s::%s contains unresolvable type.',
				$constantReflection->getDeclaringClass()->getName(),
				$constantName
			))->build();
		} else {
			$nativeType = ConstantTypeHelper::getTypeFromValue($constantReflection->getValue());
			$isSuperType = $phpDocType->isSuperTypeOf($nativeType);
			$verbosity = VerbosityLevel::getRecommendedLevelByType($phpDocType, $nativeType);
			if ($isSuperType->no()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var for constant %s::%s with type %s is incompatible with value %s.',
					$constantReflection->getDeclaringClass()->getDisplayName(),
					$constantName,
					$phpDocType->describe($verbosity),
					$nativeType->describe(VerbosityLevel::value())
				))->build();

			} elseif ($isSuperType->maybe()) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @var for constant %s::%s with type %s is not subtype of value %s.',
					$constantReflection->getDeclaringClass()->getDisplayName(),
					$constantName,
					$phpDocType->describe($verbosity),
					$nativeType->describe(VerbosityLevel::value())
				))->build();
			}
		}

		return array_merge($errors, $this->genericObjectTypeCheck->check(
			$phpDocType,
			sprintf(
				'PHPDoc tag @var for constant %s::%s contains generic type %%s but class %%s is not generic.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName
			),
			sprintf(
				'Generic type %%s in PHPDoc tag @var for constant %s::%s does not specify all template types of class %%s: %%s',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName
			),
			sprintf(
				'Generic type %%s in PHPDoc tag @var for constant %s::%s specifies %%d template types, but class %%s supports only %%d: %%s',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName
			),
			sprintf(
				'Type %%s in generic type %%s in PHPDoc tag @var for constant %s::%s is not subtype of template type %%s of class %%s.',
				$constantReflection->getDeclaringClass()->getDisplayName(),
				$constantName
			)
		));
	}

}
