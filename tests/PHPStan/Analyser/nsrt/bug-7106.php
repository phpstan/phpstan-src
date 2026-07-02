<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug7106;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertNativeType;
use function openssl_error_string;

Class Example
{
    public function openSslError(string $signature): string
    {
        assertType('string|false', openssl_error_string());
        assertNativeType('string|false', openssl_error_string());

        if (false === \openssl_error_string()) {
            assertType('false', openssl_error_string());
            assertNativeType('false', openssl_error_string());
            openssl_sign('1', $signature, '');
            assertType('string|false', openssl_error_string());
            assertNativeType('string|false', openssl_error_string());
        }
    }

    public function impureCallForgetsOpenSslError(string $signature): void
    {
        if (false === \openssl_error_string()) {
            assertType('false', openssl_error_string());
            // the impure method may call openssl_*() transitively
            $this->doImpure($signature);
            assertType('string|false', openssl_error_string());
        }
    }

    public function doImpure(string $signature): void
    {
        openssl_sign('1', $signature, '');
    }
}
