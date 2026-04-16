<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<TooWidePropertyHookThrowTypeRule>
 */
class TooWidePropertyHookThrowTypeRuleTest extends RuleTestCase
{

	private bool $checkProtectedAndPublicMethods = true;

	protected function getRule(): Rule
	{
		return new TooWidePropertyHookThrowTypeRule(new TooWideThrowTypeCheck(true), $this->checkProtectedAndPublicMethods);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/too-wide-throws-property-hook.php'], [
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$c has InvalidArgumentException in PHPDoc @throws tag but it\'s not thrown.',
				26,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$d has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				33,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$g has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				58,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$h has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				68,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$j has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				76,
			],
			[
				'Get hook for property TooWideThrowsPropertyHook\Foo::$k has DomainException in PHPDoc @throws tag but it\'s not thrown.',
				83,
			],
		]);
	}

	public static function dataAlwaysCheckFinal(): iterable
	{
		yield [
			false,
			[
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$foo has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					13,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$baz2 has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					34,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$baz3 has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					41,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\FinalFoo::$baz has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					53,
				],
			],
		];

		yield [
			true,
			[
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$foo has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					13,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$bar has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					20,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$baz has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					27,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$baz2 has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					34,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\Foo::$baz3 has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					41,
				],
				[
					'Get hook for property TooWideThrowsPropertyHookAlwaysCheckFinal\FinalFoo::$baz has RuntimeException in PHPDoc @throws tag but it\'s not thrown.',
					53,
				],
			],
		];
	}

	/**
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 */
	#[DataProvider('dataAlwaysCheckFinal')]
	#[RequiresPhp('>= 8.4.0')]
	public function testAlwaysCheckFinal(bool $checkProtectedAndPublicMethods, array $expectedErrors): void
	{
		$this->checkProtectedAndPublicMethods = $checkProtectedAndPublicMethods;
		$this->analyse([__DIR__ . '/data/too-wide-throw-type-always-check-final-property-hooks.php'], $expectedErrors);
	}

}
