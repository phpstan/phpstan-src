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

/** @throws \RuntimeException */
function name(): string { return ''; }

function d(): void
{
	inTransaction(function (): void { try { var_dump(name()); } catch (\RuntimeException $e) { throw $e; } });
}

final class Item { public ?string $name = null; public function label(): string { return ''; } }

/**
 * @param list<Item> $items
 */
function e(?int $n, bool $b, array $items): void
{
	inTransaction(function () use ($n): void { $x = $n ?? 1; throw new \Exception(); });
	inTransaction(function () use ($n): void { if (isset($n)) { $n++; } throw new \Exception(); });
	inTransaction(function () use ($b): void { if ($b) { $y = 1; } else { $y = 2; } throw new \Exception(); });
	inTransaction(function () use ($b): void { $z = $b ? 1 : 2; throw new \Exception(); });
	inTransaction(function () use ($b): void { takesTwo($b ? 1 : 2, throw new \Exception()); });
	inTransaction(function () use ($items): void { foreach ($items as $item) { $item->name = 'x'; } throw new \Exception(); });
	inTransaction(function () use ($items): void { foreach ($items as $i => $item) { if ($item->name !== null) { break; } $label = $items[$i]->label(); } throw new \Exception(); });
	inTransaction(function () use ($b): void { while ($b) { $b = false; } throw new \Exception(); });
	inTransaction(function (): void { try { var_dump(name()); } finally { $f = 1; } throw new \Exception(); });
	inTransaction(function (): void { try { var_dump(name(), name()); } catch (\RuntimeException $e) { throw $e; } });
	inTransaction(function (): void { $c = \Closure::bind(static function (): void {}, null, null); throw new \Exception(); });
}
