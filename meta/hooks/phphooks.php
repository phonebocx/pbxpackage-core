<?php

return
	[
		"hooks" => [
			"fastagi" => "PhoneBocx\\FastAGI::request",
		],
		"scheduler" => [
			"pkgupdate" => "PhoneBocx\\Services\\CheckPkgUpdates",
		]
	];
