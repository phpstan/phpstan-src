<?php declare(strict_types = 1);

namespace PureBuiltins;

/** @phpstan-pure */
function calendar(int $year): int
{
	return cal_days_in_month(CAL_GREGORIAN, 2, $year) + gregoriantojd(1, 1, $year);
}

/** @phpstan-pure */
function strings(string $a, string $b): int
{
	return levenshtein($a, $b) + strlen((string) hex2bin('00'));
}

/** @phpstan-pure */
function passwords(string $password, string $hash): bool
{
	return password_verify($password, $hash) && !password_needs_rehash($hash, PASSWORD_DEFAULT);
}

/**
 * @phpstan-pure
 * @param non-empty-string $key
 */
function sodium(string $message, string $key): string
{
	return sodium_bin2hex(sodium_crypto_generichash($message, $key));
}

/** @phpstan-pure */
function objectId(object $o): int
{
	return spl_object_id($o);
}

/** @phpstan-pure */
function throwables(\Throwable $e): string
{
	return $e->getMessage() . $e->getFile() . $e->getLine();
}

/** @phpstan-pure */
function dates(\DateTimeImmutable $d, \DateInterval $i): string
{
	return $d->getMicrosecond() . $i->format('%d') . (new \DateTimeZone('UTC'))->getName();
}

/**
 * @phpstan-pure
 * @param \ArrayObject<int, int> $a
 * @param \SplObjectStorage<object, mixed> $s
 */
function spl(\ArrayObject $a, \SplObjectStorage $s, object $o): int
{
	return $a->count() + count($a->getArrayCopy()) + ($s->contains($o) ? 1 : 0);
}

/**
 * @phpstan-pure
 * @param \ReflectionClass<object> $c
 */
function reflection(\ReflectionClass $c, \ReflectionProperty $p): bool
{
	return $c->hasMethod('foo') && $c->isEnum() && $p->hasType() && $p->isReadOnly();
}
