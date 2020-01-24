<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\TestCase;

class IgnoredRegexValidatorTest extends TestCase
{

	public function dataGetIgnoredTypes(): array
	{
		return [
			[
				'#^Call to function method_exists\\(\\) with ReflectionProperty and \'(?:hasType|getType)\' will always evaluate to true\\.$#iu',
				[],
			],
			[
				'#^Call to function method_exists\\(\\) with ReflectionProperty and \'(?:hasType|getType)\' will always evaluate to true\\.$#',
				[],
			],
			[
				'#Call to function method_exists\\(\\) with ReflectionProperty and \'(?:hasType|getType)\' will always evaluate to true\\.#',
				[],
			],
			[
				'#Parameter \#2 $destination of method Nette\\\\Application\\\\UI\\\\Component::redirect\(\) expects string|null, array|string|int given#',
				[
					'null' => 'null, array',
					'string' => 'string',
					'int' => 'int given',
				],
			],
			[
				'#Parameter \#2 $destination of method Nette\\\\Application\\\\UI\\\\Component::redirect\(\) expects string|null, array|Foo|Bar given#',
				[
					'null' => 'null, array',
				],
			],
			[
				'#Parameter \#2 $destination of method Nette\\\\Application\\\\UI\\\\Component::redirect\(\) expects string\|null, array\|string\|int given#',
				[],
			],
			[
				'#Invalid array key type array|string\.#',
				[
					'string' => 'string\\.',
				],
			],
			[
				'#Invalid array key type array\|string\.#',
				[],
			],
			[
				'#Array (array<string>) does not accept key resource|iterable\.#',
				[
					'iterable' => 'iterable\.',
				],
			],
			[
				'#Parameter \#1 $i of method Levels\\\\AcceptTypes\\\\Foo::doBarArray\(\) expects array<int>, array<float|int> given.#',
				[
					'int' => 'int> given.',
				],
			],
			[
				'#Parameter \#1 $i of method Levels\\\\AcceptTypes\\\\Foo::doBarArray\(\) expects array<int>|callable, array<float|int> given.#',
				[
					'callable' => 'callable, array<float',
					'int' => 'int> given.',
				],
			],
			[
				'#Unclosed parenthesis(\)#',
				[],
			],
		];
	}

	/**
	 * @dataProvider dataGetIgnoredTypes
	 * @param string $regex
	 * @param string[] $expectedTypes
	 */
	public function testGetIgnoredTypes(string $regex, array $expectedTypes): void
	{
		$grammar = new \Hoa\File\Read('hoa://Library/Regex/Grammar.pp');
		$parser = \Hoa\Compiler\Llk\Llk::load($grammar);
		$validator = new IgnoredRegexValidator($parser, self::getContainer()->getByType(TypeStringResolver::class));
		$this->assertSame($expectedTypes, $validator->getIgnoredTypes($regex));
	}

}
