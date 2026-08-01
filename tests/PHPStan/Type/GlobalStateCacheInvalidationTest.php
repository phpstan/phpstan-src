<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Exception;
use PHPStan\Reflection\ReflectionProvider\DummyReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Testing\PHPStanTestCase;
use RuntimeException;

/**
 * Type operations read process-wide state - the reflection provider, the PHP
 * version, the feature toggles - so a memoized result is only valid for the
 * state it was computed under. Whoever swaps that state has to drop the memo,
 * otherwise results computed under a throwaway state (ValidateIgnoredErrorsExtension
 * resolves the types named in ignoreErrors patterns under a DummyReflectionProvider)
 * are handed back to the real analysis. Only observable with the turbo extension
 * active, which is what installs the memo in the first place.
 */
class GlobalStateCacheInvalidationTest extends PHPStanTestCase
{

	public function testRegisteringAReflectionProviderDropsMemoizedTypes(): void
	{
		self::createReflectionProvider();
		$originalReflectionProvider = ReflectionProviderStaticAccessor::getInstance();

		// The memo borrows its results: an entry whose result object died is
		// tombstoned, so only a retained result can survive into the next state.
		$underDummyProvider = null;
		ReflectionProviderStaticAccessor::registerInstance(new DummyReflectionProvider());
		try {
			$underDummyProvider = $this->exceptionUnion();
			$this->assertInstanceOf(
				UnionType::class,
				$underDummyProvider,
				'the dummy provider knows no class hierarchy, so the union cannot collapse',
			);
		} finally {
			ReflectionProviderStaticAccessor::registerInstance($originalReflectionProvider);
			ObjectType::resetCaches();
		}

		$this->assertSame(
			Exception::class,
			$this->exceptionUnion()->describe(VerbosityLevel::precise()),
		);
	}

	private function exceptionUnion(): Type
	{
		return TypeCombinator::union(new ObjectType(Exception::class), new ObjectType(RuntimeException::class));
	}

}
