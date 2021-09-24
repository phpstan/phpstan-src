<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
class CallMethodsRule implements \PHPStan\Rules\Rule
{

	private \PHPStan\Reflection\ReflectionProvider $reflectionProvider;

	private \PHPStan\Rules\FunctionCallParametersCheck $check;

	private \PHPStan\Rules\RuleLevelHelper $ruleLevelHelper;

	private bool $checkFunctionNameCase;

	private bool $reportMagicMethods;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		FunctionCallParametersCheck $check,
		RuleLevelHelper $ruleLevelHelper,
		bool $checkFunctionNameCase,
		bool $reportMagicMethods
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->check = $check;
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
		$this->reportMagicMethods = $reportMagicMethods;
	}

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}

		$name = $node->name->name;

		$typeResult = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$node->var,
			sprintf('Call to method %s() on an unknown class %%s.', SprintfHelper::escapeFormatString($name)),
			static function (Type $type) use ($name): bool {
				return $type->canCallMethods()->yes() && $type->hasMethod($name)->yes();
			},
			true
		);
		$type = $typeResult->getType();
		if ($type instanceof ErrorType) {
			return $typeResult->getUnknownClassErrors();
		}
		if (!$type->canCallMethods()->yes()) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Cannot call method %s() on %s.',
					$name,
					$type->describe(VerbosityLevel::typeOnly())
				))->build(),
			];
		}

		if (!$type->hasMethod($name)->yes()) {
			$directClassNames = $typeResult->getReferencedClasses();
			if (!$this->reportMagicMethods) {
				foreach ($directClassNames as $className) {
					if (!$this->reflectionProvider->hasClass($className)) {
						continue;
					}

					$classReflection = $this->reflectionProvider->getClass($className);
					if ($classReflection->hasNativeMethod('__call')) {
						return [];
					}
				}
			}

			if (count($directClassNames) === 1) {
				$referencedClass = $directClassNames[0];
				$methodClassReflection = $this->reflectionProvider->getClass($referencedClass);
				$parentClassReflection = $methodClassReflection->getParentClass();
				while ($parentClassReflection !== null) {
					if ($parentClassReflection->hasMethod($name)) {
						return [
							RuleErrorBuilder::message(sprintf(
								'Call to private method %s() of parent class %s.',
								$parentClassReflection->getMethod($name, $scope)->getName(),
								$parentClassReflection->getDisplayName()
							))->build(),
						];
					}

					$parentClassReflection = $parentClassReflection->getParentClass();
				}
			}

			return [
				RuleErrorBuilder::message(sprintf(
					'Call to an undefined method %s::%s().',
					$type->describe(VerbosityLevel::typeOnly()),
					$name
				))->build(),
			];
		}

		$methodReflection = $type->getMethod($name, $scope);
		$declaringClass = $methodReflection->getDeclaringClass();
		$messagesMethodName = SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()');
		$errors = [];
		if (!$scope->canCallMethod($methodReflection)) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'Call to %s method %s() of class %s.',
				$methodReflection->isPrivate() ? 'private' : 'protected',
				$methodReflection->getName(),
				$declaringClass->getDisplayName()
			))->build();
		}

		$errors = array_merge($errors, $this->check->check(
			ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$node->getArgs(),
				$methodReflection->getVariants()
			),
			$scope,
			$declaringClass->isBuiltin(),
			$node,
			[
				'Method ' . $messagesMethodName . ' invoked with %d parameter, %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameter, at least %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, at least %d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameter, %d-%d required.',
				'Method ' . $messagesMethodName . ' invoked with %d parameters, %d-%d required.',
				'Parameter %s of method ' . $messagesMethodName . ' expects %s, %s given.',
				'Result of method ' . $messagesMethodName . ' (void) is used.',
				'Parameter %s of method ' . $messagesMethodName . ' is passed by reference, so it expects variables only.',
				'Unable to resolve the template type %s in call to method ' . $messagesMethodName,
				'Missing parameter $%s in call to method ' . $messagesMethodName . '.',
				'Unknown parameter $%s in call to method ' . $messagesMethodName . '.',
				'Return type of call to method ' . $messagesMethodName . ' contains unresolvable type.',
			]
		));

		if (
			$this->checkFunctionNameCase
			&& strtolower($methodReflection->getName()) === strtolower($name)
			&& $methodReflection->getName() !== $name
		) {
			$errors[] = RuleErrorBuilder::message(
				sprintf('Call to method %s with incorrect case: %s', $messagesMethodName, $name)
			)->build();
		}

		return $errors;
	}

}
