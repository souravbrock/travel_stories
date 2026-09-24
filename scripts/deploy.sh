#!/bin/bash
# Deploy / sync Travel Stories to DomainAdda (kaveri).
# Routine sync (run on server over SSH):  bash ~/travel_stories/scripts/deploy.sh
# Full run from local Git Bash:           bash scripts/deploy.sh
set -e
HOST=reddevil@kaveri.domainadda.com
KEY="$HOME/.ssh/cpanel-deploy"
DOC=~/public_html/tstory

if [ -f ~/.tstory_dbpw ]; then
  # marker file exists only on the server: run the server half here
  MODE=server
else
  MODE=${1:-local}
fi
if [ "$MODE" = "local" ]; then
  # running locally: execute the server half over SSH
  ssh -i "$KEY" "$HOST" 'bash ~/travel_stories/scripts/deploy.sh server'
  echo DONE
  exit 0
fi

# running on server:
cd ~/travel_stories && git pull --ff-only origin main
mkdir -p "$DOC"
cp -R public/* "$DOC"/
cp -R api "$DOC"/
find "$DOC" -type d -exec chmod 755 {} \;
find "$DOC" -type f -exec chmod 644 {} \;
chmod 750 "$DOC/config" "$DOC/storage"
chmod 640 "$DOC/config/config.php"
echo "Deployed to $DOC"
