<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Nette\Utils\Json;
use PHPUnit\Framework\TestCase;

class ParallelAnalyserIntegrationTest extends TestCase
{

	public function testTraitsInDifferentJobAnalysed(): void
	{
		exec(sprintf(
			'%s %s analyse -l 8 -c %s --error-format json %s',
			escapeshellarg(PHP_BINARY),
			escapeshellarg(__DIR__ . '/../../../bin/phpstan'),
			escapeshellarg(__DIR__ . '/parallel-analyser.neon'),
			implode(' ', array_map(static function (string $path): string {
				return escapeshellarg($path);
			}, [
				__DIR__ . '/data/trait-definition.php',
				__DIR__ . '/data/traits.php',
			]))
		), $outputLines, $exitCode);
		$output = implode("\n", $outputLines);
		$this->assertJsonStringEqualsJsonString(Json::encode([
			'totals' => [
				'errors' => 0,
				'file_errors' => 3,
			],
			'files' => [
				sprintf('%s/data/trait-definition.php (in context of class ParallelAnalyserIntegrationTest\\Bar)', __DIR__) => [
					'errors' => 1,
					'messages' => [
						[
							'message' => 'Method ParallelAnalyserIntegrationTest\\Bar::doFoo() has no return typehint specified.',
							'line' => 8,
							'ignorable' => true,
						],
					],
				],
				sprintf('%s/data/trait-definition.php (in context of class ParallelAnalyserIntegrationTest\\Foo)', __DIR__) => [
					'errors' => 2,
					'messages' => [
						[
							'message' => 'Method ParallelAnalyserIntegrationTest\\Foo::doFoo() has no return typehint specified.',
							'line' => 8,
							'ignorable' => true,
						],
						[
							'message' => 'Access to an undefined property ParallelAnalyserIntegrationTest\\Foo::$test.',
							'line' => 10,
							'ignorable' => true,
						],
					],
				],
			],
			'errors' => [],
		]), $output);
		$this->assertSame(1, $exitCode);
	}

}
