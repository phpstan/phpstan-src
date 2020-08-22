<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\RuleLevelHelper;

/**
 * @extends \PHPStan\Testing\RuleTestCase<OffsetAccessAssignmentRule>
 */
class OffsetAccessAssignmentRuleTest extends \PHPStan\Testing\RuleTestCase
{

	/** @var bool */
	private $checkUnionTypes;

	protected function getRule(): \PHPStan\Rules\Rule
	{
		$ruleLevelHelper = new RuleLevelHelper($this->createReflectionProvider(), true, false, $this->checkUnionTypes, false);
		return new OffsetAccessAssignmentRule($ruleLevelHelper);
	}

	public function testOffsetAccessAssignmentToScalar(): void
	{
		$this->checkUnionTypes = true;
		$this->analyse(
			[__DIR__ . '/data/offset-access-assignment-to-scalar.php'],
			[
				[
					'Cannot assign offset \'foo\' to string.',
					14,
				],
				[
					'Cannot assign new offset to string.',
					17,
				],
				[
					'Cannot assign offset 12.34 to string.',
					20,
				],
				[
					'Cannot assign offset \'foo\' to array|string.',
					28,
				],
				[
					'Cannot assign offset int|object to array|string.',
					35,
				],
				[
					'Cannot assign offset int|object to string.',
					38,
				],
				[
					'Cannot assign offset false to string.',
					66,
				],
				[
					'Cannot assign offset stdClass to string.',
					68,
				],
				[
					'Cannot assign offset array(1, 2, 3) to SplObjectStorage.',
					72,
				],
				[
					'Cannot assign offset false to OffsetAccessAssignment\ObjectWithOffsetAccess.',
					75,
				],
				[
					'Cannot assign new offset to OffsetAccessAssignment\ObjectWithOffsetAccess.',
					81,
				],
			]
		);
	}

	public function testOffsetAccessAssignmentToScalarWithoutMaybes(): void
	{
		$this->checkUnionTypes = false;
		$this->analyse(
			[__DIR__ . '/data/offset-access-assignment-to-scalar.php'],
			[
				[
					'Cannot assign offset \'foo\' to string.',
					14,
				],
				[
					'Cannot assign new offset to string.',
					17,
				],
				[
					'Cannot assign offset 12.34 to string.',
					20,
				],
				[
					'Cannot assign offset false to string.',
					66,
				],
				[
					'Cannot assign offset stdClass to string.',
					68,
				],
				[
					'Cannot assign offset array(1, 2, 3) to SplObjectStorage.',
					72,
				],
				[
					'Cannot assign offset false to OffsetAccessAssignment\ObjectWithOffsetAccess.',
					75,
				],
				[
					'Cannot assign new offset to OffsetAccessAssignment\ObjectWithOffsetAccess.',
					81,
				],
			]
		);
	}

	public function testInheritDocTemplateTypeResolution(): void
	{
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/inherit-doc-template-type-resolution.php'], []);
	}

	public function testAssignNewOffsetToStubbedClass(): void
	{
		$this->checkUnionTypes = true;
		$this->analyse([__DIR__ . '/data/new-offset-stub.php'], []);
	}

}
