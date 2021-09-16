<?php

namespace Bug5627;

class Foo
{

	public function a(): string {
		try {
			throw new \Exception('try');
		} catch (\Exception $e) {
			throw new \Exception('catch');
		} finally {
			return 'finally';
		}
	}

	/**
	 * @throws \Exception
	 * @return never
	 */
	public function abort()
	{
		throw new \Exception();
	}

	public function b(): string {
		try {
			$this->abort();
		} catch (\Exception $e) {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

	public function c(): string {
		try {
			$this->abort();
		} catch (\Throwable $e) {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

	public function d(): string {
		try {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

}

class Bar
{

	public function a(): string {
		try {
			throw new \Exception('try');
		} catch (\Exception $e) {
			throw new \Exception('catch');
		} finally {
			return 'finally';
		}
	}

	/**
	 *
	 * @return never
	 */
	public function abort()
	{
		throw new \Exception();
	}

	public function b(): string {
		try {
			$this->abort();
		} catch (\Exception $e) {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

	public function c(): string {
		try {
			$this->abort();
		} catch (\Throwable $e) {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

	public function d(): string {
		try {
			$this->abort();
		} finally {
			return 'finally';
		}
	}

}

/**
 * @return never
 */
function abort()
{

}

class Baz
{

	public function a(): string {
		try {
			throw new \Exception('try');
		} catch (\Exception $e) {
			throw new \Exception('catch');
		} finally {
			return 'finally';
		}
	}










	public function b(): string {
		try {
			abort();
		} catch (\Exception $e) {
			abort();
		} finally {
			return 'finally';
		}
	}

	public function c(): string {
		try {
			abort();
		} catch (\Throwable $e) {
			abort();
		} finally {
			return 'finally';
		}
	}

	public function d(): string {
		try {
			abort();
		} finally {
			return 'finally';
		}
	}

}
