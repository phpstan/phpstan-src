<?php declare(strict_types = 1);

namespace PHPStan\RobotLoader;

use Nette;
use SplFileInfo;
use const DIRECTORY_SEPARATOR;

class RobotLoader
{

	use Nette\SmartObject;

	private const RETRY_LIMIT = 3;

	/** @var string[] */
	public $ignoreDirs = ['.*', '*.old', '*.bak', '*.tmp', 'temp'];

	/** @var string[] */
	public $acceptFiles = ['*.php'];

	/** @var bool */
	private $autoRebuild = true;

	/** @var bool */
	private $reportParseErrors = true;

	/** @var string[] */
	private $scanPaths = [];

	/** @var string[] */
	private $excludeDirs = [];

	/** @var array<string, array{file: string, time: int}> */
	private $classes = [];

	/** @var bool */
	private $refreshed = false;

	/** @var array<string, int> */
	private $missing = [];

	/** @var string|null */
	private $tempDirectory;

	public function __construct()
	{
		if (!extension_loaded('tokenizer')) {
			throw new \Nette\NotSupportedException('PHP extension Tokenizer is not loaded.');
		}
	}

	/**
	 * Register autoloader.
	 */
	public function register(bool $prepend = false): self
	{
		$this->loadCache();
		spl_autoload_register([$this, 'tryLoad'], true, $prepend);
		return $this;
	}

	/**
	 * Handles autoloading of classes, interfaces or traits.
	 */
	public function tryLoad(string $type): void
	{
		$type = ltrim($type, '\\'); // PHP namespace bug #49143
		$info = $this->classes[$type] ?? null;

		if ($this->autoRebuild) {
			if ($info === null || !is_file($info['file'])) {
				$missing = &$this->missing[$type];
				$missing++;
				if (!$this->refreshed && $missing <= self::RETRY_LIMIT) {
					$this->refreshClasses();
					$this->saveCache();
				} elseif ($info !== null) {
					unset($this->classes[$type]);
					$this->saveCache();
				}

			} elseif (!$this->refreshed && filemtime($info['file']) !== $info['time']) {
				$this->updateFile($info['file']);
				if (empty($this->classes[$type])) {
					$this->missing[$type] = 0;
				}
				$this->saveCache();
			}
			$info = $this->classes[$type] ?? null;
		}

		if (!$info) {
			return;
		}

		(static function ($file): void {
			require $file;
		})($info['file']);
	}

	/**
	 * Add path or paths to list.
	 * @param  string  ...$paths  absolute path
	 */
	public function addDirectory(string ...$paths): self
	{
		$this->scanPaths = array_merge($this->scanPaths, $paths);
		return $this;
	}

	public function reportParseErrors(bool $on = true): self
	{
		$this->reportParseErrors = $on;
		return $this;
	}

	/**
	 * Excludes path or paths from list.
	 * @param  string  ...$paths  absolute path
	 */
	public function excludeDirectory(string ...$paths): self
	{
		$this->excludeDirs = array_merge($this->excludeDirs, $paths);
		return $this;
	}

	/**
	 * @return array<string, string>
	 */
	public function getIndexedClasses(): array
	{
		$res = [];
		foreach ($this->classes as $class => $info) {
			$res[$class] = $info['file'];
		}
		return $res;
	}

	/**
	 * Rebuilds class list cache.
	 */
	public function rebuild(): void
	{
		$this->classes = $this->missing = [];
		$this->refreshClasses();
		if (!$this->tempDirectory) {
			return;
		}

		$this->saveCache();
	}

	/**
	 * Refreshes class list cache.
	 */
	public function refresh(): void
	{
		$this->loadCache();
		if ($this->refreshed) {
			return;
		}

		$this->refreshClasses();
		$this->saveCache();
	}

