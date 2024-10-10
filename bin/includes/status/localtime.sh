#!/bin/bash

get_from_api() {
  URL=$(printf "$API/ping?utime=%d" $(date +%s))
  echo Hi $URL
  echo curl -s $URL
}


