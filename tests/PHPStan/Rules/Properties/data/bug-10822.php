<?php // lint >= 8.1

namespace Bug10822;

enum Deprecation: string
{
	case callString = 'call-string';
	case userAuthored = 'user-authored';
}

interface FileLocation
{
	public function getOffset(): int;

	public function getLine(): int;

	public function getColumn(): int;
}

interface FileSpan
{
	public function getSourceUrl(): ?string;

	public function getStart(): FileLocation;

	public function getEnd(): FileLocation;
}

final class Frame
{
	public function __construct(private readonly string $url, private readonly ?int $line, private readonly ?int $column, private readonly ?string $member)
	{
	}

	public function getMember(): ?string
	{
		return $this->member;
	}

	public function getLocation(): string
	{
		$library = $this->url;

		if ($this->line === null) {
			return $library;
		}

		if ($this->column === null) {
			return $library . ' ' . $this->line;
		}

		return $library . ' ' . $this->line . ':' . $this->column;
	}
}

final class Trace
{
	/**
	 * @param list<Frame> $frames
	 */
	public function __construct(public readonly array $frames)
	{
	}
}

interface DeprecationAwareLoggerInterface
{
	public function warn(string $message, bool $deprecation = false, ?FileSpan $span = null, ?Trace $trace = null): void;

	public function warnForDeprecation(Deprecation $deprecation, string $message, ?FileSpan $span = null, ?Trace $trace = null): void;
}

interface AstNode
{
	public function getSpan(): FileSpan;
}

final class SassScriptException extends \Exception
{
}

final class SassRuntimeException extends \Exception
{
	public readonly FileSpan $span;
	public readonly Trace $sassTrace;

	public function __construct(string $message, FileSpan $span, ?Trace $sassTrace = null, ?\Throwable $previous = null)
	{
		$this->span = $span;
		$this->sassTrace = $sassTrace ?? new Trace([]);

		parent::__construct($message, 0, $previous);
	}
}

interface SassCallable
{
	public function getName(): string;
}

class BuiltInCallable implements SassCallable
{
	/**
	 * @param callable(list<Value>): Value $callback
	 */
	public static function function (string $name, string $arguments, callable $callback): BuiltInCallable
	{
		return new BuiltInCallable($name, [[$arguments, $callback]]);
	}

	/**
	 * @param list<array{string, callable(list<Value>): Value}> $overloads
	 */
	private function __construct(private readonly string $name, public readonly array $overloads)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}
}

abstract class Value
{
	public function assertString(string $name): SassString
	{
		throw new SassScriptException("\$$name: this is not a string.");
	}
}

final class SassString extends Value
{
	public function __construct(
		private readonly string $text,
		public readonly bool    $hasQuotes,
	)
	{
	}

	public function getText(): string
	{
		return $this->text;
	}

	public function assertString(string $name): SassString
	{
		return $this;
	}
}

final class SassMixin extends Value
{
	public function __construct(public readonly SassCallable $callable)
	{
	}
}

interface ImportCache
{
	public function humanize(string $uri): string;
}

final class Environment
{
	public function getMixin(string $name): SassCallable
	{
		throw new \BadMethodCallException('not implemented yet');
	}
}

class EvaluateVisitor
{
	private readonly ImportCache $importCache;

	/**
	 * @var array<string, SassCallable>
	 */
	public array $builtInFunctions = [];

	private readonly DeprecationAwareLoggerInterface $logger;

	/**
	 * @var array<string, array<string, true>>
	 */
	private array $warningsEmitted = [];

	private Environment $environment;

	private string $member = "root stylesheet";

	private ?AstNode $callableNode = null;

	/**
	 * @var list<array{string, AstNode}>
	 */
	private array $stack = [];

	public function __construct(ImportCache $importCache, DeprecationAwareLoggerInterface $logger)
	{
		$this->importCache = $importCache;
		$this->logger = $logger;
		$this->environment = new Environment();

		// These functions are defined in the context of the evaluator because
		// they need access to the environment or other local state.
		$metaFunctions = [
			BuiltInCallable::function('get-mixin', '$name', function ($arguments) {
				$name = $arguments[0]->assertString('name');

				\assert($this->callableNode !== null);
				$callable = $this->addExceptionSpan($this->callableNode, function () use ($name) {
					return $this->environment->getMixin(str_replace('_', '-', $name->getText()));
				});

				return new SassMixin($callable);
			}),
		];

		foreach ($metaFunctions as $function) {
			$this->builtInFunctions[$function->getName()] = $function;
		}
	}

	private function stackFrame(string $member, FileSpan $span): Frame
	{
		$url = $span->getSourceUrl();

		if ($url !== null) {
			$url = $this->importCache->humanize($url);
		}

		return new Frame(
			$url ?? $span->getSourceUrl() ?? '-',
			$span->getStart()->getLine() + 1,
			$span->getStart()->getColumn() + 1,
			$member
		);
	}

	private function stackTrace(?FileSpan $span = null): Trace
	{
		$frames = [];

		foreach ($this->stack as [$member, $nodeWithSpan]) {
			$frames[] = $this->stackFrame($member, $nodeWithSpan->getSpan());
		}

		if ($span !== null) {
			$frames[] = $this->stackFrame($this->member, $span);
		}

		return new Trace(array_reverse($frames));
	}

	public function warn(string $message, FileSpan $span, ?Deprecation $deprecation = null): void
	{
		$spanString = ($span->getSourceUrl() ?? '') . "\0" . $span->getStart()->getOffset() . "\0" . $span->getEnd()->getOffset();

		if (isset($this->warningsEmitted[$message][$spanString])) {
			return;
		}
		$this->warningsEmitted[$message][$spanString] = true;

		$trace = $this->stackTrace($span);

		if ($deprecation === null) {
			$this->logger->warn($message, false, $span, $trace);
		} else {
			$this->logger->warnForDeprecation($deprecation, $message, $span, $trace);
		}
	}

	/**
	 * Runs $callback, and converts any {@see SassScriptException}s it throws to
	 * {@see SassRuntimeException}s with $nodeWithSpan's source span.
	 *
	 * @template T
	 *
	 * @param callable(): T $callback
	 *
	 * @return T
	 *
	 * @throws SassRuntimeException
	 */
	private function addExceptionSpan(AstNode $nodeWithSpan, callable $callback, bool $addStackFrame = true)
	{
		try {
			return $callback();
		} catch (SassScriptException $e) {
			throw new SassRuntimeException($e->getMessage(), $nodeWithSpan->getSpan(), $this->stackTrace($addStackFrame ? $nodeWithSpan->getSpan() : null), $e);
		}
	}
}