	/**
	 * Refreshes $classes.
	 */
	private function refreshClasses(): void
	{
		$this->refreshed = true; // prevents calling refreshClasses() or updateFile() in tryLoad()
		$files = [];
		foreach ($this->classes as $class => $info) {
			$files[$info['file']]['time'] = $info['time'];
			$files[$info['file']]['classes'][] = $class;
		}

		$this->classes = [];
		foreach ($this->scanPaths as $path) {
			$iterator = is_file($path) ? [new SplFileInfo($path)] : $this->createFileIterator($path);
			foreach ($iterator as $file) {
				$file = $file->getPathname();
				if (isset($files[$file]) && $files[$file]['time'] === filemtime($file)) {
					$classes = $files[$file]['classes'];
				} else {
					$classes = $this->scanPhp($file);
				}
				$files[$file] = ['classes' => [], 'time' => filemtime($file)];

				foreach ($classes as $class) {
					$info = &$this->classes[$class];
					if (isset($info['file'])) {
						throw new \Nette\InvalidStateException(sprintf('Ambiguous class %s resolution; defined in %s and in %s.', $class, $info['file'], $file));
					}
					$info = ['file' => $file, 'time' => filemtime($file)];
					unset($this->missing[$class]);
				}
			}
		}
	}

	/**
	 * Creates an iterator scaning directory for PHP files, subdirectories and 'netterobots.txt' files.
	 * @throws Nette\IOException if path is not found
	 * @return Nette\Utils\Finder<SplFileInfo>
	 */
	private function createFileIterator(string $dir): Nette\Utils\Finder
	{
		if (!is_dir($dir)) {
			throw new \Nette\IOException(sprintf('File or directory %s not found.', $dir));
		}

		$ignoreDirs = $this->ignoreDirs;
		$disallow = [];
		foreach (array_merge($ignoreDirs, $this->excludeDirs) as $item) {
			$item = realpath($item);
			if ($item === false) {
				continue;
			}

			$disallow[str_replace('\\', '/', $item)] = true;
		}

		$acceptFiles = $this->acceptFiles;
		$filter = static function (SplFileInfo $dir) use (&$disallow): bool {
			/** @var string $realPath */
			$realPath = $dir->getRealPath();
			$path = str_replace('\\', '/', $realPath);
			if (is_file(sprintf('%s/netterobots.txt', $path))) {
				foreach (file(sprintf('%s/netterobots.txt', $path)) as $s) {
					if (!preg_match('#^(?:disallow\\s*:)?\\s*(\\S+)#i', $s, $matches)) {
						continue;
					}

					$disallow[$path . rtrim('/' . ltrim($matches[1], '/'), '/')] = true;
				}
			}
			return !isset($disallow[$path]);
		};
		$iterator = Nette\Utils\Finder::findFiles($acceptFiles)
			->filter(static function (SplFileInfo $file) use (&$disallow): bool {
				return !isset($disallow[str_replace('\\', '/', $file->getRealPath())]);
			})
			->from($dir)
			->exclude($ignoreDirs)
			->filter($filter);

		$filter(new SplFileInfo($dir));
		return $iterator;
	}

	private function updateFile(string $file): void
	{
		foreach ($this->classes as $class => $info) {
			if (!isset($info['file']) || $info['file'] !== $file) {
				continue;
			}

			unset($this->classes[$class]);
		}

		$classes = is_file($file) ? $this->scanPhp($file) : [];
		foreach ($classes as $class) {
			$info = &$this->classes[$class];
			if (isset($info['file']) && @filemtime($info['file']) !== $info['time']) { // @ file may not exists
				$this->updateFile($info['file']);
				$info = &$this->classes[$class];
			}
			if (isset($info['file'])) {
				throw new \Nette\InvalidStateException(sprintf('Ambiguous class %s resolution; defined in %s and in %s.', $class, $info['file'], $file));
			}
			$info = ['file' => $file, 'time' => filemtime($file)];
		}
	}

