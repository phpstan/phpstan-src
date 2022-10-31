<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleInstanceOfRule>
 */
class ImpossibleInstanceOfRuleTest extends RuleTestCase
{

	private bool $checkAlwaysTrueInstanceOf;

	private bool $treatPhpDocTypesAsCertain;

	protected function getRule(): Rule
	{
		return new ImpossibleInstanceOfRule($this->checkAlwaysTrueInstanceOf, $this->treatPhpDocTypesAsCertain);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testInstanceof(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse(
			[__DIR__ . '/data/impossible-instanceof.php'],
			[
				[
					'Instanceof between ImpossibleInstanceOf\Lorem and ImpossibleInstanceOf\Lorem will always evaluate to true.',
					59,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Ipsum and ImpossibleInstanceOf\Lorem will always evaluate to true.',
					65,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Ipsum and ImpossibleInstanceOf\Ipsum will always evaluate to true.',
					68,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					71,
				],
				[
					'Instanceof between ImpossibleInstanceOf\FooImpl and ImpossibleInstanceOf\Foo will always evaluate to true.',
					74,
				],
				[
					'Instanceof between ImpossibleInstanceOf\BarChild and ImpossibleInstanceOf\Bar will always evaluate to true.',
					77,
				],
				[
					'Instanceof between string and ImpossibleInstanceOf\Foo will always evaluate to false.',
					94,
				],
				[
					'Instanceof between string and \'str\' will always evaluate to false.',
					98,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					107,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					119,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					124,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					137,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					142,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					155,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test and ImpossibleInstanceOf\Test will always evaluate to true.',
					160,
				],
				[
					'Instanceof between callable and ImpossibleInstanceOf\FinalClassWithoutInvoke will always evaluate to false.',
					204,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Dolor will always evaluate to true.',
					226,
					$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					228,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					232,
					$tipText,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Bar will always evaluate to true.',
					232,
					$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Foo will always evaluate to false.',
					234,
					$tipText,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar&ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					238,
					//$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Bar will always evaluate to false.',
					240,
					//$tipText,
				],
				[
					'Instanceof between object and Exception will always evaluate to false.',
					303,
				],
				[
					'Instanceof between object and InvalidArgumentException will always evaluate to false.',
					307,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarChild will always evaluate to false.',
					318,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarGrandChild will always evaluate to false.',
					322,
				],
				[
					'Instanceof between mixed and int results in an error.',
					353,
				],
				[
					'Instanceof between mixed and ImpossibleInstanceOf\InvalidTypeTest|int results in an error.',
					362,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Foo and ImpossibleInstanceOf\Foo will always evaluate to true.',
					388,
					$tipText,
				],
				[
					'Instanceof between T of Exception and Error will always evaluate to false.',
					404,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and DateTimeInterface will always evaluate to false.',
					418,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and class-string<DateTimeInterface> will always evaluate to false.',
					419,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and \'DateTimeInterface\' will always evaluate to false.',
					432,
					$tipText,
				],
				[
					'Instanceof between DateTimeInterface and \'DateTimeInterface\' will always evaluate to true.',
					433,
					$tipText,
				],
			],
		);
	}

	public function testInstanceofWithoutAlwaysTrue(): void
	{
		$this->checkAlwaysTrueInstanceOf = false;
		$this->treatPhpDocTypesAsCertain = true;

		$tipText = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->analyse(
			[__DIR__ . '/data/impossible-instanceof.php'],
			[
				[
					'Instanceof between ImpossibleInstanceOf\Dolor and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					71,
				],
				[
					'Instanceof between string and ImpossibleInstanceOf\Foo will always evaluate to false.',
					94,
				],
				[
					'Instanceof between string and \'str\' will always evaluate to false.',
					98,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					119,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					137,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Test|null and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					155,
				],
				[
					'Instanceof between callable and ImpossibleInstanceOf\FinalClassWithoutInvoke will always evaluate to false.',
					204,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Lorem will always evaluate to false.',
					228,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Foo will always evaluate to false.',
					234,
					$tipText,
				],
				[
					'Instanceof between *NEVER* and ImpossibleInstanceOf\Bar will always evaluate to false.',
					240,
					//$tipText,
				],
				[
					'Instanceof between object and Exception will always evaluate to false.',
					303,
				],
				[
					'Instanceof between object and InvalidArgumentException will always evaluate to false.',
					307,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarChild will always evaluate to false.',
					318,
				],
				[
					'Instanceof between ImpossibleInstanceOf\Bar and ImpossibleInstanceOf\BarGrandChild will always evaluate to false.',
					322,
				],
				[
					'Instanceof between mixed and int results in an error.',
					353,
				],
				[
					'Instanceof between mixed and ImpossibleInstanceOf\InvalidTypeTest|int results in an error.',
					362,
				],
				[
					'Instanceof between T of Exception and Error will always evaluate to false.',
					404,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and DateTimeInterface will always evaluate to false.',
					418,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and class-string<DateTimeInterface> will always evaluate to false.',
					419,
					$tipText,
				],
				[
					'Instanceof between class-string<DateTimeInterface> and \'DateTimeInterface\' will always evaluate to false.',
					432,
					$tipText,
				],
			],
		);
	}

	public function testDoNotReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/impossible-instanceof-not-phpdoc.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				12,
			],
			[
				'Instanceof between stdClass and Exception will always evaluate to false.',
				15,
			],
			[
				'Instanceof between DateTimeImmutable and DateTimeInterface will always evaluate to true.',
				27,
			],
			[
				'Instanceof between DateTimeImmutable and ImpossibleInstanceofNotPhpDoc\SomeFinalClass will always evaluate to false.',
				30,
			],
		]);
	}

	public function testReportTypesFromPhpDocs(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impossible-instanceof-not-phpdoc.php'], [
			[
				'Instanceof between stdClass and stdClass will always evaluate to true.',
				12,
			],
			[
				'Instanceof between stdClass and Exception will always evaluate to false.',
				15,
			],
			[
				'Instanceof between DateTimeImmutable and DateTimeInterface will always evaluate to true.',
				27,
			],
			[
				'Instanceof between DateTimeImmutable and ImpossibleInstanceofNotPhpDoc\SomeFinalClass will always evaluate to false.',
				30,
			],
			[
				'Instanceof between DateTimeImmutable and DateTimeImmutable will always evaluate to true.',
				33,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Instanceof between DateTimeImmutable and DateTime will always evaluate to false.',
				36,
				//'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
		]);
	}

	public function testBug3096(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-3096.php'], []);
	}

	public function testBug6213(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-6213.php'], []);
	}

	public function testBug5333(): void
	{
		$this->checkAlwaysTrueInstanceOf = true;
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/bug-5333.php'], [
			[
				'Instanceof between Bug5333\FinalRoute and Bug5333\FinalRoute will always evaluate to true.',
				113,
			],
		]);
	}

}
