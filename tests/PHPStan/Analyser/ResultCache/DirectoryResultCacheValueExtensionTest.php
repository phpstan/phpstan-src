<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use PHPStan\Testing\PHPStanTestCase;
use function file_put_contents;
use function mkdir;
use function rename;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class DirectoryResultCacheValueExtensionTest extends PHPStanTestCase
{

	public function testValueFollowsTheMatchingFiles(): void
	{
		$directory = sys_get_temp_dir() . '/phpstan-directory-value-' . uniqid();
		mkdir($directory . '/sub', 0777, true);
		file_put_contents($directory . '/a.php', '<?php');
		file_put_contents($directory . '/sub/b.php', '<?php');

		$extension = self::getContainer()->getByType(DirectoryResultCacheValueExtension::class);
		$php = DirectoryResultCacheValueExtension::createKey($directory, '*.php');
		$all = DirectoryResultCacheValueExtension::createKey($directory, '*');
		$value = $extension->getValue($php);
		$allValue = $extension->getValue($all);

		file_put_contents($directory . '/sub/b.php', '<?php // changed');
		$changed = $extension->getValue($php);
		$this->assertNotSame($value, $changed);

		file_put_contents($directory . '/sub/b.php', '<?php');
		$this->assertSame($value, $extension->getValue($php));

		file_put_contents($directory . '/notes.txt', 'a file the pattern does not match');
		$this->assertSame($value, $extension->getValue($php));
		$this->assertNotSame($allValue, $extension->getValue($all));

		file_put_contents($directory . '/sub/c.php', '<?php');
		$withC = $extension->getValue($php);
		$this->assertNotSame($value, $withC);

		rename($directory . '/sub/c.php', $directory . '/sub/d.php');
		$this->assertNotSame($withC, $extension->getValue($php));

		unlink($directory . '/sub/d.php');
		$this->assertSame($value, $extension->getValue($php));

		$this->assertSame('missing', $extension->getValue(DirectoryResultCacheValueExtension::createKey($directory . '/nope', '*.php')));
	}

	public function testKeyIsStoredRelative(): void
	{
		$extension = self::getContainer()->getByType(DirectoryResultCacheValueExtension::class);
		$key = DirectoryResultCacheValueExtension::createKey(__DIR__ . '/data', '*.php');

		$this->assertSame($key, $extension->keyFromResultCache($extension->keyToResultCache($key)));
	}

}
