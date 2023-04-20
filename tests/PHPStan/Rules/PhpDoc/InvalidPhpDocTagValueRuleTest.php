<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function array_merge;

/**
 * @extends RuleTestCase<InvalidPhpDocTagValueRule>
 */
class InvalidPhpDocTagValueRuleTest extends RuleTestCase
{

	private bool $checkAllInvalidPhpDocs;

	protected function getRule(): Rule
	{
		return new InvalidPhpDocTagValueRule(
			self::getContainer()->getByType(Lexer::class),
			self::getContainer()->getByType(PhpDocParser::class),
			$this->checkAllInvalidPhpDocs,
		);
	}

	public function dataRule(): iterable
	{
		$errors = [
			[
				'PHPDoc tag @param has invalid value (): Unexpected token "\n * ", expected type at offset 13 on line 2',
				25,
			],
			[
				'PHPDoc tag @param has invalid value (A & B | C $paramNameA): Unexpected token "|", expected variable at offset 72 on line 5',
				25,
			],
			[
				'PHPDoc tag @param has invalid value ((A & B $paramNameB): Unexpected token "$paramNameB", expected \')\' at offset 105 on line 6',
				25,
			],
			[
				'PHPDoc tag @param has invalid value (~A & B $paramNameC): Unexpected token "~A", expected type at offset 127 on line 7',
				25,
			],
			[
				'PHPDoc tag @var has invalid value (): Unexpected token "\n * ", expected type at offset 156 on line 9',
				25,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid): Unexpected token "$invalid", expected type at offset 165 on line 10',
				25,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid Foo): Unexpected token "$invalid", expected type at offset 182 on line 11',
				25,
			],
			[
				'PHPDoc tag @return has invalid value (): Unexpected token "\n * ", expected type at offset 208 on line 13',
				25,
			],
			[
				'PHPDoc tag @return has invalid value ([int, string]): Unexpected token "[", expected type at offset 220 on line 14',
				25,
			],
			[
				'PHPDoc tag @return has invalid value (A & B | C): Unexpected token "|", expected TOKEN_OTHER at offset 251 on line 15',
				25,
			],
			[
				'PHPDoc tag @var has invalid value (\\\Foo|\Bar $test): Unexpected token "\\\\\\\Foo|\\\Bar", expected type at offset 9 on line 1',
				29,
			],
			[
				'PHPDoc tag @var has invalid value (callable(int)): Unexpected token "(", expected TOKEN_HORIZONTAL_WS at offset 17 on line 1',
				59,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18 on line 1',
				62,
			],
			[
				'PHPDoc tag @throws has invalid value ((\Exception): Unexpected token "*/", expected \')\' at offset 24 on line 1',
				72,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18 on line 1',
				81,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				89,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				92,
			],
		];

		yield [false, $errors];
		yield [true, array_merge($errors, [
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				102,
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
		$this->analyse([__DIR__ . '/data/invalid-phpdoc.php'], $expectedErrors);
	}

	public function testBug4731(): void
	{
		$this->checkAllInvalidPhpDocs = true;
		$this->analyse([__DIR__ . '/data/bug-4731.php'], []);
	}

	public function testBug4731WithoutFirstTag(): void
	{
		$this->checkAllInvalidPhpDocs = true;
		$this->analyse([__DIR__ . '/data/bug-4731-no-first-tag.php'], []);
	}

	public function testInvalidTypeInTypeAlias(): void
	{
		$this->checkAllInvalidPhpDocs = true;
		$this->analyse([__DIR__ . '/data/invalid-type-type-alias.php'], [
			[
				'PHPDoc tag @phpstan-type InvalidFoo has invalid value: Unexpected token "{", expected TOKEN_PHPDOC_EOL at offset 65 on line 3',
				12,
			],
		]);
	}

}
