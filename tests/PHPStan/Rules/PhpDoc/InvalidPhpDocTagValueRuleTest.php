<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidPhpDocTagValueRule>
 */
class InvalidPhpDocTagValueRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new InvalidPhpDocTagValueRule(
			$container->getByType(Lexer::class),
			$container->getByType(PhpDocParser::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpdoc.php'], [
			[
				'PHPDoc tag @param has invalid value (): Unexpected token "\n * ", expected type at offset 13 on line 2',
				6,
			],
			[
				'PHPDoc tag @param has invalid value (A & B | C $paramNameA): Unexpected token "|", expected variable at offset 72 on line 5',
				9,
			],
			[
				'PHPDoc tag @param has invalid value ((A & B $paramNameB): Unexpected token "$paramNameB", expected \')\' at offset 105 on line 6',
				10,
			],
			[
				'PHPDoc tag @param has invalid value (~A & B $paramNameC): Unexpected token "~A", expected type at offset 127 on line 7',
				11,
			],
			[
				'PHPDoc tag @var has invalid value (): Unexpected token "\n * ", expected type at offset 156 on line 9',
				13,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid): Unexpected token "$invalid", expected type at offset 165 on line 10',
				14,
			],
			[
				'PHPDoc tag @var has invalid value ($invalid Foo): Unexpected token "$invalid", expected type at offset 182 on line 11',
				15,
			],
			[
				'PHPDoc tag @return has invalid value (): Unexpected token "\n * ", expected type at offset 208 on line 13',
				17,
			],
			[
				'PHPDoc tag @return has invalid value ([int, string]): Unexpected token "[", expected type at offset 220 on line 14',
				18,
			],
			[
				'PHPDoc tag @return has invalid value (A & B | C): Unexpected token "|", expected TOKEN_OTHER at offset 251 on line 15',
				19,
			],
			[
				'PHPDoc tag @var has invalid value (\\\Foo|\Bar $test): Unexpected token "\\\\\\\Foo|\\\Bar", expected type at offset 9 on line 1',
				28,
			],
			[
				'PHPDoc tag @var has invalid value (callable(int)): Unexpected token "(", expected TOKEN_HORIZONTAL_WS at offset 17 on line 1',
				58,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18 on line 1',
				61,
			],
			[
				'PHPDoc tag @throws has invalid value ((\Exception): Unexpected token "*/", expected \')\' at offset 24 on line 1',
				71,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo|Bar): Unexpected token "*/", expected \')\' at offset 18 on line 1',
				80,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				88,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				91,
			],
			[
				'PHPDoc tag @var has invalid value ((Foo&): Unexpected token "*/", expected type at offset 15 on line 1',
				101,
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

	public function testInvalidTypeInTypeAlias(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-type-type-alias.php'], [
			[
				'PHPDoc tag @phpstan-type InvalidFoo has invalid value: Unexpected token "{", expected TOKEN_PHPDOC_EOL at offset 65 on line 3',
				7,
			],
		]);
	}

	public function testIgnoreWithinPhpDoc(): void
	{
		$this->analyse([__DIR__ . '/data/ignore-line-within-phpdoc.php'], []);
	}

	public function testBug6299(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6299.php'], [
			[
				"PHPDoc tag @phpstan-return has invalid value (array{'numeric': stdClass[], 'branches': array{'names': string[], 'exclude': bool}}}|int): Unexpected token \"}\", expected TOKEN_HORIZONTAL_WS at offset 107 on line 2",
				10,
			],
		]);
	}

	public function testBug6692(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6692.php'], [
			[
				'PHPDoc tag @return has invalid value ($this<string>): Unexpected token "<", expected TOKEN_HORIZONTAL_WS at offset 21 on line 2',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.4')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpdoc-property-hooks.php'], [
			[
				'PHPDoc tag @return has invalid value (Test(): Unexpected token "(", expected TOKEN_HORIZONTAL_WS at offset 16 on line 1',
				9,
			],
		]);
	}

}
