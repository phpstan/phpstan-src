<?php

namespace BugWithoutIssue1;

interface User {
	function getId() : int;
}

interface Message {
	/** @return object|null */
	public function getScheduleRequest();
	public function getFromUser() : User;
}

interface MessageThread {
	/** @return object|null */
	public function getLastScheduleRequest();
	/** @return User[] */
	public function getParticipants() : array;
	/** @return Message[] */
	public function getMessages() : array;
}

class X
{
	public function checkAndSendThreadNotRepliedNotification(MessageThread $thread) : bool
	{
		$threadSR = $thread->getLastScheduleRequest();

		$p = [];
		foreach($thread->getParticipants() as $user)
			$p[$user->getId()] = $user;

		$reminderMsg = null;
		$reachedSR = !$threadSR;
		foreach($thread->getMessages() as $msg)
		{
			$msgSR = $msg->getScheduleRequest();
			if(!$reachedSR && ($threadSR && $msgSR != $threadSR))
				continue;

			$reachedSR = true;

			if(!$reminderMsg)
				$reminderMsg = $msg;

			unset($p[$msg->getFromUser()->getId()]);

			if(!$p)
				return false;
		}

		if(!$reminderMsg)
			throw new \UnexpectedValueException('Expected a reminderMsg but got null for thread');

		return true;
	}
}

class Foo
{
	/** @var int */
	protected $index = 0;

	/** @var string[][] */
	protected $data = [
		0 => ['type' => 'id', 'value' => 'foo'],
		1 => ['type' => 'special', 'value' => '.'],
		2 => ['type' => 'id', 'value' => 'bar'],
		3 => ['type' => 'special', 'value' => ';'],
	];

	protected function next(): void
	{
		$this->index = $this->index + 1;
	}

	protected function check(string $type, ?string $value = null): bool {
		return ($this->type() === $type) && (($value === null) || ($this->value() === $value));
	}

	protected function type(): string
	{
		return $this->data[$this->index]['type'];
	}

	protected function value(): string
	{
		return $this->data[$this->index]['value'];
	}

	public function separatedName(): string
	{
		$name = '';
		$previousType = null;
		$separator = '.';

		$currentValue = $this->value();

		while ((($this->check('special', $separator)) || ($this->check('id'))) &&
			(($previousType === null) || ($this->type() !== $previousType)) &&
			(($previousType !== null) || ($currentValue !== $separator))
		) {
			$name .= $currentValue;
			$previousType = $this->type();

			$this->next();
			$currentValue = $this->value();
		}

		if (($previousType === null) || ($previousType !== 'id')) {
			throw new \RuntimeException();
		}

		return $name;
	}
}
