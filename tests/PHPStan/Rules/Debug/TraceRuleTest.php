<?php declare(strict_types = 1);

namespace PHPStan\Rules\Debug;

use PhpParser\Lexer\Emulative;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser\Php7;
use PHPStan\Parser\SimpleParser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<TraceRule>
 */
class TraceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TraceRule(
			new Lexer(),
			new PhpDocParser(new TypeParser(), new ConstExprParser()),
			new SimpleParser(
				new Php7(new Emulative()),
				new NameResolver(),
			),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/trace.php'], [
			[
				'Dumped type: non-empty-array',
				10,
			],
			[
				'Dumped type: 10',
				13,
			],
			[
				'Dumped type: *ERROR*',
				15,
			],
		]);
	}

}
