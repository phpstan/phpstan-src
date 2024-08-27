<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PhpParser\Node\Stmt\ClassLike;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\PhpDoc\UnresolvableTypeHelper;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function implode;
use function sprintf;

final class MethodTagCheck
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private ClassNameCheck $classCheck,
		private GenericObjectTypeCheck $genericObjectTypeCheck,
		private MissingTypehintCheck $missingTypehintCheck,
		private UnresolvableTypeHelper $unresolvableTypeHelper,
		private bool $checkClassCaseSensitivity,
	)
	{
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function check(
		ClassReflection $classReflection,
		ClassLike $node,
	): array
	{
		$errors = [];
		foreach ($classReflection->getMethodTags() as $methodName => $methodTag) {
			$i = 0;
			foreach ($methodTag->getParameters() as $parameterName => $parameterTag) {
				$i++;
				$parameterDescription = sprintf('parameter #%d $%s', $i, $parameterName);
				foreach ($this->checkMethodType($classReflection, $methodName, $parameterDescription, $parameterTag->getType(), $node) as $error) {
					$errors[] = $error;
				}

				if ($parameterTag->getDefaultValue() === null) {
					continue;
				}

				foreach ($this->checkMethodType($classReflection, $methodName, sprintf('%s default value', $parameterDescription), $parameterTag->getDefaultValue(), $node) as $error) {
					$errors[] = $error;
				}
			}

			foreach ($this->checkMethodType($classReflection, $methodName, 'return type', $methodTag->getReturnType(), $node) as $error) {
				$errors[] = $error;
			}
		}

		return $errors;
	}

	/**
	 * @return list<IdentifierRuleError>
	 */
	private function checkMethodType(ClassReflection $classReflection, string $methodName, string $description, Type $type, ClassLike $node): array
	{
		if ($this->unresolvableTypeHelper->containsUnresolvableType($type)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'PHPDoc tag @method for method %s::%s() %s contains unresolvable type.',
					$classReflection->getDisplayName(),
					$methodName,
					$description,
				))->identifier('methodTag.unresolvableType')
					->build(),
			];
		}

		$escapedClassName = SprintfHelper::escapeFormatString($classReflection->getDisplayName());
		$escapedMethodName = SprintfHelper::escapeFormatString($methodName);
		$escapedDescription = SprintfHelper::escapeFormatString($description);

		$errors = $this->genericObjectTypeCheck->check(
			$type,
			sprintf('PHPDoc tag @method for method %s::%s() %s contains generic type %%s but %%s %%s is not generic.', $escapedClassName, $escapedMethodName, $escapedDescription),
			sprintf('Generic type %%s in PHPDoc tag @method for method %s::%s() %s does not specify all template types of %%s %%s: %%s', $escapedClassName, $escapedMethodName, $escapedDescription),
			sprintf('Generic type %%s in PHPDoc tag @method for method %s::%s() %s specifies %%d template types, but %%s %%s supports only %%d: %%s', $escapedClassName, $escapedMethodName, $escapedDescription),
			sprintf('Type %%s in generic type %%s in PHPDoc tag @method for method %s::%s() %s is not subtype of template type %%s of %%s %%s.', $escapedClassName, $escapedMethodName, $escapedDescription),
			sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @method for method %s::%s() %s is in conflict with %%s template type %%s of %%s %%s.', $escapedClassName, $escapedMethodName, $escapedDescription),
			sprintf('Call-site variance of %%s in generic type %%s in PHPDoc tag @method for method %s::%s() %s is redundant, template type %%s of %%s %%s has the same variance.', $escapedClassName, $escapedMethodName, $escapedDescription),
		);

		foreach ($this->missingTypehintCheck->getNonGenericObjectTypesWithGenericClass($type) as [$innerName, $genericTypeNames]) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'PHPDoc tag @method for method %s::%s() %s contains generic %s but does not specify its types: %s',
				$classReflection->getDisplayName(),
				$methodName,
				$description,
				$innerName,
				implode(', ', $genericTypeNames),
			))
				->identifier('missingType.generics')
				->build();
		}

		foreach ($this->missingTypehintCheck->getIterableTypesWithMissingValueTypehint($type) as $iterableType) {
			$iterableTypeDescription = $iterableType->describe(VerbosityLevel::typeOnly());
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s has PHPDoc tag @method for method %s() %s with no value type specified in iterable type %s.',
				$classReflection->getClassTypeDescription(),
				$classReflection->getDisplayName(),
				$methodName,
				$description,
				$iterableTypeDescription,
			))
				->tip(MissingTypehintCheck::MISSING_ITERABLE_VALUE_TYPE_TIP)
				->identifier('missingType.iterableValue')
				->build();
		}

		foreach ($this->missingTypehintCheck->getCallablesWithMissingSignature($type) as $callableType) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s %s has PHPDoc tag @method for method %s() %s with no signature specified for %s.',
				$classReflection->getClassTypeDescription(),
				$classReflection->getDisplayName(),
				$methodName,
				$description,
				$callableType->describe(VerbosityLevel::typeOnly()),
			))->identifier('missingType.callable')->build();
		}

		foreach ($type->getReferencedClasses() as $class) {
			if (!$this->reflectionProvider->hasClass($class)) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @method for method %s::%s() %s contains unknown class %s.', $classReflection->getDisplayName(), $methodName, $description, $class))
					->identifier('class.notFound')
					->discoveringSymbolsTip()
					->build();
			} elseif ($this->reflectionProvider->getClass($class)->isTrait()) {
				$errors[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @method for method %s::%s() %s contains invalid type %s.', $classReflection->getDisplayName(), $methodName, $description, $class))
					->identifier('methodTag.trait')
					->build();
			} else {
				$errors = array_merge(
					$errors,
					$this->classCheck->checkClassNames([
						new ClassNameNodePair($class, $node),
					], $this->checkClassCaseSensitivity),
				);
			}
		}

		return $errors;
	}

}
