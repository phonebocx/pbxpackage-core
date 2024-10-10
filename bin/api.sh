#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"
cd $DIR

. ./bootstrap.inc.sh

include_component api.inc

echo "Requesting authentication tokens..."
tmpfile=/tmp/boot
rm -f /tmp/boot
#tmpfile=$(mktemp)
genCurl poll
#genCurl startup "" fullapidata
echo $CURL -o $tmpfile
$CURL -o $tmpfile
cat $tmpfile
set -x
parseResponse $tmpfile
exit
rm -f $tmpfile

exit

# rm -f $queuefile
outfile=/tmp/json
echo >/tmp/json
#echo > /var/run/dahdi_scan
# rm -f /var/run/dahdi_scan
genApiData $outfile
genCurl updatesmbios "" $outfile
echo $CURL
exit
$CURL -o $outfile
cat $outfile
echo ""
parseResponse $outfile
#rm -f $outfile
exit

# $CURL

genApiData
exit

echo Loading into $f
genCurl ping utime=$(date +%s) $dahdi
rm -f $dahdi

echo >/tmp/json
outfile=/tmp/json
echo $CURL
$CURL -o $outfile
cat $outfile
parseResponse $outfile
# $CURL
