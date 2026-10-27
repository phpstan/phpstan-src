<?php declare(strict_types = 1);

namespace ResultCacheCollectedDataRulesTracking;

class Controller
{

	public function home(): string
	{
		return view('home');
	}

	public function about(): string
	{
		return view('about');
	}

	public function contact(): string
	{
		return view('contact');
	}

	public function faq(): string
	{
		return view('faq');
	}

}
