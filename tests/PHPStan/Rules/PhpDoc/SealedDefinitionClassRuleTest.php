<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<SealedDefinitionClassRule>
 */
class SealedDefinitionClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		$container = self::getContainer();
		return new SealedDefinitionClassRule(
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			true,
			true,
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-sealed.php'], [
			[
				'PHPDoc tag @phpstan-sealed is only valid on class or interface.',
				16,
			],
			[
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				21,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'PHPDoc tag @phpstan-sealed contains unknown class IncompatibleSealed\UnknownClass.',
				26,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
