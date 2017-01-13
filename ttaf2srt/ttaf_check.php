<?php
/**
 * ttaf_check.php
 *   examine caption files from the ttaf format
 *
 * echo 'hicaptionfile.xml' | php hicaption2srt.php  [frame offset]
 *
 * argument frame offset
 *   optional
 *   if given, it's a number of frames to subtract from the cc@start attributes before converting
 *   this is to support the 5 films that had their excessively long headers removed
 *
 * Input:
 *

<tt xmlns="http://www.w3.org/2006/04/ttaf1" xmlns:tts="http://www.w3.org/2006/04/ttaf1#styling" xml:lang="en">
<head>
<metadata xmlns:ttm="http://www.w3.org/2006/10/ttaf1#metadata">
<ttm:title>mk_100_eng.txt</ttm:title>
</metadata>
<styling>
<style xml:id="s1" tts:color="#222222" tts:fontFamily="Verdana,proportionalSansSerif" tts:fontSize="12" tts:textAlign="left"/>
<style xml:id="italic" style="s1" tts:fontStyle="italic"/>
</styling>
</head>
<body>
<div xml:lang="en">
<p begin="00:00:29.00" style="s1">Running Text: Heiner Müller visited Japan /</p>
<p begin="00:00:32.00" style="s1">
There he discussed the formal dimensions of opera /
</p>
<p begin="00:00:35.83" style="s1">
At the same time, Heiner Müller was staging the Fatzer Fragment, an enigmatic 1932 text by Bert Brecht, in Berlin
</p>
<p begin="00:00:47.08" style="s1">
Intertitle: Anti-Opera / Mechanized Warfare in 1914 / A Flight over Siberia / Interview with Heiner Müller
</p>
...
<p begin="00:44:38.67" style="s1">
Intertitle: Anti-Opera / Mechanized Warfare in 1914 / A Flight over Siberia / Interview with Heiner Müller
</p>
</div>
</body>
</tt>

 *
 * Output:
 *
 * 1
 * Running Text: Heiner Müller has left a poem behind in anticipation of his death: "The Death of Seneca" /
 *
 * 2
 * 00:00:17,451 --> 00:00:20,120
 * Seneca, a teacher and minister to the Emperor Nero, took his own life /
 *
 * ...
 * 193
 * 00:09:25,365 --> 00:09:28,068
 * He demonstrated ataraxia: "the unshakeable tranquility of the soul"
 *
 */

define("FRAMES_PER_SECOND", 12.0);

require_once('../QueryPath/QueryPath.php');

/**
 * convert frame number to HH:MM:SS,mmm
 * @param  integer  $frame  frame number
 * @param  float    $rate   frames per second
 * @return string           timecode for frame
 */
function time_format($frame) {
  $seconds = floatval($frame) / FRAMES_PER_SECOND;
  $sec = floor($seconds);
  $partial = $seconds - $sec;
  $hms = strftime('%T', mktime(0,0,$sec));
  $ms = substr(sprintf('%0.3f', $partial), 2);  // skip 0. prefix
  return $hms . ',' . $ms;
}

function time_code($start_frame, $end_frame) {
  // format start and end time for SRT
  // example: 00:02:17,440 --> 00:02:20,375
  return time_format($start_frame) . ' --> ' . time_format($end_frame);
}

try {
  date_default_timezone_set('America/New_York');

  // find any offset argument
  if (isset($argv[1]) && is_int($argv[1])) {
    $offset = $argv[1];
  }
  else {
    $offset = 0;
  }

  // expecting a filename on stdin
  $handle = fopen ("php://stdin","r");
  $filename = trim(fgets($handle));
  if (!is_file($filename)) {
    print "Needs a file on stdin";
  }

  // find the xml
  $qp = qp($filename)->find(':root body div');

  $captions = array();
  $max_time = ''; // biggest timestamp found (some have a bogus timestame at the end)
  $max_words = 0; // max word count
  $max_chars = 0; // max numer of chars
  foreach($qp->find('p') as $cap) {
    $start_frame = $cap->attr('begin');
    $text = $cap->text();
    $captions[] = array('start' => $start_frame, 'speaker' => $speaker, 'text' => $text);
    if (strcmp($start_frame, $max_time) > 0) {
      $max_time = $start_frame;
    }
    if (str_word_count($text) > $max_words) {
      $max_words = str_word_count($text);
    }
    if (strlen($text) > $max_chars) {
      $max_chars = strlen($text);
    }
  }

  // sometimes the last caption is just . indicating the end time of the second to last caption
  // the speaker should not be included if it's the same as the last speaker

  $ncaptions = count($captions);
  print "\t$ncaptions\t$max_time\t$max_words\t$max_chars";
  }
catch(Exception $ex) {
  die($ex->getMessage());
  }

?>
