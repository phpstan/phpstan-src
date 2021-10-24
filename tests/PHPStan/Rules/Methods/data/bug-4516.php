<?php

namespace Bug4516;

interface RecordsMessages
{
	public function record($message): void;
}

trait PrivateMessageRecorderCapabilities
{
	protected function record($message): void
	{
	}
}

class PublicMessageRecorder implements RecordsMessages
{
	use PrivateMessageRecorderCapabilities { record as public; }
}
