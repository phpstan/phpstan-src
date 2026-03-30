<?php

namespace Bug11417;

class Wrap {
	/**
	 * @param-immediately-invoked-callable $cb
	 */
	public static function run(callable $cb): void
	{
		$cb();
	}
}

class HelloWorld
{
	private ?string $conn = null;

	public function getConn(): string
	{
		if (!is_null($this->conn)) {
			return $this->conn;
		}

		Wrap::run(function() {
			$this->conn = "conn";
		});

		if (is_null($this->conn)) {
			throw new \Exception("conn failed");
		}

		return $this->conn;
	}

	public function disc(): void
	{
		$this->conn = null;
	}
}
