<?php

namespace Bug4590;

/**
 * @template T
 */
class OkResponse
{
	/**
	 * @phpstan-var T
	 */
	private $body;

	/**
	 * @phpstan-param T $body
	 */
	public function __construct($body)
	{
		$this->body = $body;
	}

	/**
	 * @phpstan-return T
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @return static<array<string, string>>
	 */
	public static function testGenericStatic()
	{
		return new static(["ok" => "hello"]);
	}

	/**
	 * @return static<array<int, string>>
	 */
	public static function testGenericStaticMismatch()
	{
		return new static(["ok" => "hello"]);
	}
}

class Controller
{
	/**
	 * @return OkResponse<array<string, string>>
	 */
	public function test1(): OkResponse
	{
		return new OkResponse(["ok" => "hello"]);
	}

	/**
	 * @return OkResponse<array<int, string>>
	 */
	public function test2(): OkResponse
	{
		return new OkResponse([0 => "hello"]);
	}

	/**
	 * @return OkResponse<string[]>
	 */
	public function test3(): OkResponse
	{
		return new OkResponse(["hello"]);
	}

	/**
	 * @return OkResponse<string>
	 */
	public function test4(): OkResponse
	{
		return new OkResponse("hello");
	}

	/**
	 * @param array<int, string> $a
	 * @return OkResponse<array<int, string>>
	 */
	public function test5(array $a): OkResponse
	{
		return new OkResponse($a);
	}

}
