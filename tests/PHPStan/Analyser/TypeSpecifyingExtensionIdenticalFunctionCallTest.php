<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

class TypeSpecifyingExtensionIdenticalFunctionCallTest extends TypeInferenceTestCase
{

	#[RequiresPhp('>= 8.0.0')]
	public function testAsserts(): void
	{
		// gathered here instead of in a data provider, so that mutation testing sees the coverage
		foreach (self::gatherAssertTypes(__DIR__ . '/data/type-specifying-extensions-identical-function-call.php') as $args) {
			$this->assertFileAsserts(...$args);
		}
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/TypeSpecifyingExtensionIdenticalFunctionCall.neon',
		];
	}

}
