<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\Reflection\BetterReflection\BetterReflectionSourceLocatorFactory;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function sprintf;

class SkipBundledPolyfillSourceLocatorTest extends PHPStanTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/data/php-8.4.neon'];
	}

	/** @return iterable<string, array{string}> */
	public static function dataPolyfilledFunctions(): iterable
	{
		yield 'array_first' => ['array_first'];
		yield 'array_last' => ['array_last'];
		yield 'get_error_handler' => ['get_error_handler'];
	}

	/**
	 * PHP 8.5 functions are declared in the PHPStan process by symfony/polyfill-php85,
	 * but they don't exist in a project analysed with PHP 8.4.
	 */
	#[DataProvider('dataPolyfilledFunctions')]
	public function testFunctionFromBundledPolyfillIsNotFound(string $functionName): void
	{
		$reflector = $this->createReflector();

		try {
			$reflection = $reflector->reflectFunction($functionName);
		} catch (IdentifierNotFound) {
			$this->expectNotToPerformAssertions();
			return;
		}

		$this->fail(sprintf('Function %s() should not be found, it is declared in %s.', $functionName, $reflection->getFileName() ?? 'an unknown file'));
	}

	/**
	 * On PHP 8.5 the class is a real internal class of the running PHP version,
	 * symfony/polyfill-php85 declares nothing.
	 */
	#[RequiresPhp('<8.5')]
	public function testClassFromBundledPolyfillIsNotFound(): void
	{
		$reflector = $this->createReflector();

		try {
			$reflection = $reflector->reflectClass('Filter\FilterException');
		} catch (IdentifierNotFound) {
			$this->expectNotToPerformAssertions();
			return;
		}

		$this->fail(sprintf('Class Filter\FilterException should not be found, it is declared in %s.', $reflection->getFileName() ?? 'an unknown file'));
	}

	public function testOtherRuntimeSymbolsAreStillFound(): void
	{
		$reflector = $this->createReflector();

		$this->assertSame(self::class, $reflector->reflectClass(self::class)->getName());
	}

	private function createReflector(): Reflector
	{
		$factory = self::getContainer()->getByType(BetterReflectionSourceLocatorFactory::class);

		return new DefaultReflector($factory->create());
	}

}
