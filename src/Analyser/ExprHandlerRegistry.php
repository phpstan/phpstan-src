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
 *
 * Call-likes (Expr\CallLike) are never memoized: their handlers select on
 * isFirstClassCallable(), which the Expr class alone does not determine. Every
 * other handler's supports() is a pure instanceof check, so the class fully
 * determines it.
 */
#[AutowiredService]
final class ExprHandlerRegistry
{

	/** @var array<class-string<Expr>, ExprHandler<Expr>|false> */
	private array $exprHandlersByClass = [];

	public function __construct(private Container $container)
	{
	}

	/**
	 * @return ExprHandler<Expr>|null
	 */
	public function resolve(Expr $expr): ?ExprHandler
	{
		if (!$expr instanceof Expr\CallLike) {
			$cached = $this->exprHandlersByClass[get_class($expr)] ?? null;
			if ($cached !== null) {
				return $cached === false ? null : $cached;
			}
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

		if (!$expr instanceof Expr\CallLike) {
			$this->exprHandlersByClass[get_class($expr)] = $matchedHandler ?? false;
		}

		return $matchedHandler;
	}

}
