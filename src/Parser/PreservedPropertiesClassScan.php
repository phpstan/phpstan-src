<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node\Expr;
use PHPStan\Analyser\PreservedProperties\PreservedProperties;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function count;

/**
 * What PreservedPropertiesVisitor learns about one class while it walks it, and
 * the PreservedProperties of each call on $this, resolved when it leaves the class.
 *
 * Only the class's own code is read: a call that can run code outside it - an
 * inherited or overridable method, parent::, another object, a function or
 * 'new' - may write any public or protected property, and may run what the class
 * hands out: its closures and the methods it turns into first-class callables.
 * Private properties are forgotten when the followed code writes them; code outside
 * calling a method of $this it cannot reach otherwise needs $this, so once the class
 * lets $this out anywhere, a call that runs code outside keeps no property. A call that reaches a __call
 * method, which can write private properties, keeps nothing either, and nothing is kept in a class
 * using a trait.
 *
 * @internal
 * @phpstan-type Summary array{writes: array<string, true>, calls: list<array{self::CALL_*, string}>, functionArguments: list<array{list<non-empty-string>, int, string}>, escapes: bool, unknown: bool}
 */
final class PreservedPropertiesClassScan
{

	/** '$this->m()' and 'static::m()' */
	public const CALL_THIS = 0;

	/** 'self::m()' and 'C::m()' inside C - runs exactly C::m() */
	public const CALL_SELF = 1;

	/** 'parent::m()' and 'Other::m()' */
	public const CALL_OUTSIDE = 2;

	/** The name the class's closures are followed by, run by whoever they are handed to - no method has an empty name. */
	public const CLOSURES = '';

	/** @var array<string, bool> declared instance properties, each with whether only the class's own code can write it */
	public array $properties = [];

	/** @var array<string, array{bool, bool}> methods by lowercase name, each with whether it is private and whether a subclass can override it */
	public array $methods = [];

	/**
	 * @var array<string, array{list<bool>, array<string, bool>, bool}> parameters of the methods by lowercase name:
	 *      whether each is by reference by position and by name, and whether the last one is variadic -
	 *      an override has to keep them by reference or by value
	 */
	public array $methodParameters = [];

	/**
	 * Magic __get/__set/__unset, a hooked property or a trait: code nothing summarises can write the
	 * properties, so the calls keep and forget what they did before.
	 */
	public bool $unsupported = false;

	public ?string $parentName = null;

	/** Some code of the class lets $this out, so code outside can call any of its methods whenever it runs. */
	public bool $thisEscapes = false;

	/** Lowercase name of the method being walked, null outside of method bodies. */
	public ?string $currentMethod = null;

	public bool $returnsByRef = false;

	public int $closureDepth = 0;

	/** Depth of named functions declared in a method body, which do not share its $this. */
	public int $functionDepth = 0;

	/** @var array<string, Summary> */
	public array $summaries = [];

	/** @var Summary of all the class's closures together, followed as the method self::CLOSURES */
	public array $closures = ['writes' => [], 'calls' => [], 'functionArguments' => [], 'escapes' => false, 'unknown' => false];

	/** @var array<string, array{self::CALL_*, string}> what the class hands out to be run later: its closures and first-class callables of its methods */
	public array $handedOut = [];

	/** @var list<array{Expr, self::CALL_*, string}> calls on $this outside closures, with their kind and lowercase method name */
	public array $calls = [];

	public function __construct(
		public bool $isFinal,
		public bool $hasParent,
		public ?string $lowerName,
	)
	{
	}

	/**
	 * @param self::CALL_* $kind
	 */
	public function handOut(int $kind, string $methodName): void
	{
		$this->handedOut[$kind . ':' . $methodName] = [$kind, $methodName];
	}

