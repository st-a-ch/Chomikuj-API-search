<?php 
error_reporting(0);
?>
/* 
	Zmiany: $_REQUEST zamiast $_POST --> dziala z linia komend w url
	dodane wyszukiwanie wg grupy
	Braki: realID w wynikach wyszukiwania
*/

<html dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xmlns:b="http://www.google.com/2005/gml/b" xmlns:data="http://www.google.com/2005/gml/data" xmlns:expr="http://www.google.com/2005/gml/expr">
    <head>
        <title>Chomikuj search v.2</title>
        <meta content="text/html; charset=utf-8" http-equiv="Content-Type"/>

    </head>
        

    <body>
####################### request form #######################################
	        <form action='' class='search' method="post" >
        szukana fraza: <input class="" type="text" name="q" value="<?php if(isset($_REQUEST['q'])){echo $_REQUEST['q'];}?>" style="width:250px;">
        Strona: <input class="par" type="text" name="p" value="<?php if(isset($_REQUEST['p'])){echo $_REQUEST['p'];}?>">
        Typ: <select name="typ" class="">
					<option value="All" <?php if($_REQUEST['typ'] == "All"){echo "selected";}?>>All</option>
					<option value="Video" <?php if($_REQUEST['typ'] == "Video"){echo "selected";}?>>Video</option>
					<option value="Music" <?php if($_REQUEST['typ'] == "Music"){echo "selected";}?>>Music</option>
					<option value="Documents" <?php if($_REQUEST['typ'] == "Documents"){echo "selected";}?>>Documents</option>
					<option value="Archives" <?php if($_REQUEST['typ'] == "Archives"){echo "selected";}?>>Archives</option>
					<option value="Programs" <?php if($_REQUEST['typ'] == "Programs"){echo "selected";}?>>Programs</option>
					<option value="Image" <?php if($_REQUEST['typ'] == "Image"){echo "selected";}?>>Image</option>
					<option value="Others" <?php if($_REQUEST['typ'] == "Others"){echo "selected";}?>>Others</option>
				</select> 
        Min Size:<input class="par" type="text" name="min" value="<?php if(isset($_REQUEST['min'])){echo $_REQUEST['min'];}?>">
        Max Size: <input class="par" type="text" name="max" value="<?php if(isset($_REQUEST['max'])){echo $_REQUEST['max'];}?>">
        Extension<input class="par" type="text" name="ext"> <select name="all">
          <option value="1" <?php if($_REQUEST['all'] == "1"){echo "selected";}?>>Wszystkie</option>
          <option value="0" <?php if($_REQUEST['all'] == "0"){echo "selected";}?>>niesprzedażowe</option>
        </select> 
        <input type="submit" name="szukaj" value="Szukaj"></form>

    </body>
</html>


<?php

if(isset($_REQUEST['szukaj'])){
    echo 'start:'.date('Y-m-d H:i:s');
    
    echo "<table>";
    $wsdl = 'http://chomikuj.pl/services/abuse/PublishmentService.svc?wsdl';
    
    $soapClient = new SoapClient($wsdl, array('cache_wsdl' => 0));

    $i=0;
    
    $search_start = 1;
    $search_stop = 50;
    if(isset($_REQUEST['p']) && (int)$_REQUEST['p'] > 0){
        $search_start = $search_stop = (int)$_REQUEST['p'];
    }
    
    $i = 1;
    
    for($p=$search_start;$p<=$search_stop; $p++){
        $parameters = new StdClass;
        $parameters->Query = $_REQUEST['q'];
        if($p) {$parameters->Page = $p;} else {$parameters->Page = 1;}
        if($_REQUEST['typ']) {$parameters->MediaType = $_REQUEST['typ'];}
        if($_REQUEST['ext']) {$parameters->FileExtension = $_REQUEST['ext'];}
        if($_REQUEST['max']) {$parameters->MaxSize = $_REQUEST['max'];}
        if($_REQUEST['min']) {$parameters->MinSize = $_REQUEST['min'];}
#####################################################################################################################
        $parameters->ApiKey = "TWOJ KLUCZ API";		//API KEY
#####################################################################################################################
        $request = new StdClass;
        $request->searchParams = $parameters;	
        try {$result = $soapClient->Search($request);}
        catch (SoapFault $fault) {
            echo "Fault code: {$fault->faultcode}";
            echo "Fault string: {$fault->faultstring}";
            if ($soapClient != null) {$soapClient = null;}
            exit();
        }

        $wyniki = $result->SearchResult->Results->SearchFileItem;
        
        if(is_array($wyniki)){
            
            foreach($wyniki as $w){
                $i = show_data($w, $_REQUEST['all'], $i);
                if($i == 0) {
                    $p=$search_stop; break;
                }
            }
        }
        else{
            $i = show_data($wyniki, $_REQUEST['all'], 1);
            if($i == 0) {
                $p=$search_stop; break;
            }
        }
    }
    
    echo "</table>";
    $soapClient = null;    

    echo 'stop:'.date('Y-m-d H:i:s');
}

#################### non optymized MySql Database request and options ################################
function show_data($data, $all, $i){
    $data = (array)$data;
// global $hostname, $username, $password, $database, $DBTab;		//import zmiennych do funkcji
// $conn = new mysqli($hostname, $username, $password, $database);	//start wlasnej DB 

    if(!isset($data['IsSalesFile'])){
        return 0;
    }
    if(isset($data['IsSalesFile']) && ($data['IsSalesFile'] != 1 || $all == 1)) {
        // $sql = "UPDATE $DBTab SET ready='0' WHERE id=$data['Id']";		//ewentualne operacje na wasnej DB
        // $conn->query($sql);
        echo "<tr><td>".$i."</td><td>".$data['Id']."</td><td><a class='sale_".$data['IsSalesFile']."' href='".$data['Url']."'>".$data['Name'].".".$data['Extension']."</a></td></tr>\n";
        return $i+1;
    }
    else{
        return $i;
    }
// $conn->close();	//stop wasnej DB
}
