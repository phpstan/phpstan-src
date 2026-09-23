<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\RestrictedUsage\RestrictedClassConstantUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedPropertyUsageExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicMethodThrowTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use function sprintf;

/**
 * Every one of these extension interfaces hands the call's Scope to the extension,
 * so the analysed PHP version must be read from Scope::getPhpVersion(). A DI-injected
 * PhpVersion always answers with the configured version and silently ignores both
 * PHP_VERSION_ID narrowing in the analysed code and configured version ranges.
 *
 * The list holds every extension interface with a method taking the public Scope.
 * Interfaces taking the engine-internal MutatingScope (ExprHandler, StmtHandler) are not
 * extensions in this sense, and OperatorTypeSpecifyingExtension gets no Scope at all,
 * so both are absent on purpose. An extension interface added later belongs here too.
 *
 * @implements Rule<InClassNode>
 */
final class NoInjectedPhpVersionInScopeAwareExtensionRule implements Rule
{

	private const SCOPE_AWARE_EXTENSIONS = [
		DynamicFunctionReturnTypeExtension::class,
		DynamicMethodReturnTypeExtension::class,
		DynamicStaticMethodReturnTypeExtension::class,
		DynamicFunctionThrowTypeExtension::class,
		DynamicMethodThrowTypeExtension::class,
		DynamicStaticMethodThrowTypeExtension::class,
		FunctionTypeSpecifyingExtension::class,
		MethodTypeSpecifyingExtension::class,
		StaticMethodTypeSpecifyingExtension::class,
		FunctionParameterOutTypeExtension::class,
		MethodParameterOutTypeExtension::class,
		StaticMethodParameterOutTypeExtension::class,
		FunctionParameterClosureTypeExtension::class,
		MethodParameterClosureTypeExtension::class,
		StaticMethodParameterClosureTypeExtension::class,
		FunctionParameterClosureThisExtension::class,
		MethodParameterClosureThisExtension::class,
		StaticMethodParameterClosureThisExtension::class,
		RestrictedClassConstantUsageExtension::class,
		RestrictedClassNameUsageExtension::class,
		RestrictedFunctionUsageExtension::class,
		RestrictedMethodUsageExtension::class,
		RestrictedPropertyUsageExtension::class,
		ExpressionTypeResolverExtension::class,
		IgnoreErrorExtension::class,
		Collector::class,
	];

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		if (!$classReflection->hasConstructor()) {
			return [];
		}

		$implementedExtension = null;
		foreach (self::SCOPE_AWARE_EXTENSIONS as $extensionInterface) {
			if (!$classReflection->is($extensionInterface)) {
				continue;
			}

			$implementedExtension = $extensionInterface;
			break;
		}

		if ($implementedExtension === null) {
			return [];
		}

		$constructorVariant = $classReflection->getConstructor()->getOnlyVariant();
		$errors = [];
		foreach ($constructorVariant->getParameters() as $parameter) {
			foreach ($parameter->getType()->getObjectClassNames() as $className) {
				if ($className !== PhpVersion::class) {
					continue;
				}

				$errors[] = RuleErrorBuilder::message(sprintf(
					'%s implements %s and must not inject %s - read the analysed PHP version from Scope::getPhpVersion() instead.',
					$classReflection->getDisplayName(),
					$implementedExtension,
					PhpVersion::class,
				))->identifier('phpstanBuild.phpVersionInExtension')->build();
			}
		}

		return $errors;
	}

}
