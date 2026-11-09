<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedFunctionUsageRule>
 */
class RuntimeReflectionFunctionUsageExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedFunctionUsageRule(
			self::getContainer()->getExtensionsCollection(RestrictedFunctionUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/runtime-reflection-function.php'], [
			[
				'Function is_a() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				43,
			],
			[
				'Function is_subclass_of() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				46,
			],
			[
				'Function class_parents() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				49,
			],
			[
				'Function class_implements() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				50,
			],
			[
				'Function class_uses() is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				51,
			],
		]);
	}

}
