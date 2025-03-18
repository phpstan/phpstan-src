<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12748;

use SessionHandlerInterface;

class HelloWorld
{
	public function getHandler(): SessionHandlerInterface
	{
		return new SessHandler;
	}
}

class SessHandler implements SessionHandlerInterface
{

    public function close(): bool
    {
        return true;
    }

    public function destroy(string $id): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return false;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        return false;
    }

    public function write(string $id, string $data): bool
    {
        return true;
    }
}

$sessionHandler = (new HelloWorld)->getHandler();
$session = $sessionHandler->read('123');

if ($session === false) {
	return null;
}
