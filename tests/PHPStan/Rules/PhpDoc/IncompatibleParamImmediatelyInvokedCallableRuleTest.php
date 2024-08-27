<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;

/**
 * @extends RuleTestCase<IncompatibleParamImmediatelyInvokedCallableRule>
 */
class IncompatibleParamImmediatelyInvokedCallableRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new IncompatibleParamImmediatelyInvokedCallableRule(
			self::getContainer()->getByType(FileTypeMapper::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-param-immediately-invoked-callable.php'], [
			[
				'PHPDoc tag @param-immediately-invoked-callable references unknown parameter: $b',
				21,
			],
			[
				'PHPDoc tag @param-later-invoked-callable references unknown parameter: $c',
				21,
			],
			[
				'PHPDoc tag @param-immediately-invoked-callable is for parameter $b with non-callable type int.',
				30,
			],
			[
				'PHPDoc tag @param-later-invoked-callable is for parameter $b with non-callable type int.',
				39,
			],
			[
				'PHPDoc tag @param-immediately-invoked-callable references unknown parameter: $b',
				59,
			],
			[
				'PHPDoc tag @param-later-invoked-callable references unknown parameter: $c',
				59,
			],
			[
				'PHPDoc tag @param-immediately-invoked-callable is for parameter $b with non-callable type int.',
				68,
			],
			[
				'PHPDoc tag @param-later-invoked-callable is for parameter $b with non-callable type int.',
				77,
			],
		]);
	}

}
