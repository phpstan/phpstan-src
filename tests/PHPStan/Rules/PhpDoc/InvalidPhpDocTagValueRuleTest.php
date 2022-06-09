<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<InvalidPhpDocTagValueRule>
 */
class InvalidPhpDocTagValueRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidPhpDocTagValueRule(
			self::getContainer()->getByType(Lexer::class),
			self::getContainer()->getByType(PhpDocParser::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpdoc.php'], [
			[
				'PHPDoc tag @param has invalid value (): Unexpected token "\n * ", expected type at offset 13',
				25,
			],
			[
				'PHPDoc tag @param has invalid value (A & B | C $paramNameA): Unexpected token "|", expected variable at offset 72',
				25,
			],
			[
				'PHPDoc tag @param has invalid value ((A & B $paramNameB): Unexpected token "$paramNameB", expected \')\' at offset 105',
				25,
			],
			[
				'PHPDoc tag @param has invalid value (~A & B $paramNameC): Unexpected token "~A", expected type at offset 127',
				25,
			],
			[
				'PHPDoc tag @var has invalid value (): Unexpected token "\n * ", expected type at offset 156',
				25,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid): Unexpected token "$invalid", expected type at offset 165',
				25,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid Foo): Unexpected token "$invalid", expected type at offset 182',
				25,
			],
			[
				'PHPDoc tag @return has invalid value (): Unexpected token "\n * ", expected type at offset 208',
				25,
			],
			[
				'PHPDoc tag @return has invalid value ([int, string]): Unexpected token "[", expected type at offset 220',
				25,
			],
			[
				'PHPDoc tag @return has invalid value (A & B | C): Unexpected token "|", expected TOKEN_OTHER at offset 251',
				25,
			],
			[
				'PHPDoc tag @var has invalid value (\\\Foo|\Bar $test): Unexpected token "\\\\\\\Foo|\\\Bar", expected type at offset 9',
				29,
			],
			[
				'PHPDoc tag @var has invalid value (callable(int)): Unexpected token "(", expected TOKEN_HORIZONTAL_WS at offset 17',
				59,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18',
				62,
			],
			[
				'PHPDoc tag @throws has invalid value ((\Exception): Unexpected token "*/", expected \')\' at offset 24',
				72,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18',
				81,
			],
		]);
	}

	public function testBug4731(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4731.php'], []);
	}

	public function testBug4731WithoutFirstTag(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4731-no-first-tag.php'], []);
	}

}
