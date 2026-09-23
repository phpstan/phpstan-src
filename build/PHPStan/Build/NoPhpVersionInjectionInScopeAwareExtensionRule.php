<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
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
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use PHPStan\Type\TypeCombinator;
use function sprintf;

/**
 * Extensions that receive a Scope must ask Scope::getPhpVersion() about the analysed
 * PHP version so that PHP_VERSION_ID checks in the analysed code narrow their answers.
 *
 * @implements Rule<InClassNode>
 */
final class NoPhpVersionInjectionInScopeAwareExtensionRule implements Rule
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
		FunctionParameterClosureTypeExtension::class,
		MethodParameterClosureTypeExtension::class,
		StaticMethodParameterClosureTypeExtension::class,
		FunctionParameterClosureThisExtension::class,
		MethodParameterClosureThisExtension::class,
		StaticMethodParameterClosureThisExtension::class,
		FunctionParameterOutTypeExtension::class,
		MethodParameterOutTypeExtension::class,
		StaticMethodParameterOutTypeExtension::class,
		ExpressionTypeResolverExtension::class,
	];

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$implementedExtension = null;
		foreach (self::SCOPE_AWARE_EXTENSIONS as $extension) {
			if ($classReflection->implementsInterface($extension)) {
				$implementedExtension = $extension;
				break;
			}
		}
		if ($implementedExtension === null) {
			return [];
		}

		if (!$classReflection->hasConstructor()) {
			return [];
		}

		$phpVersionType = new ObjectType(PhpVersion::class);
		$errors = [];
		foreach ($classReflection->getConstructor()->getOnlyVariant()->getParameters() as $parameter) {
			if (!$phpVersionType->isSuperTypeOf(TypeCombinator::removeNull($parameter->getType()))->yes()) {
				continue;
			}

			$errors[] = RuleErrorBuilder::message(sprintf(
				'%s implements %s and should not inject %s via constructor parameter $%s. Use Scope::getPhpVersion() instead.',
				$classReflection->getDisplayName(),
				$implementedExtension,
				PhpVersion::class,
				$parameter->getName(),
			))
				->identifier('phpstan.phpVersionInjection')
				->build();
		}

		return $errors;
	}

}
