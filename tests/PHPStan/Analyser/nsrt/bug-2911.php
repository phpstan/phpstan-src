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
		assertType('non-empty-array<string, mixed>', $settings);

		if (!is_string($settings['remove'])) {
			throw $this->configException($settings, 'remove');
		}

		assertType("non-empty-array<string, mixed>&hasOffsetValue('remove', string)", $settings);

		$settings['remove'] = strtolower($settings['remove']);

		assertType("non-empty-array<string, mixed>&hasOffsetValue('remove', string)", $settings);

		if (!in_array($settings['remove'], ['first', 'last', 'all'], true)) {
			throw $this->configException($settings, 'remove');
		}

		assertType("non-empty-array<string, mixed>&hasOffsetValue('remove', 'all'|'first'|'last')", $settings);

		if (!is_numeric($settings['limit']) || $settings['limit'] < 1) {
			throw $this->configException($settings, 'limit');
		}
		assertType("non-empty-array<string, mixed>&hasOffsetValue('limit', float|int<1, max>|numeric-string)&hasOffsetValue('remove', 'all'|'first'|'last')", $settings);

		$settings['limit'] = (int) $settings['limit'];

		assertType("non-empty-array<string, mixed>&hasOffsetValue('limit', int)&hasOffsetValue('remove', 'all'|'first'|'last')", $settings);

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

		assertType('non-empty-array<string, mixed>', $settings);

		if (!is_string($settings['remove'])) {
			throw new Exception();
		}

		assertType("non-empty-array<string, mixed>&hasOffsetValue('remove', string)", $settings);

		if (!is_int($settings['limit'])) {
			throw new Exception();
		}

		assertType("non-empty-array<string, mixed>&hasOffsetValue('limit', int)&hasOffsetValue('remove', string)", $settings);

		return $settings;
	}


	/**
	 * @param array<mixed> $array
	 */
	function foo(array $array): void {
		$array['bar'] = 'string';

		assertType("hasOffsetValue('bar', 'string')&non-empty-array", $array);
	}
}
