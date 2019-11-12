<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\TypeWithClassName;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\New_>
 */
class InstantiationRule implements \PHPStan\Rules\Rule
{

	/** @var \PHPStan\Broker\Broker */
	private $broker;

	/** @var \PHPStan\Rules\FunctionCallParametersCheck */
	private $check;

	/** @var \PHPStan\Rules\ClassCaseSensitivityCheck */
	private $classCaseSensitivityCheck;

	public function __construct(
		Broker $broker,
		FunctionCallParametersCheck $check,
		ClassCaseSensitivityCheck $classCaseSensitivityCheck
	)
	{
		$this->broker = $broker;
		$this->check = $check;
		$this->classCaseSensitivityCheck = $classCaseSensitivityCheck;
	}

	public function getNodeType(): string
	{
		return New_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$errors = [];
		foreach ($this->getClassNames($node, $scope) as $class) {
			$errors = array_merge($errors, $this->checkClassName($class, $node, $scope));
		}
		return $errors;
	}

	/**
	 * @param string $class
	 * @param \PhpParser\Node\Expr\New_ $node
	 * @param Scope $scope
	 * @return RuleError[]
	 */
	private function checkClassName(string $class, Node $node, Scope $scope): array
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
			if ($scope->getClassReflection()->getParentClass() === false) {
				return [
					RuleErrorBuilder::message(sprintf(
						'%s::%s() calls new parent but %s does not extend any class.',
						$scope->getClassReflection()->getDisplayName(),
						$scope->getFunctionName(),
						$scope->getClassReflection()->getDisplayName()
					))->build(),
				];
			}
			$classReflection = $scope->getClassReflection()->getParentClass();
		} else {
			if (!$this->broker->hasClass($class)) {
				return [
					RuleErrorBuilder::message(sprintf('Instantiated class %s not found.', $class))->build(),
				];
			} else {
				$messages = $this->classCaseSensitivityCheck->checkClassNames([
					new ClassNameNodePair($class, $node->class),
				]);
			}

			$classReflection = $this->broker->getClass($class);
		}

		if (!$isStatic && $classReflection->isInterface()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Cannot instantiate interface %s.', $classReflection->getDisplayName())
				)->build(),
			];
		}

		if (!$isStatic && $classReflection->isAbstract()) {
			return [
				RuleErrorBuilder::message(
					sprintf('Instantiated class %s is abstract.', $classReflection->getDisplayName())
				)->build(),
			];
		}

		if (!$classReflection->hasConstructor()) {
			if (count($node->args) > 0) {
				return array_merge($messages, [
					RuleErrorBuilder::message(sprintf(
						'Class %s does not have a constructor and must be instantiated without any parameters.',
						$classReflection->getDisplayName()
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
				$constructorReflection->getName()
			))->build();
		}

		return array_merge($messages, $this->check->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->args,
				$constructorReflection->getVariants()
			),
			$scope,
			$node,
			[
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameter, %d required.',
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameters, %d required.',
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameter, at least %d required.',
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameters, at least %d required.',
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameter, %d-%d required.',
				'Class ' . $classReflection->getDisplayName() . ' constructor invoked with %d parameters, %d-%d required.',
				'Parameter #%d %s of class ' . $classReflection->getDisplayName() . ' constructor expects %s, %s given.',
				'', // constructor does not have a return type
				'Parameter #%d %s of class ' . $classReflection->getDisplayName() . ' constructor is passed by reference, so it expects variables only',
				'Unable to resolve the template type %s in instantiation of class ' . $classReflection->getDisplayName(),
			]
		));
	}

	/**
	 * @param \PhpParser\Node\Expr\New_ $node $node
	 * @param Scope $scope
	 * @return string[]
	 */
	private function getClassNames(Node $node, Scope $scope): array
	{
		if ($node->class instanceof \PhpParser\Node\Name) {
			return [(string) $node->class];
		}

		if ($node->class instanceof Node\Stmt\Class_) {
			$anonymousClassType = $scope->getType($node);
			if (!$anonymousClassType instanceof TypeWithClassName) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			return [$anonymousClassType->getClassName()];
		}

		$type = $scope->getType($node->class);

		return array_merge(
			array_map(
				static function (ConstantStringType $type): string {
					return $type->getValue();
				},
				TypeUtils::getConstantStrings($type)
			),
			TypeUtils::getDirectClassNames($type)
		);
	}

}
