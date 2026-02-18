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

	public function sayHelloWithInvoke(Endpoint $endpoint, string $methodName): void
	{
		try {
			$methodResponse = (new \ReflectionMethod($endpoint, $methodName))->invoke($endpoint, 2);
		} catch (\RuntimeException $e) {
			echo $e->getMessage();
			die;
		}
		var_dump($methodResponse);
	}

	public function sayHelloWithFunction(string $functionName): void
	{
		try {
			$result = (new \ReflectionFunction($functionName))->invokeArgs([1, 2]);
		} catch (\RuntimeException $e) {
			echo $e->getMessage();
			die;
		}
		var_dump($result);
	}

	public function sayHelloWithFunctionInvoke(string $functionName): void
	{
		try {
			$result = (new \ReflectionFunction($functionName))->invoke(1, 2);
		} catch (\RuntimeException $e) {
			echo $e->getMessage();
			die;
		}
		var_dump($result);
	}
}
