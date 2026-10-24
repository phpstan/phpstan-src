<?php declare(strict_types = 1);

namespace Bug15392;

/** @phpstan-sealed SubA|SubB */
abstract class BaseEx extends \RuntimeException {}
final class SubA extends BaseEx {}
final class SubB extends BaseEx {}
final class OtherEx extends \RuntimeException {}

/** @throws BaseEx */
function process(): void
{
	throw new SubA();
}

/** @throws void */
function g(): void
{
	try {
		process();
	} catch (BaseEx | OtherEx $e) {
	}
}

/** @throws void */
function h(): void
{
	try {
		process();
	} catch (OtherEx | BaseEx $e) {
	}
}
