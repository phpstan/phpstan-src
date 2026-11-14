<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MbstringDeprecatedEncodingRule>
 */
#[RequiresPhp('>= 8.0.0')]
class MbstringDeprecatedEncodingRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MbstringDeprecatedEncodingRule(self::createReflectionProvider());
	}

	public function testRule(): void
	{
		$htmlTip = 'Use htmlspecialchars(), htmlentities(), or mb_encode_numericentity()/mb_decode_numericentity() instead.';
		$this->analyse([__DIR__ . '/data/mbstring-deprecated-encoding.php'], [
			['Handling HTML entities via mbstring in mb_convert_encoding() is deprecated in PHP 8.2.', 7, $htmlTip],
			['Handling HTML entities via mbstring in mb_convert_encoding() is deprecated in PHP 8.2.', 8, $htmlTip],
			['Handling Base64 via mbstring in mb_strlen() is deprecated in PHP 8.2.', 9, 'Use base64_encode()/base64_decode() instead.'],
			['Handling Uuencode via mbstring in mb_substr() is deprecated in PHP 8.2.', 10, 'Use convert_uuencode()/convert_uudecode() instead.'],
			['Handling QPrint via mbstring in mb_strtoupper() is deprecated in PHP 8.2.', 11, 'Use quoted_printable_encode()/quoted_printable_decode() instead.'],
			['Handling QPrint via mbstring in mb_check_encoding() is deprecated in PHP 8.2.', 12, 'Use quoted_printable_encode()/quoted_printable_decode() instead.'],
			['Handling HTML entities via mbstring in mb_strlen() is deprecated in PHP 8.2.', 13, $htmlTip],
			['Handling HTML entities via mbstring in mb_strwidth() is deprecated in PHP 8.2.', 14, $htmlTip],
			['Handling HTML entities via mbstring in mb_strlen() is deprecated in PHP 8.2.', 19, $htmlTip],
			['Handling Base64 via mbstring in mb_strlen() is deprecated in PHP 8.2.', 19, 'Use base64_encode()/base64_decode() instead.'],
		]);
	}

	public function testPhpVersionNarrowedScope(): void
	{
		$this->analyse([__DIR__ . '/data/mbstring-deprecated-encoding-php-version.php'], [
			['Handling Base64 via mbstring in mb_strlen() is deprecated in PHP 8.2.', 12, 'Use base64_encode()/base64_decode() instead.'],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range-80100-80200.neon',
		];
	}

}
