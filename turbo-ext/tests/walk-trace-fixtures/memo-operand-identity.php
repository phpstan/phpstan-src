<?php declare(strict_types = 1);

namespace WalkTraceMemoOperandIdentity;

// reduced from tests/PHPStan/Rules/Variables/data/bug-13623.php: the nested
// writes reach ArrayType::setExistingOffsetValueType(), which tells "nothing
// was written" by `TypeCombinator::union(...) === $this->itemType` - a memo hit
// must return the operand of the call at hand, not the one it was computed for
function (array $results): void {
	foreach ($results as $row) {
		$customers[$row['customer_id']] ??= [];
		$customers[$row['customer_id']]['orders'][$row['order_id']] ??= [];
		$customers[$row['customer_id']]['orders'][$row['order_id']]['balance_forward'] ??= 0;
		$customers[$row['customer_id']]['orders'][$row['order_id']]['new_invoice'] ??= 0;
		$customers[$row['customer_id']]['orders'][$row['order_id']]['payments'] ??= 0;
	}
};
