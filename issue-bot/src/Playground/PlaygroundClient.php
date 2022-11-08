<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

use GuzzleHttp\Client;
use Nette\Utils\Json;
use function array_map;
use function array_values;
use function sprintf;

class PlaygroundClient
{

	public function __construct(private Client $client)
	{
	}

	public function getResult(string $hash): PlaygroundResult
	{
		$response = $this->client->get(sprintf('https://api.phpstan.org/sample?id=%s', $hash));

		$body = (string) $response->getBody();
		$json = Json::decode($body, Json::FORCE_ARRAY);

		$versionedErrors = [];
		foreach ($json['versionedErrors'] as ['phpVersion' => $phpVersion, 'errors' => $errors]) {
			$versionedErrors[(int) $phpVersion] = array_map(static fn (array $error) => new PlaygroundError($error['line'] ?? -1, $error['message']), array_values($errors));
		}

		return new PlaygroundResult(
			sprintf('https://phpstan.org/r/%s', $hash),
			$hash,
			$json['code'],
			$json['level'],
			$json['config']['strictRules'],
			$json['config']['bleedingEdge'],
			$json['config']['treatPhpDocTypesAsCertain'],
			$versionedErrors,
		);
	}

}
