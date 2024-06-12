<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use JsonSerializable;
use ReturnTypeWillChange;
use Throwable;
use function array_map;
use function array_unshift;

/**
 * @phpstan-type Trace = list<array{file: string|null, line: int|null}>
 */
class InternalError implements JsonSerializable
{

	public const STACK_TRACE_METADATA_KEY = 'stackTrace';

	/**
	 * @param Trace $trace
	 */
	public function __construct(
		private string $message,
		private array $trace,
	)
	{
	}

	/**
	 * @return Trace
	 */
	public static function prepareTrace(Throwable $exception): array
	{
		$trace = array_map(static fn (array $trace) => [
			'file' => $trace['file'] ?? null,
			'line' => $trace['line'] ?? null,
		], $exception->getTrace());

		array_unshift($trace, [
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
		]);

		return $trace;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	/**
	 * @return Trace
	 */
	public function getTrace(): array
	{
		return $this->trace;
	}

	/**
	 * @param mixed[] $json
	 */
	public static function decode(array $json): self
	{
		return new self($json['message'], $json['trace']);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'message' => $this->message,
			'trace' => $this->trace,
		];
	}

}
