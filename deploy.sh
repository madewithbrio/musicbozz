#!/bin/bash

rsync -aPvr --delete-after --delete-excluded --exclude '.svn' . -e ssh root@62.28.238.103:/servers/musicbozz
