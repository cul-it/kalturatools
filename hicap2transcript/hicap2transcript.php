<?php
/**
 * hicap2transcript.php
 * convert HiCaptionCC files to transcripts
 * hicap2transcript.php?hicap=http://bla.com/hicaption.xml
 */

require_once('../QueryPath/QueryPath.php');

// get the 'hicap' file argument
if (!empty($_GET['hicap'])) {
  $source =  $_GET['hicap'];
  if (filter_var($source, FILTER_VALIDATE_URL) !== FALSE) {
    $qp = qp($source, 'HiCaptionCC');

    $captions = array();
    $output = array();
    $output[] = '<dl>';
    $speech = array();
    $speaker = FALSE;
    foreach($qp->find('cc') as $cap) {
      $new_speaker = $cap->branch('speaker')->text();
      $text = $cap->branch('caption')->text();
      if (empty($new_speaker) || ($new_speaker == $speaker)) {
        $speech[] = $text;
      }
      else {
        if ($speaker !== FALSE) {
          $output[] = "<dt>$speaker</dt>";
          $output[] = "<dd>" . implode(' ', $speech) . '</dd>';
        }
        $speaker = $new_speaker;
        $speech = array($text);
      }
    }
    $output[] = '</dl>';

    echo implode(PHP_EOL, $output);
  }
  else {
    echo "$source is not valid";
  }
}
else {
  echo "needs a hicap argument with url of HiCaptionCC file";
}




?>
