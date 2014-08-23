#!/bin/bash
BUILD_ID=$(date +%Y%m%d%H%M)
PLUGIN_FILE="wordpress-sns-$BUILD_ID.zip"
DIST_FILE="wordpress-sns.zip"
ABSPATH=$(cd "$(dirname "$0")"; pwd)
PLUGIN_DIR="$ABSPATH/plugin"
BUILD_DIR="$ABSPATH/build"
DIST_DIR="$ABSPATH/dist"

function clean() {
  [ -d $BUILD_DIR ] && rm -r $BUILD_DIR
}

function build() {
  echo "Building to $BUILD_DIR/$PLUGIN_FILE"
  [ -d $BUILD_DIR ] || mkdir $BUILD_DIR
  cd $PLUGIN_DIR
  zip -r $BUILD_DIR/$PLUGIN_FILE *
}

function dist() {
  echo "Saving to $DIST_DIR/$DIST_FILE";
  [ -f $BUILD_DIR/$PLUGIN_FILE ] || echo "Could not find $BUILD_DIR/$PLUGIN_FILE"
  [ -f $BUILD_DIR/$PLUGIN_FILE ] && mkdir $DIST_DIR
  [ -f $BUILD_DIR/$PLUGIN_FILE ] && cp $BUILD_DIR/$PLUGIN_FILE $DIST_DIR/$DIST_FILE
}

case "$1" in
  build|b)
    clean
    build
    ;;
  dist|d)
    clean
    build
    dist
    ;;
  *)
    clean
    build
    dist
esac
