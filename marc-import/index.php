<?php
require_once "db.php"; //database connection settings
require_once "php-marc/php-marc.php"; //does the MaRC heavy lifting
//require_once 'ansel/Ansel2Unicode.php';

//$a2u = new Ansel2Unicode();
$marc_data = new File("marc_import_files/toc-data.mrc"); //the file we want to process

//   -----Timer-----    //
$mtime = microtime(); 
$mtime = explode(' ', $mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$starttime = $mtime; 
//   -----Timer-----    //


$count=0; //just counting MARC records
while ($record = $marc_data->next()) {
	$count++; 
	$fields = $record->fields();
	//var_dump($record);  //troubleshooting - dumps a php-marc generated array
	$i=0;$j=0; //used for for/foreach loops below
	
	//   ----------LOOP THROUGH MARC FIELDS----------   //	
		foreach($fields as $field) {		 
			switch((string) $field[0]->tagno) {
					case '001'; //Innovative-supplied Bibid
	    				$bibid = $field[0]->data;
					break;
					case '005'; //last updated
						$last_updated = substr($field[0]->data,0,14); //format:  YYYYMMDDHHMMSS
					break;
					case '008'; //Langugage of work
						$language = substr($field[0]->data,35,3);  //the field is 35 characters into the 008 field 
					break;
					case '020'; //ISBN (10 or 13)
	    				foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $isbn = trim(preg_replace('/[^\d\s]/', '', $value)," "); //just want isbn digits
						}
					case '022'; //ISSN
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $issn = trim(preg_replace('/[^\d\s]/', '', $value)," "); //just want issn digits
						}
					break;
					case '99'; //Brock assigned call number (full)
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $callnumber = $value;
						}
					break;
					case '100'; //Author - sole primary
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								if($key == "a")	$author[$j]["a"] = $value; //author name - will have to split on comma
								if($key == "d") $author[$j]["dob"] = $value; //author date of birth
							}
						}
					break;
					case '240'; //Uniform title - typically used when the work's original language is not English
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $uniformtitle = $value; //can do FRBRization on this field?
						}
					break;
					case '245'; //Title a=main title; b=subtitle; c=statement of responsibility (editors, usually)
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $title = trim($value,"/:-; ,.[]");
							if($key == "b") $title .= ":  ".trim($value,"/:-; ,.[]");
							if($key == "c") $responsibility = trim($value,"/:-; ,.[]");
						}
					break;
					case '246'; //Varying form of the title.
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $varyingtitle = $value;
						}
					break;
					case '250'; //Edition (also in 440)
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $edition = $value;
						}
					break;
					case '260'; //Publication data
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $publocation = $value;
							if($key == "b") $publisher = $value;
							if($key == "c") $pubdate = substr(ereg_replace('[^0-9]+','',$value),0,4);//cataloguers put all sorts of crap in this field - we only want a year.
							$pubdate .= "-00-00";
						}
					break;
					case '300'; //physical details of a book, mashed into one variable.
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $physical = trim($value,"/:-; ,.[]");
							if($key == "c") $physical .= "; " . trim($value,"/:-; ,.[]");
						}
					break;
					case '440'; //Another series field - this became obsolete in 2008 - so why is Brock still using it?
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $series2 = $value;
						}
					break;
					case '490'; //Series - a & v mashed into one variable
						foreach($field[0	]->subfields as $key => $value) {
							if($key == "a") $series = trim($value,"/:-; ,.[]");
							if($key == "v") $series .= "; " . trim($value,"/:-; ,.[]");
						}
					break;
					case '500'; //Notes
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $notes1 = $value;
						}
					break;
					case '504'; //More notes
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $notes2 = $value;
						}
					break;
					case '505'; //Technically, a notes field, but Brock uses for table of contents.
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $contents = $value; //TOC - will have to split on " -- "
						}
					break;
					case '520'; //Description
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $description = $value;
						}
					break;
					case '530'; //more notes
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $notes3 = $value;
						}
					break;
					case '590'; //more notes
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $notes4 = $value;
						}
					break;
					case '600';
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								$subject600[$j] .= trim($value, "/:-; ,.") . " -- ";
							}
						}
					break;
					case '610';
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								$subject610[$j] .= trim($value, "/:-; ,.") . " -- ";
							}
						}
					break;
					case '650';
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								$subject650[$j] .= trim($value, "/:-; ,.") . " -- ";
							}
						}
					break;
					case '651': //Subject fields.
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								$subject651[$j] .= trim($value,"/:-; ,.[]") . " -- ";
							}
						}
					break;
					case '700'; //Secondary authors
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								if($key == "a")	$secauthor[$j]["sa"] = $value;
								if($key == "d") $secauthor[$j]["dob"] = $value; //date of birth
							}
						}
					break;
					case '710'; //Additional corporate author
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								if($key == "a")	$secauthor710[$j] = $value;
							}
						}
					break;
					case '740'; //Why 2 varyingtitles, you ask?  pre-1996, 740 was used i/o 246.
						foreach($field[0]->subfields as $key => $value) {
							if($key == "a") $varyingtitle = $value;
						}
					break;
					case '856'; //URL field
						foreach($field[0]->subfields as $key => $value) if($key == "u") $url = $value;
					break;
					case '970'; //Table of contents, also
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								if($key == "l")	$toc[$j]["section"] = $value;
								if($key == "t")	$toc[$j]["title"] = $value;
								if($key == "f") $toc[$j]["author"] = $value;
								if($key == "p") $toc[$j]["page"] = $value;	
							}
						}
					case '998'; //Extra note fields assigned by whoever adds TOC data?
						for($j=0;$j<count($field);$j++) {
							foreach($field[$j]->subfields as $key => $value) {
								if($key == "a") $notes5[$i] = $value;	
								$i++;
							}
						}	
					break;
					$i=0;$j=0;
				} //end switch
		}  //end foreach
	//   ---------- END LOOP THROUGH MARC FIELDS----------   //
		
	//   -----Start the MySQL-----    //
	
	//   ----------ITEM TABLE----------   //
	$itemTABLEsql = "INSERT INTO item ";
	if(!empty($isbn)) $itemTABLEsql .= "SET standard_number = '".cleanup($isbn)."'";
	else $itemTABLEsql .= "SET standard_number = '".cleanup($issn)."'";
	if(!empty($title)) $itemTABLEsql .= ", title = '".cleanup($title)."'";
	if(!empty($responsibility)) $itemTABLEsql .= ", responsibility = '".cleanup($responsibility)."'";
	if(!empty($physical)) $itemTABLEsql .= ", physical = '".cleanup($physical)."'";
	if(!empty($pubdate)) $itemTABLEsql .= ", publish_date = '".cleanup($pubdate)."'";
	if(!empty($description)) $itemTABLEsql .= ", description = '".cleanup($description)."'";
	if(!empty($edition)) $itemTABLEsql .= ", edition = '".cleanup($edition)."'"; //ADD TO DB!
	if(!empty($bibid)) $itemTABLEsql .= ", bib_id = '".cleanup($bibid)."'";
	if(!empty($language)) $itemTABLEsql .= ", language = '".cleanup($language)."'";
	if(!empty($last_updated)) $itemTABLEsql .= ", last_updated = '$last_updated'";
	echo $itemTABLEsql . "<br />";  //output to browser
	$result = mysql_db_query($db,$itemTABLEsql,$connection);
	$itemid = mysql_insert_id();

	//   ----------CONTENTS TABLE----------   //	
	//Need to split contents from 505 field on ' -- '
	if(!empty($contents)) {
		$contentsTABLEsql = "";
		$contents_array = explode(" -- ",$contents);
		for($i=0;$i<count($contents_array);$i++) {
			/*
			preg_match("/[\x80-\xFF]./", $contents_array[$i], $matches);
			require_once 'Ansel2Unicode.php';
        	$a2u = new Ansel2Unicode();
			$unicode = $a2u->con($matches[0]);
			//var_dump($matches);
			*/
			$contentsTABLEsql = "INSERT INTO contents SET section = '$i', title = '".cleanup($contents_array[$i])."' #505'";
//			$contentsTABLEsql = "INSERT INTO contents SET section = '$i', title = '".$a2u->convert(cleanup($contents_array[$i]))."' #505'";
			echo $contentsTABLEsql . "<br />"; //output to browser
			$result = mysql_db_query($db,$contentsTABLEsql,$connection);
			$contentsid = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_contents SET item = '$itemid', contents = '$contentsid'",$connection);
			echo "INSERT INTO lookup_item_contents SET item = '$itemid', contents = '$contentsid'";
		}
	}
	
	if(!empty($toc)) { //from the 970 field (table of contents)
		for($i=0;$i<count($toc);$i++) {
			unset($contentsTABLEsql);
			$contentsTABLEsql = "INSERT INTO contents SET ";
			if(!empty($toc[$i]["section"])) $contentsTABLEsql .= "section = '".cleanup($toc[$i]["section"])."', ";
			if(!empty($toc[$i]["title"])) $contentsTABLEsql .= "title = '".cleanup($toc[$i]["title"])."'";
			if(!empty($toc[$i]["author"])) { //separating 'lastname, firstname' into two separate fields
				$author_array = explode(", ",$toc[$i]["author"]);
				$contentsTABLEsql .= ", author_last_name = '".cleanup($author_array[0])."'";
				$contentsTABLEsql .= ", author_first_name = '".cleanup($author_array[1])."'";
			}
			if(!empty($toc[$i]["page"])) $contentsTABLEsql .= ", page = '".cleanup($toc[$i]["page"])."'";
			$result = mysql_db_query($db,$contentsTABLEsql,$connection);
			echo $contentsTABLEsql . " #970<br />"; //output to browser
			$contentsid = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_contents SET item = '$itemid', contents = '$contentsid'",$connection);
			echo "<br />INSERT INTO lookup_item_contents SET item = '$itemid', contents = '$contentsid'";
		}
	}
	//   ----------PUBLISHER TABLE----------   //	
	if(!empty($publisher)) {
		$publisherTABLEsql = "INSERT INTO publisher ";
		if(!empty($publisher)) $publisherTABLEsql .= "SET name = '".cleanup($publisher)."'";
		if(!empty($publocation)) $publisherTABLEsql .= ", location = '".cleanup($publocation)."'";
		echo $publisherTABLEsql . "<br />";  //output to browser
		$result = mysql_db_query($db,$publisherTABLEsql,$connection);
		$publisherid = mysql_insert_id();
		$result = mysql_db_query($db,"INSERT INTO lookup_item_publisher SET item = '$itemid', publisher = '$publisherid'",$connection);
		echo "INSERT INTO lookup_item_publisher SET item = '$itemid', publisher = '$publisherid'";
	}

	//   ----------AUTHOR TABLE----------   //	
	if(!empty($author)) { //primary author
		for($i=0;$i<count($author);$i++) {
			$authorTABLEsql = "INSERT INTO author ";
//			$author_array = explode(", ",$author[$i]["a"]); //ok to reuse this array all over the place?
//			$authorTABLEsql .= "SET name = '".cleanup($author_array[0])."'";
//			$authorTABLEsql .= ", first_name = '".cleanup($author_array[1])."'";
			if(!empty($author[$i]["a"])) $author_TABLEsql .= "SET name = '".$author[$i]["a"]."'";
			if(!empty($author[$i]["dob"])) $authorTABLEsql .= ", date_of_birth = '".cleanup($author[$i]["dob"])."'";
			echo "<br />" . $authorTABLEsql . "#100 <br />";  //output to browser
			$result = mysql_db_query($db,$authorTABLEsql,$connection);
			$authorid = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_author SET item = '$itemid', author = '$authorid', is_primary = '1'",$connection);
			echo "INSERT INTO lookup_item_author SET item = '$itemid', author = '$authorid', is_primary = '1'";
		}
	}
	
	if(!empty($secauthor)) {
		for($i=0;$i<count($secauthor);$i++) {
			$authorTABLEsql2 = "INSERT INTO author ";
//			$author_array = explode(", ",$secauthor[$i]["sa"]); //ok to reuse this array all over the place?
			$authorTABLEsql2 .= "SET name = '".$secauthor[$i]["a"]."'";
			if(!empty($secauthor[$i]["dob"])) $authorTABLEsql2 .= ", date_of_birth = '".cleanup($secauthor[$i]["dob"]) . "'#700";
			echo $authorTABLEsql2 . " #secauthor<br />"; //output to browser
			$result = mysql_db_query($db,$authorTABLEsql2,$connection);
			$authorid2 = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_author SET item = '$itemid', author = '$authorid2'",$connection);
		}
	}

	if(!empty($secauthor710)) {
		for($i=0;$i<count($secauthor710);$i++) {
			//var_dump($secauthor710);
			$authorTABLEsql3 = "INSERT INTO author ";
			$authorTABLEsql3 .= "SET corporate_name = '".cleanup($secauthor710[$i])."'";
			echo $authorTABLEsql3 . " #710 <br />"; //output to browser
			$result = mysql_db_query($db,$authorTABLEsql3,$connection);
			$authorid3 = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_author SET item = '$itemid', author = '$authorid3'",$connection);
		}
	}
	//   ----------ADDITIONAL TITLE TABLE----------   //
	if(!empty($uniformtitle)) {
		$additional_titleTABLEsql = "INSERT INTO additional_title SET value = '".cleanup($uniformtitle)."'";
		$additional_titleTABLEsql .= ", item = '$itemid'";
		echo $additional_titleTABLEsql . "<br />"; //output to browser
		$result = mysql_db_query($db,$additional_titleTABLEsql,$connection);
	}
	
	if(!empty($varyingtitle)) {
		$additional_titleTABLEsql2 = "INSERT INTO additional_title SET value = '".cleanup($varyingtitle)."'";
		$additional_titleTABLEsql2 .= ", item = '$itemid'";
		echo $additional_titleTABLEsql2 . "<br />"; //output to browser
		$result = mysql_db_query($db,$additional_titleTABLEsql2,$connection);
	}
	//   ----------URL TABLE----------   //	
	if(!empty($url)) {
		$urlTABLEsql = "INSERT INTO url SET value = '".cleanup($url)."'";
		$urlTABLEsql .= ",item = '$itemid'";
		echo $urlTABLEsql . " #856 <br />"; //output to browser
		$result = mysql_db_query($db,$urlTABLEsql,$connection);
	}
	//   ----------SERIES TABLE----------   //	
	if(!empty($series)) {
		$seriesTABLEsql = "INSERT INTO series SET value = '".cleanup($series)."' #490";
		echo $seriesTABLEsql . "<br />"; //output to browser
		$result = mysql_db_query($db,$seriesTABLEsql,$connection);
		$seriesid = mysql_insert_id();
		$result = mysql_db_query($db,"INSERT INTO lookup_item_series SET item = '$itemid', series = '$seriesid'",$connection);
	}
	
	if(!empty($series2)) {
		$seriesTABLEsql2 = "INSERT INTO series SET value = '".cleanup($series2)."' #440";
		echo $seriesTABLEsql2 . "<br />"; //output to browser
		$result = mysql_db_query($db,$seriesTABLEsql2,$connection);
		$seriesid2 = mysql_insert_id();
		$result = mysql_db_query($db,"INSERT INTO lookup_item_series SET item = '$itemid', series = '$seriesid2'",$connection);
	}
	//   ----------NOTES TABLE----------   //	
	if(!empty($notes1)) {
		$notesTABLEsql = "INSERT INTO notes SET value = '".cleanup($notes1)."'";
		$notesTABLEsql .= ",item = '$itemid'";
		echo $notesTABLEsql . "<br />"; //output to browser
		$result = mysql_db_query($db,$notesTABLEsql,$connection);
	}
	
	if(!empty($notes2)) {
		$notesTABLEsql2 = "INSERT INTO notes SET value = '".cleanup($notes2)."'";
		$notesTABLEsql2 .= ",item = '$itemid'";
		echo $notesTABLEsql2 . "<br />"; //output to browser
		$result = mysql_db_query($db,$notesTABLEsql2,$connection);
	}
	if(!empty($notes3)) {
		$notesTABLEsql3 = "INSERT INTO notes SET value = '".cleanup($notes3)."'";
		$notesTABLEsql3 .= ",item = '$itemid'";
		echo $notesTABLEsql3 . "<br />"; //output to browser
		$result = mysql_db_query($db,$notesTABLEsql3,$connection);
	}
	if(!empty($notes4)) {
		$notesTABLEsql4 = "INSERT INTO notes SET value = '".cleanup($notes4)."'";
		$notesTABLEsql4 .= ",item = '$itemid'";
		echo $notesTABLEsql4 . "<br />"; //output to browser
		$result = mysql_db_query($db,$notesTABLEsql4,$connection);
	}
	if(!empty($notes5)) {
		sort($notes5);
		for($i=0;$i<count($notes5);$i++) {
			$notesTABLEsql5 = "INSERT INTO notes SET value = '".cleanup($notes5[$i])."'";
			$notesTABLEsql5 .= ",item = '$itemid'";
			echo $notesTABLEsql5 . "<br />"; //output to browser
			$result = mysql_db_query($db,$notesTABLEsql5,$connection);
		}
	}
	
	//   ----------SUBJECT TABLE----------   //
	if(!empty($subject600)) {
		for($i=0;$i<count($subject600);$i++) {
			$subjectTABLEsql[$i] = "INSERT INTO subject SET value = '".cleanup($subject600[$i]). "'";
			echo $subjectTABLEsql[$i] . " #600<br />"; //output to browser
			$result = mysql_db_query($db,$subjectTABLEsql[$i],$connection);
			$subjectid = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_subject SET item = '$itemid', subject = '$subjectid'",$connection);	
		}
	}
	if(!empty($subject610)) {
		for($i=0;$i<count($subject610);$i++) {
			$subjectTABLEsql2[$i] = "INSERT INTO subject SET value = '".cleanup($subject610[$i]). "'";
			echo $subjectTABLEsql2[$i] . " #610<br />"; //output to browser
			$result = mysql_db_query($db,$subjectTABLEsql2[$i],$connection);
			$subjectid2 = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_subject SET item = '$itemid', subject = '$subjectid2'",$connection);	
		}
	}
	if(!empty($subject650)) {
		for($i=0;$i<count($subject650);$i++) {
			$subjectTABLEsql3[$i] = "INSERT INTO subject SET value = '".cleanup($subject650[$i]). "'";
			echo $subjectTABLEsql3[$i] . " #650<br />"; //output to browser
			$result = mysql_db_query($db,$subjectTABLEsql3[$i],$connection);
			$subjectid3 = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_subject SET item = '$itemid', subject = '$subjectid3'",$connection);	
		}
	}
	if(!empty($subject651)) {
		for($i=0;$i<count($subject651);$i++) {
			$subjectTABLEsql4[$i] = "INSERT INTO subject SET value = '".cleanup($subject651[$i]). "'";
			echo $subjectTABLEsql4[$i] . " #651<br />"; //output to browser
			$result = mysql_db_query($db,$subjectTABLEsql4[$i],$connection);
			$subjectid4 = mysql_insert_id();
			$result = mysql_db_query($db,"INSERT INTO lookup_item_subject SET item = '$itemid', subject = '$subjectid4'",$connection);	
		}
	}
	//   ----------HOLDINGS TABLE----------   //	
	if(!empty($callnumber)) {
		$holdingsTABLEsql = "INSERT INTO holdings SET call_number = '".cleanup($callnumber)."'";
		$holdingsTABLEsql .= ",item = '$itemid'";
		echo $holdingsTABLEsql ."<br />"; //output to browser
		$result = mysql_db_query($db,$holdingsTABLEsql,$connection);
	}
		
	unset(
		$bibid,
		$isbn,
		$issn,
		$callnumber,
		$author,
		$publocation,
		$publisher,
		$pubdate,
		$description,
		$uniformtitle,
		$title,
		$title2,
		$responsibility,
		$series,
		$edition,
		$notes1,
		$notes2,
		$notes3,
		$notes4,
		$notes5,
		$subject600,
		$subject610,
		$subject650,
		$subject651,
		$secauthor,
		$secauthor710,
		$url,
		$toc,
		$varyingtitle,
		$contents,
		$series2,
		$subjects,
		$subjectTABLEsql,
		$subjectTABLEsql2,
		$subjectTABLEsql3,
		$subjectTABLEsql4,
		$publisherTABLEsql,
		$toc,
		$contents_array,
		$author_array,
		$last_updated,
		$result,
		$itemid,
		$publisherid
		);
		
		echo "<p />"; //output to browser
		
	//   -----End the MySQL-----    //
	
} //end while

//   -----FUNCTIONS-----    //
function cleanup ($string) {
	$string = trim(mysql_real_escape_string($string),"/:-; ,.[]");
	return $string;
}	
	
//   -----END FUNCTIONS-----    //
	
echo "TOTAL RECORDS:  ". $count;

//   -----Timer-----    //
$mtime = microtime(); 
$mtime = explode(" ", $mtime); 
$mtime = $mtime[1] + $mtime[0]; 
$endtime = $mtime; 
$totaltime = ($endtime - $starttime); 
echo '<br />This process took ' .$totaltime. ' seconds.';
//   -----Timer-----    //
?>
