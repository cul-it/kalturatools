<?php
/*
 reference2entryID.php?input=http://test.library.cornell.edu/bla.xml

 Argument is the URL of an xml file

 Input file format:
 	<?xml version="1.0"?>
 	<references>
 		<referenceID>http://ecommons.library.cornell.edu/bitstream/1813/30386/1</referenceID>
 		...
 	</references>

 Output:
 	<?xml version="1.0"?>
 	<entries>
 		<entry>
 			<referenceID>http://ecommons.library.cornell.edu/bitstream/1813/30386/1</referenceID>
 			<entryID>1_o2dqvq4n</entryID>
 			<name>518251_1349784429</name>
 		</entry>
 		...
 	</entries>

 	http://mediaws.library.cornell.edu/reference2entryID/reference2entryID.php?input=http://mediaws.library.cornell.edu/reference2entryID/test.xml
*/

require_once('../php5/KalturaClient.php');
require_once('../QueryPath/QueryPath.php');
require_once('../conf/MyKalturaConfiguration.php');

class Sample
{
	public static function run($referenceIDs)
	{
		if(!MyKalturaConfiguration::SECRET)
		{
			throw new Exception("Please fill the partner credentials in MyKalturaConfiguration class");
		}

		$test = new Sample();
		return $test->search($referenceIDs);
	}

	private function getKalturaClient($partnerId, $adminSecret, $isAdmin)
	{
		$kConfig = new KalturaConfiguration($partnerId);
		$kConfig->serviceUrl = MyKalturaConfiguration::SERVICE_URL;
		$client = new KalturaClient($kConfig);

		$userId = "SomeUser";
		$sessionType = ($isAdmin)? KalturaSessionType::ADMIN : KalturaSessionType::USER;
		try
		{
			$ks = $client->generateSession($adminSecret, $userId, $sessionType, $partnerId);
			$client->setKs($ks);
		}
		catch(Exception $ex)
		{
			die("could not start session - check configurations in MyKalturaConfiguration class");
		}

		return $client;
	}

	public function search($referenceIDs) {
		try {
			$client = $this->getKalturaClient(MyKalturaConfiguration::PARTNER_ID, MyKalturaConfiguration::ADMIN_SECRET, true);
			$client->startMultiRequest();
			foreach ($referenceIDs as $refid) {
				$client->baseEntry->listByReferenceId($refid['referenceID']);
				}
			$multiRequest = $client->doMultiRequest();
			$results = array();
			foreach ($multiRequest as $mr) {
				$results[] = array(
					'referenceID' => $mr->objects[0]->referenceId,
					'entryID' => $mr->objects[0]->id,
					'name' => $mr->objects[0]->name,
					);
				}
			return $results;
			}
		catch(Exception $ex) {
			die($ex->getMessage());
			}
	}

}

try {
	if (!empty($_GET['input'])) {
		$xml_input = $_GET['input'];
	}
	else if (!empty($argv[1])) {
		$xml_input = $argv[1];
	}
	else {
		throw new Exception ('needs an input argument.');
	}

	// find the xml file argument
	$url_parts = parse_url($xml_input);
	if ($url_parts === FALSE) {
		throw new Exception ('badly formed url in input argument');
		}
	if (substr_compare($url_parts['path'], '.xml', -4, 4, TRUE) != 0) {
		throw new Exception ('url path must end in .xml');
		}

	// find the xml
	$qp = qp($xml_input, 'references');

	$referenceIDs = array();
	foreach ($qp->children() as $ref_qp) {
		$referenceIDs[] = array('referenceID' => $ref_qp->text());
		}
	$chunks = array_chunk($referenceIDs, 5);

	$output[] = '<?xml version="1.0"?>';
	$output[] = '<entries>';
	foreach ($chunks as $chunk) {
		$results = Sample::run($chunk);
		foreach ($results as $result) {
			$output[] = '<entry>';
			foreach ($result as $key => $val) {
				$output[] = sprintf('<%s>%s</%s>', $key, htmlspecialchars($val), $key);
				}
			$output[] = '</entry>';
			}
		}

	$output[] = '</entries>';

	header ("Content-Type:text/xml");
	$text = implode(PHP_EOL,$output);
	qp($text)->writeXML();
	}
catch(Exception $ex) {
	die($ex->getMessage());
	}


?>
