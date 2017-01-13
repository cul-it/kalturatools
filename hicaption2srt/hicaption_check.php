<?php
/**
 * hicaption_check.php
 *   examine caption files from the HiCaption format
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
 * <?xml version="1.0" encoding="UTF-16" ?>
 * <HiCaptionCC>
 * <hmccheader>
 *  <ccCopyright copyrightVal="Add Copyright Here" />
 *  <ccMedia mediaFile="mk_103.avi" />
 *  <ccMetrics timing="frames" duration="1458399.5465" />
 *  <ccStyles>
 *   <ccStyle ccStyleName="P" ccStyleType="tag">
 * margin-top:3pt;
 *          margin-left:5pt;
 *          font-size: 10pt;
 *          font-family: tahoma, sans-serif;
 *          font-weight: normal;
 *          color: white;
 *   </ccStyle>
 *   <ccStyle ccStyleName="ENUSCC" ccStyleType="caption" ccLang="en-US" ccName="English Captions">
 *                 name:English Captions; lang:en-US;
 *                 font-family:Verdana, Arial;
 *                 font-size:12pt;
 *                 text-align:left;
 *                 samitype:CC;
 *   </ccStyle>
 *  </ccStyles>
 * </hmccheader>
 * <captionset styleClass="ENUSCC">
 * <cc start="433">
 *  <speaker styleId="Source">Running Text</speaker>
 *  <caption>Heiner Müller has left a poem behind in anticipation of his death: &quot;The Death of Seneca&quot; /</caption>
 * </cc>
 * <cc start="523">
 *  <speaker styleId="Source">Running Text</speaker>
 *  <caption>Seneca, a teacher and minister to the Emperor Nero, took his own life /</caption>
 * </cc>
 * ...
 * <cc start="17025">
 *  <speaker styleId="Source">Intertitle</speaker>
 *  <caption>The Death of Seneca</caption>
 * </cc>
 * </captionset>
 * </HiCaptionCC>
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
  $qp = qp($filename, 'HiCaptionCC');

  $duration = $qp->branch('ccMetrics')->attr('duration') / 1000.0;  // to seconds
  $hms = strftime('%T', mktime(0,0,$duration));

  $captions = array();
  foreach($qp->find('cc') as $cap) {
    $start_frame = $cap->attr('start');
    $speaker = $cap->branch('speaker')->text();
    $text = $cap->branch('caption')->text();
    $captions[] = array('start' => $start_frame, 'speaker' => $speaker, 'text' => $text);
  }

  // sometimes the last caption is just . indicating the end time of the second to last caption
  // the speaker should not be included if it's the same as the last speaker

  // check frames per duration with last caption
  $last_caption_frame = $captions[count($captions) - 1]['start'];
  if ($last_caption_frame == 0) {
    $last_caption_frame = $captions[count($captions) - 2]['start'];
  }
  $fps = round($last_caption_frame / $duration);
  $ncaptions = count($captions);
  print "\t$hms\t$ncaptions\t$last_caption_frame\t$fps";
  }
catch(Exception $ex) {
  die($ex->getMessage());
  }

?>
