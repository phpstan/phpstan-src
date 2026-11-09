<?php declare(strict_types = 1);

namespace PHPStan\Rules\Api;

use PHPStan\Rules\Classes\ExistingClassInClassExtendsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<ExistingClassInClassExtendsRule>
 */
class ApiClassNameUsageExtensionClassExtendsTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(ExistingClassInClassExtendsRule::class);
	}

	public function testRuleInPhpStan(): void
	{
		$this->analyse([__DIR__ . '/data/class-extends-in-phpstan.php'], [
			[
				'Class PHPStan\ClassExtends\Foo extends final class PHPStan\Type\FileTypeMapper.',
				8,
			],
		]);
	}

	public function testRuleOutOfPhpStan(): void
	{
		$tip = sprintf(
			"If you think it should be covered by backward compatibility promise, open a discussion:\n   %s\n\n   See also:\n   https://phpstan.org/developing-extensions/backward-compatibility-promise",
			'https://github.com/phpstan/phpstan/discussions',
		);

		$this->analyse([__DIR__ . '/data/class-extends-out-of-phpstan.php'], [
			[
				'Class AppClassExtends\Foo extends final class PHPStan\Type\FileTypeMapper.',
				9,
			],
			[
				'Extending PHPStan\Type\FileTypeMapper is not covered by backward compatibility promise. The class might change in a minor PHPStan version.',
				9,
				$tip,
			],
		]);
	}

}
