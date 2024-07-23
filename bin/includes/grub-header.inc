set default=__DEFAULT__
set timeout=__TIMEOUT__
loadfont $prefix/dejavu-bold-16.pf2
loadfont $prefix/dejavu-bold-14.pf2
loadfont $prefix/unicode.pf2
set gfxmode=1024x768
set gfxpayload=keep
insmod efi_gop
insmod efi_uga
insmod png
terminal_output gfxterm

set color_normal=light-gray/black
set color_highlight=white/black
set theme=/boot/grub/theme.txt
search -l --no-floppy --set=efibase EFI
if [ -f ($efibase)/override.cfg ]; then
  source ($efibase)/override.cfg
fi

