<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use Override;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ConditionallyDeclaredSymbolDetector;
use function str_contains;
use function str_replace;

final class SkipPolyfillSourceLocator implements SourceLocator
{

	public function __construct(
		private SourceLocator $sourceLocator,
		private PhpVersion $phpVersion,
		private ConditionallyDeclaredSymbolDetector $conditionallyDeclaredSymbolDetector,
		private PhpStormStubsSourceStubber $phpstormStubsSourceStubber,
	)
	{
	}

	#[Override]
	public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?Reflection
	{
		$reflection = $this->sourceLocator->locateIdentifier($reflector, $identifier);
		if ($reflection === null) {
			return null;
		}

		if ($reflection instanceof ReflectionClass || $reflection instanceof ReflectionFunction || $reflection instanceof ReflectionConstant) {
			$fileName = $reflection->getFileName();
			if ($fileName !== null) {
				$normalized = str_replace('\\', '/', $fileName);
				if (str_contains($normalized, '/symfony/polyfill-php80/') && $this->phpVersion->getVersionId() >= 80000) {
					return null;
				}
				if (str_contains($normalized, '/symfony/polyfill-php81/') && $this->phpVersion->getVersionId() >= 80100) {
					return null;
				}
				if (str_contains($normalized, '/symfony/polyfill-php82/') && $this->phpVersion->getVersionId() >= 80200) {
					return null;
				}
				if (str_contains($normalized, '/symfony/polyfill-php83/') && $this->phpVersion->getVersionId() >= 80300) {
					return null;
				}
				if (str_contains($normalized, '/symfony/polyfill-php84/') && $this->phpVersion->getVersionId() >= 80400) {
					return null;
				}
				if (str_contains($normalized, '/symfony/polyfill-php85/') && $this->phpVersion->getVersionId() >= 80500) {
					return null;
				}
				if ($this->isShadowingNativeSymbol($reflection, $fileName)) {
					return null;
				}
			}
		}

		return $reflection;
	}

	/**
	 * A polyfill guards its declaration so that it never runs when PHP provides
	 * the symbol natively. Its shape is then only an approximation of the real
	 * one and must not be reflected instead of it.
	 *
	 * Functions are left alone here: hiding them would also hide their
	 * existence from a PHP version that does not have them natively yet.
	 * NativeFunctionReflectionProvider prefers the native signature instead.
	 */
	private function isShadowingNativeSymbol(ReflectionClass|ReflectionFunction|ReflectionConstant $reflection, string $fileName): bool
	{
		if ($reflection instanceof ReflectionClass) {
			return $this->conditionallyDeclaredSymbolDetector->isConditionallyDeclaredClass($fileName, $reflection->getName())
				&& $this->phpstormStubsSourceStubber->isPresentClass($reflection->getName()) === true;
		}

		if ($reflection instanceof ReflectionConstant) {
			return $this->conditionallyDeclaredSymbolDetector->isConditionallyDeclaredConstant($fileName, $reflection->getName())
				&& $this->phpstormStubsSourceStubber->generateConstantStub($reflection->getName()) !== null;
		}

		return false;
	}

	#[Override]
	public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
	{
		return $this->sourceLocator->locateIdentifiersByType($reflector, $identifierType);
	}

}
