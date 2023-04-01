<?php

namespace Bug9126;

class User {}

class Resume {

	/**
	 * @return User
	 */
	public function getOwner() {
		return $this->owner;
	}
}

function (): void {
	$resume = new Resume();
	$owner = $resume->getOwner();
	if (!empty($owner)) {
		echo "not empty";
	}
};
