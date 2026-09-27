<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_keys;
use function array_pop;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function spl_object_id;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Infers the signature of a closure or an arrow function written where nothing
 * types it - assigned to a variable, put in an array - from where its value goes
 * in the rest of the enclosing body: the arguments it is invoked with and the
 * callable types it is sent to (parameters, returns, typed properties, @var).
 *
 * During the observation pass of the body (see
 * StatementsHandler::processBodyStmtNodesTwoPass()) the closure's ClosureType
 * carries a marker per parameter - an UnresolvedTemplateArgumentType whose site
 * is the closure node and whose delegate is the declared parameter type - so
 * the value can be followed through variables, arrays and unions by its type.
 * Invocations put their arguments as lower bounds on the markers, callable send
 * targets put their parameter types as lower bounds and their return type as an
 * upper bound on the return marker. The body itself is walked with the declared
 * types, exactly as without the inference.
 *
 * The second pass walks the body with the resolved parameter types (intersected
 * with the declared ones, as for a closure passed straight to a callable
 * parameter) and gives returned expressions the resolved return bound as their
 * expected type.
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ClosureSignatureInference.cpp')]
final class ClosureSignatureInference
{

	public const RETURN_TEMPLATE_NAME = '@return';

	/**
	 * A by-ref use `&$x` of a closure whose effects apply where it is invoked:
	 * its marker's lower bounds are the types of `$x` at the invocations.
	 */
	private const BY_REF_TEMPLATE_PREFIX = '&';

	/**
	 * The type a by-ref variable `$x` had at an invocation in the second pass -
	 * kept apart from the observation's `&$x` facts, which a replayed statement's
	 * recorded scope still carries.
	 */
	private const ENTRY_TEMPLATE_PREFIX = '~';

	/** A call invoking such a closure - its statement is walked again in the second pass. */
	private const INVOCATION_TEMPLATE_NAME = '@invokes';

	private const BY_REF_USES_ATTRIBUTE = 'closureSignatureByRefUses';

	private const ARROW_FUNCTION_OUTER_VARIABLES_ATTRIBUTE = 'closureSignatureArrowFunctionOuterVariables';

	private const CLOSED_BODY_ATTRIBUTE = 'closureSignatureClosedBody';

	private const ASSIGNED_CLOSURES_ATTRIBUTE = 'closureSignatureAssignedClosures';

	public function __construct(
		#[AutowiredParameter(ref: '%featureToggles.closureSignaturesFromUsages%')]
		private bool $enabled,
	)
	{
	}

	public static function isClosureSignatureMarker(UnresolvedTemplateArgumentType $marker): bool
	{
		$site = $marker->getSite();

		return $site instanceof Closure || $site instanceof ArrowFunction;
	}

	public static function isReturnMarker(UnresolvedTemplateArgumentType $marker): bool
	{
		return $marker->getTemplateName() === self::RETURN_TEMPLATE_NAME && self::isClosureSignatureMarker($marker);
	}

	public static function isByRefMarker(UnresolvedTemplateArgumentType $marker): bool
	{
		return str_starts_with($marker->getTemplateName(), self::BY_REF_TEMPLATE_PREFIX) && $marker->getSite() instanceof Closure;
	}

	/**
	 * The markers of the by-ref uses of a closure written where nothing types it,
	 * by variable name - while observing, and in the second pass for a site the
	 * observation resolved. Empty for a closure whose invocations cannot be
	 * followed: a generator (invoking it does not run the body), a closure
	 * invoking its own by-ref variable.
	 *
	 * @return array<string, Type>
	 */
	public function getByRefUseMarkers(MutatingScope $scope, Closure|ArrowFunction $expr): array
	{
		if (!$expr instanceof Closure) {
			return [];
		}
		$names = self::byRefUseNames($expr);
		if ($names === []) {
			return [];
		}
		$frame = $this->getFrame($scope);
		if ($frame === null) {
			return [];
		}
		if (!$frame->isObservingClosures() && $frame->getByRefSiteMode($expr) === null) {
			return [];
		}

		$markers = [];
		foreach ($names as $name) {
			$markers[$name] = new UnresolvedTemplateArgumentType(
				$expr,
				TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(), self::BY_REF_TEMPLATE_PREFIX . $name, null, TemplateTypeVariance::createCovariant()),
				$scope->hasVariableType($name)->yes() ? $scope->getVariableType($name) : new NullType(),
			);
		}

