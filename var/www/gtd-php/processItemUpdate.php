<?php
//INCLUDES
include_once('header.php');
include_once('config.php');

//CONNECT TO DATABASE
$connection = mysql_connect($host, $user, $pass) or die ("Unable to connect!");
mysql_select_db($db) or die ("Unable to select database!");

//GET URL VARIABLES
$itemId = (int) $_GET['itemId'];
$contextId=(int) $_POST['contextId'];
$type=$_POST['type']{0};
$referrer=$_POST['referrer']{0};
$projectId=(int) $_GET['projectId'];
$timeId=(int) $_POST['timeId'];

//GET FORM VARIABLES
$completedNas = $_POST['completedNas'];

//SQL CODE

if(isset($completedNas)){
	$today=strtotime("now");
	$date=date('Y-m-d');
        foreach ($completedNas as $completedNa) {
                //echo "Updating item: ";
                //echo $completedNa.'<br>';

//test to see if action is repeating
		$testquery = "SELECT itemattributes.repeat FROM itemattributes WHERE itemattributes.itemId='$completedNa'";
		$testresult = mysql_query($testquery) or die ("Error in query");
		$testrow = mysql_fetch_assoc($testresult);	

//if repeating, copy result row to new row (new action) with updated due date

		if ($testrow['repeat']!=0) {
			
			$nextdue=strtotime("+".$testrow['repeat']."day");
			$nextduedate=gmdate("Y-m-d", $nextdue);

			//retrieve item details
			$copyquery = "SELECT items.title, items.description FROM items WHERE items.itemId='$completedNa'";
			$copyresult = mysql_query($copyquery) or die ("Error in query");
			$copyitem = mysql_fetch_assoc($copyresult);

			//copy data to items table with new id
			$addquery = "INSERT INTO `items` (title,description) VALUES ('".addslashes(stripslashes($copyitem['title']))."','".addslashes(stripslashes($copyitem['description']))."')";
			$addresult = mysql_query($addquery) or die ("Error in query");
			$newitemId = mysql_insert_id();

			//retrieve item attributes
			$copyquery = "SELECT itemattributes.projectId, itemattributes.contextId, itemattributes.timeframeId,
					itemattributes.deadline, itemattributes.repeat, itemattributes.suppress, itemattributes.suppressUntil
					FROM itemattributes WHERE itemattributes.itemId='$completedNa'";
			$copyresult = mysql_query($copyquery) or die ("Error in query");
			$copyattributes = mysql_fetch_assoc($copyresult);

			//copy data to itemattributes table with newid and new due date
			$addquery = "INSERT INTO `itemattributes` (itemId,projectId,contextId,timeframeId,deadline,`repeat`,suppress,suppressUntil) VALUES ('$newitemId','".$copyattributes['projectId']."','".$copyattributes['contextId']."','".$copyattributes['timeframeId']."','$nextduedate','".$copyattributes['repeat']."','".$copyattributes['suppress']."','".$copyattributes['suppressUntil']."')";
			$addresult = mysql_query($addquery) or die ("Error in query:");

			//add newid to itemstatus table
			$addquery = "INSERT INTO `itemstatus` (itemId,dateCreated) VALUES ('$newitemId','$date')";
			$addresult = mysql_query($addquery) or die ("Error in query");

			//test to see if item is a nextaction
			$copyquery = "SELECT projectId, nextaction FROM nextactions WHERE nextaction='$completedNa'";
			$copyresult = mysql_query($copyquery) or die ("Error in query");
			$nextactiontest = mysql_fetch_assoc($copyresult);

			//update nextactions list with new itemId if nextaction (may want to set user option for this later)
			if ($nextactiontest['nextaction']==$completedNa) {
				$query = "INSERT INTO nextactions (projectId,nextAction) VALUES ('".$copyattributes['projectId']."','$newitemId')
       	 	        		ON DUPLICATE KEY UPDATE nextaction='$newitemId'";
        			$result = mysql_query($query) or die ("Error in query");
				}
               		}

		//in either case, set original row completed
                $query= "UPDATE itemstatus SET dateCompleted='$date' where itemId='$completedNa'";
                $result = mysql_query($query) or die ("Error in query");

		//remove original row from nextActions list
                $query= "DELETE FROM nextactions WHERE nextAction='$completedNa'";
                $result = mysql_query($query) or die ("Error in query");
        	}
	}

if ($referrer=="i") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=listItems.php?type='.$type.'&contextId='.$contextId.'&timeId='.$timeId.'">';
	}

elseif ($referrer=="p") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=projectReport.php?projectId='.$projectId.'">';
	}

elseif ($referrer=="c") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=reportContext.php">';
	}

elseif ($referrer=="t") {
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=tickler.php">';
}
else{
	echo '<META HTTP-EQUIV="Refresh" CONTENT="1; url=reportContext.php">';
	}

mysql_close($connection);
include_once('footer.php');
?>
