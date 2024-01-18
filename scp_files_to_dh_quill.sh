#!/bin/bash

DEST_USER="quill_dh_plasz3gi"
DEST_HOST="quill.plasticaddy.com"
DEST_PATH="/home/$DEST_USER/quill.plasticaddy.com/"
SSH_KEY="/home/thunderrabbit/.ssh/quill.plasticaddy.com"

# This will watch for changes in the source directory and scp them to the destination
inotifywait --exclude '.git/*' -mr -e close_write . | sed -ue 's/ CLOSE_WRITE,CLOSE //' | xargs -d$'\n' -I% scp  -P 22 -i $SSH_KEY % $DEST_USER@$DEST_HOST:$DEST_PATH%

