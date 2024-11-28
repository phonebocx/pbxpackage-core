<?php

use PhoneBocx\Services\ProcessQueue;

return
	[
		"hooks" => [
			"fastagi" => "PhoneBocx\\FastAGI::request",
		],
		"scheduler" => [
			"pkgupdate" => "PhoneBocx\\Services\\CheckPkgUpdates",
			"queueworker" => ProcessQueue::class,
		]
	];
