<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<UnsetRule>
 */
class UnsetRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnsetRule();
	}

	public function testUnsetRule(): void
	{
		require_once __DIR__ . '/data/unset.php';
		$this->analyse([__DIR__ . '/data/unset.php'], [
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				6,
			],
			[
				'Cannot unset offset \'a\' on 3.',
				10,
			],
			[
				'Cannot unset offset \'b\' on 1.',
				14,
			],
			[
				'Cannot unset offset \'c\' on 1.',
				18,
			],
			[
				'Cannot unset offset \'string\' on iterable<int, int>.',
				31,
			],
			[
				'Call to function unset() contains undefined variable $notSetVariable.',
				36,
			],
		]);
	}

	public function testBug2752(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2752.php'], []);
	}

	public function testBug4289(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4289.php'], []);
	}

	public function testBug5223(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-5223.php'], [
			[
				'Cannot unset offset \'page\' on array{categoryKeys: array<string>, tagNames: array<string>}.',
				20,
			],
			[
				'Cannot unset offset \'limit\' on array{categoryKeys: array<string>, tagNames: array<string>}.',
				23,
			],
		]);
	}

	public function testBug3391(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3391.php'], []);
	}

	public function testBug7417(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7417.php'], []);
	}

	public function testBug8113(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8113.php'], []);
	}

	public function testBug4565(): void
	{
		$this->analyse([__DIR__ . '/../../Analyser/nsrt/bug-4565.php'], []);
	}

	public function testBug12421(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12421.php'], [
			[
				'Cannot unset readonly property y of Bug12421\NativeReadonlyClass.',
				11,
			],
			[
				'Cannot unset readonly property y of Bug12421\NativeReadonlyProperty.',
				15,
			],
			[
				'Cannot unset @readonly property y of Bug12421\PhpdocReadonlyClass.',
				19,
			],
			[
				'Cannot unset @readonly property y of Bug12421\PhpdocReadonlyProperty.',
				23,
			],
			[
				'Cannot unset @readonly property y of Bug12421\PhpdocImmutableClass.',
				27,
			],
			[
				'Cannot unset readonly property y of Bug12421\NativeReadonlyPropertySubClass.',
				34,
			],
		]);
	}

}
