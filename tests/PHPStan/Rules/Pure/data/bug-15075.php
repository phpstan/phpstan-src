<?php declare(strict_types = 1);

namespace Bug15075;

/**
 * @pure-unless-callable-is-impure $fun
 * @param callable():void $fun
 */
function haveFun(callable $fun): void {
    $fun();
}

/**
 * @pure-unless-callable-is-impure $fun
 * @param callable():void $fun
 */
function indirectFun(callable $fun): void {
    haveFun($fun);
}

/**
 * @pure-unless-callable-is-impure $fun
 * @pure-unless-callable-is-impure $other
 * @param callable():void $fun
 * @param callable():void $other
 */
function haveTwoFuns(callable $fun, callable $other): void {
    $fun();
    $other();
}

/**
 * @pure-unless-callable-is-impure $fun
 * @param callable():void $fun
 */
function forwardsNextToImpure(callable $fun): void {
    haveTwoFuns($fun, static function (): void {
        echo 'a';
    });
}

/**
 * @pure-unless-callable-is-impure $fun
 * @param callable():void $fun
 */
function forwardsNextToPure(callable $fun): void {
    haveTwoFuns($fun, static function (): void {
    });
}

/**
 * @param callable():void $fun
 */
function impureHaveFun(callable $fun): void {
    $fun();
}

/**
 * @pure-unless-callable-is-impure $fun
 * @param callable():void $fun
 */
function forwardsToImpure(callable $fun): void {
    impureHaveFun($fun);
}

/** @phpstan-pure */
function pureCaller(): int {
    haveFun(static function (): void {
    });
    haveFun(static function (): void {
        echo 'a';
    });

    return 1;
}
