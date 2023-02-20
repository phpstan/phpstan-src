<?php

namespace Bug5986;

/**
 * @param array{
 *     mov?: int,
 *     appliesTo?: string,
 *     expireDate?: string|null,
 *     effectiveFrom?: string,
 *     merchantId?: int,
 *     link?: string,
 *     channel?: string,
 *     voucherExternalId?: int
 * } $data
 */
function test(array $data): bool {
	return test2($data);
}

/**
 * @param array{
 *     mov?: int,
 *     appliesTo?: string,
 *     expireDate?: string|null,
 *     effectiveFrom?: int,
 *     merchantId?: int,
 *     link?: string,
 *     channel?: string,
 *     voucherExternalId?: int
 * } $data
 */
function test2(array $data): bool {
	return true;
}
