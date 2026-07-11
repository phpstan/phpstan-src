<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use function get_class;

/**
 * Resolves which ExprHandler handles a given Expr, memoizing the result by
 * Expr class so dispatch does not re-scan every tagged handler (a linear
 * supports() sweep) on each call.
 */
#[AutowiredService]
final class ExprHandlerRegistry
{

	/** @var array<non-empty-string, ExprHandler<Expr>|false> */
	private array $exprHandlersByClass = [];

	public function __construct(private Container $container)
	{
	}

	/**
	 * @return ExprHandler<Expr>|null
	 */
	public function resolve(Expr $expr): ?ExprHandler
	{
		$cacheKey = get_class($expr);
		if ($expr instanceof Expr\CallLike) {
			$cacheKey .= '|' . $expr->isFirstClassCallable();
		}

		$cached = $this->exprHandlersByClass[$cacheKey] ?? null;
		if ($cached !== null) {
			return $cached === false ? null : $cached;
		}

		$matchedHandler = null;
		/** @var ExprHandler<Expr> $exprHandler */
		foreach ($this->container->getServicesByTag(ExprHandler::EXTENSION_TAG) as $exprHandler) {
			if (!$exprHandler->supports($expr)) {
				continue;
			}

			$matchedHandler = $exprHandler;
			break;
		}

		$this->exprHandlersByClass[$cacheKey] = $matchedHandler ?? false;

		return $matchedHandler;
	}

}
