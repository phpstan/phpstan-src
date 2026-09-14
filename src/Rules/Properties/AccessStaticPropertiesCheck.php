<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\ClosureBindScopeResolver;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

#[AutowiredService]
final class AccessStaticPropertiesCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private RuleLevelHelper $ruleLevelHelper,
		private ClassNameCheck $classCheck,
		private PhpVersion $phpVersion,
		private ClosureBindScopeResolver $closureBindScopeResolver,
		#[AutowiredParameter(ref: '%tips.discoveringSymbols%')]
		private bool $discoveringSymbolsTip,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(StaticPropertyFetch $node, Scope $scope, bool $write): array
	{
		if ($node->name instanceof Node\VarLikeIdentifier) {
			$names = [$node->name->name];
		} else {
			$names = array_map(static fn (ConstantStringType $type): string => $type->getValue(), $scope->getType($node->name)->getConstantStrings());
		}

		$errors = [];
		foreach ($names as $name) {
			$errors = array_merge($errors, $this->processSingleProperty($scope, $node, $name, $write));
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function processSingleProperty(Scope $scope, StaticPropertyFetch $node, string $name, bool $write): array
	{
		$messages = [];
		if ($node->class instanceof Name) {
			$class = (string) $node->class;
			$lowercasedClass = strtolower($class);
			$bindScopeClassReflection = $this->closureBindScopeResolver->resolveScopeClass($scope, $node->class);
			if (in_array($lowercasedClass, ['self', 'static'], true)) {
				if ($bindScopeClassReflection === null && !$scope->isInClass()) {
					return [
						RuleErrorBuilder::message(sprintf(
							'Accessing %s::$%s outside of class scope.',
							$class,
							$name,
						))
							->line($node->name->getStartLine())
							->identifier(sprintf('outOfClass.%s', $lowercasedClass))
							->build(),
					];
				}
				$classType = $scope->resolveTypeByName($node->class);
			} elseif ($lowercasedClass === 'parent') {
				if ($bindScopeClassReflection !== null) {
					if ($bindScopeClassReflection->getParentClass() === null) {
						return [
							RuleErrorBuilder::message(sprintf(
								'Accessing parent::$%s but %s does not extend any class.',
								$name,
								$bindScopeClassReflection->getDisplayName(),
							))
								->line($node->name->getStartLine())
								->identifier('class.noParent')
								->build(),
						];
					}
				} else {
					if (!$scope->isInClass()) {
						return [
							RuleErrorBuilder::message(sprintf(
								'Accessing %s::$%s outside of class scope.',
								$class,
								$name,
							))
								->line($node->name->getStartLine())
								->identifier('outOfClass.parent')
								->build(),
						];
					}
					if ($scope->getClassReflection()->getParentClass() === null) {
						return [
							RuleErrorBuilder::message(sprintf(
								'%s::%s() accesses parent::$%s but %s does not extend any class.',
								$scope->getClassReflection()->getDisplayName(),
								$scope->getFunctionName(),
								$name,
								$scope->getClassReflection()->getDisplayName(),
							))
								->line($node->name->getStartLine())
								->identifier('class.noParent')
								->build(),
						];
					}
				}

				$classType = $scope->resolveTypeByName($node->class);
			} else {
				if (!$this->reflectionProvider->hasClass($class)) {
					if ($scope->isInClassExists($class)) {
						return [];
					}

					$errorBuilder = RuleErrorBuilder::message(sprintf(
						'Access to static property $%s on an unknown class %s.',
						$name,
						$class,
					))
						->line($node->name->getStartLine())
						->identifier('class.notFound');

					if ($this->discoveringSymbolsTip) {
						$errorBuilder->discoveringSymbolsTip();
					}

					return [
						$errorBuilder->build(),
					];
				}

				$locationData = [];
				$locationClassReflection = $this->reflectionProvider->getClass($class);
				if ($locationClassReflection->hasStaticProperty($name)) {
					$locationData['property'] = $locationClassReflection->getStaticProperty($name);
				}

				$messages = $this->classCheck->checkClassNames(
					$scope,
					[new ClassNameNodePair($class, $node->class)],
					ClassNameUsageLocation::from(ClassNameUsageLocation::STATIC_PROPERTY_ACCESS, $locationData),
				);

				$classType = $scope->resolveTypeByName($node->class);
			}
		} else {
			$classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $node->class),
				sprintf('Access to static property $%s on an unknown class %%s.', SprintfHelper::escapeFormatString($name)),
				static fn (Type $type): bool => $type->canAccessProperties()->yes() && $type->hasStaticProperty($name)->yes(),
			);
			$classType = $classTypeResult->getType();
			if ($classType instanceof ErrorType) {
				return $classTypeResult->getUnknownClassErrors();
			}
		}

		if ($classType->isString()->yes()) {
			return [];
		}

		$typeForDescribe = $classType;
		if ($classType instanceof ThisType) {
			$typeForDescribe = $classType->getStaticObjectType();
		}
		$classType = TypeCombinator::remove($classType, new StringType());

		if ($scope->isInExpressionAssign($node)) {
			return [];
		}

		if ($classType->canAccessProperties()->no() || $classType->canAccessProperties()->maybe() && !$scope->isUndefinedExpressionAllowed($node)) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Cannot access static property $%s on %s.',
					$name,
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
				))
					->line($node->name->getStartLine())
					->identifier('staticProperty.nonObject')
					->build(),
			]);
		}

		$has = $classType->hasStaticProperty($name);
		if (!$has->no() && $scope->isUndefinedExpressionAllowed($node)) {
			return [];
		}

		if (!$has->yes()) {
			if ($scope->hasExpressionType($node)->yes()) {
				return $messages;
			}

			$classNames = $classType->getObjectClassNames();
			if (count($classNames) === 1) {
				$propertyClassReflection = $this->reflectionProvider->getClass($classNames[0]);
				$parentClassReflection = $propertyClassReflection->getParentClass();

				while ($parentClassReflection !== null) {
					if ($parentClassReflection->hasStaticProperty($name)) {
						if ($write) {
							if ($scope->canWriteProperty($parentClassReflection->getStaticProperty($name))) {
								return [];
							}
						} elseif ($scope->canReadProperty($parentClassReflection->getStaticProperty($name))) {
							return [];
						}
						return [
							RuleErrorBuilder::message(sprintf(
								'Access to private static property $%s of parent class %s.',
								$name,
								$parentClassReflection->getDisplayName(),
							))
								->line($node->name->getStartLine())
								->identifier('staticProperty.private')
								->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			if ($classType->hasInstanceProperty($name)->yes()) {
				$hasPropertyTypes = TypeUtils::getHasPropertyTypes($classType);
				foreach ($hasPropertyTypes as $hasPropertyType) {
					if ($hasPropertyType->getPropertyName() === $name) {
						return [];
					}
				}

				return array_merge($messages, [
					RuleErrorBuilder::message(sprintf(
						'Static access to instance property %s::$%s.',
						$classType->getInstanceProperty($name, $scope)->getDeclaringClass()->getDisplayName(),
						$name,
					))
						->line($node->name->getStartLine())
						->identifier('property.staticAccess')
						->build(),
				]);
			}

			if ($node->class instanceof Name) {
				$classExpr = new ClassConstFetch($node->class, new Identifier('class'));
			} else {
				$classExpr = $node->class;
			}
			$propertyExistsCall = new FuncCall(new FullyQualified('property_exists'), [
				new Arg($classExpr),
				new Arg(new String_($name)),
			]);
			if ($scope->getType($propertyExistsCall)->isTrue()->yes()) {
				return [];
			}

			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to an undefined static property %s::$%s.',
					$typeForDescribe->describe(VerbosityLevel::typeOnly()),
					$name,
				))
					->line($node->name->getStartLine())
					->identifier('staticProperty.notFound')
					->build(),
			]);
		}

		$property = $classType->getStaticProperty($name, $scope);
		if ($write) {
			if ($scope->canWriteProperty($property)) {
				return $messages;
			}
		} elseif ($scope->canReadProperty($property)) {
			return $messages;
		}

		if (
			!$this->phpVersion->supportsAsymmetricVisibilityForStaticProperties()
			|| !$write
			|| (!$property->isPrivateSet() && !$property->isProtectedSet())
		) {
			return array_merge($messages, [
				RuleErrorBuilder::message(sprintf(
					'Access to %s property $%s of class %s.',
					$property->isPrivate() ? 'private' : 'protected',
					$name,
					$property->getDeclaringClass()->getDisplayName(),
				))
					->line($node->name->getStartLine())
					->identifier(sprintf('staticProperty.%s', $property->isPrivate() ? 'private' : 'protected'))
					->build(),
			]);
		}

		return array_merge($messages, [
			RuleErrorBuilder::message(sprintf(
				'Access to %s property $%s of class %s.',
				$property->isPrivateSet() ? 'private(set)' : 'protected(set)',
				$name,
				$property->getDeclaringClass()->getDisplayName(),
			))
				->line($node->name->getStartLine())
				->identifier(sprintf('assign.staticProperty%s', $property->isPrivateSet() ? 'PrivateSet' : 'ProtectedSet'))
				->build(),
		]);
	}

}
