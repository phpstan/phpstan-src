<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Console;

use Exception;
use PHPStan\Compiler\Filesystem\Filesystem;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use function chdir;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function json_decode;
use function json_encode;
use function realpath;
use function rename;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;
use function unlink;
use function var_export;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

final class PrepareCommand extends Command
{

	public function __construct(
		private Filesystem $filesystem,
		private string $buildDir,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('prepare')
			->setDescription('Prepare PHAR');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->buildPreloadScript();
		$this->deleteUnnecessaryVendorCode();
		$this->fixComposerJson($this->buildDir);
		$this->renamePhpStormStubs();
		$this->renamePhp8Stubs();
		$this->transformSource();
		return 0;
	}

	private function fixComposerJson(string $buildDir): void
	{
		$json = json_decode($this->filesystem->read($buildDir . '/composer.json'), true);

		unset($json['replace']);
		$json['name'] = 'phpstan/phpstan';
		$json['require']['php'] = '^7.2';

		// simplify autoload (remove not packed build directory]
		$json['autoload']['psr-4']['PHPStan\\'] = 'src/';

		$encodedJson = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($encodedJson === false) {
			throw new Exception('json_encode() was not successful.');
		}

		$this->filesystem->write($buildDir . '/composer.json', $encodedJson);
	}

	private function renamePhpStormStubs(): void
	{
		$directory = $this->buildDir . '/vendor/jetbrains/phpstorm-stubs';
		if (!is_dir($directory)) {
			return;
		}

		$stubFinder = Finder::create();
		$stubsMapPath = realpath($directory . '/PhpStormStubsMap.php');
		if ($stubsMapPath === false) {
			throw new Exception('realpath() failed');
		}
		foreach ($stubFinder->files()->name('*.php')->in($directory) as $stubFile) {
			$path = $stubFile->getPathname();
			if ($path === $stubsMapPath) {
				continue;
			}

			$renameSuccess = rename(
				$path,
				dirname($path) . '/' . $stubFile->getBasename('.php') . '.stub',
			);
			if ($renameSuccess === false) {
				throw new ShouldNotHappenException(sprintf('Could not rename %s', $path));
			}
		}

		$stubsMapContents = file_get_contents($stubsMapPath);
		if ($stubsMapContents === false) {
			throw new ShouldNotHappenException(sprintf('Could not read %s', $stubsMapPath));
		}

		$stubsMapContents = str_replace('.php\',', '.stub\',', $stubsMapContents);

		$putSuccess = file_put_contents($stubsMapPath, $stubsMapContents);
		if ($putSuccess === false) {
			throw new ShouldNotHappenException(sprintf('Could not write %s', $stubsMapPath));
		}
	}

	private function renamePhp8Stubs(): void
	{
		$directory = $this->buildDir . '/vendor/phpstan/php-8-stubs/stubs';
		if (!is_dir($directory)) {
			return;
		}

		$stubFinder = Finder::create();
		$stubsMapPath = $directory . '/../Php8StubsMap.php';
		foreach ($stubFinder->files()->name('*.php')->in($directory) as $stubFile) {
			$path = $stubFile->getPathname();
			if ($path === $stubsMapPath) {
				continue;
			}

			$renameSuccess = rename(
				$path,
				dirname($path) . '/' . $stubFile->getBasename('.php') . '.stub',
			);
			if ($renameSuccess === false) {
				throw new ShouldNotHappenException(sprintf('Could not rename %s', $path));
			}
		}

		$stubsMapContents = file_get_contents($stubsMapPath);
		if ($stubsMapContents === false) {
			throw new ShouldNotHappenException(sprintf('Could not read %s', $stubsMapPath));
		}

		$stubsMapContents = str_replace('.php\',', '.stub\',', $stubsMapContents);

		$putSuccess = file_put_contents($stubsMapPath, $stubsMapContents);
		if ($putSuccess === false) {
			throw new ShouldNotHappenException(sprintf('Could not write %s', $stubsMapPath));
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
		$finder = Finder::create();
		$root = realpath(__DIR__ . '/../../..');
		if ($root === false) {
			return;
		}
		$output = '';
		foreach ($finder->files()->name('*.php')->in([
			$this->buildDir . '/src',
			$vendorDir . '/nikic/php-parser/lib/PhpParser',
			$vendorDir . '/phpstan/phpdoc-parser/src',
		])->exclude([
			'Testing',
		]) as $phpFile) {
			$realPath = $phpFile->getRealPath();
			if ($realPath === false) {
				return;
			}
			if (in_array($realPath, [
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Expr/ArrayItem.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Expr/ClosureUse.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Stmt/DeclareDeclare.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Scalar/DNumber.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Scalar/Encapsed.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Scalar/EncapsedStringPart.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Scalar/LNumber.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Stmt/PropertyProperty.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Stmt/StaticVar.php',
				$vendorDir . '/nikic/php-parser/lib/PhpParser/Node/Stmt/UseUse.php',
			], true)) {
				continue;
			}
			$path = substr($realPath, strlen($root));
			$output .= 'require_once __DIR__ . ' . var_export($path, true) . ';' . "\n";
		}

		file_put_contents($preloadScript, sprintf($template, $output));
	}

	private function deleteUnnecessaryVendorCode(): void
	{
		$vendorDir = $this->buildDir . '/vendor';
		if (!is_dir($vendorDir . '/nikic/php-parser')) {
			return;
		}

		@unlink($vendorDir . '/nikic/php-parser/grammar/rebuildParsers.php');
		@unlink($vendorDir . '/nikic/php-parser/bin/php-parse');
	}

	private function transformSource(): void
	{
		chdir(__DIR__ . '/../../..');
		exec(escapeshellarg(__DIR__ . '/../../../vendor/bin/simple-downgrade') . ' downgrade -c ' . escapeshellarg('build/downgrade.php') . ' 7.2', $outputLines, $exitCode);
		if ($exitCode === 0) {
			return;
		}

		throw new ShouldNotHappenException(implode("\n", $outputLines));
	}

}
