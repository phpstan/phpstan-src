<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnserializeRule>
 */
class UnserializeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnserializeRule(new PhpVersion(PHP_VERSION_ID), self::createReflectionProvider(), true);
	}

	public function testFile(): void
	{
		$expectedErrors = [
			[
				'Parameter #2 $options to function unserialize contains an invalid value for "allowed_classes" item #1.',
				5,
			],
			[
				'Parameter #2 $options to function unserialize contains an invalid value null for "allowed_classes".',
				7,
			],
			[
				'Parameter #2 $options to function unserialize contains an invalid value null for "max_depth".',
				9,
			],
			[
				'Parameter #2 $options to function unserialize must be present with "allowed_classes" set to false or a list of allowed class names.',
				9,
			],
			[
				'Parameter #2 $options to function unserialize contains unsupported option "foo".',
				11,
			],
			[
				'Parameter #2 $options to function unserialize must be present with "allowed_classes" set to false or a list of allowed class names.',
				11,
			],
			[
				'Parameter #2 $options to function unserialize must either be false or a list of allowed class names.',
				13,
			],
			[
				'Calling unserialize() without parameter $2 options and "allowed_classes" set to false or a list of allowed class names is insecure.',
				15,
			],
		];

		$this->analyse([__DIR__ . '/data/unserialize.php'], $expectedErrors);
	}

	#[RequiresPhp('< 7.4')]
	public function testMaxDepth(): void
	{
		$expectedErrors = [
			[
				'Parameter #2 $options to function unserialize contains an option "max_depth" which is not supported by this PHP version.',
				5,
			],
		];

		$this->analyse([__DIR__ . '/data/unserialize_max_depth.php'], $expectedErrors);
	}

}
