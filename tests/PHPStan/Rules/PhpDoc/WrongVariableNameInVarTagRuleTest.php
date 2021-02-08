<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<WrongVariableNameInVarTagRule>
 */
class WrongVariableNameInVarTagRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new WrongVariableNameInVarTagRule(
			self::getContainer()->getByType(FileTypeMapper::class)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-variable-name-var.php'], [
			[
				'Variable $foo in PHPDoc tag @var does not match assigned variable $test.',
				17,
			],
			[
				'Multiple PHPDoc @var tags above single variable assignment are not supported.',
				23,
			],
			[
				'Variable $foo in PHPDoc tag @var does not match any variable in the foreach loop: $list, $key, $val',
				66,
			],
			[
				'PHPDoc tag @var above foreach loop does not specify variable name.',
				71,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				85,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				91,
			],
			[
				'PHPDoc tag @var above multiple static variables does not specify variable name.',
				91,
			],
			[
				'Variable $foo in PHPDoc tag @var does not match any static variable: $test',
				94,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				103,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				109,
			],
			[
				'Multiple PHPDoc @var tags above single variable assignment are not supported.',
				125,
			],
			[
				'Variable $b in PHPDoc tag @var does not exist.',
				134,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				155,
			],
			[
				'PHPDoc tag @var does not specify variable name.',
				176,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				210,
			],
			[
				'PHPDoc tag @var above foreach loop does not specify variable name.',
				234,
			],
			[
				'Variable $foo in PHPDoc tag @var does not exist.',
				248,
			],
			[
				'Variable $bar in PHPDoc tag @var does not exist.',
				248,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				262,
			],
			[
				'Variable $slots in PHPDoc tag @var does not exist.',
				268,
			],
			[
				'PHPDoc tag @var above assignment does not specify variable name.',
				274,
			],
			[
				'Variable $slots in PHPDoc tag @var does not match assigned variable $itemSlots.',
				280,
			],
		]);
	}

	public function testEmptyFileWithVarThis(): void
	{
		$this->analyse([__DIR__ . '/data/wrong-variable-name-var-empty-this.php'], []);
	}

	public function testAboveUse(): void
	{
		$this->analyse([__DIR__ . '/data/var-above-use.php'], []);
	}

	public function testAboveDeclare(): void
	{
		$this->analyse([__DIR__ . '/data/var-above-declare.php'], []);
	}

	public function testBug3515(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3515.php'], []);
	}

	public function testBug4500(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4500.php'], [
			[
				'PHPDoc tag @var above multiple global variables does not specify variable name.',
				23,
			],
			[
				'Variable $baz in PHPDoc tag @var does not match any global variable: $lorem',
				43,
			],
			[
				'Variable $baz in PHPDoc tag @var does not match any global variable: $lorem',
				49,
			],
		]);
	}

	public function testBug4504(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4504.php'], []);
	}

}
