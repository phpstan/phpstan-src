<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ErrorTip;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<EchoRule>
 */
class ErrorTipExtensionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EchoRule();
	}

	public function testTips(): void
	{
		$this->analyse([__DIR__ . '/data/error-tip-extension.php'], [
			[
				'plain',
				5,
				"• Extension tip for plain in namespace ErrorTipExtensionTest.\n• Second extension tip.",
			],
			[
				'tip',
				6,
				"• Rule tip.\n• Extension tip for tip in namespace ErrorTipExtensionTest.",
			],
			[
				'tips',
				7,
				"• Rule tip 1.\n• Rule tip 2.\n• Extension tip for tips in namespace ErrorTipExtensionTest.",
			],
			[
				'nonIgnorable',
				8,
			],
			[
				'none',
				9,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/error-tip-extension.neon',
		];
	}

}
