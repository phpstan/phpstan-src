<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Ignore;

use Generator;
use Override;
use PHPStan\Analyser\FixedErrorDiff;
use PHPStan\Dependency\PackageDependencyResolver;
use PHPStan\File\FileHelper;
use PHPStan\Fixable\Patcher;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class IgnoreErrorsConfigFixerTest extends PHPStanTestCase
{

	private const BASELINE_START = "<?php declare(strict_types = 1);\n\n\$ignoreErrors = [];\n";

	private const BASELINE_END = "\nreturn ['parameters' => ['ignoreErrors' => \$ignoreErrors]];\n";

	public static function dataRemoveUnmatched(): Generator
	{
		yield 'entry between other entries' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 1,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^B$#',\n\t'count' => 2,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^C$#',\n\t'count' => 1,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^B$#', 'count' => 2, 'path' => '/Foo.php'],
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 1,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^C$#',\n\t'count' => 1,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
		];

		yield 'same message in another path is kept' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Bar.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^A$#', 'path' => '/Foo.php'],
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Bar.php',\n];\n"
			. self::BASELINE_END,
		];

		yield 'entry with an identifier is told apart from one without it' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'identifier' => 'foo.bar',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^A$#', 'identifier' => 'foo.bar', 'path' => '/Foo.php'],
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
		];

		yield 'escaped percent sign in the message' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^100%% sure$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^100% sure$#', 'path' => '/Foo.php'],
			self::BASELINE_START . self::BASELINE_END,
		];

		yield 'duplicate entries merged into one are all removed' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^B$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^A$#', 'count' => 2, 'path' => '/Foo.php'],
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^B$#',\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
		];

		yield 'middle item of a multi-line list' => [
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'messages' => [\n\t\t\t'#^A$#',\n\t\t\t'#^B$#',\n\t\t\t'#^C$#',\n\t\t],\n\t\t'path' => __DIR__ . '/Foo.php',\n\t],\n]]];\n",
			['message' => '#^B$#', 'path' => '/Foo.php'],
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'messages' => [\n\t\t\t'#^A$#',\n\t\t\t'#^C$#',\n\t\t],\n\t\t'path' => __DIR__ . '/Foo.php',\n\t],\n]]];\n",
		];

		yield 'first item of a one-line list' => [
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['messages' => ['#^A$#', '#^B$#'], 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
			['message' => '#^A$#', 'path' => '/Foo.php'],
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['messages' => ['#^B$#'], 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
		];

		yield 'last item of a one-line list' => [
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['messages' => ['#^A$#', '#^B$#'], 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
			['message' => '#^B$#', 'path' => '/Foo.php'],
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['messages' => ['#^A$#'], 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
		];

		yield 'only item of a list removes the entry' => [
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['identifiers' => ['foo.bar'], 'path' => __DIR__ . '/Foo.php'],\n\t['identifier' => 'baz.qux', 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
			['identifier' => 'foo.bar', 'path' => '/Foo.php'],
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['identifier' => 'baz.qux', 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
		];

		yield 'last entry of a list without a trailing comma' => [
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'message' => '#^A$#',\n\t\t'path' => __DIR__ . '/Foo.php',\n\t],\n\t[\n\t\t'message' => '#^B$#',\n\t\t'path' => __DIR__ . '/Foo.php',\n\t]\n]]];\n",
			['message' => '#^B$#', 'path' => '/Foo.php'],
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'message' => '#^A$#',\n\t\t'path' => __DIR__ . '/Foo.php',\n\t]\n]]];\n",
		];
	}

	/**
	 * @param array{message?: string, identifier?: string, count?: int, path: string} $ignoreError path relative to this directory
	 */
	#[DataProvider('dataRemoveUnmatched')]
	public function testRemoveUnmatched(string $code, array $ignoreError, string $expected): void
	{
		[$fixer, $file] = $this->createFixer($code);
		$ignoreError['path'] = __DIR__ . $ignoreError['path'];
		$ignoreError['realPath'] = $ignoreError['path'];

		$this->assertSame($expected, $this->apply($file, $code, $fixer->removeUnmatched($ignoreError)));
	}

	public function testRemovingAllItemsOfListTakesTwoRounds(): void
	{
		$code = "<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'messages' => [\n\t\t\t'#^A$#',\n\t\t\t'#^B$#',\n\t\t],\n\t\t'path' => __DIR__ . '/Foo.php',\n\t],\n\t['message' => '#^C$#', 'path' => __DIR__ . '/Foo.php'],\n]]];\n";
		[$fixer, $file] = $this->createFixer($code);
		$path = __DIR__ . '/Foo.php';
		$patcher = self::getContainer()->getByType(Patcher::class);

		$result = $patcher->apply([$file => $code], [
			$fixer->removeUnmatched(['message' => '#^A$#', 'path' => $path, 'realPath' => $path]),
			$fixer->removeUnmatched(['message' => '#^B$#', 'path' => $path, 'realPath' => $path]),
		]);
		$this->assertSame(1, $result['appliedCount']);
		$this->assertSame(
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t[\n\t\t'messages' => [\n\t\t\t'#^B$#',\n\t\t],\n\t\t'path' => __DIR__ . '/Foo.php',\n\t],\n\t['message' => '#^C$#', 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
			$result['contents'][$file],
		);

		// the next round
		$code = $result['contents'][$file];
		[$fixer, $file] = $this->createFixer($code);
		$this->assertSame(
			"<?php\nreturn ['parameters' => ['ignoreErrors' => [\n\t['message' => '#^C$#', 'path' => __DIR__ . '/Foo.php'],\n]]];\n",
			$this->apply($file, $code, $fixer->removeUnmatched(['message' => '#^B$#', 'path' => $path, 'realPath' => $path])),
		);
	}

	public function testLowerCount(): void
	{
		$code = self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 5,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END;
		[$fixer, $file] = $this->createFixer($code);
		$path = __DIR__ . '/Foo.php';

		$this->assertSame(
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 2,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			$this->apply($file, $code, $fixer->lowerCount(['message' => '#^A$#', 'count' => 5, 'path' => $path, 'realPath' => $path], 2)),
		);
	}

	public static function dataNotFixable(): Generator
	{
		// the merged count is spread over the entries, it is not clear which one to lower
		yield 'count of duplicate entries' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 2,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 3,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^A$#', 'count' => 5, 'path' => '/Foo.php'],
			true,
		];

		// the rest of the count comes from an entry in a config file that is not fixed, like a NEON file
		yield 'count of an entry merged with an entry in another file' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 2,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^A$#', 'count' => 3, 'path' => '/Foo.php'],
			true,
		];

		yield 'count of an entry that is not in the file' => [
			self::BASELINE_START
			. "\$ignoreErrors[] = [\n\t'message' => '#^A$#',\n\t'count' => 2,\n\t'path' => __DIR__ . '/Foo.php',\n];\n"
			. self::BASELINE_END,
			['message' => '#^B$#', 'count' => 2, 'path' => '/Foo.php'],
			true,
		];

		yield 'entry in a variable is not removed on its own' => [
			"<?php\n\$entry = ['message' => '#^A$#', 'path' => __DIR__ . '/Foo.php'];\nreturn ['parameters' => ['ignoreErrors' => [\$entry]]];\n",
			['message' => '#^A$#', 'path' => '/Foo.php'],
			false,
		];
	}

	/**
	 * @param array{message?: string, identifier?: string, count?: int, path: string} $ignoreError path relative to this directory
	 */
	#[DataProvider('dataNotFixable')]
	public function testNotFixable(string $code, array $ignoreError, bool $lowerCount): void
	{
		[$fixer] = $this->createFixer($code);
		$ignoreError['path'] = __DIR__ . $ignoreError['path'];
		$ignoreError['realPath'] = $ignoreError['path'];

		$this->assertSame([], $lowerCount ? $fixer->lowerCount($ignoreError, 1) : $fixer->removeUnmatched($ignoreError));
	}

	public function testNeonIsNotFixed(): void
	{
		$file = __DIR__ . '/' . uniqid('baseline-') . '.neon';
		file_put_contents($file, "parameters:\n\tignoreErrors:\n\t\t-\n\t\t\tmessage: '#^A$#'\n\t\t\tpath: Foo.php\n");
		try {
			$fixer = $this->createFixerForFiles([$file]);
			$path = __DIR__ . '/Foo.php';
			$this->assertSame([], $fixer->removeUnmatched(['message' => '#^A$#', 'path' => $path, 'realPath' => $path]));
		} finally {
			@unlink($file);
		}
	}

	/**
	 * The config file sits next to this test, so __DIR__ . '/Foo.php' in it is __DIR__ . '/Foo.php' here.
	 *
	 * @return array{IgnoreErrorsConfigFixer, string}
	 */
	private function createFixer(string $code): array
	{
		$file = __DIR__ . '/' . uniqid('baseline-') . '.php';
		file_put_contents($file, $code);
		$this->filesToDelete[] = $file;

		return [$this->createFixerForFiles([$file]), $file];
	}

	/** @var list<string> */
	private array $filesToDelete = [];

	#[Override]
	protected function tearDown(): void
	{
		foreach ($this->filesToDelete as $file) {
			@unlink($file);
		}
		$this->filesToDelete = [];
		parent::tearDown();
	}

	/**
	 * @param list<string> $configFiles
	 */
	private function createFixerForFiles(array $configFiles): IgnoreErrorsConfigFixer
	{
		$container = self::getContainer();

		return new IgnoreErrorsConfigFixer(
			$container->getService('currentPhpVersionPhpParser'),
			$container->getByType(PackageDependencyResolver::class),
			$container->getByType(FileHelper::class),
			$configFiles,
			sys_get_temp_dir(),
			__DIR__,
		);
	}

	/**
	 * @param list<FixedErrorDiff> $diffs
	 */
	private function apply(string $file, string $code, array $diffs): string
	{
		$this->assertCount(1, $diffs);
		$result = self::getContainer()->getByType(Patcher::class)->apply([$file => $code], [$diffs]);
		$this->assertSame(1, $result['appliedCount']);

		return $result['contents'][$file];
	}

}
