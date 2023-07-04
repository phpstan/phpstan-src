<?php declare(strict_types = 1);

namespace PHPStan\File;

use Closure;
use Exception;
use PHPStan\Testing\PHPStanTestCase;
use function array_keys;
use function array_map;
use function array_shift;
use function assert;
use function explode;
use function get_class;
use function is_array;
use function is_bool;
use function parse_url;
use function preg_replace;
use function sort;
use function str_replace;
use function stream_wrapper_register;
use function stream_wrapper_unregister;
use const DIRECTORY_SEPARATOR;

class FileFinderTest extends PHPStanTestCase
{

	private static int $vfsNextIndex = 0;

	/** @var array<string, Closure(string $path, 'list_dir_open'|'list_dir_rewind'|'is_dir' $op): (list<string>|bool)> */
	public static array $vfsProviders = [];

	private string $vfsScheme;

	/** @var list<array{string, string, mixed}> */
	private array $vfsLog = [];

	public function setUp(): void
	{
		$this->vfsScheme = 'phpstan-file-finder-test-' . ++self::$vfsNextIndex;

		$vfsWrapperClass = get_class(new class() {

			/** @var resource */
			public $context;

			private string $scheme;

			private string $dirPath;

			/** @var list<string> */
			private array $dirData;

			private function parsePathAndSetScheme(string $url): string
			{
				$urlArr = parse_url($url);
				assert(is_array($urlArr));
				assert(isset($urlArr['scheme']));
				assert(isset($urlArr['host']));

				$this->scheme = $urlArr['scheme'];

				return str_replace(DIRECTORY_SEPARATOR, '/', $urlArr['host'] . ($urlArr['path'] ?? ''));
			}

			public function processListDir(bool $fromRewind): bool
			{
				$providerFx = FileFinderTest::$vfsProviders[$this->scheme];
				$data = $providerFx($this->dirPath, 'list_dir' . ($fromRewind ? '_rewind' : '_open'));
				assert(is_array($data));
				$this->dirData = $data;

				return true;
			}

			public function dir_opendir(string $url): bool
			{
				$this->dirPath = $this->parsePathAndSetScheme($url);

				return $this->processListDir(false);
			}

			public function dir_readdir(): string|false
			{
				return array_shift($this->dirData) ?? false;
			}

			public function dir_closedir(): bool
			{
				unset($this->dirPath);
				unset($this->dirData);

				return true;
			}

			public function dir_rewinddir(): bool
			{
				return $this->processListDir(true);
			}

			/**
			 * @return array<string, mixed>
			 */
			public function stream_stat(): array
			{
				return [];
			}

			/**
			 * @return array<string, mixed>
			 */
			public function url_stat(string $url): array
			{
				$path = $this->parsePathAndSetScheme($url);
				$providerFx = FileFinderTest::$vfsProviders[$this->scheme];
				$isDir = $providerFx($path, 'is_dir');
				assert(is_bool($isDir));

				return ['mode' => $isDir ? 0040755 : 0100644];
			}

		});

		stream_wrapper_register($this->vfsScheme, $vfsWrapperClass);
	}

	public function tearDown(): void
	{
		stream_wrapper_unregister($this->vfsScheme);
	}

