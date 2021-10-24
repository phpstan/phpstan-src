<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use Nette\Utils\Json;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\SimpleRelativePathHelper;
use PHPUnit\Framework\TestCase;
use function escapeshellarg;
use function file_put_contents;
use function str_replace;

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
		$this->assertSame(3, $result['totals']['file_errors']);
		$this->assertSame(0, $result['totals']['errors']);

		$fileHelper = new FileHelper(__DIR__);

		$this->assertSame('Parameter #1 $source of function token_get_all expects string, PhpParser\Node\Expr\MethodCall given.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Lexer.php')]['messages'][0]['message']);
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
		$this->assertSame(5, $result['totals']['file_errors'], Json::encode($result));
		$this->assertSame(0, $result['totals']['errors'], Json::encode($result));

		$message = $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][0]['message'];
		$this->assertStringContainsString('Ignored error pattern #^Argument of an invalid type PhpParser\\\\Node supplied for foreach, only iterables are supported\\.$# in path', $message);
		$this->assertStringContainsString('was not matched in reported errors.', $message);
		$this->assertSame('Reflection error: PhpParser\Serializer not found.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][1]['message']);
		$this->assertSame('Reflection error: PhpParser\Serializer not found.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][2]['message']);
		$this->assertSame('Reflection error: PhpParser\Serializer not found.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][3]['message']);
		$this->assertSame('Reflection error: PhpParser\Serializer not found.', $result['files'][$fileHelper->normalizePath(__DIR__ . '/PHP-Parser/lib/PhpParser/Serializer/XML.php')]['messages'][4]['message']);

		file_put_contents($serializerPath, $originalSerializerCode);
		$this->runPhpstan(0);
		$this->assertResultCache(__DIR__ . '/resultCache_1.php');
	}

	/**
	 * @param int $expectedExitCode
	 * @return mixed[]
	 */
	private function runPhpstan(int $expectedExitCode): array
	{
		exec(sprintf(
			'%s %s analyse -c %s -l 5 --no-progress --error-format json lib 2>&1',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/../../bin/phpstan'),
			escapeshellarg(__DIR__ . '/phpstan.neon')
		), $outputLines, $exitCode);
		$output = implode("\n", $outputLines);

		try {
			$json = Json::decode($output, Json::FORCE_ARRAY);
		} catch (\Nette\Utils\JsonException $e) {
			$this->fail(sprintf('%s: %s', $e->getMessage(), $output));
		}

		if ($exitCode !== $expectedExitCode) {
			$this->fail($output);
		}

		return $json;
	}

	/**
	 * @param mixed[] $resultCache
	 * @return mixed[]
	 */
	private function transformResultCache(array $resultCache): array
	{
		$new = [];
		foreach ($resultCache['dependencies'] as $file => $data) {
			$files = array_map(function (string $file): string {
				return $this->relativizePath($file);
			}, $data['dependentFiles']);
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

	private function assertResultCache(string $expectedCachePath): void
	{
		$resultCachePath = __DIR__ . '/tmp/resultCache.php';
		$resultCache = $this->transformResultCache(require $resultCachePath);
		$expectedResultCachePath = require $expectedCachePath;
		$this->assertSame($expectedResultCachePath, $resultCache);
	}

}
