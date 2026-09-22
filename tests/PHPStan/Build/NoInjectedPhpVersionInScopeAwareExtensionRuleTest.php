<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoInjectedPhpVersionInScopeAwareExtensionRule>
 */
final class NoInjectedPhpVersionInScopeAwareExtensionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoInjectedPhpVersionInScopeAwareExtensionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/php-version-in-extension.php'], [
			[
				'PhpVersionInExtension\InjectsPhpVersion implements PHPStan\Type\DynamicFunctionReturnTypeExtension and must not inject PHPStan\Php\PhpVersion - read the analysed PHP version from Scope::getPhpVersion() instead.',
				14,
			],
		]);
	}

}
