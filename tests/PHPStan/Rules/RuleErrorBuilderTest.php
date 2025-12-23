<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPUnit\Framework\TestCase;

class RuleErrorBuilderTest extends TestCase
{

	public function testMessageAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo');
		$ruleError = $builder->build();
		self::assertSame('Foo', $ruleError->getMessage());
	}

	public function testMessageAndLineAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->line(25);
		$ruleError = $builder->build();
		self::assertSame('Foo', $ruleError->getMessage());

		self::assertInstanceOf(LineRuleError::class, $ruleError); // @phpstan-ignore staticMethod.alreadyNarrowedType
		self::assertSame(25, $ruleError->getLine());
	}

	public function testMessageAndFileAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->file(__FILE__);
		$ruleError = $builder->build();
		self::assertSame('Foo', $ruleError->getMessage());

		self::assertInstanceOf(FileRuleError::class, $ruleError); // @phpstan-ignore staticMethod.alreadyNarrowedType
		self::assertSame(__FILE__, $ruleError->getFile());
	}

	public function testMessageAndLineAndFileAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->line(25)->file(__FILE__);
		$ruleError = $builder->build();
		self::assertSame('Foo', $ruleError->getMessage());

		self::assertInstanceOf(LineRuleError::class, $ruleError); // @phpstan-ignore staticMethod.alreadyNarrowedType
		self::assertInstanceOf(FileRuleError::class, $ruleError);  // @phpstan-ignore staticMethod.alreadyNarrowedType
		self::assertSame(25, $ruleError->getLine());
		self::assertSame(__FILE__, $ruleError->getFile());
	}

}
