<?php
/*
 Returns metadata about a Kaltura asset

 assetLister.php?entryID=1_lcfb82xi

 Argument is the internal Kaltura ID

 Output example:
	<entries>
	<entry>
	<entryID>1_lcfb82xi</entryID>
	<metadata>
	<ContentCategories>Instructional Recordings</ContentCategories>
	<CreationDate>1313384400</CreationDate>
	<Identifier>olinuris_005</Identifier>
	<ContributingUnit>Olin Library (John M. Olin Library)</ContributingUnit>
	<ContributingUnit>Uris Library</ContributingUnit>
	<ContributingUnit>Mann Library (Albert R. Mann Library)</ContributingUnit>
	<RightsStatement>
	This video has been produced by Cornell University Library, with permission of the presenting speaker, to benefit education, research and scholarship. Cornell University Library videos may be embedded or linked on any non-commercial web site without formal permission. Please cite Cornell University Library as the source of the video. The right to excerpt, modify or in any way reproduce any video published by Cornell University Library is subject to the terms of copyright ownership outlined by the United States Copyright Act. Users must seek permission from the copyright owner for all uses of any Cornell University Library-produced video that are not allowed by fair use and other provisions of the Copyright Act.
	</RightsStatement>
	<Repository>Cornell University Library</Repository>
	</metadata>
	</entry>
	</entries>

	http://mediaws.library.cornell.edu/assetLister/assetLister.php?entryID=1_lcfb82xi

*/

require_once('../php5/KalturaClient.php');
require_once('../QueryPath/QueryPath.php');
require_once('../conf/MyKalturaConfiguration.php');

class Sample
{
	public static function run($entryID)
	{
		if(!MyKalturaConfiguration::SECRET)
		{
			throw new Exception("Please fill the partner credentials in MyKalturaConfiguration class");
		}

		$test = new Sample();
		return $test->search($entryID);
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

	public function search($entryID) {
		try {
			$metadataProfileId = 5761;

			$kalturaClient = $this->getKalturaClient(MyKalturaConfiguration::PARTNER_ID, MyKalturaConfiguration::ADMIN_SECRET, true);

			// get the php library caption plugin object created for this client.
			$metadataPlugin = KalturaMetadataClientPlugin::get($kalturaClient);

			// instantiating a filter object required for the API call.
			$metadataFilter = new KalturaMetadataFilter();
			//known ID of metadata profile copied from KMC
			$metadataFilter->metadataProfileIdEqual = $metadataProfileId;
			// the type of object for which we want metadata
			$metadataFilter->metadataObjectTypeEqual = KalturaMetadataObjectType::ENTRY;
			// filtering metadata objects for specific entry:
			$metadataFilter->objectIdEqual = $entryID;
			// calling the specific service ‘metadata’ and a specific action ‘list’
			// so we can get existing values from metadata
			$metadataForEntry = $metadataPlugin->metadata->listAction($metadataFilter);
			// var_dump() for testing.
			$result = $metadataForEntry->objects[0]->xml;
			return $result;

			/*
			echo '<pre>';
			echo var_dump($result);
			echo '</pre>';
			throw new Exception('outa here');
			*/

			}
		catch(Exception $ex) {
			die($ex->getMessage());
			}
	}

}

try {
	if (empty($_GET['entryID'])) {
		throw new Exception ('needs an entryID argument.');
		}

	// find the xml file argument
	$entryID = trim($_GET['entryID']);
	if (preg_match('/^[0-9a-z_]+$/', $entryID) !== 1) {
		throw new Exception ('invalid entryID format.');
		}

	$results = Sample::run($entryID);

	$output[] = '<?xml version="1.0"?>';
	$output[] = '<entries>';
	$output[] = '<entry>';
	$output[] = "<entryID>$entryID</entryID>";
	$output[] = $results;
	$output[] = '</entry>';
	$output[] = '</entries>';

	header ("Content-Type:text/xml");
	qp(implode(PHP_EOL,$output))->writeXML();
	}
catch(Exception $ex) {
	die($ex->getMessage());
	}


?>
