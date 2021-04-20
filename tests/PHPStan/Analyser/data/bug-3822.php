<?php

namespace Bug3822;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class Response {
	/** @var int */
	private $status;

	/** @var string */
	private $body;

	public function __construct(int $status, string $body) {
		$this->status = $status;
		$this->body = $body;
	}

	public function status(): int { return $this->status; }
	public function body(): string { return $this->body; }
}

class HelloWorld
{
	public function sayHello(Response $response): string
	{
		$body = $response->body();
		$status = $response->status();

		if ($status !== 200) {
			throw new \LogicException('NOOOO' . $body);
		}

		try {
			$payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
			unset($body);
		} catch (\JsonException $e) {
			assertVariableCertainty(TrinaryLogic::createYes(), $body);
		}

		return $payload['thing'] ?? 'default';
	}
}
