<?php declare(strict_types = 1);

namespace Bug14519Analogous;

use function PHPStan\Testing\assertType;

// Test old apc_fetch (analogous to apcu_fetch)
$val = apc_fetch('key1');
assertType('mixed', $val);
if ($val === false) {
	die();
}
assertType('mixed~false', $val);
$val = apc_fetch('key1');
assertType('mixed', $val);

// Test opcache_get_status (returns array|false)
$status = opcache_get_status();
assertType('array|false', $status);
if ($status === false) {
	die();
}
$status = opcache_get_status();
assertType('array|false', $status);

// Test shm_get_var (returns mixed)
/** @var \SysvSharedMemory $shm */
$shm = shm_attach(1234);
$shmVal = shm_get_var($shm, 1);
assertType('mixed', $shmVal);
if ($shmVal === false) {
	die();
}
$shmVal = shm_get_var($shm, 1);
assertType('mixed', $shmVal);
