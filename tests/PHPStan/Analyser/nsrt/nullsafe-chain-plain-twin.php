<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeChainPlainTwin;

use function PHPStan\Testing\assertType;

class Order {}
class Tx { public function getOrder(): ?Order { return null; } }
class Cap { public function getTransaction(): ?Tx { return null; } }
class Refund { public function getTransactionCapture(): ?Cap { return null; } }

function truthy(Refund $r): void
{
	if ($r->getTransactionCapture()?->getTransaction()?->getOrder()) {
		assertType('NullsafeChainPlainTwin\Order', $r->getTransactionCapture()->getTransaction()->getOrder());
		assertType('NullsafeChainPlainTwin\Tx', $r->getTransactionCapture()->getTransaction());
	}
}

function falsey(Refund $r): void
{
	if (!$r->getTransactionCapture()?->getTransaction()?->getOrder()) {
		assertType('NullsafeChainPlainTwin\Cap|null', $r->getTransactionCapture());
	}
}

function guardReturn(Refund $r): void
{
	if (!$r->getTransactionCapture()?->getTransaction()?->getOrder()) {
		return;
	}
	assertType('NullsafeChainPlainTwin\Order', $r->getTransactionCapture()->getTransaction()->getOrder());
}
