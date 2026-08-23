<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Node\Printer\Printer;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<NoopRule>
 */
class NoopRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NoopRule(new ExprPrinter(new Printer()));
	}

	protected function getCollectors(): array
	{
		// Asks for the type of a not-yet-walked expression, the same way
		// TypesAssignedToPropertiesRule does. The hasAssign gatherer feeding
		// NoopExpressionNode must not be affected by that ask
		// (https://github.com/phpstan/phpstan/issues/15038).
		return [
			new /** @implements Collector<PropertyAssignNode, string> */ class implements Collector {

				public function getNodeType(): string
				{
					return PropertyAssignNode::class;
				}

				public function processNode(Node $node, Scope $scope): string
				{
					return $scope->getType($node->getAssignedExpr())->describe(VerbosityLevel::typeOnly());
				}

			},
		];
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/noop.php'], [
			[
				'Expression "$arr" on a separate line does not do anything.',
				9,
			],
			[
				'Expression "$arr[\'test\']" on a separate line does not do anything.',
				10,
			],
			[
				'Expression "$foo::$test" on a separate line does not do anything.',
				11,
			],
			[
				'Expression "$foo->test" on a separate line does not do anything.',
				12,
			],
			[
				'Expression "\'foo\'" on a separate line does not do anything.',
				14,
			],
			[
				'Expression "1" on a separate line does not do anything.',
				15,
			],
			[
				'Expression "@\'foo\'" on a separate line does not do anything.',
				17,
			],
			[
				'Expression "+1" on a separate line does not do anything.',
				18,
			],
			[
				'Expression "-1" on a separate line does not do anything.',
				19,
			],
			[
				'Expression "isset($test)" on a separate line does not do anything.',
				25,
			],
			[
				'Expression "empty($test)" on a separate line does not do anything.',
				26,
			],
			[
				'Expression "true" on a separate line does not do anything.',
				27,
			],
			[
				'Expression "\DeadCodeNoop\Foo::TEST" on a separate line does not do anything.',
				28,
			],
			[
				'Expression "(string) 1" on a separate line does not do anything.',
				30,
			],
			[
				'Unused result of "xor" operator.',
				32,
				'This operator has unexpected precedence, try disambiguating the logic with parentheses ().',
			],
			[
				'Unused result of "and" operator.',
				35,
				'This operator has unexpected precedence, try disambiguating the logic with parentheses ().',
			],
			[
				'Unused result of "or" operator.',
				38,
				'This operator has unexpected precedence, try disambiguating the logic with parentheses ().',
			],
			[
				'Unused result of ternary operator.',
				40,
			],
			[
				'Unused result of ternary operator.',
				41,
			],
			[
				'Unused result of "||" operator.',
				46,
			],
			[
				'Unused result of "&&" operator.',
				49,
			],
		]);
	}

	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-property-fetch-noop.php'], [
			[
				'Expression "$ref?->name" on a separate line does not do anything.',
				10,
			],
		]);
	}

	public function testRuleImpurePoints(): void
	{
		$this->analyse([__DIR__ . '/data/noop-impure-points.php'], [
			[
				'Unused result of "&&" operator.',
				12,
			],
			[
				'Expression "$b()" on a separate line does not do anything.',
				59,
			],
			[
				'Expression "new class…" on a separate line does not do anything.',
				98,
			],
			[
				'Expression "new class…" on a separate line does not do anything.',
				104,
			],
		]);
	}

	public function testBug11001(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11001.php'], []);
	}

	public function testBug11361(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11361.php'], []);
	}

	public function testBug13067(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13067.php'], []);
	}

	public function testBug13698(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13698.php'], [
			[
				'Expression "new class extends \Bug13698\NoConstructorClass…" on a separate line does not do anything.',
				47,
			],
			[
				'Expression "new class…" on a separate line does not do anything.',
				50,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testBug15038(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15038.php'], []);
	}

	#[RequiresPhp('>= 8.5.0')]
	public function testPipeOperator(): void
	{
		$this->analyse([__DIR__ . '/data/noop-pipe.php'], [
			[
				'Expression "\'doFoo\'" on a separate line does not do anything.',
				13,
			],
		]);
	}

}
