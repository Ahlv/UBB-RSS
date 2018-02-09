<?php

//Variable containing path to analytics report and API key
$url = "https://api-eu.hosted.exlibrisgroup.com/almaws/v1/analytics/reports?apikey=l7xxf66cd92827a3492e8e22b448d4bed112&path=%2Fshared%2FUniversitetsbiblioteket%20i%20Bergen%2FReports%2FUBBDST%2Fcopy%20of%20PhysicalAVN&limit=50";

//Include database information
require 'dbinfo.php';

//Start curl-request
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $url);
$outputFraCurl = curl_exec ($ch);
curl_close($ch);


//Interprets the string from the curl-request to a xml-object
$xml=simplexml_load_string($outputFraCurl);


//Connect to database
$conn = new mysqli($hostname, $username, $password, $dbname)
  or die("Unable to connect to MySQL");
echo "Tilkobling i orden.<br>" . " ";

//Set the default character set to utf-8. Special characters will be a problem unless this function is set
mysqli_set_charset($conn,"utf8");

//Register your namespace
$xml->registerXPathNamespace('UBB', 'urn:schemas-microsoft-com:xml-analysis:rowset');

//Set xpath with your namespace
$result = $xml->xpath('//UBB:rowset/UBB:Row');

//Namespace must be set anew in the foreach-loop. [0] is set to return value from the array
foreach ($result as $key => $value){
        $value ->registerXPathNamespace('UBB', 'urn:schemas-microsoft-com:xml-analysis:rowset');
        $forfatter= $value->xpath('UBB:Column1')[0];
        $boktittel= $value->xpath('UBB:Column4')[0];
        $utgivelsesar= $value->xpath('UBB:Column3')[0];
        $dewey= $value->xpath('UBB:Column5')[0];
        $mms= $value->xpath('UBB:Column2')[0];
        $avdeling= (string) $value->xpath('UBB:Column6')[0];



         //Dette filteret og flaggene fjerner alle ikke-integers, men ikke komma hvilket jeg trenger. Men bokstavene etter ints fjernes også. er dette et problem?
        $dewey = filter_var($dewey, FILTER_SANITIZE_NUMBER_FLOAT,
        FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);

        //Vasker bort uønskede tegn i utgivelsesår
        $utgivelsesar = filter_var($utgivelsesar, FILTER_SANITIZE_NUMBER_INT);

        //Tillater apostrof
        $boktittel = $conn->real_escape_string($boktittel);

        //Tillater apostrof
        $avdeling = $conn->real_escape_string($avdeling);

        //Creates permalink from combining institutional URL with the mms_id
        $primopl = 'http://bibsys-almaprimo.hosted.exlibrisgroup.com/primo_library/libweb/action/dlSearch.do?institution=UBB&vid=UBB&tab=default_tab&query=any,contains,{mms_id}';
        $permalenke = str_replace('{mms_id}', $mms, $primopl);


        //Inserts sanitized data into database
        $sql = "INSERT INTO Test(Forfatter, Boktittel, Utgivelsesar, Dewey, mms, permalenke, avdeling)
        values('$forfatter', '$boktittel', '$utgivelsesar', '$dewey', '$mms', '$permalenke', '$avdeling')";



    //tester om INSERT INTO er vellykket
    if (mysqli_query($conn, $sql)) {
    echo "<br>Ny innførsel lagd <br>";
} else {
    echo "Error: " . $sql . "<br>" . mysqli_error($conn);
}


 };

?>
