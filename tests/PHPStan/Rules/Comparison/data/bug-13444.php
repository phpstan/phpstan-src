<?php declare(strict_types = 1);

namespace Bug13444;

function checkStoreCas(): void
{
	$memcached = new \Memcached();

	do {
		$extendedReturn = $memcached->get('key', null, \Memcached::GET_EXTENDED);

		if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
			return;
		}

		if (!is_array($extendedReturn) || !isset($extendedReturn['value'], $extendedReturn['cas'])) {
			return;
		}

		$data = $extendedReturn['value'];
		$cas = $extendedReturn['cas'];
		\assert(is_float($cas));

		// Do some work on the data..
		$memcached->cas($cas, 'key', $data);

	} while ($memcached->getResultCode() !== \Memcached::RES_SUCCESS);
}

function checkStoreCasByKey(string $key): void
{
	$memcached = new \Memcached();

	do {
		$extendedReturn = $memcached->get($key, null, \Memcached::GET_EXTENDED);

		if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
			return;
		}

		if (!is_array($extendedReturn) || !isset($extendedReturn['value'], $extendedReturn['cas'])) {
			return;
		}

		$data = $extendedReturn['value'];
		$cas = $extendedReturn['cas'];
		\assert(is_float($cas));

		// Do some work on the data..
		$memcached->casByKey($cas, 'server', $key, $data);

	} while ($memcached->getResultCode() !== \Memcached::RES_SUCCESS);
}

function checkAddMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->add('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->add('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkAddByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->addByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->addByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkAppendMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->append('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->append('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkAppendByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->appendByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->appendByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDecrementMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->decrement('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->decrement('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDecrementByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->decrementByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->decrementByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDeleteMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->delete('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->delete('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDeleteByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->deleteByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->deleteByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDeleteMultiMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->deleteMulti(['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->deleteMulti(['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkDeleteMultiByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->deleteMultiByKey('server', ['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->deleteMultiByKey('server', ['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkFetchMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->fetch();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->fetch();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkFetchAllMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->fetchAll();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->fetchAll();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkFlushMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->flush();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->flush();

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->get('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->get('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetDelayedMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getDelayed(['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getDelayed(['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetDelayedByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getDelayedByKey('server', ['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getDelayedByKey('server', ['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetMultiMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getMulti(['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getMulti(['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetMultiByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getMultiByKey('server', ['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getMultiByKey('server', ['key']);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkGetServerByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->getServerByKey('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->getServerByKey('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkIncrementMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->increment('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->increment('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkIncrementByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->incrementByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->incrementByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkPrependMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->prepend('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->prepend('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkPrependByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->prependByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->prependByKey('server', 'key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkReplaceMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->replace('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->replace('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkReplaceByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->replaceByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->replaceByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkSetMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->set('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->set('key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkSetByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->setByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->setByKey('server', 'key', 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkSetMultiMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->setMulti(['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->setMulti(['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkSetMultiByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->setMultiByKey('server', ['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->setMultiByKey('server', ['key'], 'value');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkTouchMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->touch('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->touch('key');

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}

function checkTouchByKeyMultiple(): void
{
	$memcached = new \Memcached();

	$memcached->touchByKey('server', 'key', 0);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}

	$memcached->touchByKey('server', 'key', 0);

	if ($memcached->getResultCode() !== \Memcached::RES_SUCCESS) {
		return;
	}
}
