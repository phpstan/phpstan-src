<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\PreservedProperties\PreservedProperties;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_pop;
use function count;
use function str_starts_with;
use function usort;

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
 * method, which can write private properties, keeps nothing either.
 *
 * The code of the traits a class uses is read like the class's own, when they are in analysed
 * files and used without adaptations: PreservedPropertiesResolver merges their scans into the
 * class's at its first call. Otherwise nothing is kept in a class using a trait.
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

	/** @var array<string, array{bool, bool}> declared instance properties, each with whether it is private and whether it is protected */
	private array $propertyVisibility = [];

	/** @var list<Node\Stmt\TraitUse> the trait uses of the class, whose code is merged in before its calls are annotated */
	public array $traitUses = [];

	/** @var array<string, true> protected properties of a final class with a parent, which only the class's own code writes unless the parent declares them too */
	private array $unlessParentDeclares = [];

	/** @var array<string, true> properties written in ways no walk sees the value of: not by assigning it, or in closures */
	public array $nonPlainWrites = [];

	/** @var array<string, array<string, true>> properties each method by lowercase name assigns, which its walk sees the values of */
	public array $assignments = [];

	/** @var array<string, array{bool, bool}> methods by lowercase name, each with whether it is private and whether a subclass can override it */
	public array $methods = [];

	/**
	 * @var array<string, array{list<bool>, array<string, bool>, bool}> parameters of the methods by lowercase name:
	 *      whether each is by reference by position and by name, and whether the last one is variadic -
	 *      an override has to keep them by reference or by value
	 */
	public array $methodParameters = [];

	/**
	 * Magic __get/__set/__unset, a hooked property or a trait whose code is not known: code nothing summarises can write the
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

	/** @var array<string, array<string, true>> the methods calling each method of the class on $this by lowercase name, self::CLOSURES for calls in closures */
	public array $callers = [];

	/** @var array<string, true> lowercase method names called other than on $this, handed out, or mentioned in a string, which can name a callable */
	public array $calledElsewhere = [];

	/** Some code calls a method by a name that is not written in it, which can be any method of the class. */
	public bool $hasDynamicCalls = false;

	public function __construct(
		public bool $isFinal,
		public bool $hasParent,
		public ?string $lowerName,
	)
	{
	}

	public function addProperty(string $propertyName, bool $isPrivate, bool $isProtected): void
	{
		$this->propertyVisibility[$propertyName] = [$isPrivate, $isProtected];
		// no subclass reaches a protected property of a final class, and the parent's code only one it declares too
		$this->properties[$propertyName] = $isPrivate || ($isProtected && $this->isFinal);
		if ($isPrivate || !$isProtected || !$this->isFinal || !$this->hasParent) {
			return;
		}

		$this->unlessParentDeclares[$propertyName] = true;
	}

	/**
	 * @param self::CALL_* $kind
	 */
	public function handOut(int $kind, string $methodName): void
	{
		$this->handedOut[$kind . ':' . $methodName] = [$kind, $methodName];
		$this->calledElsewhere[$methodName] = true;
	}

	/**
	 * Attaches to each method only the class's own code can call - private, or protected
	 * in a final class without a parent - the methods it is only ever run from: the
	 * methods outside this set that call it on $this, directly or through other such
	 * methods. A method also called in a closure, on another object, through a callable
	 * or one the class never calls gets nothing.
	 */
	public function annotateOnlyCalledFrom(Node\Stmt\ClassLike $node): void
	{
		if ($this->unsupported || $this->traitUses !== [] || $this->hasDynamicCalls) {
			// code of a trait or a property hook, which the scan does not follow, can call the methods too
			return;
		}

		$candidates = [];
		foreach ($node->stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\ClassMethod || $stmt->isStatic() || $stmt->stmts === null) {
				continue;
			}
			$methodName = $stmt->name->toLowerString();
			if (str_starts_with($methodName, '__')) {
				// magic methods are called by PHP
				continue;
			}
			if (!$stmt->isPrivate() && !($stmt->isProtected() && $this->isFinal && !$this->hasParent)) {
				continue;
			}
			if (
				!isset($this->callers[$methodName])
				|| isset($this->callers[$methodName][self::CLOSURES])
				|| isset($this->calledElsewhere[$methodName])
			) {
				continue;
			}
			$candidates[$methodName] = $stmt;
		}

		$roots = [];
		foreach (array_keys($candidates) as $methodName) {
			$roots[$methodName] = [];
			foreach (array_keys($this->callers[$methodName]) as $callerName) {
				if (isset($candidates[$callerName])) {
					continue;
				}
				$roots[$methodName][$callerName] = true;
			}
		}

		do {
			$changed = false;
			foreach (array_keys($candidates) as $methodName) {
				foreach (array_keys($this->callers[$methodName]) as $callerName) {
					if (!isset($candidates[$callerName])) {
						continue;
					}
					$count = count($roots[$methodName]);
					$roots[$methodName] += $roots[$callerName];
					if (count($roots[$methodName]) === $count) {
						continue;
					}
					$changed = true;
				}
			}
		} while ($changed);

		foreach ($candidates as $methodName => $stmt) {
			if ($roots[$methodName] === []) {
				// only called by methods nothing else calls
				continue;
			}
			$stmt->setAttribute(PreservedPropertiesVisitor::ONLY_CALLED_FROM_ATTRIBUTE_NAME, array_map('strval', array_keys($roots[$methodName])));
		}
	}

	public function annotateCalls(): void
	{
		$overrideWrites = $this->unsupported ? [] : $this->collectOverrideWrites();
		$results = [];
		foreach ($this->calls as [$node, $kind, $methodName]) {
			$key = $kind . ':' . $methodName;
			if (!array_key_exists($key, $results)) {
				$results[$key] = $this->unsupported ? null : $this->resolve($kind, $methodName, $overrideWrites);
			}
			if ($results[$key] === null) {
				if ($node->hasAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME)) {
					// the scan deferred until the traits were merged
					$node->setAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME, null);
				}
				continue;
			}

			$node->setAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME, $results[$key]);
		}
	}

	/**
	 * Attaches this scan to the class's calls instead of what they keep, which the class's
	 * traits decide too: their code is in other files.
	 */
	public function deferCalls(): void
	{
		foreach ($this->calls as [$node]) {
			$node->setAttribute(PreservedPropertiesVisitor::ATTRIBUTE_NAME, $this);
		}
	}

	/**
	 * Merges the scans of the traits the class uses into it and annotates its calls.
	 *
	 * @param list<self>|null $traitScans null when the code of some trait is not known
	 */
	public function annotateCallsWithTraits(?array $traitScans): void
	{
		if ($traitScans === null) {
			$this->unsupported = true;
		}
		foreach ($traitScans ?? [] as $traitScan) {
			$this->mergeTrait($traitScan);
		}
		$this->traitUses = [];

		$this->annotateCalls();
	}

	private function mergeTrait(self $trait): void
	{
		if ($trait->unsupported || $trait->traitUses !== []) {
			$this->unsupported = true;
			return;
		}

		foreach ($trait->methods as $methodName => $method) {
			if (isset($this->methods[$methodName])) {
				// the class's own method replaces the trait's
				continue;
			}
			$this->methods[$methodName] = $method;
			if (isset($trait->methodParameters[$methodName])) {
				$this->methodParameters[$methodName] = $trait->methodParameters[$methodName];
			}
			if (!isset($trait->summaries[$methodName])) {
				continue;
			}
			$this->summaries[$methodName] = $trait->summaries[$methodName];
		}

		foreach ($trait->propertyVisibility as $propertyName => [$isPrivate, $isProtected]) {
			if (isset($this->properties[$propertyName])) {
				continue;
			}
			$this->addProperty($propertyName, $isPrivate, $isProtected);
		}

		$this->closures['writes'] += $trait->closures['writes'];
		foreach ($trait->closures['calls'] as $call) {
			$this->closures['calls'][] = $call;
		}
		foreach ($trait->closures['functionArguments'] as $functionArgument) {
			$this->closures['functionArguments'][] = $functionArgument;
		}
		$this->closures['escapes'] = $this->closures['escapes'] || $trait->closures['escapes'];
		$this->closures['unknown'] = $this->closures['unknown'] || $trait->closures['unknown'];
		$this->handedOut += $trait->handedOut;
		$this->nonPlainWrites += $trait->nonPlainWrites;
		foreach ($trait->assignments as $traitMethodName => $propertyNames) {
			$this->assignments[$traitMethodName] ??= $propertyNames;
		}
		$this->thisEscapes = $this->thisEscapes || $trait->thisEscapes;
	}

	/**
	 * Ranks the methods so that each comes after the methods of the class it calls - the
	 * methods are walked by it, and a call can then use the walk of what it calls. The
	 * constructor and what it calls come first: the other methods start from the scope the
	 * constructor ends with. Mutually recursive methods keep their order in the class.
	 */
	public function rankMethods(Node\Stmt\ClassLike $node): void
	{
		$methods = [];
		foreach ($node->stmts as $stmt) {
			if (!$stmt instanceof Node\Stmt\ClassMethod) {
				continue;
			}
			$methods[] = $stmt;
		}
		usort($methods, static fn (Node\Stmt\ClassMethod $a, Node\Stmt\ClassMethod $b): int => ($a->name->toLowerString() !== '__construct') <=> ($b->name->toLowerString() !== '__construct'));

		$methodsByName = [];
		foreach ($methods as $method) {
			$methodsByName[$method->name->toLowerString()] ??= $method;
		}

		$rank = 0;
		$ranked = [];
		foreach ($methods as $method) {
			$this->rankMethod($method->name->toLowerString(), $methodsByName, $ranked, $rank);
			if ($method->hasAttribute(PreservedPropertiesVisitor::RANK_ATTRIBUTE_NAME)) {
				continue;
			}

			// a method declared twice
			$method->setAttribute(PreservedPropertiesVisitor::RANK_ATTRIBUTE_NAME, $rank++);
		}
	}

	/**
	 * @param array<string, Node\Stmt\ClassMethod> $methodsByName
	 * @param array<string, true> $ranked
	 */
	private function rankMethod(string $methodName, array $methodsByName, array &$ranked, int &$rank): void
	{
		if (isset($ranked[$methodName])) {
			return;
		}
		$ranked[$methodName] = true;
		foreach ($this->summaries[$methodName]['calls'] ?? [] as [$kind, $calledMethodName]) {
			if ($kind === self::CALL_OUTSIDE || !isset($methodsByName[$calledMethodName])) {
				continue;
			}
			$this->rankMethod($calledMethodName, $methodsByName, $ranked, $rank);
		}
		$methodsByName[$methodName]->setAttribute(PreservedPropertiesVisitor::RANK_ATTRIBUTE_NAME, $rank++);
	}

	/**
	 * The private properties each non-private method of a class with a parent writes, by lowercase
	 * method name: the parent's code can run the ones that override its methods whenever it runs.
	 * Each property comes with the methods
	 * the method runs that assign it and the function arguments it is passed as, which write it when
	 * the parameter is by reference, or null when it is written in a way no walk sees the value of.
	 *
	 * @return array<string, array<string, array{list<string>, list<array{list<non-empty-string>, int, string}>}|null>>
	 */
	private function collectOverrideWrites(): array
	{
		if (!$this->hasParent) {
			return [];
		}

		$overrideWrites = [];
		foreach ($this->methods as $methodName => [$isPrivate]) {
			if ($isPrivate || $methodName === '__construct' || !isset($this->summaries[$methodName])) {
				continue;
			}
			$collected = $this->collect([[self::CALL_SELF, $methodName]]);
			if ($collected === null) {
				foreach ($this->properties as $propertyName => $isPrivateProperty) {
					if (!$isPrivateProperty) {
						continue;
					}
					$overrideWrites[$methodName][$propertyName] = null;
				}
				continue;
			}

			$followed = $this->followMethods($methodName, $collected[2]);
			$written = $collected[0];
			foreach ($collected[1] as [, , $propertyName]) {
				// written when the parameter is by reference
				$written[$propertyName] = true;
			}
			foreach (array_keys($written) as $propertyName) {
				if (($this->properties[$propertyName] ?? false) !== true) {
					continue;
				}
				if (isset($this->nonPlainWrites[$propertyName])) {
					$overrideWrites[$methodName][$propertyName] = null;
					continue;
				}
				$writers = [];
				$functionArguments = [];
				foreach ($followed as $followedMethodName) {
					foreach ($this->summaries[$followedMethodName]['functionArguments'] as $functionArgument) {
						if ($functionArgument[2] !== $propertyName) {
							continue;
						}
						$functionArguments[] = $functionArgument;
					}
					if (!isset($this->assignments[$followedMethodName][$propertyName])) {
						continue;
					}
					$writers[] = $followedMethodName;
				}
				if (count($writers) === 0 && count($functionArguments) === 0) {
					// only passed to methods of other objects
					continue;
				}
				$overrideWrites[$methodName][$propertyName] = [$writers, $functionArguments];
			}
		}

		return $overrideWrites;
	}

	/**
	 * The methods of the class the method runs, itself included, and what the class hands out
	 * when code outside can run.
	 *
	 * @return list<string>
	 */
	private function followMethods(string $methodName, bool $escapes): array
	{
		$worklist = [$methodName];
		if ($escapes) {
			foreach ($this->handedOut as [, $handedOutMethodName]) {
				$worklist[] = $handedOutMethodName;
			}
		}
		$visited = [];
		while (count($worklist) > 0) {
			$name = array_pop($worklist);
			if (isset($visited[$name]) || !isset($this->summaries[$name])) {
				continue;
			}
			$visited[$name] = true;
			foreach ($this->summaries[$name]['calls'] as [$kind, $calledMethodName]) {
				if ($kind === self::CALL_OUTSIDE) {
					continue;
				}
				$worklist[] = $calledMethodName;
			}
		}

		return array_keys($visited);
	}

	/**
	 * @param self::CALL_* $kind
	 * @param array<string, array<string, array{list<string>, list<array{list<non-empty-string>, int, string}>}|null>> $overrideWrites
	 */
	private function resolve(int $kind, string $methodName, array $overrideWrites): ?PreservedProperties
	{
		$collected = $this->collect([[$kind, $methodName]]);
		if ($collected === null) {
			return null;
		}
		[$written, $functionArguments, $escapes, $directWritten, $directFunctionArguments] = $collected;

		$preserved = [];
		$unlessParentDeclares = [];
		foreach ($this->properties as $propertyName => $isPrivate) {
			if (isset($written[$propertyName]) || ($escapes && !$isPrivate)) {
				continue;
			}
			$preserved[$propertyName] = true;
			if (!$escapes || !isset($this->unlessParentDeclares[$propertyName])) {
				continue;
			}
			$unlessParentDeclares[$propertyName] = true;
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

		return new PreservedProperties($preserved, $preservedFunctionArguments, $directWritten, $directFunctionArgumentsUnwritten, $this->closures['writes'], $this->parentName, $unlessParentDeclares, $overrideWrites);
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
