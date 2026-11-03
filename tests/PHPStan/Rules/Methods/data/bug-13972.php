<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug13972Methods;

class HelloWorld
{
	public function getAssignment(string $flagKey, string|bool $defaultValue): string|bool
    {
            $type = gettype($defaultValue);

            return match ($type) {
                'string' => $this->getString($defaultValue),
                'boolean' => $this->getBool($defaultValue),
            };
    }

	public function getBool(bool $default): bool {
		return true;
	}

	public function getString(string $default): string {
		return "toto";
	}
}
