<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use SplObjectStorage;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\ExpressionResultStorage', implementation: __DIR__ . '/../../turbo-ext/src/ExpressionResultStorage.cpp')]
final class ExpressionResultStorage
{

	/** @var SplObjectStorage<Expr, ExpressionResult> */
	private SplObjectStorage $exprResults;

	/**
	 * Read-only fallback - writes never reach it. Makes duplicate() O(1)
	 * instead of copying all stored results.
	 */
	private ?self $fallback = null;

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

}
