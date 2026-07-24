<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\DependencyInjection\LazyExtensionsCollection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedFunctionCallableUsageRule>
 */
class RestrictedFunctionCallableUsageRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedFunctionCallableUsageRule(
			new LazyExtensionsCollection(self::getContainer(), RestrictedFunctionUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-function-callable.php'], [
			[
				'Cannot call doFoo',
				7,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/restricted-usage.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
