#!/bin/bash
# mount london02.library.cornell.edu/Projects tomake these source files availabile
#
for file in /Volumes/Projects/Miscellaneous/Bathrick_dm/films/mk_*/mk_*_*.xml
do
  FILM=`echo $file| cut -d'/' -f 7| cut -d'_' -f 2`
  case $FILM in
    102 )
      OFFSET=1349
      ;;
    103 )
      OFFSET=2398
      ;;
    107 )
      OFFSET=1798
      ;;
    108 )
      OFFSET=2098
      ;;
    114 )
      OFFSET=2517
      ;;
    *)
      OFFSET=0
      ;;
  esac
  echo -n $file
  #echo $file | php hicaption2srt.php $OFFSET
  echo $file | php hicaption_check.php $OFFSET
  echo ''
done