	/**
	 * @dataProvider dataTestFindFiles
	 * @param array<string, mixed>               $fsDefinition
	 * @param list<string>                       $inPaths
	 * @param list<string>                       $excludePaths
	 * @param list<string>                       $expectedPaths
	 * @param list<array{string, string, mixed}> $expectedAccessLog
	 */
	public function testFindFiles(array $fsDefinition, array $inPaths, array $excludePaths, array $expectedPaths, array $expectedAccessLog): void
	{
		self::$vfsProviders[$this->vfsScheme] = function (string $path, string $op) use ($fsDefinition) {
			$pathArr = explode('/', $path);
			$fileEntry = $fsDefinition;
			while (($name = array_shift($pathArr)) !== null) {
				if (!isset($fileEntry[$name])) {
					$fileEntry = false;

					break;
				}

				$fileEntry = $fileEntry[$name];
			}

			if ($op === 'list_dir_open' || $op === 'list_dir_rewind') {
				/** @var list<string> $res */
				$res = array_keys($fileEntry);
			} elseif ($op === 'is_dir') {
				$res = is_array($fileEntry);
			} else {
				throw new Exception('Unexpected operation type');
			}

			$this->vfsLog[] = [$path, $op, $res];

			return $res;
		};

		$prependSchemeFx = fn (string $v): string => $this->vfsScheme . '://' . $v;
		$stripSchemeFx = fn (string $v): string => preg_replace('~^' . $this->vfsScheme . '://~', '', $v) ?? '';

		$fileHelper = $this->getFileHelper();
		$fileExcluder = new FileExcluder($fileHelper, array_map($prependSchemeFx, $excludePaths));
		$fileFinder = new FileFinder($fileExcluder, $fileHelper, ['php', 'p']);
		$fileFinderResult = $fileFinder->findFiles(array_map($prependSchemeFx, $inPaths));

		$expected = str_replace('/', DIRECTORY_SEPARATOR, $expectedPaths);
		$actual = array_map($stripSchemeFx, $fileFinderResult->getFiles());
		sort($expected);
		sort($actual);

		$this->assertSame($expected, $actual);
		$this->assertSame($expectedAccessLog, $this->vfsLog);
	}

	public function dataTestFindFiles(): iterable
	{
		yield 'basic' => [
			[
				'x' => [
					'a.txt' => '',
					'b.php' => '',
					'c.php' => '',
					'd' => [
						'u.php' => '',
					],
				],
				'y' => [
					'c.php' => '',
				],
			],
			['x'],
			[],
			['x/b.php', 'x/c.php', 'x/d/u.php'],
			[
				['x', 'is_dir', true],
				['x', 'list_dir_open', ['a.txt', 'b.php', 'c.php', 'd']],
				['x', 'list_dir_open', ['a.txt', 'b.php', 'c.php', 'd']], // directory should be opened once only, remove once https://github.com/symfony/symfony/issues/50851 is fixed
				['x', 'list_dir_rewind', ['a.txt', 'b.php', 'c.php', 'd']],
				['x/a.txt', 'is_dir', false],
				['x/b.php', 'is_dir', false],
				['x/c.php', 'is_dir', false],
				['x/d', 'is_dir', true],
				['x/d', 'list_dir_open', ['u.php']],
				['x/d', 'list_dir_rewind', ['u.php']],
				['x/d/u.php', 'is_dir', false],
			],
		];

		yield 'excluded directory must prune early' => [
			[
				'x' => [
					'a.txt' => '',
					'b.php' => '',
					'c.php' => '',
					'd' => [
						'u.php' => '',
					],
					'x' => [
						'd' => [
							'u2.php' => '',
						],
					],
				],
				'y' => [
					'c.php' => '',
				],
			],
			['x'],
			['x/d'],
			// ['x/b.php', 'x/c.php' , 'x/x/d/u2.php'],
			// x/d exclude should keep x/x/d, but that is currently not possible,
			// as Symfony Finder impl. escapes all patterns in
			// https://github.com/symfony/symfony/blob/v6.2.12/src/Symfony/Component/Finder/Iterator/ExcludeDirectoryFilterIterator.php#L45
			// so the generated exclude pattern cannot start with "^"
			['x/b.php', 'x/c.php'],
			[
				['x', 'is_dir', true],
				['x', 'list_dir_open', ['a.txt', 'b.php', 'c.php', 'd', 'x']],
				['x', 'list_dir_open', ['a.txt', 'b.php', 'c.php', 'd', 'x']],
				['x', 'list_dir_rewind', ['a.txt', 'b.php', 'c.php', 'd', 'x']],
				['x/a.txt', 'is_dir', false],
				['x/b.php', 'is_dir', false],
				['x/c.php', 'is_dir', false],
				['x/d', 'is_dir', true],
				['x/x', 'is_dir', true],
				['x/x', 'list_dir_open', ['d']],
				['x/x', 'list_dir_rewind', ['d']],
				['x/x/d', 'is_dir', true],
			],
		];
	}

}
