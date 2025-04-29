<?php // lint >= 8.1

namespace Bug12946;

interface UserInterface {}
class User implements UserInterface{}

class UserMapper {
	function getFromId(int $id) : ?UserInterface {
		return $id === 10 ? new User : null;
	}
}

class GetUserCommand {

	private ?UserInterface $currentUser = null;

	public function __construct(
		private readonly UserMapper $userMapper,
		private readonly int $id,
	) {
	}

	public function __invoke() : UserInterface {
		if( $this->currentUser ) {
			return $this->currentUser;
		}

		$this->currentUser = $this->userMapper->getFromId($this->id);
		if( $this->currentUser === null ) {
			throw new \Exception;
		}

		return $this->currentUser;
	}

}
