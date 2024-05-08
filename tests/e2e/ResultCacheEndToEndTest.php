<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\SimpleRelativePathHelper;
use PHPUnit\Framework\TestCase;
use function chdir;
use function escapeshellarg;
use function exec;
use function file_put_contents;
use function implode;
use function ksort;
use function sort;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;
use function unlink;
use const DIRECTORY_SEPARATOR;
use const FILE_APPEND;
use const PHP_BINARY;

class ResultCacheEndToEndTest extends TestCase
{

	public function setUp(): void
	{
		chdir(__DIR__ . '/PHP-Parser');

		if (DIRECTORY_SEPARATOR !== '\\') {
			return;
		}

		$baselinePath = __DIR__ . '/baseline.neon';
		$baselineContents = FileReader::read($baselinePath);
		$baselineContents = str_replace('offset 88', 'offset 91', $baselineContents);
		file_put_contents($baselinePath, $baselineContents);
	}

	public function tearDown(): void
	{
		exec(sprintf('git -C %s reset --hard 2>&1', escapeshellarg(__DIR__ . '/PHP-Parser')), $outputLines, $exitCode);
		if ($exitCode === 0) {
			return;
		}

		$this->fail(implode("\n", $outputLines));
	}

	public function testResultCache(): void
	{
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');

		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');

		$lexerPath = __DIR__ . '/PHP-Parser/lib/PhpParser/Lexer.php';
		$lexerCode = FileReader::read($lexerPath);
		$originalLexerCode = $lexerCode;

		$lexerCode = str_replace('@param string $code', '', $lexerCode);
		$lexerCode = str_replace('public function startLexing($code', 'public function startLexing(\\PhpParser\\Node\\Expr\\MethodCall $code', $lexerCode);
		file_put_contents($lexerPath, $lexerCode);

		$errorHandlerPath = __DIR__ . '/PHP-Parser/lib/PhpParser/ErrorHandler.php';
		$errorHandlerContents = FileReader::read($errorHandlerPath);
		$errorHandlerContents .= "\n\n";
		file_put_contents($errorHandlerPath, $errorHandlerContents);

		$bootstrapPath = __DIR__ . '/PHP-Parser/lib/bootstrap.php';
		$originalBootstrapContents = FileReader::read($bootstrapPath);
		file_put_contents($bootstrapPath, "\n\n echo ['foo'];", FILE_APPEND);

		$this->runPhpstanWithErrors();
		$this->runPhpstanWithErrors();

		file_put_contents($lexerPath, $originalLexerCode);

		unlink($bootstrapPath);
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_3.php');

		file_put_contents($bootstrapPath, $originalBootstrapContents);
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');
	}

	private function runPhpstanWithErrors(): void
	{
		$result = $this->runPhpstan(1);
		$this->assertIsArray($result['totals']);
		$this->assertSame(3, $result['totals']['file_errors']);
		$this->assertSame(0, $result['totals']['errors']);

		$fileHelper = new FileHelper(__DIR__);

		$this->assertSame('Parameter #1 $code of function token_get_all expects string, PhpParser\Node\Expr\MethodCall given.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Lexer.php')]['messages'][0]['message']);
		$this->assertSame('Parameter #1 $code of method PhpParser\Lexer::startLexing() expects PhpParser\Node\Expr\MethodCall, string given.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/ParserAbstract.php')]['messages'][0]['message']);
		$this->assertSame('Parameter #1 (array{\'foo\'}) of echo cannot be converted to string.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/bootstrap.php')]['messages'][0]['message']);
		$this->assertResultCache(__DIR__ . '/resultCache_2.php');
	}

	public function testResultCacheDeleteFile(): void
	{
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');

		$serializerPath = __DIR__ . '/PHP-Parser/lib/PhpParser/Serializer.php';
		$serializerCode = FileReader::read($serializerPath);
		$originalSerializerCode = $serializerCode;
		unlink($serializerPath);

		$fileHelper = new FileHelper(__DIR__);

		$result = $this->runPhpstan(1);
		$this->assertIsArray($result['totals']);
		$this->assertSame(1, $result['totals']['file_errors'], Json::encode($result));
		$this->assertSame(0, $result['totals']['errors'], Json::encode($result));

		$message = $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][0]['message'];
		$this->assertSame('Class PhpParser\\Serializer\\XML implements unknown interface PhpParser\\Serializer.', $message);

		file_put_contents($serializerPath, $originalSerializerCode);
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');
	}

	public function testResultCachePath(): void
	{
		$this->runPhpstan(0, __DIR__ . '/phpstan_resultcachepath.neon');

		$this->assertFileExists(sys_get_temp_dir() . '/phpstan/myResultCacheFile.php');
		$this->assertResultCache(__DIR__ . '/resultCache_1.php', sys_get_temp_dir() . '/phpstan/myResultCacheFile.php');
	}

	/**
	 * @return mixed[]
	 */
	private function runPhpstan(int $expectedExitCode, string $phpstanConfigPath = __DIR__ . '/phpstan.neon'): array
	{
		exec(sprintf(
			'%s %s analyse -c %s -l 5 --no-progress --error-format json lib 2>&1',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/../../bin/phpstan'),
			escapeshellarg($phpstanConfigPath),
		), $outputLines, $exitCode);
		$output = implode("\n", $outputLines);

		try {
			$json = Json::decode($output, Json::FORCE_ARRAY);
			$this->assertIsArray($json);
		} catch (JsonException $e) {
			$this->fail(sprintf('%s: %s', $e->getMessage(), $output));
		}

		if ($exitCode !== $expectedExitCode) {
			$this->fail($output);
		}

		return $json;
	}

	/**
	 * @param mixed[] $resultCache
	 * @return array<string, array<int, string>>
	 */
	private function transformResultCache(array $resultCache): array
	{
		$new = [];
		$this->assertIsArray($resultCache['dependencies']);
		foreach ($resultCache['dependencies'] as $file => $data) {
			$this->assertIsString($file);
			$this->assertIsArray($data);
			$this->assertIsArray($data['dependentFiles']);

			$files = [];
			foreach ($data['dependentFiles'] as $filePath) {
				$this->assertIsString($filePath);
				$files[] = $this->relativizePath($filePath);
			}
			sort($files);
			$new[$this->relativizePath($file)] = $files;
		}

		ksort($new);

		return $new;
	}

	private function relativizePath(string $path): string
	{
		$path = str_replace('\\', '/', $path);
		$helper = new SimpleRelativePathHelper(str_replace('\\', '/', __DIR__ . '/PHP-Parser'));
		return $helper->getRelativePath($path);
	}

	private function assertResultCache(string $expectedCachePath, string $actualCachePath = __DIR__ . '/tmp/resultCache.php'): void
	{
		$resultCache = $this->transformResultCache(require $actualCachePath);
		$expectedResultCachePath = require $expectedCachePath;
		$this->assertSame($expectedResultCachePath, $resultCache);
	}

}
