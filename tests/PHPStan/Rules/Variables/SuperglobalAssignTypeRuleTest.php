<?php declare(strict_types = 1);

namespace PHPStan\Rules\Variables;

use PHPStan\Analyser\SuperglobalTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use function sprintf;

/**
 * @extends RuleTestCase<SuperglobalAssignTypeRule>
 */
class SuperglobalAssignTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new SuperglobalAssignTypeRule(
			self::getContainer()->getByType(SuperglobalTypeResolver::class),
			self::getContainer()->getByType(RuleLevelHelper::class),
		);
	}

	public function testRule(): void
	{
		$request = 'array<array<array<mixed>|string>|string>';
		$this->analyse([__DIR__ . '/data/superglobal-assign-type.php'], [
			[
				sprintf('Superglobal $_GET (%s) does not accept array<array<array<mixed>|string>|int|string>.', $request),
				21,
			],
			[
				sprintf('Superglobal $_POST (%s) does not accept array<array<array<mixed>|int|string>|string>.', $request),
				23,
			],
			[
				'Superglobal $_ENV (array<string>) does not accept array<int|string>.',
				24,
			],
			[
				// the second int written on the next line is not reported again
				sprintf('Superglobal $_POST (%s) does not accept array<array<array<mixed>|string>|int|string>.', $request),
				29,
			],
			[
				sprintf('Superglobal $_GET (%s) does not accept string.', $request),
				40,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/superglobal-assign-type.neon',
		];
	}

}
