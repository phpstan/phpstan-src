<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Analyser\Generics\TemplateArgumentFrame;
use PHPStan\Turbo\ShadowedByTurboExtension;
use SplObjectStorage;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ExpressionResultStorage.cpp')]
final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, ExpressionResult> */
	private SplObjectStorage $exprResults;

	/**
	 * Read-only fallback - writes never reach it. Makes duplicate() O(1)
	 * instead of copying all stored results.
	 */
	private ?self $fallback = null;

	/**
	 * The walks of closure bodies run for invocations of closures whose by-ref
	 * uses are followed (see ClosureProcessor::processByRefInvocation()),
	 * keyed by the closure: [frame, until fixpoint, native types promoted,
	 * entry scope, exit scope, throw points]. The walk is a function of these,
	 * so an invocation entering the body in a state already walked reads it.
	 *
	 * @var SplObjectStorage<Expr\Closure, list<array{TemplateArgumentFrame|null, bool, bool, MutatingScope, MutatingScope, list<InternalThrowPoint>}>>|null
	 */
	private ?SplObjectStorage $byRefInvocationWalks = null;

	public function __construct()
	{
		$this->exprResults = new SplObjectStorage();
	}

	public function duplicate(): self
	{
		$new = new self();
		$new->fallback = $this;
		return $new;
	}

	public function mergeResults(self $other): void
	{
		$this->exprResults->addAll($other->exprResults);
	}

	public function storeExpressionResult(Expr $expr, ExpressionResult $expressionResult): void
	{
		$this->exprResults[$expr] = $expressionResult;
	}

	public function findExpressionResult(Expr $expr): ?ExpressionResult
	{
		return $this->exprResults[$expr] ?? ($this->fallback !== null ? $this->fallback->findExpressionResult($expr) : null);
	}

	public function removeExpressionResult(Expr $expr): void
	{
		unset($this->exprResults[$expr]);
	}

	/**
	 * @param array{TemplateArgumentFrame|null, bool, bool, MutatingScope, MutatingScope, list<InternalThrowPoint>} $walk
	 */
	public function storeByRefInvocationWalk(Expr\Closure $closure, array $walk): void
	{
		$this->byRefInvocationWalks ??= new SplObjectStorage();
		$walks = $this->byRefInvocationWalks[$closure] ?? [];
		$walks[] = $walk;
		$this->byRefInvocationWalks[$closure] = $walks;
	}

	/**
	 * The walks stored here and in the storages this one falls back to.
	 *
	 * @return list<array{TemplateArgumentFrame|null, bool, bool, MutatingScope, MutatingScope, list<InternalThrowPoint>}>
	 */
	public function findByRefInvocationWalks(Expr\Closure $closure): array
	{
		$walks = $this->byRefInvocationWalks[$closure] ?? [];
		if ($this->fallback === null) {
			return $walks;
		}

		return [...$walks, ...$this->fallback->findByRefInvocationWalks($closure)];
	}

}
