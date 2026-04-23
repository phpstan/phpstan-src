<?php declare(strict_types = 1);

namespace Bug3795;

class User {
	private string $id;
	private string $name;

	public function __construct(string $id, string $name) {
		$this->id = $id;
		$this->name = $name;
	}

	public function getId() : string {
		return $this->id;
	}

	public function getName() : string {
		return $this->name;
	}
}

class Users {
	/**
	 * @param array{id?: string, name?: string} $data
	 */
	public static function create(array $data) : User {
		foreach (['id', 'name'] as $required) {
			if (!array_key_exists($required, $data)) {
				throw new \InvalidArgumentException('Data is missing ' . $required);
			}
		}

		return new User(
			(string) $data['id'],
			(string) $data['name'],
		);
	}
}
