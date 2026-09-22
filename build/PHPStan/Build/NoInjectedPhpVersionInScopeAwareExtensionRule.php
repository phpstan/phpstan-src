<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\Node\InClassNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function sprintf;

/**
 * An extension that is handed the call's Scope must read the analysed PHP version from
 * Scope::getPhpVersion(). A DI-injected PhpVersion always answers with the configured version
 * and silently ignores both PHP_VERSION_ID narrowing in the analysed code and configured
 * version ranges.
 *
 * Scope-aware extensions are discovered instead of listed: every interface marked with
 * #[ExtensionInterface] that declares a method taking the public Scope counts, so extension
 * interfaces added later are covered automatically. Interfaces taking the engine-internal
 * MutatingScope (ExprHandler, StmtHandler) are not extensions in this sense, and neither is
 * PHPStan\Rules\Rule, whose processNode() takes Scope&NodeCallbackInvoker&CollectedDataEmitter.
 *
 * @implements Rule<InClassNode>
 */
final class NoInjectedPhpVersionInScopeAwareExtensionRule implements Rule
{

	/** @var array<string, bool> */
	private array $scopeAwareExtensions = [];

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

		$implementedExtension = $this->findScopeAwareExtension($classReflection);
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

	private function findScopeAwareExtension(ClassReflection $classReflection): ?string
	{
		foreach ($classReflection->getInterfaces() as $interface) {
			if (!$this->isScopeAwareExtension($interface)) {
				continue;
			}

			return $interface->getName();
		}

		return null;
	}

	private function isScopeAwareExtension(ClassReflection $interface): bool
	{
		$interfaceName = $interface->getName();
		if (!array_key_exists($interfaceName, $this->scopeAwareExtensions)) {
			$this->scopeAwareExtensions[$interfaceName] = $this->isExtensionInterface($interface)
				&& $this->acceptsScope($interface);
		}

		return $this->scopeAwareExtensions[$interfaceName];
	}

	private function isExtensionInterface(ClassReflection $interface): bool
	{
		foreach ($interface->getAttributes() as $attribute) {
			if ($attribute->getName() === ExtensionInterface::class) {
				return true;
			}
		}

		return false;
	}

	private function acceptsScope(ClassReflection $interface): bool
	{
		foreach ($interface->getNativeReflection()->getMethods() as $nativeMethod) {
			$method = $interface->getNativeMethod($nativeMethod->getName());
			foreach ($method->getOnlyVariant()->getParameters() as $parameter) {
				if ($parameter->getType()->getObjectClassNames() !== [Scope::class]) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

}
