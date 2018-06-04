<?php
	// If a grading period isn't specified, we will use the grading period based on the current date.  
	// When should we assume it is the 1st Semester? Use the Format "1 July"
	$semesterOneBegin	= "1 July";
	$semesterOneEnd		= "9 December";
	
	// Functions
	include_once('functions.php');
		
	if(!$_GET["grading_period"]) {
			
			$baseURL = curPageURL();
				if(strtotime($semesterOneBegin) <= time() && strtotime($semesterOneEnd) >= time()) {
					header('Location: ' . $baseURL . '&grading_period=ALL');
				} else {
					header('Location: ' . $baseURL . '&grading_period=S2');
				}
			
	}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Veracross Schedule and Follett Book List</title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<link href="style.css" rel="stylesheet" type="text/css" />

<!-- AJAX Scripts -->
<script type="text/javascript">
$.urlParam = function(name){
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return results[1] || 0;
    }
}

function makeRequest(url){
	$('#loader').show();
	$.get(url, function(data) {
		$('#loader').hide();
		$('#resultDiv').html(data);
	}).fail(function() {
			alert( 'Error occured while requesting the page.' );
			$('#loader').hide();
	});
}

makeRequest('https://fqdn.domain.url.tld/follett/process.php?id=' + $.urlParam('id') + '&key=' + $.urlParam('key') + '&grading_period=' + $.urlParam('grading_period'));
 
</script>

</head>

<body>

<div id="loader">
    <div id="floatingCirclesG">
            <div class="f_circleG" id="frotateG_01">
            </div>
            <div class="f_circleG" id="frotateG_02">
            </div>
            <div class="f_circleG" id="frotateG_03">
            </div>
            <div class="f_circleG" id="frotateG_04">
            </div>
            <div class="f_circleG" id="frotateG_05">
            </div>
            <div class="f_circleG" id="frotateG_06">
            </div>
            <div class="f_circleG" id="frotateG_07">
            </div>
            <div class="f_circleG" id="frotateG_08">
            </div>
    </div>
    <p class="loaderText">gathering enrollments</p>
</div>

<div id="resultDiv"></div>

</body>
</html>
