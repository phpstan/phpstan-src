<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_keys;
use function array_pop;
use function count;
use function in_array;
use function is_array;
use function is_string;

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

	private const CLOSED_BODY_ATTRIBUTE = 'closureSignatureClosedBody';

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
		if ($frame === null || !$frame->isObserving()) {
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

		return $frame !== null && $frame->isObserving();
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
			if (!$parameter->passedByReference()->no()) {
				$parameters[] = $parameter;
				continue;
			}

			if ($frame->isObserving()) {
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
		if ($frame === null || $frame->isObserving()) {
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
		if ($frame === null || !$frame->isObserving() || !self::returnsContextTypedExpression($expr)) {
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
		if ($frame === null || $frame->isObserving()) {
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
		if ($frame === null || !$frame->isObserving()) {
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
