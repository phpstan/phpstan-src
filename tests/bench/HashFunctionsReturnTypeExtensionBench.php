<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Php\PhpVersion;
use PHPStan\Type\Php\HashFunctionsReturnTypeExtension;
use Tally\Attribute\Warmup;

#[Warmup(calls: 1)]
final class HashFunctionsReturnTypeExtensionBench
{

	/** @var class-string<HashFunctionsReturnTypeExtension> */
	private string $class = HashFunctionsReturnTypeExtension::class;

	/**
	 * The container creates the extension for every analysis, also of code that calls no hash function.
	 * Since 2.3.0 its constructor takes no PHP version, PHP ignores the argument there.
	 */
	public function benchCreate(): HashFunctionsReturnTypeExtension
	{
		// @phpstan-ignore new.noConstructor (2.2.13 to 2.2.17 take the PHP version)
		return new $this->class(new PhpVersion(80400));
	}

}