	/**
	 * Searches classes, interfaces and traits in PHP file.
	 * @return string[]
	 */
	private function scanPhp(string $file): array
	{
		$code = file_get_contents($file);
		$expected = false;
		$namespace = $name = '';
		$level = $minLevel = 0;
		$classes = [];

		try {
			$tokens = token_get_all($code, TOKEN_PARSE);
		} catch (\ParseError $e) {
			if ($this->reportParseErrors) {
				$rp = new \ReflectionProperty($e, 'file');
				$rp->setAccessible(true);
				$rp->setValue($e, $file);
				throw $e;
			}
			$tokens = [];
		}

		foreach ($tokens as $token) {
			if (is_array($token)) {
				switch ($token[0]) {
					case T_COMMENT:
					case T_DOC_COMMENT:
					case T_WHITESPACE:
						continue 2;

					case T_NS_SEPARATOR:
					case T_STRING:
						if ($expected) {
							$name .= $token[1];
						}
						continue 2;

					case T_NAMESPACE:
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						$expected = $token[0];
						$name = '';
						continue 2;
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
						$level++;
				}
			}

			if ($expected) {
				switch ($expected) {
					case T_CLASS:
					case T_INTERFACE:
					case T_TRAIT:
						if ($name && $level === $minLevel) {
							$classes[] = $namespace . $name;
						}
						break;

					case T_NAMESPACE:
						$namespace = $name ? $name . '\\' : '';
						$minLevel = $token === '{' ? 1 : 0;
				}

				$expected = null;
			}

			if ($token === '{') {
				$level++;
			} elseif ($token === '}') {
				$level--;
			}
		}
		return $classes;
	}

	/********************* caching ****************d*g**/

	/**
	 * Sets auto-refresh mode.
	 */
	public function setAutoRefresh(bool $on = true): self
	{
		$this->autoRebuild = $on;
		return $this;
	}

	/**
	 * Sets path to temporary directory.
	 */
	public function setTempDirectory(string $dir): self
	{
		Nette\Utils\FileSystem::createDir($dir);
		$this->tempDirectory = $dir;
		return $this;
	}

	/**
	 * Loads class list from cache.
	 */
	private function loadCache(): void
	{
		$file = $this->getCacheFile();
		[$this->classes, $this->missing] = @include $file; // @ file may not exist
		if (is_array($this->classes)) {
			return;
		}

		$handle = fopen(sprintf('%s.lock', $file), 'cb+');
		if (!$handle || !flock($handle, LOCK_EX)) {
			throw new \RuntimeException(sprintf('Unable to create or acquire exclusive lock on file %s.lock.', $file));
		}

		[$this->classes, $this->missing] = @include $file; // @ file may not exist
		if (!is_array($this->classes)) {
			$this->rebuild();
		}

		flock($handle, LOCK_UN);
		fclose($handle);
		@unlink(sprintf('%s.lock', $file)); // @ file may become locked on Windows
	}

	/**
	 * Writes class list to cache.
	 */
	private function saveCache(): void
	{
		$file = $this->getCacheFile();
		$tempFile = $file . uniqid('', true) . '.tmp';
		$code = "<?php\nreturn " . var_export([$this->classes, $this->missing], true) . ";\n";
		if (file_put_contents($tempFile, $code) !== strlen($code) || !rename($tempFile, $file)) {
			@unlink($tempFile); // @ - file may not exist

			if (DIRECTORY_SEPARATOR === '/') {
				throw new \RuntimeException(sprintf('Unable to create %s.', $file));
			}
		}
		if (!function_exists('opcache_invalidate')) {
			return;
		}

		@opcache_invalidate($file, true); // @ can be restricted
	}

	private function getCacheFile(): string
	{
		if ($this->tempDirectory === null) {
			throw new \LogicException('Set path to temporary directory using setTempDirectory().');
		}
		return $this->tempDirectory . '/' . md5(serialize($this->getCacheKey())) . '.php';
	}

	/**
	 * @return mixed[]
	 */
	protected function getCacheKey(): array
	{
		return [$this->ignoreDirs, $this->acceptFiles, $this->scanPaths, $this->excludeDirs];
	}

}
