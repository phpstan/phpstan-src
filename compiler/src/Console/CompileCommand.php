<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Console;

use PHPStan\Compiler\Filesystem\Filesystem;
use PHPStan\Compiler\Process\ProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommand extends Command
{

	/** @var Filesystem */
	private $filesystem;

	/** @var ProcessFactory */
	private $processFactory;

	/** @var string */
	private $dataDir;

	/** @var string */
	private $buildDir;

	public function __construct(
		Filesystem $filesystem,
		ProcessFactory $processFactory,
		string $dataDir,
		string $buildDir
	)
	{
		parent::__construct();
		$this->filesystem = $filesystem;
		$this->processFactory = $processFactory;
		$this->dataDir = $dataDir;
		$this->buildDir = $buildDir;
	}

	protected function configure(): void
	{
		$this->setName('phpstan:compile')
			->setDescription('Compile PHAR');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->processFactory->setOutput($output);

		$this->buildPreloadScript();
		$this->fixComposerJson($this->buildDir);
		$this->renamePhpStormStubs();

		$this->processFactory->create(['php', 'box.phar', 'compile', '--no-parallel'], $this->dataDir);

		return 0;
	}

	private function fixComposerJson(string $buildDir): void
	{
		$json = json_decode($this->filesystem->read($buildDir . '/composer.json'), true);

		unset($json['replace']);
		$json['name'] = 'phpstan/phpstan';

		// simplify autoload (remove not packed build directory]
		$json['autoload']['psr-4']['PHPStan\\'] = 'src/';

		$encodedJson = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($encodedJson === false) {
			throw new \Exception('json_encode() was not successful.');
		}

		$this->filesystem->write($buildDir . '/composer.json', $encodedJson);
	}

	private function renamePhpStormStubs(): void
	{
		$directory = $this->buildDir . '/vendor/jetbrains/phpstorm-stubs';
		if (!is_dir($directory)) {
			return;
		}

		$stubFinder = \Symfony\Component\Finder\Finder::create();
		$stubsMapPath = $directory . '/PhpStormStubsMap.php';
		foreach ($stubFinder->files()->name('*.php')->in($directory) as $stubFile) {
			$path = $stubFile->getPathname();
			if ($path === $stubsMapPath) {
				continue;
			}

			$renameSuccess = rename(
				$path,
				dirname($path) . '/' . $stubFile->getBasename('.php') . '.stub'
			);
			if ($renameSuccess === false) {
				throw new \PHPStan\ShouldNotHappenException(sprintf('Could not rename %s', $path));
			}
		}

		$stubsMapContents = file_get_contents($stubsMapPath);
		if ($stubsMapContents === false) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Could not read %s', $stubsMapPath));
		}

		$stubsMapContents = str_replace('.php\',', '.stub\',', $stubsMapContents);

		$putSuccess = file_put_contents($directory . '/PhpStormStubsMap.php', $stubsMapContents);
		if ($putSuccess === false) {
			throw new \PHPStan\ShouldNotHappenException(sprintf('Could not write %s', $stubsMapPath));
		}
	}

	private function buildPreloadScript(): void
	{
		$vendorDir = $this->buildDir . '/vendor';
		if (!is_dir($vendorDir . '/nikic/php-parser/lib/PhpParser')) {
			return;
		}

		$preloadScript = $this->buildDir . '/preload.php';
		$template = <<<'php'
<?php declare(strict_types = 1);

%s
php;
		$finder = \Symfony\Component\Finder\Finder::create();
		$root = realpath(__DIR__ . '/../../..');
		if ($root === false) {
			return;
		}
		$output = '';
		foreach ($finder->files()->name('*.php')->in([
			$vendorDir . '/nikic/php-parser/lib/PhpParser',
			$vendorDir . '/phpstan/phpdoc-parser/src',
		]) as $phpFile) {
			$realPath = $phpFile->getRealPath();
			if ($realPath === false) {
				return;
			}
			$path = substr($realPath, strlen($root));
			$output .= 'require_once __DIR__ . ' . var_export($path, true) . ';' . "\n";
		}

		file_put_contents($preloadScript, sprintf($template, $output));
	}

}
