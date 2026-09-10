<?php

namespace UnusedVariableNestedOffsetWrites;

function breadcrumb(array $data, array $breadcrumb): array
{
	$data['translated']['breadcrumb'] = $data['breadcrumb'] = $breadcrumb;
	return $data;
}

function incrementDifferentOffset(array $data): array
{
	$data['next'] = ++$data['current'];
	return $data;
}

function replaceRoot(array $data): array
{
	$data['value'] = count($data = ['other' => 1]);
	return $data;
}
