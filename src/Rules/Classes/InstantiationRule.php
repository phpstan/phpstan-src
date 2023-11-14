<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use function array_map;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<Node\Expr\New_>
 */
class InstantiationRule implements Rule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private FunctionCallParametersCheck $check,
		private ClassCaseSensitivityCheck $classCaseSensitivityCheck,
	)
	{
	}

	public function getNodeType(): string
	{
		return New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($this->getClassNames($node, $scope) as [$class, $isName]) {
			$errors = array_merge($errors, $this->checkClassName($class, $isName, $node, $scope));
		}
		return $errors;
	}

	/**
	 * @param Node\Expr\New_ $node
	 * @return RuleError[]
	 */
	private function checkClassName(string $class, bool $isName, Node $node, Scope $scope): array
	{
		$lowercasedClass = strtolower($class);
		$messages = [];
		$isStatic = false;
		if ($lowercasedClass === 'static') {
			if (!$scope->isInClass()) {
				return [
					RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->build(),
				];
			}

			$isStatic = true;
			$classReflection = $scope->getClassReflection();
			if (!$classReflection->isFinal()) {
				if (!$classReflection->hasConstructor()) {
					return [];
				}

				$constructor = $classReflection->getConstructor();
				if (
					!$constructor->getPrototype()->getDeclaringClass()->isInterface()
					&& $constructor instanceof PhpMethodReflection
					&& !$constructor->isFinal()->yes()
					&& !$constructor->getPrototype()->isAbstract()
				) {
					return [];
				}
			}
		} elseif ($lowercasedClass === 'self') {
			if (!$scope->isInClass()) {
				return [
					RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->build(),
				];
			}
			$classReflection = $scope->getClassReflection();
		} elseif ($lowercasedClass === 'parent') {
			if (!$scope->isInClass()) {
				return [
					RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->build(),
				];
			}
			if ($scope->getClassReflection()->getParentClass() === null) {
				return [
					RuleErrorBuilder::message(sprintf(
						'%s::%s() calls new parent but %s does not extend any class.',
						$scope->getClassReflection()->getDisplayName(),
						$scope->getFunctionName(),
						$scope->getClassReflection()->getDisplayName(),
					))->build(),
				];
			}
			$classReflection = $scope->getClassReflection()->getParentClass();
		} else {
			if (!$this->reflectionProvider->hasClass($class)) {
				if ($scope->isInClassExists($class)) {
					return [];
				}

				return [
					RuleErrorBuilder::message(sprintf('Instantiated class %s not found.', $class))->discoveringSymbolsTip()->build(),
				];
			}

			$messages = $this->classCaseSensitivityCheck->checkClassNames([
				new ClassNameNodePair($class, $node->class),
			]);

			$classReflection = $this->reflectionProvider->getClass($class);
		}

		if ($classReflection->isEnum() && $isName) {
			return [
				RuleErrorBuilder::message(
					sprintf('Cannot instantiate enum %s.', $classReflection->getDisplayName()),
				)->build(),
			];
		}

		if (!$isStatic && $classReflection->isInterface() && $isName) {
			return [
				RuleErrorBuilder::message(
					sprintf('Cannot instantiate interface %s.', $classReflection->getDisplayName()),
				)->build(),
			];
		}

		if (!$isStatic && $classReflection->isAbstract() && $isName) {
			return [
				RuleErrorBuilder::message(
					sprintf('Instantiated class %s is abstract.', $classReflection->getDisplayName()),
				)->build(),
			];
		}

		if (!$isName) {
			return [];
		}

		if (!$classReflection->hasConstructor()) {
			if (count($node->getArgs()) > 0) {
				return array_merge($messages, [
					RuleErrorBuilder::message(sprintf(
						'Class %s does not have a constructor and must be instantiated without any parameters.',
						$classReflection->getDisplayName(),
					))->build(),
				]);
			}

			return $messages;
		}

		$constructorReflection = $classReflection->getConstructor();
		if (!$scope->canCallMethod($constructorReflection)) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Cannot instantiate class %s via %s constructor %s::%s().',
				$classReflection->getDisplayName(),
				$constructorReflection->isPrivate() ? 'private' : 'protected',
				$constructorReflection->getDeclaringClass()->getDisplayName(),
				$constructorReflection->getName(),
			))->build();
		}

		$classDisplayName = SprintfHelper::escapeFormatString($classReflection->getDisplayName());

		return array_merge($messages, $this->check->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$constructorReflection->getVariants(),
				$constructorReflection->getNamedArgumentsVariants(),
			),
			$scope,
			$constructorReflection->getDeclaringClass()->isBuiltin(),
			$node,
			[
				'Class ' . $classDisplayName . ' constructor invoked with %d parameter, %d required.',
				'Class ' . $classDisplayName . ' constructor invoked with %d parameters, %d required.',
				'Class ' . $classDisplayName . ' constructor invoked with %d parameter, at least %d required.',
				'Class ' . $classDisplayName . ' constructor invoked with %d parameters, at least %d required.',
				'Class ' . $classDisplayName . ' constructor invoked with %d parameter, %d-%d required.',
				'Class ' . $classDisplayName . ' constructor invoked with %d parameters, %d-%d required.',
				'Parameter %s of class ' . $classDisplayName . ' constructor expects %s, %s given.',
				'', // constructor does not have a return type
				'Parameter %s of class ' . $classDisplayName . ' constructor is passed by reference, so it expects variables only',
				'Unable to resolve the template type %s in instantiation of class ' . $classDisplayName,
				'Missing parameter $%s in call to ' . $classDisplayName . ' constructor.',
				'Unknown parameter $%s in call to ' . $classDisplayName . ' constructor.',
				'Return type of call to ' . $classDisplayName . ' constructor contains unresolvable type.',
				'Parameter %s of class ' . $classDisplayName . ' constructor contains unresolvable type.',
			],
		));
	}

	/**
	 * @param Node\Expr\New_ $node $node
	 * @return array<int, array{string, bool}>
	 */
	private function getClassNames(Node $node, Scope $scope): array
	{
		if ($node->class instanceof Node\Name) {
			return [[(string) $node->class, true]];
		}

		if ($node->class instanceof Node\Stmt\Class_) {
			$classNames = $scope->getType($node)->getObjectClassNames();
			if ($classNames === []) {
				throw new ShouldNotHappenException();
			}

			return array_map(
				static fn (string $className) => [$className, true],
				$classNames,
			);
		}

		$type = $scope->getType($node->class);

		return array_merge(
			array_map(
				static fn (ConstantStringType $type): array => [$type->getValue(), true],
				$type->getConstantStrings(),
			),
			array_map(
				static fn (string $name): array => [$name, false],
				$type->getObjectClassNames(),
			),
		);
	}

}
