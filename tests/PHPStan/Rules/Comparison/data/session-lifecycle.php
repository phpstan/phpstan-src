<?php declare(strict_types = 1);

namespace SessionLifecycle;

function startIfNeeded(): void
{
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
		if (session_status() === PHP_SESSION_ACTIVE) {
			echo 'started';
		}
	}
}

function startWithoutId(): void
{
	if (session_id() === '') {
		session_start();
		if (session_id() !== '') {
			echo 'started';
		}
	}
}

function closeIfActive(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		session_write_close();
		if (session_status() === PHP_SESSION_NONE) {
			echo 'closed';
		}
	}
}
