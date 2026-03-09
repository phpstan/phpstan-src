<?php declare(strict_types = 1);

namespace Bug4560;

use function PHPStan\Testing\assertType;

final class ResetPasswordForm
{
	/**
	 * @param array{token: string, password: string, email: string} $data
	 */
	public static function fromArray(array $data): self
	{
		return new self();
	}
}

class HelloWorld
{
	public function sayHello(): void
	{
		if (!empty($_POST['resetPassword'])) {
			$data = $_POST['resetPassword'];
			$data['token'] = $_POST['token'];

			assert(array_key_exists('password', $data));
			assert(array_key_exists('email', $data));

			assertType("non-empty-array&hasOffset('email')&hasOffset('password')&hasOffsetValue('token', mixed)", $data);
		}
	}
}
