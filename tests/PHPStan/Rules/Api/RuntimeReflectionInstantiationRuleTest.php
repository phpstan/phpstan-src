<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<RuntimeReflectionInstantiationRule>
 */
class RuntimeReflectionInstantiationRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RuntimeReflectionInstantiationRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$errors = [
			[
				'Creating new ReflectionMethod is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				43,
			],
			[
				'Creating new ReflectionClass is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				44,
			],
			[
				'Creating new ReflectionClassConstant is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				45,
			],
			[
				'Creating new ReflectionZendExtension is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				48,
			],
			[
				'Creating new ReflectionExtension is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				49,
			],
			[
				'Creating new ReflectionFunction is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				50,
			],
			[
				'Creating new ReflectionObject is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				51,
			],
			[
				'Creating new ReflectionParameter is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				52,
			],
			[
				'Creating new ReflectionProperty is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				53,
			],
			[
				'Creating new ReflectionGenerator is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				54,
			],
		];
		if (PHP_VERSION_ID >= 80100) {
			$errors[] = [
				'Creating new ReflectionFiber is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				55,
			];
		}
		if (PHP_VERSION_ID >= 80000) {
			$errors[] = [
				'Creating new ReflectionEnum is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				56,
			];
			$errors[] = [
				'Creating new ReflectionEnumBackedCase is a runtime reflection concept that might not work in PHPStan because it uses fully static reflection engine. Use objects retrieved from ReflectionProvider instead.',
				57,
			];
		}
		$this->analyse([__DIR__ . '/data/runtime-reflection-instantiation.php'], $errors);
	}

}
