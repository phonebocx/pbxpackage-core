<?php


$retarr = [
	"actions" => [
		[
			"type" => "files", "contents" => [
				["filename" => "update.nsh", "loc" => "efi", "contents" => genUpdateNsh()],
				["filename" => "smbios/Update.efi", "loc" => "efi", "url" => "http://goldlinux.com/smbios/Update.efi",],
				["filename" => "smbios/Shell.efi", "loc" => "efi", "url" => "http://goldlinux.com/smbios/Shell.efi",],
				["filename" => "smbios/H2OSDE-Sx64.efi", "loc" => "efi", "url" => "http://goldlinux.com/smbios/H2OSDE-Sx64.efi",],
				["filename" => "boot/grub/x86_64-efi/smbios.mod", "loc" => "boot", "url" => "http://goldlinux.com/smbios/smbios.mod",],
				["filename" => "override.cfg", "loc" => "efi", "contents" => genOverrideCfg()],
			],
		],
		[
			"type" => "command", "contents" => [
				["cmd" => "sync"],
				["cmd" => "true"],
			],
		]
	],
];
print json_encode($retarr)."\n";


function genUpdateNsh()
{
	$cmd = '@echo -off
cls
if exist \smbios\H2OSDE-Sx64.efi then
	goto FOUNDIMAGE
	endif
	if exist fs0:\smbios\H2OSDE-Sx64.efi then
		fs0:
echo Found Packages on fs0:
	goto FOUNDIMAGE
	endif
	if exist fs1:\smbios\H2OSDE-Sx64.efi then
		fs1:
echo Found Packages on fs1:
	goto FOUNDIMAGE
	endif
	if exist fs2:\smbios\H2OSDE-Sx64.efi then
		fs2:
echo Found Packages on fs2:
	goto FOUNDIMAGE
	endif
	echo "Unable to find SMBIOS Tool".
	echo "Please mount the drive with the update package".
	echo ""
	stall 500000000
	goto END
	:FOUNDIMAGE

	cd smbios
';
	$cmd .= genCmd();
	$cmd .= '
:END
cd ..
del -q smbios override.cfg update.nsh
reset
';
	return $cmd;
}

function genCmd()
{
	$defaults = [
		'SM' => 'ClearlyIP', 'SP' => 'Fax Yeeter', 'SV' => 'CIP 715', 'SS' => 'N/A',
		'SKU' => 'ReadOnly', 'SF' => 'ReadOnly', 'BM' => 'ClearlyIP', 'BP' => 'CIP Yeeter 1.6',
		'BV' => 'UEFI 1.6', 'BS' => "N/A", 'CM' => "ClearlyIP", 'CV' => "CIP Yeeter v2", 'CS' => "N/A",
		'CA' => "Unlocked",
	];
	$overrides = [];

	$exec = "fake-H2OSDE-Sx64.efi ";
	$myarr = [];
	foreach ($defaults as $k => $v) {
		$myarr[$k] = $v;
	}
	foreach ($overrides as $k => $v) {
		if ($v === false) {
			unset($myarr[$k]);
		} else {
			$myarr[$k] = $v;
		}
	}
	foreach ($myarr as $k => $v) {
		$exec .= "-$k \"$v\" ";
	}
	return $exec;
}

function genOverrideCfg()
{
	$auto = 'if [ $uuid != "12345678-1234-5678-90ab-cddeefaabbcc" ]; then
		set default=2
		fi';
	$auto = "";
	return 'insmod smbios
		smbios --type 1 --get-uuid 8 --set uuid
		menuentry "Update UUID $uuid" {
		chainloader ($efibase)/smbios/Update.efi
}
menuentry "UEFI Shell" {
chainloader ($efibase)/smbios/Shell.efi
}
' . $auto;
}


/*
$cmd = "/bin/true";

$actions[] = ["type" => "b64command", "contents" => [["encodedcmd" => base64_encode($cmd)]]];
$actions[] = ["type" => "command", "contents" => [["cmd" => "/bin/false"]]];
print json_encode(["actions" => $actions])."\n";
 */
