<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<InvalidPHPStanDocTagRule>
 */
class InvalidPHPStanDocTagRuleTest extends RuleTestCase
{

	private bool $checkAllInvalidPhpDocs;

	protected function getRule(): Rule
	{
		return new InvalidPHPStanDocTagRule(
			self::getContainer()->getByType(Lexer::class),
			self::getContainer()->getByType(PhpDocParser::class),
			$this->checkAllInvalidPhpDocs,
		);
	}

	public function dataRule(): iterable
	{
		$errors = [
			[
				'Unknown PHPDoc tag: @phpstan-extens',
				7,
			],
			[
				'Unknown PHPDoc tag: @phpstan-pararm',
				14,
			],
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				44,
			],
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				47,
			],
		];
		yield [false, $errors];
		yield [true, array_merge($errors, [
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				57,
			],
		])];
	}

	/**
	 * @dataProvider dataRule
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testRule(bool $checkAllInvalidPhpDocs, array $expectedErrors): void
	{
		$this->checkAllInvalidPhpDocs = $checkAllInvalidPhpDocs;
		$this->analyse([__DIR__ . '/data/invalid-phpstan-doc.php'], $expectedErrors);
	}

	public function testBug8697(): void
	{
		$this->checkAllInvalidPhpDocs = true;
		$this->analyse([__DIR__ . '/data/bug-8697.php'], []);
	}

}
