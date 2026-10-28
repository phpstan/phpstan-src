<?php declare(strict_types = 1);

namespace PHPStan\Process\Restart;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use function file_get_contents;
use function ini_get;
use function is_file;
use function parse_ini_string;
use function unlink;

#[CoversNothing]
class IniComposerTest extends TestCase
{

	private const MAIN_INI = __DIR__ . '/data/main.ini';

	private const SCANNED_INI = __DIR__ . '/data/scanned.ini';

	private const TIDEWAYS_INI = __DIR__ . '/data/tideways.ini';

	public function testStrippedExtensionLinesAreCommentedOut(): void
	{
		$content = IniComposer::composeContent([self::MAIN_INI], ['xdebug'], [], []);
		$this->assertNotNull($content);

		$this->assertStringContainsString(';zend_extension = /usr/lib/php/20220829/xdebug.so', $content);
		$this->assertStringNotContainsString("\nzend_extension = /usr/lib/php/20220829/xdebug.so", $content);
		// the other extension lines are untouched
		$this->assertStringContainsString("\nextension=pcov.so", $content);
	}

	public function testStrippingLeavesAnExtensionWhoseNameMerelyStartsTheSame(): void
	{
		$content = IniComposer::composeContent([self::TIDEWAYS_INI], ['tideways'], [], ['tideways.enable_cli' => '1']);
		$this->assertNotNull($content);

		$this->assertStringContainsString(';extension=tideways.so', $content);
		$this->assertStringContainsString("\nextension=tideways_xhprof.so", $content);
	}

	public function testStrippingIsCaseInsensitiveAndCoversBothExtensionForms(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			['xdebug', 'pcov'],
			[],
			[],
		);
		$this->assertNotNull($content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertArrayNotHasKey('zend_extension', $parsed);
		$this->assertArrayNotHasKey('extension', $parsed);
	}

	public function testFlaggedSettingsWinOverTheIniFilesAndTheRuntimeDiff(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			[],
			['pcov.enabled' => '0'],
			['pcov.enabled' => '1'],
		);
		$this->assertNotNull($content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertSame('0', $parsed['pcov.enabled']);
	}

	public function testOpcacheSettingsPassThroughVerbatim(): void
	{
		$content = IniComposer::composeContent([self::MAIN_INI], ['xdebug'], ['pcov.enabled' => '0'], []);
		$this->assertNotNull($content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertSame('1', $parsed['opcache.enable_cli']);
		$this->assertSame('tracing', $parsed['opcache.jit']);
		$this->assertStringNotContainsString('opcache.enable_cli=0', $content);
	}

	public function testRuntimeOverridesSurviveWithoutArgvReconstruction(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			[],
			[],
			// what `php -d memory_limit=2G -d sys_temp_dir='tmp~1'` leaves behind
			['memory_limit' => '2G', 'sys_temp_dir' => 'tmp~1'],
		);
		$this->assertNotNull($content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertSame('2G', $parsed['memory_limit']);
		$this->assertSame('tmp~1', $parsed['sys_temp_dir']);
	}

	public function testRuntimeSettingsEqualToTheIniFilesAreNotRepeated(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			[],
			[],
			['memory_limit' => '128M'],
		);
		$this->assertNotNull($content);

		$this->assertStringNotContainsString('memory_limit="128M"', $content);
	}

	public function testStrippedExtensionsDoNotCarryTheirOwnSettingsOver(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			['xdebug'],
			[],
			['xdebug.mode' => 'debug', 'xdebug.client_port' => '9003'],
		);
		$this->assertNotNull($content);

		$this->assertStringNotContainsString('xdebug.client_port', $content);
		// the line coming from the ini file itself is left as it is, harmlessly:
		// it names a setting no loaded extension owns any more
		$this->assertStringContainsString('xdebug.mode = debug', $content);
	}

	public function testApcMmapFileMaskIsNeverCarriedOver(): void
	{
		$content = IniComposer::composeContent([self::MAIN_INI], [], [], ['apc.mmap_file_mask' => '/tmp/apc.XXXXXX']);
		$this->assertNotNull($content);

		$this->assertStringNotContainsString('apc.mmap_file_mask', $content);
	}

	public function testScannedFilesAreConcatenatedInOrderAndCutAtTheFirstPathSection(): void
	{
		$content = IniComposer::composeContent([self::MAIN_INI, self::SCANNED_INI], [], [], []);
		$this->assertNotNull($content);

		$this->assertStringContainsString('extension = /usr/lib/php/20220829/intl.so', $content);
		// directives after [PATH=] only apply to that path
		$this->assertStringNotContainsString('[PATH=/var/www/other]', $content);
		$this->assertStringNotContainsString('memory_limit = 1G', $content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertSame('128M', $parsed['memory_limit']);
	}

	public function testAnUnreadableIniFileGivesUpRatherThanComposingAPartialFile(): void
	{
		$this->assertNull(IniComposer::composeContent([__DIR__ . '/data/does-not-exist.ini'], [], [], []));
	}

	public function testNonStringRuntimeSettingsAreSkipped(): void
	{
		$content = IniComposer::composeContent([self::MAIN_INI], [], [], ['error_prepend_string' => null]);
		$this->assertNotNull($content);

		$this->assertStringNotContainsString('error_prepend_string', $content);
	}

	public function testValuesAreQuotedAndEscaped(): void
	{
		$content = IniComposer::composeContent(
			[self::MAIN_INI],
			[],
			[],
			['include_path' => '.:/usr/share/"php"'],
		);
		$this->assertNotNull($content);

		$parsed = parse_ini_string($content);
		$this->assertNotFalse($parsed);
		$this->assertSame('.:/usr/share/"php"', $parsed['include_path']);
	}

	public function testComposeWritesAReadableFileForTheCurrentProcess(): void
	{
		$file = IniComposer::compose(['xdebug'], ['pcov.enabled' => '0']);
		$this->assertNotNull($file);

		try {
			$this->assertTrue(is_file($file));

			$contents = file_get_contents($file);
			$this->assertNotFalse($contents);

			$parsed = parse_ini_string($contents);
			$this->assertNotFalse($parsed);
			$this->assertSame('0', $parsed['pcov.enabled']);
			// the runtime diff always carries the current limit over, so the
			// restarted process does not fall back to the compiled-in default
			$this->assertSame(ini_get('memory_limit'), $parsed['memory_limit']);
		} finally {
			unlink($file);
		}
	}

}
