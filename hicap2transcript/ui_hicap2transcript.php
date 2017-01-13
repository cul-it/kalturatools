<?php
/**
 * ui_hicap2transcript.php
 * form for making transcripts out of hicaption files
 */

function this_dir_url() {
  $url  = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
  $url .= $_SERVER['SERVER_NAME'];
  $url .= $_SERVER['REQUEST_URI'];

  return dirname($url);
}

$hicap_url = '';

if (!empty($_POST['transcript-submit'])) {
  if (isset($_POST['url'])) {
    $hicap_url = $_POST['url'];
    $service_url = this_dir_url() . '/hicap2transcript.php?hicap=' . $hicap_url;
    $transcript = file_get_contents($service_url);
  }
}
header('Content-Type:text/html; charset=UTF-8');
?>

<html>
<body>
<p>Convert HiCaptionCC files to transcripts</p>
<form name="speakers" method="post">
URL of HiCaptionCC file:<br />
<textarea rows="2" cols="100" name="url"><?php print $hicap_url; ?></textarea><br />
<input type="submit" name="transcript-submit" value="Generate Transcript" /><br />

<?php
if (isset($transcript)) {
  print "<textarea rows=\"4\" cols=\"50\" id=\"transcript-text\">" . PHP_EOL;
  print $transcript;
  print "</textarea>";
  print '<script>document.getElementById("transcript-text").select();</script>';
}
?>
</form>

<?php
if (isset($transcript)) {
  print $transcript;
}
?>

</body>
</html>
