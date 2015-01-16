<!DOCTYPE HTML>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ADOBE ANALYTICS REST REPORTING API 1.3</title>
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
		$server = "https://api.omniture.com";
		*/
        $username = '[WEB SERVICES USERNAME]';
        $secret = '[WEB SERVICES PASSWORD]';
        $nonce = md5(uniqid(php_uname('n'), true));
        $nonce_ts = date('c');
        $digest = base64_encode(sha1($nonce.$nonce_ts.$secret));
		
		/*Make sure to point to the right data center $server
		 * Sanjose : api.omniture.com
		 * Dallas : api2.omniture.com
		 * London : api3.omniture.com
		 * Singapore : api4.omniture.com
		 * Portland : api5.omniture.com
		 * */
        $server = "https://api.omniture.com";
        $path = "/admin/1.3/rest/";

        $rc=new SimpleRestClient();
        $rc->setOption(CURLOPT_HTTPHEADER, array("X-WSSE: UsernameToken Username=\"$username\", PasswordDigest=\"$digest\", Nonce=\"$nonce\", Created=\"$nonce_ts\""));

        $rc->postWebRequest($server.$path.'?method='.$method, $data);

        return $rc;
    }
	/*Use the right method. All 1.3 methods can be found here : https://marketing.adobe.com/developer/documentation/sitecatalyst-reporting/c-overview-1*/
	
	/*Example of methods :
	Report.QueueOvertime
	Report.QueueRanked
	Report.QueueTrended
	
	*/
    $method="Report.QueueRanked";
	
	/*Build you REST requests. For example of requests go to API explorer : https://marketing.adobe.com/developer/api-explorer*/
    $data='{
            "reportDescription":{
                "reportSuiteID":"[REPORT SUITE ID]",
                "dateFrom":"[START DATE IN FORMAT YYYY-MM-DD]",
                "dateTo":"[END DATE IN FORMAT YYYY-MM-DD]",
                "metrics":[{"id":"pageviews"}],
                "elements":[{"id":"page","top":"25"}]
            }
           }';
	
	/*Request the data*/
    $rc=GetAPIData($method, $data);
	
	/*Check the status of the request*/
    if ($rc->getStatusCode()==200) {
        $response=$rc->getWebResponse();
        $json=json_decode($response);
		/*Extract Report ID*/
        if ($json->status=='queued') {
            $reportID=$json->reportID;
        }
        else {
            $error=true;
            echo "not queued - <br />";
        }
    } else {
        $error=true;
        $error=true;
        echo "something went really wrong <br />";
        var_dump($rc->getInfo());
        echo "\n".$rc->getWebResponse();
    }
	
	/*Check if the report id has finished processing*/
    while (!$done && !$error) {
        sleep(15);

        $method="Report.GetStatus";
        $data='{"reportID":"'.$reportID.'"}';

        $rc=GetAPIData($method, $data);

        if ($rc->getStatusCode()==200) {
            $response=$rc->getWebResponse();
            $json=json_decode($response);

            if ($json->status=="done") {
                $done=true;
            }
            else if ($json->status=="failed" || strstr($json->status, "error")>0) {
                $error=true;
            }
        } else {
            $done=true;
            $error=true;
            echo "something went really wrong <br />";
            var_dump($rc->getInfo());
            echo "\n".$rc->getWebResponse();
        }
    }
	
	/*Oce report ready get the data*/
    if ($error) {
        echo "report failed:<br />";
        echo $response;
    }
    else {
        $method="Report.GetReport";
        $data='{"reportID":"'.$reportID.'"}';

        $rc=GetAPIData($method, $data);
        if ($rc->getStatusCode()==200) {
            $response=$rc->getWebResponse();
            $json=json_decode($response);
            echo "<h1>Your report: </h1>";
            //var_dump($json);
            echo "<table border='1'><tr><td>Page</td><td>PageViews</td></tr>";
            foreach ($json->report->data as $el) {
                echo "<tr><td>" .$el->name. "</td><td>".$el->counts[0]."</td></tr>";
            }
        } else {
            echo "something went really wrong <br />";
            var_dump($rc->getInfo());
            echo "<br />".$rc->getWebResponse();
        }
    }

?>



</body>
</html>