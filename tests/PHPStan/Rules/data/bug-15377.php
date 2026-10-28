<?php // lint >= 8.0

namespace Bug15377;

interface Mailer { public function send(): void; }
interface Counter { public function next(): int; }
function inTransaction(callable $callback): void {}

function a(Mailer $mailer, Counter $counter): void
{
	inTransaction(function () use ($counter): void {
		$counter->next();
		throw new \Exception();
	});
	inTransaction(function () use ($mailer): void {
		$mailer->send();
		throw new \Exception();
	});
}

final class Concrete { public function send(): void {} }
interface Impure { /** @phpstan-impure */ public function next(): int; }
function voidFn(): void {}

function b(Concrete $c, Impure $i): void
{
	inTransaction(function () use ($c): void { $c->send(); throw new \Exception(); });
	inTransaction(function (): void { voidFn(); throw new \Exception(); });
	inTransaction(function () use ($i): void { $i->next(); throw new \Exception(); });
}

interface Queue
{
	/** @phpstan-impure */
	public function next(): int;
	public function peek(): ?int;
}
function takesInt(int $i): void {}
function takesTwo(int $i, mixed $j): void {}

function c(Queue $q): void
{
	inTransaction(function () use ($q): void { $x = $q->peek(); takesInt($q->next()); throw new \Exception(); });
	inTransaction(function () use ($q): void { if ($q->peek() !== null) { takesTwo($q->next(), throw new \Exception()); } });
}
