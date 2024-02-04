#!/bin/bash

DEST_USER="barefoot_rob"
DEST_HOST="quick.robnugen.com"
DEST_PATH="/home/$DEST_USER/quick.robnugen.com/"
SSH_KEY="/home/thunderrabbit/.ssh/barefoot_rob_dh"

# This will watch for changes in the source directory and scp them to the destination
inotifywait --exclude '.git/*' -mr -e close_write . | sed -ue 's/ CLOSE_WRITE,CLOSE //' | xargs -d$'\n' -I% scp  -P 22 -i $SSH_KEY % $DEST_USER@$DEST_HOST:$DEST_PATH%
