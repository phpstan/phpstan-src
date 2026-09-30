<?php

namespace ResultCacheE2EValueDependency;

function usesMailer(): void
{
	service('mailer')->send();
}
