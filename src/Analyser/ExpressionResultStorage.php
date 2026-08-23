<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function spl_object_id;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\ExpressionResultStorage', implementation: __DIR__ . '/../../turbo-ext/src/ExpressionResultStorage.cpp')]
final class ExpressionResultStorage
{

	/**
	 * Keeps every stored Expr alive so its spl_object_id() cannot be reused
	 * by another node while $scopesById still maps it.
	 *
	 * @var array<int, Expr>
	 */
	private array $exprsById = [];

	/** @var array<int, Scope> */
	private array $scopesById = [];

	public function duplicate(): self
	{
		$new = new self();
		$new->exprsById = $this->exprsById;
		$new->scopesById = $this->scopesById;
		return $new;
	}

	public function storeBeforeScope(Expr $expr, Scope $scope): void
	{
		$id = spl_object_id($expr);
		$this->exprsById[$id] = $expr;
		$this->scopesById[$id] = $scope;
	}

	public function findBeforeScope(Expr $expr): ?Scope
	{
		return $this->scopesById[spl_object_id($expr)] ?? null;
	}

}
