<?php declare(strict_types=1);

namespace Bug2911;

use Exception;
use function PHPStan\Testing\assertType;

class MutatorConfig
{
	/**
	 * @return array<mixed>
	 */
	public function getMutatorSettings(): array
	{
		return [];
	}
}

final class ArrayItemRemoval
{
	private const DEFAULT_SETTINGS = [
		'remove' => 'first',
		'limit' => PHP_INT_MAX,
	];

	/**
	 * @var string first|last|all
	 */
	private $remove;

	/**
	 * @var int
	 */
	private $limit;

	public function __construct(MutatorConfig $config)
	{
		$settings = $this->getResultSettings($config->getMutatorSettings());

		$this->remove = $settings['remove'];
		$this->limit = $settings['limit'];
	}

	/**
	 * @param array<string, mixed> $settings
	 *
	 * @return array{remove: string, limit: int}
	 */
	private function getResultSettings(array $settings): array
	{
		$settings = array_merge(self::DEFAULT_SETTINGS, $settings);
		assertType('array{remove: mixed, limit: mixed, ...<string, mixed>}', $settings);

		if (!is_string($settings['remove'])) {
			throw $this->configException($settings, 'remove');
		}

		assertType('array{remove: string, limit: mixed, ...<string, mixed>}', $settings);

		$settings['remove'] = strtolower($settings['remove']);

		assertType('array{remove: lowercase-string, limit: mixed, ...<string, mixed>}', $settings);

		if (!in_array($settings['remove'], ['first', 'last', 'all'], true)) {
			throw $this->configException($settings, 'remove');
		}

		assertType("array{remove: 'all'|'first'|'last', limit: mixed, ...<string, mixed>}", $settings);

		if (!is_numeric($settings['limit']) || $settings['limit'] < 1) {
			throw $this->configException($settings, 'limit');
		}
		assertType("array{remove: 'all'|'first'|'last', limit: float|int<1, max>|numeric-string, ...<string, mixed>}", $settings);

		$settings['limit'] = (int) $settings['limit'];

		assertType("array{remove: 'all'|'first'|'last', limit: int, ...<string, mixed>}", $settings);

		return $settings;
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function configException(array $settings, string $property): Exception
	{
		$value = $settings[$property];

		return new Exception(sprintf(
			'Invalid configuration of ArrayItemRemoval mutator. Setting `%s` is invalid (%s)',
			$property,
			is_scalar($value) ? $value : '<' . strtoupper(gettype($value)) . '>'
		));
	}
}

final class ArrayItemRemoval2
{
	private const DEFAULT_SETTINGS = [
		'remove' => 'first',
		'limit' => PHP_INT_MAX,
	];

	/**
	 * @param array<string, mixed> $settings
	 *
	 * @return array{remove: string, limit: int}
	 */
	private function getResultSettings(array $settings): array
	{
		$settings = array_merge(self::DEFAULT_SETTINGS, $settings);

		assertType('array{remove: mixed, limit: mixed, ...<string, mixed>}', $settings);

		if (!is_string($settings['remove'])) {
			throw new Exception();
		}

		assertType('array{remove: string, limit: mixed, ...<string, mixed>}', $settings);

		if (!is_int($settings['limit'])) {
			throw new Exception();
		}

		assertType('array{remove: string, limit: int, ...<string, mixed>}', $settings);

		return $settings;
	}


	/**
	 * @param array<mixed> $array
	 */
	function foo(array $array): void {
		$array['bar'] = 'string';

		assertType("non-empty-array<mixed>&hasOffsetValue('bar', 'string')", $array);
	}
}
