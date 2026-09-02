<?php

declare(strict_types=1);

namespace ResultCacheE2E\Dependency;

use PHPStan\Analyser\ResultCache\ResultCacheDependencyExtension;
use RuntimeException;
use function file_get_contents;
use function file_put_contents;
use function getmypid;
use function hash;
use function is_array;
use function is_string;
use function json_decode;
use function sprintf;
use const FILE_APPEND;
use const JSON_THROW_ON_ERROR;
use const LOCK_EX;

final class TenantConfigTypeRegistry implements ResultCacheDependencyExtension
{
	public function getKey(): string
	{
		return self::class;
	}

	public function getHash(string $dependencyKey): string
	{
		$pid = getmypid();
		if ($pid === false) {
			throw new RuntimeException('Could not determine the dependency hash process.');
		}
		if (file_put_contents(
			__DIR__ . '/../tmp/hash-calls.log',
			sprintf("%d %s %s\n", $pid, $this->getKey(), $dependencyKey),
			FILE_APPEND | LOCK_EX,
		) === false) {
			throw new RuntimeException('Could not record dependency hash call.');
		}

		return hash('sha256', $this->get($dependencyKey));
	}

	public function get(string $dependencyKey): string
	{
		$contents = file_get_contents(__DIR__ . '/../tenant-config-types.json');
		if ($contents === false) {
			throw new RuntimeException('Could not read tenant configuration types.');
		}
		$configTypes = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
		if (!is_array($configTypes)) {
			throw new RuntimeException('Tenant configuration types must be an object.');
		}
		$value = $configTypes[$dependencyKey] ?? 'missing';
		if (!is_string($value)) {
			throw new RuntimeException('Tenant configuration types must be strings.');
		}

		return $value;
	}
}
