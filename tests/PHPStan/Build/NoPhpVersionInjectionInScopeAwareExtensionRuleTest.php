<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoPhpVersionInjectionInScopeAwareExtensionRule>
 */
final class NoPhpVersionInjectionInScopeAwareExtensionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoPhpVersionInjectionInScopeAwareExtensionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/no-php-version-injection.php'], [
			[
				'NoPhpVersionInjection\InjectsPhpVersion implements PHPStan\Type\DynamicFunctionReturnTypeExtension and should not inject PHPStan\Php\PhpVersion. Use Scope::getPhpVersion() instead.',
				24,
			],
		]);
	}

}
