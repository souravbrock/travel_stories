#!/bin/bash
# SSH deploy to DomainAdda (kaveri). Run from local machine with Git Bash:
#   bash scripts/deploy.sh
# Uses key C:\Users\soura\.ssh\cpanel-deploy (from I:\Downloads\cPanel\ssh.txt)
set -e
HOST=reddevil@kaveri.domainadda.com
KEY="$HOME/.ssh/cpanel-deploy"
ssh -i "$KEY" $HOST <<'EOF'
  set -e
  cd ~/travel_stories 2>/dev/null || git clone https://github.com/souravbrock/travel_stories ~/travel_stories
  cd ~/travel_stories && git pull origin main || git pull origin master || true
  mkdir -p ~/public_html/tstory
  cp -R public/* ~/public_html/tstory/
  mkdir -p ~/tstory-api && cp -R api/* ~/tstory-api/ 2>/dev/null || true
  echo "Deployed to ~/public_html/tstory"
EOF
echo DONE
