<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NamedArgumentsRule>
 */
class NamedArgumentsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NamedArgumentsRule(self::createReflectionProvider(), new PhpVersion(PHP_VERSION_ID));
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/named-arguments.php'], [
			[
				'You\'re passing a non-default value Exception to parameter $previous but previous argument is passing default value to its parameter ($code). You can skip it and use named argument for $previous instead.',
				14,
			],
			[
				'Named argument $code can be omitted, type 0 is the same as the default value.',
				16,
			],
			[
				'You\'re passing a non-default value Exception to parameter $previous but previous arguments are passing default values to their parameters ($message, $code). You can skip them and use named argument for $previous instead.',
				20,
			],
			[
				'You\'re passing a non-default value 3 to parameter $yetAnother but previous argument is passing default value to its parameter ($another). You can skip it and use named argument for $yetAnother instead.',
				41,
			],
			[
				'Named argument $priority can be omitted, type 1 is the same as the default value.',
				59,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testNoFix(): void
	{
		$this->fix(
			__DIR__ . '/data/named-arguments-no-errors.php',
			__DIR__ . '/data/named-arguments-no-errors.php',
		);
	}

	#[RequiresPhp('>= 8.0')]
	public function testFix(): void
	{
		$this->fix(
			__DIR__ . '/data/named-arguments.php',
			__DIR__ . '/data/named-arguments.php.fixed',
		);
	}

	#[RequiresPhp('>= 8.0')]
	public function testFixFileWithMatch(): void
	{
		$this->fix(
			__DIR__ . '/data/named-arguments-match.php',
			__DIR__ . '/data/named-arguments-match.php.fixed',
		);
	}

	#[RequiresPhp('>= 8.1')]
	public function testNewInInitializer(): void
	{
		$this->analyse([__DIR__ . '/data/named-arguments-new.php'], [
			[
				'You\'re passing a non-default value \'bar\' to parameter $d but previous argument is passing default value to its parameter ($c). You can skip it and use named argument for $d instead.',
				24,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testFixNewInInitializer(): void
	{
		$this->fix(__DIR__ . '/data/named-arguments-new.php', __DIR__ . '/data/named-arguments-new.php.fixed');
	}

}
