<?php

namespace FunctionCallStatementNoSideEffectsPhpDoc;

function(): void
{
	regular('test');
	pure1('test');
	pure2('test');
	pure3('test');
	pure4('test');
	pure5('test');
	pureAndThrowsVoid();
	pureAndThrowsException();
};
