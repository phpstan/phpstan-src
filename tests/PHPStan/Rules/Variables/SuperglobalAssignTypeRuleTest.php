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
		$server = 'array{argc?: int<0, max>, argv?: list<string>, REQUEST_TIME?: int, REQUEST_TIME_FLOAT?: float, ...<string>}';
		$this->analyse([__DIR__ . '/data/superglobal-assign-type.php'], [
			[
				sprintf('Superglobal $_GET (%s) does not accept array<array<array<mixed>|string>|int|string>.', $request),
				23,
			],
			[
				sprintf('Superglobal $_POST (%s) does not accept array<array<array<mixed>|int|string>|string>.', $request),
				25,
			],
			[
				'Superglobal $_ENV (array<string>) does not accept array<int|string>.',
				26,
			],
			[
				sprintf('Superglobal $_SERVER (%s) does not accept array{argc?: int<0, max>, argv?: list<string>, REQUEST_TIME?: int, REQUEST_TIME_FLOAT?: float, SERVER_PORT: int, ...<string>}.', $server),
				27,
				'Unsealed array value type string does not accept extra offset \'SERVER_PORT\' with value type int.',
			],
			[
				// the second int written on the next line is not reported again
				sprintf('Superglobal $_POST (%s) does not accept array<array<array<mixed>|string>|int|string>.', $request),
				32,
			],
			[
				sprintf('Superglobal $_GET (%s) does not accept string.', $request),
				43,
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
