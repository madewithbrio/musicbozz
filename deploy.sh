#!/bin/bash

rsync -aPvr --delete-after --exclude 'service/*' . -e ssh root@62.28.238.103:/servers/musicbozz
