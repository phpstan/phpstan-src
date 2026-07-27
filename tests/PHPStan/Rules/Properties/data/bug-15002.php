<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15002Properties;

class Acme {
	public ?Acme $prop = null;

	public function get(): ?Acme {
		return $this->prop;
	}
}

function getAcme(bool $enabled = true): ?Acme {
	return $enabled ? new Acme() : null;
}

function repeatedNullsafePropertyFetch(): void
{
	$a = getAcme()?->prop;
	$b = getAcme()?->prop;
}

function repeatedChain(): void
{
	$a = getAcme()?->prop?->prop;
	$b = getAcme()?->prop?->prop;
}

function repeatedMethodCallChain(Acme $o): void
{
	$a = $o->get()?->prop;
	$b = $o->get()?->prop;
}

function repeatedChainEndingInMethodCall(): void
{
	$a = getAcme()?->prop?->prop?->get();
	$b = getAcme()?->prop?->prop?->get();
}

function maybeCertaintyFromMerge(bool $c): void
{
	if ($c) {
		if (getAcme() !== null) {
			echo 1;
		}
	}

	$b = getAcme()?->prop;
}
