<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidPHPStanDocTagRule>
 */
class InvalidPHPStanDocTagRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$container = self::getContainer();
		return new InvalidPHPStanDocTagRule(
			$container->getByType(Lexer::class),
			$container->getByType(PhpDocParser::class),
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

	public function testAssignOperator(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpstan-doc-assign-operator.php'], [
			[
				'Unknown PHPDoc tag: @phpstan-va',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testMisplacedIgnoreVar(): void
	{
		$this->analyse([__DIR__ . '/data/misplaced-phpstan-ignore-var.php'], [
			[
				'PHPDoc tag @phpstan-ignore-var above a constant has no effect.',
				7,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a function has no effect.',
				12,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a class has no effect.',
				24,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a property has no effect.',
				31,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a class constant has no effect.',
				37,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a method has no effect.',
				42,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a promoted property has no effect.',
				47,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an interface has no effect.',
				57,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a trait has no effect.',
				65,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a trait use has no effect.',
				77,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a property has no effect.',
				86,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a parameter has no effect.',
				92,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a closure has no effect.',
				97,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a parameter has no effect.',
				98,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an arrow function has no effect.',
				102,
			],
			[
				'PHPDoc tag @phpstan-ignore-var at the end of a class body has no effect.',
				106,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a closure has no effect.',
				112,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an arrow function has no effect.',
				115,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an arrow function has no effect.',
				117,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an elseif branch has no effect.',
				129,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above an else branch has no effect.',
				135,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a catch block has no effect.',
				146,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testMisplacedIgnoreVarInEnum(): void
	{
		$this->analyse([__DIR__ . '/data/misplaced-phpstan-ignore-var-enum.php'], [
			[
				'PHPDoc tag @phpstan-ignore-var above an enum case has no effect.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPropertyHooks(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-phpstan-tag-property-hooks.php'], [
			[
				'Unknown PHPDoc tag: @phpstan-what',
				9,
			],
			[
				'PHPDoc tag @phpstan-ignore-var above a property hook has no effect.',
				16,
			],
		]);
	}

}