		return $markers;
	}

	/**
	 * How the second pass treats the by-ref uses of the closure: `local` when
	 * every invocation was seen, `escaped` when its value went where it can be
	 * invoked at any time, null when the by-ref uses keep the creation-time
	 * fixpoint (no observation, or observing right now).
	 *
	 * @return 'local'|'escaped'|null
	 */
	public function getByRefSiteMode(MutatingScope $scope, Closure $expr): ?string
	{
		$frame = $this->getFrame($scope);
		if ($frame === null || $frame->isObservingClosures()) {
			return null;
		}

		return $frame->getByRefSiteMode($expr);
	}

	/**
	 * Where the fixpoint of an escaped closure's by-ref variable starts: the
	 * state it was created in joined with every state it was invoked from.
	 */
	public function getByRefSeed(MutatingScope $scope, Closure $expr, string $name): ?Type
	{
		$frame = $this->getFrame($scope);
		if ($frame === null || $frame->isObservingClosures()) {
			return null;
		}

		return $frame->resolve($expr, self::BY_REF_TEMPLATE_PREFIX . $name);
	}

	/**
	 * The scope the closure was created in, when the invocation runs in the
	 * same walk of the same function-like - the only place its by-ref
	 * variables are the ones the scope tracks. Its by-value uses enter the
	 * invoked body as they were there.
	 */
	public static function findCreationScope(MutatingScope $scope, ExpressionResultStorage $storage, Closure $expr): ?MutatingScope
	{
		$creationResult = $storage->findExpressionResult($expr);
		if ($creationResult === null) {
			return null;
		}
		$creationScope = $creationResult->getBeforeScope();
		if (
			$creationScope->getAnonymousFunctionReflection() !== $scope->getAnonymousFunctionReflection()
			|| $creationScope->getFunction() !== $scope->getFunction()
		) {
			return null;
		}

		return $creationScope;
	}

	/**
	 * A value captured by another function-like - a closure's use, an arrow
	 * function's outer variable: the closures it carries can be invoked when
	 * that one runs, so their by-ref uses keep the creation-time fixpoint.
	 */
	public static function collectCaptureEscapes(Type $type): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$constraints): Type {
			if ($type instanceof ClosureType) {
				foreach ($type->getByRefUseTypes() as $marker) {
					if (!$marker instanceof UnresolvedTemplateArgumentType) {
						continue;
					}
					$constraints = $constraints->withUnconstrainingSend($marker);
				}
			}

			return $traverse($type);
		});

		return $constraints;
	}

	/**
	 * An invocation of the closure: the type of every by-ref variable at the
	 * call joins the variable's entry, and while observing, the call becomes a
	 * site so the second pass walks its statement again.
	 */
	public static function collectInvocation(MutatingScope $scope, Expr $call, ClosureType $closureType, bool $observing): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		if ($observing) {
			$constraints = $constraints->withSite(new UnresolvedTemplateArgumentType(
				$call,
				TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(), self::INVOCATION_TEMPLATE_NAME, null, TemplateTypeVariance::createInvariant()),
				null,
			));
		}
		foreach ($closureType->getByRefUseTypes() as $name => $marker) {
			if (!$marker instanceof UnresolvedTemplateArgumentType) {
				continue;
			}
			$type = $scope->hasVariableType($name)->yes() ? $scope->getVariableType($name) : new NullType();
			if ($observing) {
				$constraints = $constraints->withLowerBound($marker, $type);
				continue;
			}
			$constraints = $constraints->withLowerBound(
				new UnresolvedTemplateArgumentType($marker->getSite(), TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(), self::ENTRY_TEMPLATE_PREFIX . $name, null, TemplateTypeVariance::createInvariant()), null),
				$type,
			);
		}

		return $constraints;
	}

	/**
	 * The types the by-ref variables of closures had at their second-pass
	 * invocations reaching the scope, by spl_object_id() of the closure and
	 * variable name.
	 *
	 * @return array<int, array<string, Type>>
	 */
	public static function collectByRefEntryTypes(MutatingScope $scope): array
	{
		$constraints = $scope->getTemplateArgumentConstraints();
		if ($constraints === null) {
			return [];
		}

		$types = [];
		foreach ($constraints->getFacts() as [$marker, $type]) {
			if ($type === null || !str_starts_with($marker->getTemplateName(), self::ENTRY_TEMPLATE_PREFIX)) {
				continue;
			}
			$site = $marker->getSite();
			if (!$site instanceof Closure) {
				continue;
			}
			$id = spl_object_id($site);
			$name = substr($marker->getTemplateName(), strlen(self::ENTRY_TEMPLATE_PREFIX));
			$types[$id][$name] = isset($types[$id][$name]) ? TypeCombinator::union($types[$id][$name], $type) : $type;
		}

		return $types;
	}

	/**
	 * The variables of the enclosing scope an arrow function captures - every
	 * variable its body mentions that is not its own parameter.
	 *
	 * @return array<int, string>
	 */
	public static function getArrowFunctionOuterVariables(ArrowFunction $expr): array
	{
		$cached = $expr->getAttribute(self::ARROW_FUNCTION_OUTER_VARIABLES_ATTRIBUTE);
		if (is_array($cached)) {
			return $cached;
		}

		$parameters = [];
		foreach ($expr->params as $param) {
			if (!$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
				continue;
			}
			$parameters[$param->var->name] = true;
		}
		$names = [];
		$stack = [$expr->expr];
		while (count($stack) > 0) {
			$node = array_pop($stack);
			if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassLike) {
				continue;
			}
			if ($node instanceof Closure) {
				// a closure captures through its use clause only
				foreach ($node->uses as $use) {
					$stack[] = $use->var;
				}
				continue;
			}
			if ($node instanceof Expr\Variable && is_string($node->name) && !isset($parameters[$node->name]) && $node->name !== 'this') {
				$names[$node->name] = true;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = $item;
					}
				}
			}
		}
		$names = array_keys($names);
		$expr->setAttribute(self::ARROW_FUNCTION_OUTER_VARIABLES_ATTRIBUTE, $names);

		return $names;
	}

	/**
	 * The names of the closure's by-ref uses whose invocations can be followed;
	 * empty for a generator or a closure invoking one of its own by-ref variables.
	 *
	 * @return array<int, string>
	 */
	private static function byRefUseNames(Closure $expr): array
	{
		$cached = $expr->getAttribute(self::BY_REF_USES_ATTRIBUTE);
		if (is_array($cached)) {
			return $cached;
		}

		$names = [];
		foreach ($expr->uses as $use) {
			if (!$use->byRef || !is_string($use->var->name)) {
				continue;
			}
			$names[] = $use->var->name;
		}
		if ($names !== [] && self::bodyYieldsOrInvokes($expr->stmts, $names)) {
			$names = [];
		}
		$expr->setAttribute(self::BY_REF_USES_ATTRIBUTE, $names);

		return $names;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @param array<int, string> $names
	 */
	private static function bodyYieldsOrInvokes(array $stmts, array $names): bool
	{
		$stack = $stmts;
		while (count($stack) > 0) {
			$node = array_pop($stack);
			if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
				continue;
			}
			if ($node instanceof Expr\Yield_ || $node instanceof Expr\YieldFrom) {
				return true;
			}
			if (
				$node instanceof Expr\FuncCall
				&& $node->name instanceof Expr\Variable
				&& is_string($node->name->name)
				&& in_array($node->name->name, $names, true)
			) {
				return true;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = $item;
					}
				}
			}
		}

		return false;
	}

	/** @return non-empty-string */
	private static function parameterTemplateName(string $parameterName): string
	{
		return '$' . $parameterName;
	}

	/**
	 * The frame the closure's signature is inferred under - null when the
	 * inference is off, the closure is not inside an analysed body, or it has
	 * a context of its own (see ContextualClosureParameterResolver::hasContext()).
	 */
	private function getFrame(MutatingScope $scope): ?TemplateArgumentFrame
	{
		if (!$this->enabled) {
			return null;
		}

		$frame = $scope->getCurrentTemplateArgumentFrame();
		if ($frame === null || !$frame->isObservingClosures()) {
			return $frame;
		}

		// the body is scanned only once one of its closures asks
		$body = $frame->getClosureSignatureBody();
		if ($body === null || !$this->isClosedBody($body, $frame->getClosureSignatureStmts())) {
			return null;
		}

		return $frame;
	}

	/**
	 * Whether the closures written where nothing types them get markers now -
	 * the observation pass of a body whose variables no outside code reaches.
	 */
	public function isObserving(MutatingScope $scope): bool
	{
		$frame = $this->getFrame($scope);

		return $frame !== null && $frame->isObservingClosures();
	}

	/**
	 * The parameters of the closure's own ClosureType: markers while observing,
	 * the resolved types once resolved, the declared ones otherwise.
	 *
	 * @param list<NativeParameterReflection> $declaredParameters
	 * @return list<NativeParameterReflection>
	 */
	public function getSignatureParameters(MutatingScope $scope, Closure|ArrowFunction $expr, array $declaredParameters): array
	{
		$frame = $this->getFrame($scope);
		if ($frame === null) {
			return $declaredParameters;
		}

		$parameters = [];
		foreach ($declaredParameters as $parameter) {
			// a variadic parameter collects arguments of different positions into
			// one list - spread again, one element type fits none of them
			if (!$parameter->passedByReference()->no() || $parameter->isVariadic()) {
				$parameters[] = $parameter;
				continue;
			}

			if ($frame->isObservingClosures() || $frame->isSettledClosureSite($expr)) {
				$type = $this->createParameterMarker($expr, $parameter);
			} else {
				$type = $frame->resolve($expr, self::parameterTemplateName($parameter->getName()));
				if ($type === null) {
					$parameters[] = $parameter;
					continue;
				}
			}

			$parameters[] = new NativeParameterReflection(
				$parameter->getName(),
				$parameter->isOptional(),
				$type,
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$parameter->getDefaultValue(),
			);
		}

		return $parameters;
	}

	/**
	 * The parameter types the body is walked with in the second pass - null
	 * while observing or when nothing was resolved, so the body sees the
	 * declared types.
	 *
	 * @return list<NativeParameterReflection>|null
	 */
	public function getBodyParameters(MutatingScope $scope, Closure|ArrowFunction $expr): ?array
	{
		$frame = $this->getFrame($scope);
		if ($frame === null || $frame->isObservingClosures() || $frame->isSettledClosureSite($expr)) {
			return null;
		}

		$parameters = [];
		$resolvedAny = false;
		foreach ($expr->params as $param) {
			if (!$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
				return null;
			}
			$type = !$param->byRef
				? $frame->resolve($expr, self::parameterTemplateName($param->var->name))
				: null;
			if ($type !== null) {
				$resolvedAny = true;
			}
			$parameters[] = new NativeParameterReflection(
				$param->var->name,
				$param->default !== null || $param->variadic,
				$type ?? new MixedType(),
				$param->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(),
				$param->variadic,
				null,
			);
		}

		return $resolvedAny ? $parameters : null;
	}

	/**
	 * Wraps the body-inferred return type in the return marker while observing,
	 * when the closure returns something whose own typing depends on its
	 * expected type (a closure, an arrow function, an array literal).
	 */
	public function getSignatureReturnType(MutatingScope $scope, Closure|ArrowFunction $expr, Type $returnType): Type
	{
		if ($returnType instanceof UnresolvedTemplateArgumentType) {
			return $returnType;
		}
		$frame = $this->getFrame($scope);
		if (
			$frame === null
			|| (!$frame->isObservingClosures() && !$frame->isSettledClosureSite($expr))
			|| !self::returnsContextTypedExpression($expr)
		) {
			return $returnType;
		}

		return new UnresolvedTemplateArgumentType(
			$expr,
			TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(), self::RETURN_TEMPLATE_NAME, null, TemplateTypeVariance::createCovariant()),
			$returnType,
		);
	}

	/**
	 * The expected type of the expressions the closure returns, resolved from
	 * the callable types it was sent to - null when unknown.
	 */
	public function getExpectedReturnType(MutatingScope $scope, Closure|ArrowFunction $expr): ?Type
	{
		$frame = $this->getFrame($scope);
		if ($frame === null || $frame->isObservingClosures()) {
			return null;
		}

		$type = $frame->resolve($expr, self::RETURN_TEMPLATE_NAME);
		if ($type === null || $type instanceof MixedType) {
			return null;
		}

		return $type;
	}

	/**
	 * The markers a closure creates while observing, as sites of the body, plus
	 * the default values of its optional parameters as contravariant sends - they
	 * join what the closure is invoked with, but alone they say nothing about a
	 * closure nothing invokes.
	 */
	public function collectSites(MutatingScope $scope, Type $closureType): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$frame = $this->getFrame($scope);
		if ($frame === null || !$frame->isObservingClosures()) {
			return $constraints;
		}

		foreach ($closureType->getCallableParametersAcceptors($scope) as $acceptor) {
			foreach ($acceptor->getParameters() as $parameter) {
				$marker = $parameter->getType();
				if (!$marker instanceof UnresolvedTemplateArgumentType || !self::isClosureSignatureMarker($marker)) {
					continue;
				}
				$constraints = $constraints->withSite($marker);
				$default = $parameter->getDefaultValue();
				if ($default === null) {
					continue;
				}

				// an invocation can always omit it
				$constraints = $constraints->withSend($marker, $default, TemplateTypeVariance::createContravariant());
			}
			$returnMarker = $acceptor->getReturnType();
			if (!$returnMarker instanceof UnresolvedTemplateArgumentType || !self::isReturnMarker($returnMarker)) {
				continue;
			}

			$constraints = $constraints->withSite($returnMarker);
		}
		if ($closureType instanceof ClosureType) {
			foreach ($closureType->getByRefUseTypes() as $marker) {
				if (!$marker instanceof UnresolvedTemplateArgumentType) {
					continue;
				}
				$constraints = $constraints->withSite($marker);
			}
		}

		return $constraints;
	}

	private function createParameterMarker(Closure|ArrowFunction $expr, NativeParameterReflection $parameter): UnresolvedTemplateArgumentType
	{
		return new UnresolvedTemplateArgumentType(
			$expr,
			TemplateTypeFactory::create(TemplateTypeScope::createWithAnonymousFunction(), self::parameterTemplateName($parameter->getName()), null, TemplateTypeVariance::createContravariant()),
			$parameter->getType(),
		);
	}

	private static function returnsContextTypedExpression(Closure|ArrowFunction $expr): bool
	{
		if ($expr instanceof ArrowFunction) {
			return self::isContextTyped($expr->expr);
		}

		$cached = $expr->getAttribute('closureSignatureReturnsContextTyped');
		if ($cached !== null) {
			return $cached;
		}

		$returnsContextTyped = false;
		$stack = $expr->stmts;
		while (count($stack) > 0) {
			$node = array_pop($stack);
			if ($node instanceof Node\Stmt\Return_) {
				if ($node->expr !== null && self::isContextTyped($node->expr)) {
					$returnsContextTyped = true;
					break;
				}
				continue;
			}
			if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
				continue;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = $item;
					}
				}
			}
		}

		$expr->setAttribute('closureSignatureReturnsContextTyped', $returnsContextTyped);

		return $returnsContextTyped;
	}

	/**
	 * Whether every place a closure's value can reach while the body runs is in
	 * the body itself. Not so when the body shares variables with code outside
	 * of it: `global`, `$GLOBALS`, variable variables, include/eval,
	 * extract()/compact()/get_defined_vars(), or a write to a variable bound by
	 * reference to the caller (a by-reference parameter or use). Closures and
	 * arrow functions inside the body are scanned too - they run on its behalf.
	 *
	 * @param Node\Stmt[] $stmts
	 */
	public function isClosedBody(Node $functionLike, array $stmts): bool
	{
		if (!$this->enabled) {
			return false;
		}

		$cached = $functionLike->getAttribute(self::CLOSED_BODY_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$closed = self::scanClosedBody($functionLike, $stmts);
		$functionLike->setAttribute(self::CLOSED_BODY_ATTRIBUTE, $closed);

		return $closed;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 */
	private static function scanClosedBody(Node $functionLike, array $stmts): bool
	{
		$byRefNames = [];
		if ($functionLike instanceof Node\FunctionLike) {
			foreach ($functionLike->getParams() as $param) {
				if (!$param->byRef || !$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
					continue;
				}
				$byRefNames[$param->var->name] = true;
			}
		}
		if ($functionLike instanceof Closure) {
			foreach ($functionLike->uses as $use) {
				if (!$use->byRef || !is_string($use->var->name)) {
					continue;
				}
				$byRefNames[$use->var->name] = true;
			}
		}

		$writtenNames = [];
		$stack = $stmts;
		while (count($stack) > 0) {
			$node = array_pop($stack);
			if ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\Function_) {
				continue;
			}
			if (
				$node instanceof Node\Stmt\Global_
				|| $node instanceof Expr\Include_
				|| $node instanceof Expr\Eval_
			) {
				return false;
			}
			if ($node instanceof Expr\Variable && (!is_string($node->name) || $node->name === 'GLOBALS')) {
				return false;
			}
			if (
				$node instanceof Expr\FuncCall
				&& $node->name instanceof Node\Name
				&& in_array($node->name->toLowerString(), ['extract', 'compact', 'get_defined_vars'], true)
			) {
				return false;
			}
			if ($node instanceof Node\FunctionLike) {
				foreach ($node->getParams() as $param) {
					if (!$param->byRef || !$param->var instanceof Expr\Variable || !is_string($param->var->name)) {
						continue;
					}
					$byRefNames[$param->var->name] = true;
				}
			}
			if ($node instanceof Expr\Assign || $node instanceof Expr\AssignRef || $node instanceof Expr\AssignOp) {
				self::collectTargetNames($node->var, $writtenNames);
			} elseif ($node instanceof Node\Stmt\Foreach_) {
				self::collectTargetNames($node->valueVar, $writtenNames);
				if ($node->keyVar !== null) {
					self::collectTargetNames($node->keyVar, $writtenNames);
				}
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = $item;
					}
				}
			}
		}

		foreach (array_keys($writtenNames) as $name) {
			if (isset($byRefNames[$name])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether an invocation of the closure returns what the body returns for
	 * its arguments (see FuncCallHandler): its signature is resolved - a body
	 * walked once, with no sites of its own, invokes the resolved closures of
	 * the body it is written in.
	 */
	public function infersInvocationReturnType(MutatingScope $scope, ClosureType $closureType): bool
	{
		if (!$this->enabled || $scope->getCurrentTemplateArgumentFrame() === null) {
			return false;
		}

		$containsMarker = false;
		TypeTraverser::map($closureType, static function (Type $type, callable $traverse) use (&$containsMarker): Type {
			if ($type instanceof UnresolvedTemplateArgumentType) {
				$containsMarker = true;
			}

			return $containsMarker ? $type : $traverse($type);
		});

		return !$containsMarker;
	}

	/**
	 * The closures and arrow functions assigned to the local variable in the
	 * body being walked and - a variable a closure captures - in the bodies
	 * enclosing it.
	 *
	 * @return list<Closure|ArrowFunction>
	 */
	public function findAssignedClosures(MutatingScope $scope, string $name): array
	{
		if (!$this->enabled) {
			return [];
		}

		$closures = [];
		for ($frame = $scope->getCurrentTemplateArgumentFrame(); $frame !== null; $frame = $frame->getParent()) {
			$body = $frame->getClosureSignatureBody();
			if ($body === null) {
				continue;
			}
			foreach (self::getAssignedClosures($body, $frame->getClosureSignatureStmts())[$name] ?? [] as $closure) {
				$closures[] = $closure;
			}
		}

		return $closures;
	}

	/**
	 * @param Node\Stmt[] $stmts
	 * @return array<string, list<Closure|ArrowFunction>>
	 */
	private static function getAssignedClosures(Node $body, array $stmts): array
	{
		/** @var array<string, list<Closure|ArrowFunction>>|null $cached */
		$cached = $body->getAttribute(self::ASSIGNED_CLOSURES_ATTRIBUTE);
		if ($cached !== null) {
			return $cached;
		}

		$assigned = [];
		$stack = $stmts;
		while (count($stack) > 0) {
			$node = array_pop($stack);
			if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
				continue;
			}
			if (
				$node instanceof Expr\Assign
				&& $node->var instanceof Expr\Variable
				&& is_string($node->var->name)
				&& ($node->expr instanceof Closure || $node->expr instanceof ArrowFunction)
			) {
				$assigned[$node->var->name][] = $node->expr;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $item) {
						if (!$item instanceof Node) {
							continue;
						}
						$stack[] = $item;
					}
				}
			}
		}
		$body->setAttribute(self::ASSIGNED_CLOSURES_ATTRIBUTE, $assigned);

		return $assigned;
	}

	/**
	 * @param array<string, true> $names
	 */
	private static function collectTargetNames(Expr $target, array &$names): void
	{
		while ($target instanceof Expr\ArrayDimFetch) {
			$target = $target->var;
		}
		if ($target instanceof Expr\Variable && is_string($target->name)) {
			$names[$target->name] = true;
			return;
		}
		if (!$target instanceof Expr\List_ && !$target instanceof Expr\Array_) {
			return;
		}

		foreach ($target->items as $item) {
			if ($item === null) {
				continue;
			}
			self::collectTargetNames($item->value, $names);
		}
	}

	private static function isContextTyped(Expr $expr): bool
	{
		return $expr instanceof Closure || $expr instanceof ArrowFunction || $expr instanceof Expr\Array_;
	}

}
