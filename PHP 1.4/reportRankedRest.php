<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>phpChart - A Basic Chart</title>
</head>
<body>

<?php
	include_once("SimpleRestClient.class.php");
	$error = false; 
	$done = false;
	
	function GetAPIData($method, $data) {
		/*$username = '[WEB SERVICES USERNAME]'; 
		$secret = '[WEB SERVICES PASSWORD]';
		Both can be found under ADMIN >> COMPANY SETTINGS >> WEB SERVICES (only users with web services right will be listed in the table)
		
		*/
		$username = '[WEB SERVICES USERNAME]';
		$secret = '[WEB SERVICES PASSWORD]';
		$nonce = md5(uniqid(php_uname('n'), true));
		$nonce_ts = date('c');
		$digest = base64_encode(sha1($nonce.$nonce_ts.$secret));
		/*$server possible values :
			api.omniture.com - San Jose
			api2.omniture.com - Dallas
			api3.omniture.com - London
			api4.omniture.com - Singapore
			api5.omniture.com - Pacific Northwest
		*/
		$server = "https://api.omniture.com";
		$path = "/admin/1.4/rest/";

		$rc=new SimpleRestClient();
		$rc->setOption(CURLOPT_HTTPHEADER, array("X-WSSE: UsernameToken Username=\"$username\", PasswordDigest=\"$digest\", Nonce=\"$nonce\", Created=\"$nonce_ts\""));

		$rc->postWebRequest($server.$path.'?method='.$method, $data);

		return $rc;
	}
	
	/*Build you REST requests. For example of requests go to API explorer : https://marketing.adobe.com/developer/api-explorer*/
	
	/*Check documentation https://marketing.adobe.com/developer/documentation/analytics-reporting-1-4/get-started*/
	$method="Report.Queue";
	$data='{
			"reportDescription":{
				"reportSuiteID":"[REPORT SUITE ID]",
				"dateFrom":"2015-01-01",
				"dateTo":"2015-01-15",
				"metrics":[{"id":"pageviews"}],
				"elements":[{"id":"page","top":"25"}]
			}
			}';

	$rc=GetAPIData($method, $data);

	if ($rc->getStatusCode()==200) {
		$response=$rc->getWebResponse();
		$json=json_decode($response);
		if ($json->reportID) {
			$reportID=$json->reportID;
		}
		else {
			$error=true;
			echo "not queued - <br />";
		}
	} else {
		$error=true;
		echo "something went really wrong <br />";
		var_dump($rc->getInfo());
		echo "\n".$rc->getWebResponse();
	}

	while (!$done && !$error) {
		sleep(15);

		$method="Report.Get";
		$data='{"reportID":"'.$reportID.'"}';

		$rc=GetAPIData($method, $data);

		if ($rc->getStatusCode()==200) {
			$response=$rc->getWebResponse();
			$json=json_decode($response);
			//If report is ready the data will be displayed
			if ($json->report) {
				$done=true;
				echo "<h1>Your report: </h1>";
				//var_dump($json);
				echo "<table border='1'><tr><td>Page</td><td>PageViews</td></tr>";
				foreach ($json->report->data as $el) {
					echo "<tr><td>" .$el->name. "</td><td>".$el->counts[0]."</td></tr>";
				}
			}
			//if data not ready error message
			else if ($json->error=="report_not_ready" || $json->error) {
				$error=false;
			}
		} else {
			$done=true;
			$error=true;
			echo "something went really wrong <br />";
			var_dump($rc->getInfo());
			echo "\n".$rc->getWebResponse();
		}
	}

	if ($error) {
		echo "report failed:<br />";
		echo $response;
	}

?>



</body>
</html>