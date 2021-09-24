<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\ClassConstFetch>
 */
class ClassConstantRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private \PHPStan\Rules\ClassCaseSensitivityCheck $classCaseSensitivityCheck;

	private PhpVersion $phpVersion;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		RuleLevelHelper $ruleLevelHelper,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck,
		PhpVersion $phpVersion
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
		$this->phpVersion = $phpVersion;
	}

	public function getNodeType(): string
	{
		return ClassConstFetch::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		$constantName = $node->name->name;

		$class = $node->class;
		$messages = [];
		if ($class instanceof \PhpParser\Node\Name) {
			$className = (string) $class;
			$lowercasedClassName = strtolower($className);
			if (in_array($lowercasedClassName, ['self', 'static'], true)) {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $className))->build(),
					];
				}

				$classType = $scope->resolveTypeByName($class);
			} elseif ($lowercasedClassName === 'parent') {
				if (!$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $className))->build(),
					];
				}
				$currentClassReflection = $scope->getClassReflection();
				if ($currentClassReflection->getParentClass() === null) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Access to parent::%s but %s does not extend any class.',
							$constantName,
							$currentClassReflection->getDisplayName()
						))->build(),
					];
				}
				$classType = $scope->resolveTypeByName($class);
			} else {
				if (!$this->reflectionProvider->hasClass($className)) {
					if ($scope->isInClassExists($className)) {
						return [];
					}

					if (strtolower($constantName) === 'class') {
						return [
							RuleErrorBuilder::message(sprintf('Class %s not found.', $className))->discoveringSymbolsTip()->build(),
						];
					}

					return [
						RuleErrorBuilder::message(
							sprintf('Access to constant %s on an unknown class %s.', $constantName, $className)
						)->discoveringSymbolsTip()->build(),
					];
				} else {
					$messages = $this->classCaseSensitivityCheck->checkClassNames([new ClassNameNodePair($className, $class)]);
				}

				$classType = $scope->resolveTypeByName($class);
			}

			if (strtolower($constantName) === 'class') {
				return $messages;
			}
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$class,
				sprintf('Access to constant %s on an unknown class %%s.', SprintfHelper::escapeFormatString($constantName)),
				static function (Type $type) use ($constantName): bool {
					return $type->canAccessConstants()->yes() && $type->hasConstant($constantName)->yes();
				},
				true
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return $classTypeResult->getUnknownClassErrors();
			}

			if (strtolower($constantName) === 'class') {
				if (!$this->phpVersion->supportsClassConstantOnExpression()) {
					return [
						RuleErrorBuilder::message('Accessing ::class constant on an expression is supported only on PHP 8.0 and later.')
							->nonIgnorable()
							->build(),
					];
				}

				if ((new StringType())->isSuperTypeOf($classType)->yes()) {
					return [
						RuleErrorBuilder::message('Accessing ::class constant on a dynamic string is not supported in PHP.')
							->nonIgnorable()
							->build(),
					];
				}
			}
		}

		if ((new StringType())->isSuperTypeOf($classType)->yes()) {
			return $messages;
		}

		$typeForDescribe = $classType;
		if ($classType instanceof ThisType) {
			$typeForDescribe = $classType->getStaticObjectType();
		}
		$classType = TypeCombinator::remove($classType, new StringType());

		if (!$classType->canAccessConstants()->yes()) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Cannot access constant %s on %s.',
					$constantName,
					$typeForDescribe->describe(VerbosityLevel::typeOnly())
				))->build(),
			]);
		}

		if (strtolower($constantName) === 'class') {
			return $messages;
		}

		if (!$classType->hasConstant($constantName)->yes()) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to undefined constant %s::%s.',
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					$constantName
				))->build(),
			]);
		}

		$constantReflection = $classType->getConstant($constantName);
		if (!$scope->canAccessConstant($constantReflection)) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to %s constant %s of class %s.',
					$constantReflection->isPrivate() ? 'private' : 'protected',
					$constantName,
					$constantReflection->getDeclaringClass()->getDisplayName()
				))->build(),
			]);
		}

		return $messages;
	}

}
