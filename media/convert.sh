#!/bin/sh

for i in `ls *.wav`; do
  echo $i
#  rm $i
#  svn cat -r 3000 $i > $i
  sox $i -b 8 -e a-law $i.2.wav
  mv $i.2.wav $i
done
