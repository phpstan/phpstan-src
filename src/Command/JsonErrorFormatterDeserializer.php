<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Utils\Json;
use Symfony\Component\Console\Command\Command;

class JsonErrorFormatterDeserializer extends Command
{

	/**
	 * inverse of @see \PHPStan\Command\ErrorFormatter\JsonErrorFormatter
	 *
	 * @param string $jsonString produced by JsonErrorFormatter
	 *
	 * @throws \Nette\Utils\JsonException
	 */
	public static function deserializeErrors(string $jsonString): AnalysisResult
	{
		$json = Json::decode($jsonString, Json::FORCE_ARRAY);

		$notFileSpecificErrors = $json['errors'] ?? [];

		$fileSpecificErrors = [];

		foreach ($json['files'] ?? [] as $file => ['messages' => $messages]) {
			foreach ($messages as $message) {
				$fileSpecificErrors[] = new \PHPStan\Analyser\Error(
					$message['message'],
					$file,
					$message['line'] ?? null,
					$message['ignorable']
				);
			}
		}

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			[],
			false,
			false,
			null
		);
	}

}
