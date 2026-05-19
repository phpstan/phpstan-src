<?php

namespace Bug6265;

class swThreads
{
	public function sort_comments_in_thread_array($comment, $thread_parentid = 0)
	{
		if ($thread_parentid)
		{
			if (isset($this->comments['#' . $comment['thread_parentid']]))
			{
				if (in_array('#' . $comment['parentid'], $lv1_keys))
				{
					if (!$match)
					{
						for ($ii = 0;$ii < count($lv2_keys);$ii++)
						{
							if (!$match3)
							{
								for ($iii = 0;$iii < count($lv3_keys_all);$iii++)
								{
									if (!$match4)
									{
										for ($iiii = 0;$iiii < count($lv4_keys_all);$iiii++)
										{
											if (!$match5)
											{
												for ($i6 = 0;$i6 < count($lv5_keys_all);$i6++)
												{
													if (!$match6)
													{
														for ($i7 = 0;$i7 < count($lv6_keys_all);$i7++)
														{
															if (!$match7)
															{
																for ($i8 = 0;$i8 < count($lv7_keys_all);$i8++)
																{
																	if (!$match8)
																	{
																		for ($i9 = 0;$i9 < count($lv8_keys_all);$i9++)
																		{
																			if (!$match9)
																			{

																			}
																		}
																	}
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
				return true;
			}
		}
	}

}
