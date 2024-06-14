<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Testing\TypeInferenceTestCase;

class DynamicReturnTypeExtensionTypeInferenceTest extends TypeInferenceTestCase
{

	public function dataAsserts(): iterable
	{
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-types.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-types-named-args.php');

		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-compound-types.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/dynamic-method-return-getsingle-conditional.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-7344.php');
		yield from $this->gatherAssertTypes(__DIR__ . '/data/bug-7391b.php');
	}

	/**
	 * @dataProvider dataAsserts
	 * @param mixed ...$args
	 */
	public function testAsserts(
		string $assertType,
		string $file,
		...$args,
	): void
	{
		$this->assertFileAsserts($assertType, $file, ...$args);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/dynamic-return-type.neon',
		];
	}

}
