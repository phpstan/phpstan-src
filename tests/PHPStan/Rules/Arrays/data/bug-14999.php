<?php declare(strict_types = 1);

namespace Bug14999;

class Message
{

	public static function success(string $string): self
	{
		return new self();
	}

	public function getDisplay(): string
	{
		return '';
	}

}

$message = Message::success('Import has been successfully finished, 2 queries executed. (file.sql)');
$_SESSION['Import_message'] = [];
$_SESSION['Import_message']['message'] = $message->getDisplay();
$_SESSION['Import_message']['go_back_url'] = 'https://example.com/index.php?route=/server/import';
