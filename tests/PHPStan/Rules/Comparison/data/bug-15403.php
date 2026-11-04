<?php declare(strict_types = 1);

namespace Bug15403;

class Expr
{
	/** @return string */
	public function getTitle() { return 't'; }
	/** @return string */
	public function getExpression() { return 'e'; }
}

interface Binding
{
	/** @return Expr */
	public function getCondition();
}

function sig(Binding $b): string
{
	$condition = $b->getCondition();
	return implode("\0", [
		$condition === null ? '' : (string) $condition->getTitle(),
		$condition === null ? '' : (string) $condition->getExpression(),
	]);
}

function assigned(Binding $b): void
{
	$condition = $b->getCondition();
	$x = $condition === null ? '' : (string) $condition->getTitle();
	$y = $condition === null ? '' : (string) $condition->getExpression();
}

function shortTernary(Binding $b): void
{
	$condition = $b->getCondition();
	$x = $condition === null ?: 'a';
	$y = $condition === null ?: 'b';
}

function negated(Binding $b): void
{
	$condition = $b->getCondition();
	$x = $condition !== null ? 'a' : 'b';
	$y = $condition !== null ? 'a' : 'b';
}
