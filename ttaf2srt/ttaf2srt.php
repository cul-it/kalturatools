<?php
/**
 * ttaf2srt.php
 *   convert caption files from the ttaf format to SRT
 *
 * echo 'hicaptionfile.xml' | php hicaption2srt.php  [seconds offset]
 *
 * argument seconds offset (floating point OK)
 *   optional
 *   if given, it's a number of seconds to subtract from the cc@start attributes before converting
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


require_once('../QueryPath/QueryPath.php');

/**
 * convert HH:MM:SS.mm to HH:MM:SS,mmm
 * @param  integer  $time  time number
 * @param  float    $rate   times per second
 * @return string           timecode for time
 */
function time_format($time) {
  return preg_replace('/(\d{2}\:\d{2}\:\d{2})\.(\d{3})/', '$1,$2', $time);
}

function time_code($start_time, $end_time) {
  // format start and end time for SRT
  // example: 00:02:17,440 --> 00:02:20,375
  return time_format($start_time) . ' --> ' . time_format($end_time);
}

/**
 * subtract the number of seconds from the HH:MM:SS.ss time
 * @param  string $hms     time
 * @param  float $seconds seconds to offset or FALSE
 * @return string          HH:MM:SS.sss
 */
function offset_time($hms, $seconds = 0.0, $factor = 1.0) {
  if ($seconds !== FALSE) {
    list($hr, $min, $sec) = explode(':',  $hms);
    $sec_whole = floor(floatval($sec));
    $sec_dec = floatval($sec) - floatval($sec_whole);
    $utime = mktime($hr, $min, $sec_whole, 1, 1, 1970);
    echo "utime2 $utime -> ";
    $utime2 = (floatval($utime) + $sec_dec - $seconds) * floatval($factor);
    echo "$utime2 \n";
    list($sec_whole, $sec_dec) = explode('.', $utime2);
    $hms2 = strftime('%T', $sec_whole) . '.' . substr($sec_dec . '000', 0, 3);
    //echo "$hms -> $hms2" . PHP_EOL;
    return $hms2;
  }
  return $hms . '0';  // original times are HH:MM:SS.ss - a final 0
}

try {
  date_default_timezone_set('UTC');

  // find any offset argument
  if (isset($argv[1]) && is_numeric($argv[1]) && ($argv[1] != 0)) {
    $offset = floatval($argv[1]);
  }
  else {
    $offset = FALSE;
  }

  // expecting a filename on stdin
  $handle = fopen ("php://stdin","r");
  $filename = trim(fgets($handle));
  if (!is_file($filename)) {
    print "Needs a file on stdin";
  }

  // find output file name
  $pos = strrpos($filename, '/');
  if ($pos === FALSE) $pos = 0;
  $foo = substr($filename, $pos);
  echo ("$foo offset $offset" . PHP_EOL);

  // special case of film 113 German captions
  if (strpos($foo, 'mk_113_deu') !== FALSE) {
    $factor = floatval("0.4");
    $offset = 0;
    echo "Found $foo. Factor=$factor" . PHP_EOL;
  }
  else {
    $factor = floatval(1);
  }

  // find the xml
  $qp = qp($filename)->find(':root body div');

  $captions = array();
  $max_time = ''; // biggest timestamp found (some have a bogus timestame at the end)
  foreach($qp->find('p') as $cap) {
    $start_time = offset_time($cap->attr('begin'), $offset, $factor);
    $text = $cap->text();

    // some of the captions have the speaker repeated twice at the beginning
    // like Castro: Castro: bla bla bla
    // clean these up here!
    $matches = array();
    if (preg_match('/^([^:]+: )\1/', $text, $matches)) {
      print $text . ' -> ';
      $text = substr($text, strlen($matches[1]));
      print $text . PHP_EOL;
    }

    $captions[] = array('start' => $start_time, 'speaker' => $speaker, 'text' => $text);
    if (strcmp($start_time, $max_time) > 0) {
      $max_time = $start_time;
    }
  }

  $output = array();
  for ($i=1; $i < count($captions); $i++) {
    $output[] = $i;
    $end_time = $captions[$i]['start'];
    $output[] = time_code($captions[$i-1]['start'], $end_time);
    $output[] = wordwrap($captions[$i-1]['text'], 60);
    $output[] = '';
  }

  file_put_contents("ttaf2srt_output/$foo.srt", implode("\n", $output) . "\n");
  //fwrite(STDOUT, implode("\n", $output));

  $ncaptions = count($captions);
  print "\t$ncaptions\t$max_time";
  }
catch(Exception $ex) {
  die($ex->getMessage());
  }

?>
