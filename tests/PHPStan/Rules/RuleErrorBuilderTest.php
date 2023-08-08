<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPUnit\Framework\TestCase;

class RuleErrorBuilderTest extends TestCase
{

	public function testMessageAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo');
		$ruleError = $builder->build();
		$this->assertSame('Foo', $ruleError->getMessage());
	}

	public function testMessageAndLineAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->line(25);
		$ruleError = $builder->build();
		$this->assertSame('Foo', $ruleError->getMessage());

		$this->assertInstanceOf(LineRuleError::class, $ruleError); // @phpstan-ignore method.alreadyNarrowedType
		$this->assertSame(25, $ruleError->getLine());
	}

	public function testMessageAndFileAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->file('Bar.php');
		$ruleError = $builder->build();
		$this->assertSame('Foo', $ruleError->getMessage());

		$this->assertInstanceOf(FileRuleError::class, $ruleError); // @phpstan-ignore method.alreadyNarrowedType
		$this->assertSame('Bar.php', $ruleError->getFile());
	}

	public function testMessageAndLineAndFileAndBuild(): void
	{
		$builder = RuleErrorBuilder::message('Foo')->line(25)->file('Bar.php');
		$ruleError = $builder->build();
		$this->assertSame('Foo', $ruleError->getMessage());

		$this->assertInstanceOf(LineRuleError::class, $ruleError); // @phpstan-ignore method.alreadyNarrowedType
		$this->assertInstanceOf(FileRuleError::class, $ruleError);  // @phpstan-ignore method.alreadyNarrowedType
		$this->assertSame(25, $ruleError->getLine());
		$this->assertSame('Bar.php', $ruleError->getFile());
	}

}
