<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ForbiddenInterfacesInClassImplementsRule>
 */
class ForbiddenInterfacesInClassImplementsRuleTest extends RuleTestCase
{

	private int $phpVersionId;

	protected function getRule(): Rule
	{
		return new ForbiddenInterfacesInClassImplementsRule(new PhpVersion($this->phpVersionId));
	}

	public function testRule(): void
	{
		$this->phpVersionId = PHP_VERSION_ID;
		$this->analyse([__DIR__ . '/data/forbidden-interfaces-in-class-implements.php'], [
			[
				'Class ForbiddenInterfaceInClassImplements\Traverser cannot implement Traversable.',
				18,
				'Implement either one of IteratorAggregate or Iterator instead.',
			],
			[
				'Anonymous class cannot implement Traversable.',
				23,
				'Implement either one of IteratorAggregate or Iterator instead.',
			],
			[
				'Class ForbiddenInterfaceInClassImplements\MyException cannot implement Throwable.',
				38,
				'Extend either one of Exception or Error instead.',
			],
			[
				'Class ForbiddenInterfaceInClassImplements\MyDateTime cannot implement DateTimeInterface.',
				91,
				'Extend either one of DateTime or DateTimeImmutable instead.',
			],
		]);
	}

	/**
	 * @dataProvider dataRuleOnAbstractClassExtendingTraversable
	 *
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRuleOnAbstractClassExtendingTraversable(int $phpVersionId, array $errors): void
	{
		$this->phpVersionId = $phpVersionId;
		$this->analyse([__DIR__ . '/data/forbidden-traversable-on-abstract-class.php'], $errors);
	}

	public function dataRuleOnAbstractClassExtendingTraversable(): array
	{
		return [
			[
				70400,
				[
					[
						'Class ForbiddenTraversableOnAbstractClass\AbstractTraverser cannot implement Traversable.',
						7,
						'Implement either one of IteratorAggregate or Iterator instead.',
					],
				],
			],
			[
				80000,
				[],
			],
		];
	}

}
