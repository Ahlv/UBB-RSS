<?php

header('Content-Type: application/xml; charset="UTF-8"', true); //set document header content type to be XML

require 'dbinfo.php';

date_default_timezone_set('Europe/Oslo'); //set your SERVER timezone

//Fetching with parameters from URL
$dewey_range_start = ($_GET["start"]);
$dewey_range_start = (float) $dewey_range_start;

$dewey_range_stop = ($_GET["slutt"]);
$dewey_range_stop = (float) $dewey_range_stop;

//Regex for washing paramter of strings. Not neccesary in testing
//$param_value = preg_replace('/[a-å, A-Å]/', '', $param_value);
$param_value_2 = ($_GET["avdeling"]);

$rss = new SimpleXMLElement('<rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom"></rss>');

$rss->addAttribute('version', '2.0');

$channel = $rss->addChild('channel'); //add channel node


$atom = $rss->addChild('atom:atom:link'); //add atom node
$atom->addAttribute('href', 'http://localhost'); //add atom node attribute
$atom->addAttribute('rel', 'self');
$atom->addAttribute('type', 'application/rss+xml');

$title = $channel->addChild('title','Nye fysiske titler ved universitetsbiblioteket'); //title of the feed
$link = $channel->addChild('link', 'http://158.39.74.137/nye_p-boker.php');
$description = $channel->addChild('description','Nyankomne fysiske bøker ved ' . $param_value_2); //feed description

//connect to MySQL - mysqli(HOST, USERNAME, PASSWORD, DATABASE);
$mysqli = new mysqli($hostname, $username, $password, $dbname);
mysqli_set_charset($mysqli,"utf8");


//Output any connection error
if ($mysqli->connect_error) {
    die('Error : ('. $mysqli->connect_errno .') '. $mysqli->connect_error);
}


//Start fetching

//Query based on dewey-range AND avdeling
$results = $mysqli->query("SELECT Forfatter, Boktittel, Utgivelsesar, Dewey, permalenke FROM Test WHERE Dewey BETWEEN '$dewey_range_start' AND '$dewey_range_stop' AND Avdeling LIKE '$param_value_2' ORDER BY Utgivelsesar DESC");



if($results){ //we have records
    while($row = $results->fetch_object()) //loop through each row
    {

        $item = $channel->addChild('dc:item'); //add item node

        $forfatter = $item->addChild('content:description', htmlspecialchars($row->Forfatter)); //add title node under item

        $boktittel = $item->addChild('content:title', htmlspecialchars($row->Boktittel)); //add link node under item AND added htmlspecialchars for escaping ampersands

        $utgivelsesar = $item->addChild('content:year', htmlspecialchars($row->Utgivelsesar));

        $dewey = $item->addChild('content:dewey', $row->Dewey);

        $pl = $item->addChild('content:guid', htmlspecialchars($row->permalenke));

    }
}

echo $rss->saveXML(); //output XML
