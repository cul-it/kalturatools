#!/bin/bash
#
for file in /Users/jgr25/Documents/svn_checkouts/kluge/trunk/muller-kluge.library.cornell.edu/htdocs/ttaf_captions/mk_*_*.xml
do
  FILM=`echo $file| cut -d'/' -f 11| cut -d'_' -f 2`
  case $FILM in
    101 )
      OFFSET=45
      ;;
    103 )
#      OFFSET=80
      OFFSET="10.5"
      ;;
    107 )
      OFFSET=60
      ;;
    108 )
      OFFSET=70
      ;;
    114 )
      OFFSET=84
      ;;
    *)
      OFFSET=0
      ;;
  esac
  #echo -n $file $FILM $OFFSET
  #echo $file | php ttaf_check.php $OFFSET
  echo $file | php ttaf2srt.php $OFFSET
  echo ''
done
