<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use JsonSerializable;
use ReturnTypeWillChange;
use Throwable;
use function array_map;
use function array_unshift;

/**
 * @api
 * @final
 * @phpstan-type Trace = list<array{file: string|null, line: int|null}>
 */
class InternalError implements JsonSerializable
{

	public const STACK_TRACE_METADATA_KEY = 'stackTrace';

	public const STACK_TRACE_AS_STRING_METADATA_KEY = 'stackTraceAsString';

	/**
	 * @param Trace $trace
	 */
	public function __construct(
		private string $message,
		private string $contextDescription,
		private array $trace,
		private ?string $traceAsString,
		private bool $shouldReportBug,
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

	public function getContextDescription(): string
	{
		return $this->contextDescription;
	}

	/**
	 * @return Trace
	 */
	public function getTrace(): array
	{
		return $this->trace;
	}

	public function getTraceAsString(): ?string
	{
		return $this->traceAsString;
	}

	public function shouldReportBug(): bool
	{
		return $this->shouldReportBug;
	}

	/**
	 * @param mixed[] $json
	 */
	public static function decode(array $json): self
	{
		return new self($json['message'], $json['contextDescription'], $json['trace'], $json['traceAsString'], $json['shouldReportBug']);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'message' => $this->message,
			'contextDescription' => $this->contextDescription,
			'trace' => $this->trace,
			'traceAsString' => $this->traceAsString,
			'shouldReportBug' => $this->shouldReportBug,
		];
	}

}
