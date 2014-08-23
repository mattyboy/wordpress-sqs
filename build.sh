#!/bin/bash
BUILD_ID=$(date +%Y%m%d%H%M)
PLUGIN_FILE="wordpress-sns-$BUILD_ID.zip"
ABSPATH=$(cd "$(dirname "$0")"; pwd)
PLUGIN_DIR="$ABSPATH/plugin"
BUILD_DIR="$ABSPATH/build"

function clean() {
  [ -d $BUILD_DIR ] && rm -r $BUILD_DIR
}

function dist() {
  echo "Building to $BUILD_DIR/$PLUGIN_FILE"
  [ -d $BUILD_DIR ] || mkdir $BUILD_DIR
  cd $PLUGIN_DIR
  zip -r $BUILD_DIR/$PLUGIN_FILE *
}

case "$1" in
  clean|c)
    clean
    ;;
  dist|d)
    dist
    ;;
  *)
   clean
   dist
esac
