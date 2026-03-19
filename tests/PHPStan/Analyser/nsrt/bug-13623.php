<?php

declare(strict_types = 1);

namespace Bug13623;

use function PHPStan\Testing\assertType;

function (array $results): void {
	$customers = [];

	foreach ($results as $row) {
		$customers[$row['customer_id']] ??= [];
		$customers[$row['customer_id']]['orders'] ??= [];
		$customers[$row['customer_id']]['orders'][$row['order_id']] ??= [];

		$customers[$row['customer_id']]['orders'][$row['order_id']]['balance_forward'] ??= 0;
		$customers[$row['customer_id']]['orders'][$row['order_id']]['new_invoice'] ??= 0;
		$customers[$row['customer_id']]['orders'][$row['order_id']]['payments'] ??= 0;
		$customers[$row['customer_id']]['orders'][$row['order_id']]['balance'] ??= $row['order_total'];
	}

	assertType("array<array{orders: non-empty-array<array{balance_forward: 0, new_invoice: 0, payments: 0, balance: mixed}>}>", $customers);
};
