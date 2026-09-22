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
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MethodTypeSpecifyingExtension;
use PHPStan\Type\StaticMethodTypeSpecifyingExtension;
use function sprintf;

/**
 * Every one of these extension interfaces hands the call's Scope to the extension,
 * so the analysed PHP version must be read from Scope::getPhpVersion(). A DI-injected
 * PhpVersion always answers with the configured version and silently ignores both
 * PHP_VERSION_ID narrowing in the analysed code and configured version ranges.
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
