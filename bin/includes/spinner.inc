#!/bin/bash
# vim: set ft=sh:

if [ ! "$buildvers" ]; then
  echo "** Bug - Include common before this"
  exit 1
fi

trap 'progress_indicator estop; exit 1' 1
trap 'progress_indicator estop; exit 1' 2

if [ ! "$spid" ]; then
  spid=$RANDOM
fi

progress_indicator () {
  case "$1" in
    start)
      $INCDIR/spinner $spid &
      ;;
    estop)
      find /tmp -name pi.* -exec cp /dev/null {} \;
      ;;
    *)
      retries=10
      lockfile=/tmp/pi.$spid
      while [ "$retries" -gt 0 -a ! -e $lockfile ]; do
        echo Why does the spinner lock $lockfile not exist?
        sleep .1
        retries=$(( $retries - 1 ))
      done
      cp /dev/null $lockfile
      retries=10
      while [ "$retries" -gt 0 -a -e $lockfile ]; do
        sleep .2
        retries=$(( $retries - 1 ))
      done
      if [ -e $lockfile ]; then
        echo "** Bug - $lockfile not removed by spinner"
        rm -f $lockfile
      fi
      ;;
  esac
}
