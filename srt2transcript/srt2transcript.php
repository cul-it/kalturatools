<?php
/**
 * srt2transcript.php
 * see klugemigrate/trunk/tools/vbo.action.srt2transcript.php
 * ^[0-9]+\n[0-9:,]+ --> [0-9:,]+\n(?:[^_\W]|-)+:
 */

iconv_set_encoding("input_encoding", "UTF-8");
iconv_set_encoding("output_encoding", "UTF-8");
iconv_set_encoding("internal_encoding", "UTF-8");

function speech2paragraphs($speech) {
  // break up one long array of captions into a series of paragraphs
  // all elements assumed to be the same speaker
  // return all paragraphs as a string
  $chunks = array();
  $captions = array();
  foreach ($speech as $caption) {
    $captions[] = $caption;
    if (preg_match('/[.?!]$/', $caption) == 1) {
      // end of a sentence at the end of a caption
      $chunks[] = implode(' ', $captions);
      $captions = array();
    }
  }
  if (!empty($captions)) {
    $chunks[] = implode(' ', $captions);
  }
  $paragraphs = '<p>' . implode('</p><p>', $chunks) . '</p>';
  return $paragraphs;
}

function load_speakers($url) {
  // read file into string
  $content = file_get_contents($url);
  $text = explode("\n", $content);
  $known_speakers = array();
  $state = 'number';
  foreach ($text as $line) {
    $line = rtrim($line, "\r\n") . PHP_EOL; // for preg_match $ to work
    switch ($state) {
      case 'number':
        if (preg_match('/^[0-9]+$/', $line) === 1) {
          $state = 'timing';
        }
        else {
          // allow multiple empty lines
          $line = trim($line);
          $state = empty($line) ? 'number' : 'error 1: ' . $line;
        }
        break;
      case 'timing':
        $state = (preg_match('/\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}/', $line) === 1) ? 'first line' : 'error 2';
        break;
      case 'first line':
        $state = 'another line';
        if (($offset = mb_strpos($line, ':')) !== FALSE) {
          $speaker = mb_substr($line, 0, $offset);
          if (($key = array_search($speaker, $known_speakers)) === FALSE) {
            $known_speakers[] = $speaker;
          }
         }
        // intentional fall through
      case 'another line':
        $trimmed = trim($line);
        if (empty($trimmed)) {
          $state = 'number';
        }
        break;
      default:
        print_r (array('error trying to find speakers: ', $state));
        return;
      }
    }
  return $known_speakers;
  }

function load_transcript($url, $selected_speakers, $voiceofgod = '') {
  // read file into string
  $content = file_get_contents($url);
  $text = explode("\n", $content);
  $state = 'number';
  $output = array();
  if (!empty($voiceofgod)) {
     $output[] = array('speaker' => $voiceofgod);
  }
  foreach ($text as $line) {
    $line = rtrim($line, "\r\n") . PHP_EOL; // for preg_match $ to work
    switch ($state) {
      case 'number':
        if (preg_match('/^[0-9]+$/', $line) === 1) {
          $state = 'timing';
        }
        else {
          // allow multiple empty lines
          $line = trim($line);
          $state = empty($line) ? 'number' : 'error 1: ' . $line;
        }
        break;
      case 'timing':
        $state = (preg_match('/\d{2}:\d{2}:\d{2},\d{3} --> \d{2}:\d{2}:\d{2},\d{3}/', $line) === 1) ? 'first line' : 'error 2';
        break;
      case 'first line':
        $state = 'another line';
        if (($offset = mb_strpos($line, ':')) !== FALSE) {
          $speaker = mb_substr($line, 0, $offset);
          if (in_array($speaker, $selected_speakers)) {
            $output[] = array('speaker' => $speaker);
            $output[] = array('text' => trim(mb_substr($line, $offset + 2)));
            break;
          }
        }
       // intentional fall through
      case 'another line':
        $trimmed = trim($line);
        if (empty($trimmed)) {
          $state = 'number';
        }
        else {
          $output[] = array('text' => $trimmed);
        }
        break;
      default:
        print_r (array('error trying to find speakers: ', $state));
        return;
      }
    }

    $this_speaker = FALSE;
    $this_speech = array();
    $value = array();
    $value[] = '<dl>';
    foreach ($output as $line) {
      if (isset($line['speaker'])) {
        if ($line['speaker'] != $this_speaker) {
          if (!empty($this_speech)) {
            $value[] = '<dt>' . $this_speaker . '</dt>';
            $value[] = '<dd>' . speech2paragraphs($this_speech) . '</dd>';
            $this_speech = array();
          }
          $this_speaker = $line['speaker'];
        }
      }
      elseif (!empty($line['text'])) {
        $this_speech[] = $line['text'];
      }
    }
    if (!empty($this_speech)) {
      $value[] = '<dt>' . $this_speaker . '</dt>';
      $value[] = '<dd>' . speech2paragraphs($this_speech) . '</dd>';
    }
  $value[] = '</dl>';
  return $value;
}

$speakers = array();
$formSpeaker = array();
$url = '';

$voiceofgod = (!empty($_POST['voiceofgod'])) ? $_POST['voiceofgod'] : 'Commentator';

if (!empty($_POST['url'])) {
  $url = $_POST['url'];
  $speakers = load_speakers($url);
  if (!empty($voiceofgod)) $speakers[] = $voiceofgod;
  if (isset($_POST['formSpeaker'])) {
    $formSpeaker = $_POST['formSpeaker'];
  }
}

if (!empty($_POST['speakers-submit'])) {
}

if (!empty($_POST['transcript-submit'])) {
  $selected_speakers = array();
  foreach ($speakers as $safe => $speaker) {
    if (in_array($safe, $formSpeaker)) {
      $selected_speakers[] = $speaker;
    }
  $transcript = load_transcript($url, $selected_speakers, $voiceofgod);
  }
}
header('Content-Type:text/html; charset=UTF-8');
?>

<html>
<body>
<p>Convert .srt files to transcripts</p>
<form name="speakers" method="post">
URL of .srt file: <input type="text" name="url" value="<?php print $url; ?>" /><br />
Commentator: <input type="text" name="voiceofgod" value="<?php print $voiceofgod; ?>" /><br />

<?php
$checks = array();
foreach ($speakers as $safe => $speaker) {
  $checked = in_array($safe, $formSpeaker) ? 'checked="checked"' : '';
  print "<input type=\"checkbox\" name=\"formSpeaker[]\" value=\"$safe\" $checked />$speaker<br />" . PHP_EOL;
}
?>
<input type="submit" name="speakers-submit" value="Select Speakers" /><br />
<input type="submit" name="transcript-submit" value="Generate Transcript" /><br />

<?php
if (isset($transcript)) {
  print "<textarea id=\"transcript-text\">" . PHP_EOL;
  foreach ($transcript as $line) {
    print $line . PHP_EOL;
  }
  print "</textarea>";
  print '<script>document.getElementById("transcript-text").select();</script>';
}
?>
</form>

<?php
if (isset($transcript)) {
  foreach ($transcript as $line) {
    print $line . PHP_EOL;
  }
}
?>

</body>
</html>
