<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\File\FileHelper;
use PHPStan\File\FileWriter;

/** @api */
abstract class LevelsTestCase extends \PHPUnit\Framework\TestCase
{

	/**
	 * @return array<array<string>>
	 */
	abstract public function dataTopics(): array;

	abstract public function getDataPath(): string;

	abstract public function getPhpStanExecutablePath(): string;

	abstract public function getPhpStanConfigPath(): ?string;

	protected function getResultSuffix(): string
	{
		return '';
	}

	protected function shouldAutoloadAnalysedFile(): bool
	{
		return true;
	}

	/**
	 * @dataProvider dataTopics
	 * @param string $topic
	 */
	public function testLevels(
		string $topic
	): void
	{
		$file = sprintf('%s' . DIRECTORY_SEPARATOR . '%s.php', $this->getDataPath(), $topic);
		$command = escapeshellcmd($this->getPhpStanExecutablePath());
		$configPath = $this->getPhpStanConfigPath();
		$fileHelper = new FileHelper(__DIR__ . '/../..');

		$previousMessages = [];

		$exceptions = [];

		foreach (range(0, 8) as $level) {
			unset($outputLines);
			exec(sprintf('%s %s clear-result-cache %s 2>&1', escapeshellarg(PHP_BINARY), $command, $configPath !== null ? '--configuration ' . escapeshellarg($configPath) : ''), $clearResultCacheOutputLines, $clearResultCacheExitCode);
			if ($clearResultCacheExitCode !== 0) {
				throw new \PHPStan\ShouldNotHappenException('Could not clear result cache: ' . implode("\n", $clearResultCacheOutputLines));
			}
			exec(sprintf('%s %s analyse --no-progress --error-format=prettyJson --level=%d %s %s %s', escapeshellarg(PHP_BINARY), $command, $level, $configPath !== null ? '--configuration ' . escapeshellarg($configPath) : '', $this->shouldAutoloadAnalysedFile() ? sprintf('--autoload-file %s', escapeshellarg($file)) : '', escapeshellarg($file)), $outputLines);

			$output = implode("\n", $outputLines);

			try {
				$actualJson = \Nette\Utils\Json::decode($output, \Nette\Utils\Json::FORCE_ARRAY);
			} catch (\Nette\Utils\JsonException $e) {
				throw new \Nette\Utils\JsonException(sprintf('Cannot decode: %s', $output));
			}
			if (count($actualJson['files']) > 0) {
				$normalizedFilePath = $fileHelper->normalizePath($file);
				if (!isset($actualJson['files'][$normalizedFilePath])) {
					$messagesBeforeDiffing = [];
				} else {
					$messagesBeforeDiffing = $actualJson['files'][$normalizedFilePath]['messages'];
				}

				foreach ($this->getAdditionalAnalysedFiles() as $additionalAnalysedFile) {
					$normalizedAdditionalFilePath = $fileHelper->normalizePath($additionalAnalysedFile);
					if (!isset($actualJson['files'][$normalizedAdditionalFilePath])) {
						continue;
					}

					$messagesBeforeDiffing = array_merge($messagesBeforeDiffing, $actualJson['files'][$normalizedAdditionalFilePath]['messages']);
				}
			} else {
				$messagesBeforeDiffing = [];
			}

			$messages = [];
			foreach ($messagesBeforeDiffing as $message) {
				foreach ($previousMessages as $lastMessage) {
					if (
						$message['message'] === $lastMessage['message']
						&& $message['line'] === $lastMessage['line']
					) {
						continue 2;
					}
				}

				$messages[] = $message;
			}

			$missingMessages = [];
			foreach ($previousMessages as $previousMessage) {
				foreach ($messagesBeforeDiffing as $message) {
					if (
						$previousMessage['message'] === $message['message']
						&& $previousMessage['line'] === $message['line']
					) {
						continue 2;
					}
				}

				$missingMessages[] = $previousMessage;
			}

			$previousMessages = array_merge($previousMessages, $messages);
			$expectedJsonFile = sprintf('%s/%s-%d%s.json', $this->getDataPath(), $topic, $level, $this->getResultSuffix());

			$exception = $this->compareFiles($expectedJsonFile, $messages);
			if ($exception !== null) {
				$exceptions[] = $exception;
			}

			$expectedJsonMissingFile = sprintf('%s/%s-%d-missing%s.json', $this->getDataPath(), $topic, $level, $this->getResultSuffix());
			$exception = $this->compareFiles($expectedJsonMissingFile, $missingMessages);
			if ($exception === null) {
				continue;
			}

			$exceptions[] = $exception;
		}

		if (count($exceptions) > 0) {
			throw $exceptions[0];
		}
	}

	/**
	 * @return string[]
	 */
	public function getAdditionalAnalysedFiles(): array
	{
		return [];
	}

	/**
	 * @param string $expectedJsonFile
	 * @param string[] $expectedMessages
	 * @return \PHPUnit\Framework\AssertionFailedError|null
	 */
	private function compareFiles(string $expectedJsonFile, array $expectedMessages): ?\PHPUnit\Framework\AssertionFailedError
	{
		if (count($expectedMessages) === 0) {
			try {
				self::assertFileDoesNotExist($expectedJsonFile);
				return null;
			} catch (\PHPUnit\Framework\AssertionFailedError $e) {
				unlink($expectedJsonFile);
				return $e;
			}
		}

		$actualOutput = \Nette\Utils\Json::encode($expectedMessages, \Nette\Utils\Json::PRETTY);

		try {
			$this->assertJsonStringEqualsJsonFile(
				$expectedJsonFile,
				$actualOutput
			);
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			FileWriter::write($expectedJsonFile, $actualOutput);
			return $e;
		}

		return null;
	}

	public static function assertFileDoesNotExist(string $filename, string $message = ''): void
	{
		if (!method_exists(parent::class, 'assertFileDoesNotExist')) {
			parent::assertFileNotExists($filename, $message);
			return;
		}

		parent::assertFileDoesNotExist($filename, $message);
	}

}