	public function annotateCalls(): void
	{
		if ($this->unsupported) {
			return;
		}

		$results = [];
		foreach ($this->calls as [$node, $kind, $methodName]) {
			$key = $kind . ':' . $methodName;
			if (!array_key_exists($key, $results)) {
				$results[$key] = $this->resolve($kind, $methodName);
			}
			if ($results[$key] === null) {
				continue;
			}

			$node->setAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME, $results[$key]);
		}
	}

	/**
	 * @param self::CALL_* $kind
	 */
	private function resolve(int $kind, string $methodName): ?PreservedProperties
	{
		$collected = $this->collect([[$kind, $methodName]]);
		if ($collected === null) {
			return null;
		}
		[$written, $functionArguments, $escapes, $directWritten, $directFunctionArguments] = $collected;

		$preserved = [];
		foreach ($this->properties as $propertyName => $isPrivate) {
			if (isset($written[$propertyName]) || ($escapes && !$isPrivate)) {
				continue;
			}
			$preserved[$propertyName] = true;
		}

		$preservedFunctionArguments = [];
		foreach ($functionArguments as $functionArgument) {
			if (!isset($preserved[$functionArgument[2]])) {
				continue;
			}
			$preservedFunctionArguments[] = $functionArgument;
		}

		$directFunctionArgumentsUnwritten = [];
		foreach ($directFunctionArguments as $functionArgument) {
			if (isset($directWritten[$functionArgument[2]])) {
				continue;
			}
			$directFunctionArgumentsUnwritten[] = $functionArgument;
		}

		if (count($preserved) === 0 && count($directWritten) === 0 && count($directFunctionArgumentsUnwritten) === 0) {
			return null;
		}

		return new PreservedProperties($preserved, $preservedFunctionArguments, $directWritten, $directFunctionArgumentsUnwritten);
	}

	/**
	 * Follows the roots and every method of the class they call (a worklist, so mutual
	 * recursion ends), collecting the property names they write.
	 *
	 * @param non-empty-list<array{self::CALL_*, string}> $worklist
	 * @return array{array<string, true>, list<array{list<non-empty-string>, int, string}>, bool, array<string, true>, list<array{list<non-empty-string>, int, string}>}|null
	 *         written names, by-reference candidates, whether code outside the class can run, and the written names and
	 *         by-reference candidates of the followed code without what the class hands out; null when some code cannot be followed
	 */
	private function collect(array $worklist): ?array
	{
		$written = [];
		$functionArguments = [];
		$escapes = false;
		$visited = [];
		$handedOutFollowed = false;
		$direct = null;
		while (true) {
			if (count($worklist) === 0) {
				$direct ??= [$written, $functionArguments];
				if (!$escapes || $handedOutFollowed) {
					break;
				}
				// code outside can run whatever the class handed out
				$handedOutFollowed = true;
				foreach ($this->handedOut as $handedOut) {
					$worklist[] = $handedOut;
				}
				if ($this->thisEscapes) {
					// and any method it can call on the $this the class let out, except running the constructor again
					foreach ($this->methods as $lowerMethodName => [$isPrivate]) {
						if ($isPrivate || $lowerMethodName === '__construct') {
							continue;
						}
						$worklist[] = [self::CALL_SELF, $lowerMethodName];
					}
				}
				continue;
			}
			[$kind, $methodName] = array_pop($worklist);
			if ($kind === self::CALL_OUTSIDE) {
				$escapes = true;
				continue;
			}
			if (!isset($this->methods[$methodName]) && $methodName !== self::CLOSURES) {
				if (!$this->hasParent || isset($this->methods['__call'])) {
					// not inherited, so __call or a magic @method, which can write private properties too
					return null;
				}
				// inherited
				$escapes = true;
				continue;
			}
			if (!isset($this->summaries[$methodName]) && $methodName !== self::CLOSURES) {
				// abstract, implemented in a subclass
				$escapes = true;
				continue;
			}
			if ($kind === self::CALL_THIS && !$this->isFinal && $this->methods[$methodName][1]) {
				$escapes = true;
			}
			if (isset($visited[$methodName])) {
				continue;
			}
			$visited[$methodName] = true;

			$summary = $methodName === self::CLOSURES ? $this->closures : $this->summaries[$methodName];
			if ($summary['unknown']) {
				return null;
			}
			if ($summary['escapes']) {
				$escapes = true;
			}
			$written += $summary['writes'];
			foreach ($summary['functionArguments'] as $functionArgument) {
				$functionArguments[] = $functionArgument;
			}
			foreach ($summary['calls'] as $call) {
				$worklist[] = $call;
			}
		}

		if (!$escapes && $this->hasParent) {
			foreach (array_keys($written) as $propertyName) {
				if (!isset($this->properties[$propertyName])) {
					// may be a hooked or magic property of a parent
					$escapes = true;
					break;
				}
			}
		}

		return [$written, $functionArguments, $escapes, $direct[0], $direct[1]];
	}

}
