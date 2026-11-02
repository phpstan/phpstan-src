<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function array_merge;

/**
 * @extends RuleTestCase<ArrayPointerFunctionOnObjectRule>
 */
#[RequiresPhp('>= 8.0.0')]
class ArrayPointerFunctionOnObjectRuleTest extends RuleTestCase
{

	private int $phpVersionId = 80100;

	private bool $checkUnionTypes = false;

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		return new ArrayPointerFunctionOnObjectRule(
			$reflectionProvider,
			new RuleLevelHelper(
				$reflectionProvider,
				checkNullables: true,
				checkThisOnly: false,
				checkUnionTypes: $this->checkUnionTypes,
				checkExplicitMixed: false,
				checkImplicitMixed: false,
				checkBenevolentUnionTypes: false,
				discoveringSymbolsTip: true,
			),
			new PhpVersion($this->phpVersionId),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/array-pointer-function-on-object.php'], $this->getObjectErrors());
	}

	public function testRuleWithUnionTypes(): void
	{
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/array-pointer-function-on-object.php'], array_merge($this->getObjectErrors(), [
			[
				'Calling current() on array<int>|stdClass is deprecated in PHP 8.1.',
				36,
			],
			[
				'Calling current() on iterable<int> is deprecated in PHP 8.1.',
				37,
			],
		]));
	}

	public function testBeforePhp81(): void
	{
		$this->phpVersionId = 80000;
		$this->analyse([__DIR__ . '/data/array-pointer-function-on-object.php'], []);
	}

	/**
	 * @return list<array{string, int}>
	 */
	private function getObjectErrors(): array
	{
		return [
			['Calling current() on stdClass is deprecated in PHP 8.1.', 27],
			['Calling pos() on stdClass is deprecated in PHP 8.1.', 28],
			['Calling key() on ArrayObject<int, int> is deprecated in PHP 8.1.', 29],
			['Calling next() on stdClass is deprecated in PHP 8.1.', 30],
			['Calling prev() on stdClass is deprecated in PHP 8.1.', 31],
			['Calling reset() on stdClass is deprecated in PHP 8.1.', 32],
			['Calling end() on stdClass is deprecated in PHP 8.1.', 33],
			['Calling current() on stdClass is deprecated in PHP 8.1.', 34],
		];
	}

}
