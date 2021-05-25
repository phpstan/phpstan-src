<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\PhpDoc\TypeNodeResolverAwareExtension;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtension;
use PHPStan\Rules\Exceptions\ExceptionTypeResolver;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\OperatorTypeSpecifyingExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;

/**
 * @implements Rule<Class_>
 */
class ApiClassImplementsRule implements Rule
{

	private ApiRuleHelper $apiRuleHelper;

	private ReflectionProvider $reflectionProvider;

	public function __construct(
		ApiRuleHelper $apiRuleHelper,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->apiRuleHelper = $apiRuleHelper;
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($this->apiRuleHelper->isInPhpStanNamespace($scope->getNamespace())) {
			return [];
		}

		$errors = [];
		foreach ($node->implements as $implements) {
			$errors = array_merge($errors, $this->checkName($implements));
		}

		return $errors;
	}

	/**
	 * @param Node\Name $name
	 * @return RuleError[]
	 */
	private function checkName(Node\Name $name): array
	{
		$implementedClassName = (string) $name;
		if (!$this->reflectionProvider->hasClass($implementedClassName)) {
			return [];
		}

		$implementedClassReflection = $this->reflectionProvider->getClass($implementedClassName);
		if (!$this->apiRuleHelper->isInPhpStanNamespace($implementedClassReflection->getName())) {
			return [];
		}

		if (in_array($implementedClassReflection->getName(), [
			PropertiesClassReflectionExtension::class,
			PropertyReflection::class,
			MethodsClassReflectionExtension::class,
			MethodReflection::class,
			ParametersAcceptor::class,
			DynamicMethodReturnTypeExtension::class,
			DynamicFunctionReturnTypeExtension::class,
			DynamicStaticMethodReturnTypeExtension::class,
			DynamicMethodThrowTypeExtension::class,
			DynamicFunctionThrowTypeExtension::class,
			DynamicStaticMethodThrowTypeExtension::class,
			OperatorTypeSpecifyingExtension::class,
			MethodTypeSpecifyingExtension::class,
			FunctionTypeSpecifyingExtension::class,
			StaticMethodTypeSpecifyingExtension::class,
			ErrorFormatter::class,
			Rule::class,
			TypeSpecifierAwareExtension::class,
			BrokerAwareExtension::class,
			AlwaysUsedClassConstantsExtension::class,
			ReadWritePropertiesExtension::class,
			ExceptionTypeResolver::class,
			TypeNodeResolverExtension::class,
			TypeNodeResolverAwareExtension::class,
		], true)) {
			return [];
		}

		$ruleError = RuleErrorBuilder::message(sprintf(
			'Implementing %s is not covered by backward compatibility promise. The interface might change in a minor PHPStan version.',
			$implementedClassReflection->getDisplayName()
		))->tip(sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions'
		))->build();

		return [$ruleError];
	}

}
