set default=__DEFAULT__
set timeout=__TIMEOUT__
# __THEMESRC__
set themesrc=/boot/grub/theme/grub-theme.cfg

loadfont unicode
set gfxmode=1024x768
set gfxpayload=keep
insmod efi_gop
insmod efi_uga
insmod png
terminal_output gfxterm

set color_normal=light-gray/black
set color_highlight=white/black
if [ -f $themesrc ]; then
  source $themesrc
else
  echo $themesrc does not exist
  sleep 5
fi
search -l --no-floppy --set=efibase EFI
if [ -f ($efibase)/override.cfg ]; then
  source ($efibase)/override.cfg
fi

