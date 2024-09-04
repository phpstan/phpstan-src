<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidPHPStanDocTagRule>
 */
class InvalidPHPStanDocTagRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidPHPStanDocTagRule(
			self::getContainer()->getByType(Lexer::class),
			self::getContainer()->getByType(PhpDocParser::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpstan-doc.php'], [
			[
				'Unknown PHPDoc tag: @phpstan-extens',
				6,
			],
			[
				'Unknown PHPDoc tag: @phpstan-pararm',
				11,
			],
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				43,
			],
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				46,
			],
			[
				'Unknown PHPDoc tag: @phpstan-varr',
				56,
			],
		]);
	}

	public function testBug8697(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8697.php'], []);
	}

}
