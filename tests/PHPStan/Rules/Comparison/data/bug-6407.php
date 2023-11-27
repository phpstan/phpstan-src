<?php declare(strict_types=1);  // lint >= 8.0

namespace Bug6407;

class BookEditPacket
{
	public const TYPE_REPLACE_PAGE = 0;
	public const TYPE_ADD_PAGE = 1;
	public const TYPE_DELETE_PAGE = 2;
	public const TYPE_SWAP_PAGES = 3;
	public const TYPE_SIGN_BOOK = 4;

	public int $type;
}


class PlayerEditBookEvent
{
	public const ACTION_REPLACE_PAGE = 0;
	public const ACTION_ADD_PAGE = 1;
	public const ACTION_DELETE_PAGE = 2;
	public const ACTION_SWAP_PAGES = 3;
	public const ACTION_SIGN_BOOK = 4;
}

class HelloWorld
{
	private BookEditPacket $packet;

	private function iAmImpure(): void
	{
		$this->packet->type = 999;
	}

	public function sayHello(BookEditPacket $packet): bool
	{
		$this->packet = $packet;
		switch ($packet->type) {
			case BookEditPacket::TYPE_REPLACE_PAGE:
				$this->iAmImpure();
				break;
			case BookEditPacket::TYPE_ADD_PAGE:
				break;
			case BookEditPacket::TYPE_DELETE_PAGE:
				break;
			case BookEditPacket::TYPE_SWAP_PAGES:
				break;
			case BookEditPacket::TYPE_SIGN_BOOK:
				break;
			default:
				return false;
		}

		//for redundancy, in case of protocol changes, we don't want to pass these directly
		$action = match ($packet->type) {
			BookEditPacket::TYPE_REPLACE_PAGE => PlayerEditBookEvent::ACTION_REPLACE_PAGE,
			BookEditPacket::TYPE_ADD_PAGE => PlayerEditBookEvent::ACTION_ADD_PAGE,
			BookEditPacket::TYPE_DELETE_PAGE => PlayerEditBookEvent::ACTION_DELETE_PAGE,
			BookEditPacket::TYPE_SWAP_PAGES => PlayerEditBookEvent::ACTION_SWAP_PAGES,
			BookEditPacket::TYPE_SIGN_BOOK => PlayerEditBookEvent::ACTION_SIGN_BOOK,
			default => throw new \Error("We already filtered unknown types in the switch above")
		};
		return true;
	}
}
