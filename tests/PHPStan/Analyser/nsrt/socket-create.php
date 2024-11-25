<?php // lint >= 7.4

declare(strict_types=1);

namespace SocketCreate;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function createSocket(): void
	{
		if (PHP_VERSION_ID < 80000) {
			assertType('resource|false', socket_create(AF_INET, SOCK_DGRAM, SOL_UDP));
		}

		if (PHP_VERSION_ID >= 80000) {
			assertType('\Socket|false', socket_create(AF_INET, SOCK_DGRAM, SOL_UDP));
		}
	}

	public function addrinfo($host): void
	{
		if (PHP_VERSION_ID < 80000) {
			assertType('array<resource>|false', socket_addrinfo_lookup($host));
		}

		if (PHP_VERSION_ID >= 80000) {
			assertType('array<\AddressInfo>|false', socket_addrinfo_lookup($host));
		}
	}
}
