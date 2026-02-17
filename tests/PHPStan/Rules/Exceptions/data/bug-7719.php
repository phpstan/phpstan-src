<?php declare(strict_types = 1);

namespace Bug7719;

class Endpoint {
	public function run(int $id): int {
		return $id;
	}
}

class HelloWorld
{
	public function sayHello(Endpoint $endpoint, string $methodName): void
	{
		try {
			$methodResponse = (new \ReflectionMethod($endpoint, $methodName))->invokeArgs($endpoint, ['id' => 2]);
		} catch (\RuntimeException $e) {
			echo $e->getMessage();
			die;
		}
		var_dump($methodResponse);
	}

	public function sayHelloInvoke(Endpoint $endpoint, string $methodName): void
	{
		try {
			$methodResponse = (new \ReflectionMethod($endpoint, $methodName))->invoke($endpoint, 2);
		} catch (\RuntimeException $e) {
			echo $e->getMessage();
			die;
		}
		var_dump($methodResponse);
	}
}
