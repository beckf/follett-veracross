<?php

	// Version 11 (Updated 05/15/14)
	//
	// Call the script with two varibales in the URL.  Veracross Student ID (id) and the students last name in 
	// MD5 hash (key).
	// 
	// Student John Smith with VC person id 12345
	// Example: http://host/index.php?id=12345&key=e95f770ac4fb91ac2e4873e4b2dfc0e6
	//
	// To get the MD5 hash for the last name execute the following command on any host with php installed:
	// /usr/bin/php -r 'echo md5( "Smith" );'
	//
	// To match classes in VC, setup Follett: 
	// ->to use the VC Course Description as the course name.
	// ->to use the VC Class ID as the section.
	//
	//
	// User-Defined Variables
	// 
	// To enable this page set to true otherwise set to false to redirect to the redirectURL.
	$pageEnable		= true;
	// When the page is disabled, redirect to this URL.
	$redirectURL		= "http://www.bkstr.com/webapp/wcs/stores/servlet/...";
	// Veracross API Login Information
	$vcapiusername		= "vc.api.user";
	$vcapipassword		= "vc.api.pass";
	$vcschoolshortname	= "vc";
	// Follett Bootstore API URL
	$follett_url		= "https://www.bkstr.com/webapp/wcs/stores/servlet/OnlineRegistrationServlet";
	// Follett Client ID Number
	$follett_merfnbr	= "1234";
	// Follett term ID's for various semesters.
	$follett_termid_ALL	= "FA18";
	$follett_termid_S2	= "FA18";
	$follett_termid_SUM	= "SP18";
	// If a grading period isn't specified, we will use the grading period based on the current date.  
	// When should we assume it is the 1st Semester? Use the Format "1 July"
	$semesterOneBegin	= "1 July";
	$semesterOneEnd		= "9 December";
	// Class Names to not include in Follett API.  Classes will still show in schedule.
	$follett_exceptions	= array("*Homeroom", "Advisory", "Study Hall", "*JV*", "*Varsity*");
	// Link to full list of books
	$bookList		= "booklist.pdf";

// Begin 

		if ( $pageEnable == false ) {
			header('Location: ' . $redirectURL);
		}
		
		// Functions
		include_once('functions.php');

		// Check that a grading period was specified.  If not set the grading period based on date.  
		if(!$_GET["grading_period"]) {
			
			$baseURL = curPageURL();
				if(strtotime($semesterOneBegin) <= time() && strtotime($semesterOneEnd) >= time()) {
					header('Location: ' . $baseURL . '&grading_period=ALL');
				} else {
					header('Location: ' . $baseURL . '&grading_period=S2');
				}
			
		} else {
		
		// Check that an ID was passed in the URL
		// If so, does it have the proper key?
		if($_GET["id"]) {
			
			$baseURL = curPageURL();
			
			// Move person id into a local variable
			$personid		= $_GET["id"];
			
			$person_bio		= simplexml_load_file('https://' . $vcapiusername . ':' . $vcapipassword . '@api.veracross.com/' . $vcschoolshortname . '/v2/students/' . $personid . '.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
				
			$lastname_key 	= md5($person_bio->last_name);
			
			$url_key		= $_GET["key"];
				
			if($lastname_key == $url_key) {
			
			$person_enrollments	= simplexml_load_file('https://' . $vcapiusername . ':' . $vcapipassword . '@api.veracross.com/' . $vcschoolshortname . '/v2/enrollments.xml?student=' . $personid, 'SimpleXMLElement', LIBXML_NOCDATA);
				
			// Put all classes in an array
			$classes = array();
			foreach ($person_enrollments->children() as $child)
			  {
			  $classes[] = (string) $child->class_fk;
			  }
			//print_r($classes);
			$class_schedule = array();
			
			//Get class info for each class
			
			foreach ($classes as $key => $val) {
				$class_info = simplexml_load_file('https://' . $vcapiusername . ':' . $vcapipassword . '@api.veracross.com/' . $vcschoolshortname . '/v2/classes/' . $val . '.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
			
				if ( ($class_info) && ($class_info->course_type == "Academic")) {
					
					$class_schedule[$key] = array();
					
					$class_schedule[$key]['internalid'] = $val;
					
					if($class_info->description){
						$class_schedule[$key]['course'] = $class_info->description;
					}
					if($class_info->class_id){
						$class_schedule[$key]['section'] = $class_info->class_id;
					}
					if($class_info->teacher_full_name){
						$class_schedule[$key]['teacher'] = $class_info->teacher_full_name;
					}
					if($class_info->meeting_times->meeting_time->block_abbreviation){
						$class_schedule[$key]['period'] = $class_info->meeting_times->meeting_time->block_abbreviation;
					}
					if($class_info->meeting_times->meeting_time->grading_period){
						$class_schedule[$key]['grading_period'] = $class_info->meeting_times->meeting_time->grading_period;
					}
					if($class_info->meeting_times->meeting_time->room){
						$class_schedule[$key]['room'] = $class_info->meeting_times->meeting_time->room;
					}
					if($class_info->school_year){
						$class_schedule[$key]['school_year'] = $class_info->school_year;
						if(!isset($school_year)) {
							$school_year = (int)$class_info->school_year;
							$school_year2 = $school_year + 1;
						} 
					}
				}
			
			}
			//print_r($class_schedule);
			// Set the correct semester
			if($_GET["grading_period"] == "ALL") {
				$grading_period = "ALL";
			} elseif ($_GET["grading_period"] == "S2") {
				$grading_period = "Semester 2";
			} elseif ($_GET["grading_period"] == "SUM") {
				$grading_period = "Summer";
			} else {
				$grading_period = "ALL";
			}
			
			// Setup parent table
			echo("
				<center>
				<table class=\"mainContent\" width=\"700\" border=\"0\">
					<tr>
						<td bgcolor=\"#FFFFFF\">
						<div id=\"tabs\">
							<div class=\"center\">
								<h3 style=\"color: #fff; margin: 5px;\">" . $school_year . " - " . $school_year2 . " Follett Book List</h3>
							</div>
						</div>");
			
			if(!$_GET["schedule"]) {
			
			// Setup nested table
			echo("<table width=\"700\" border=\"0\" cellspacing=\"2\" cellpadding=\"2\">");
			echo("<tr>");
			echo("<td width=\"202\" valign=\"top\" bgcolor=\"#FFFFFF\"><img src=\"https://photos.veracross.com/" . $vcschoolshortname . "/" . $personid . ".jpg\" id=\"profilepic\"/></td>");
			echo("<td width=\"498\" valign=\"top\" bgcolor=\"#FFFFFF\">");
			echo('
			');
			echo('<center><h3>Courses in Grading Period: ' . $grading_period . '<h3>
				 <form name="semesterChange">
					<select name="jumpbox"
						 OnChange="location.href=semesterChange.jumpbox.options[selectedIndex].value" style="option_select">
							<option selected>Select Grading Period...
							<option value="index.php?id=' . $personid . '&key=' . $url_key . '&grading_period=ALL">Semester 1 / ALL
							<option value="index.php?id=' . $personid . '&key=' . $url_key . '&grading_period=S2">Semester 2
							<option value="index.php?id=' . $personid . '&key=' . $url_key . '&grading_period=SUM">Summer
					</select>
					</form>
				<br />
				<h3>The book list for ' . $person_bio->first_name . ' ' . $person_bio->last_name . ' has now been generated.</h3>
				Follow the two steps below to get a list of your textbooks with pricing from Follett.  A complete list of all book ISBN numbers can be found <a href="'
				. $bookList . '" target="_blank">here</a>.  
				<br /><br />It is recommended that you print the schedule below before proceeding to Follett.  Having the schedule available will help you understand which courses are listed.
				<br /><br />');
			
			echo("<strong>Step 1:</strong>  <input type=button onClick=\"javascript:window.print()\" value='Print Schedule'");
			
			echo("<br /><br />");
			
			// Print POST form for Follett
			echo('<form action="' . $follett_url . '" METHOD="POST">');
			echo('<input type="hidden" name="merfnbr" value="' . $follett_merfnbr . '">');
			if($grading_period == "ALL") {
				echo('<input type="hidden" name="termDir" value="' . $follett_termid_ALL . '">');
			} elseif ($grading_period == "Semester 2") {
				echo('<input type="hidden" name="termDir" value="' . $follett_termid_S2 . '">');
			} elseif ($grading_period == "Summer") {
				echo('<input type="hidden" name="termDir" value="' . $follett_termid_SUM . '">');
			}	
			
			$itemCount	= 1;
			// Add hidden inputs for Follett form.
			if($grading_period == "ALL") {
				foreach ($class_schedule as $key => $val) {
					if( ($val['grading_period'] == $grading_period) || ($val['grading_period'] == "Semester 1")) {
										$section = "section" . $itemCount;
										echo("<input type=\"hidden\" name=\"" . $section . "\" value=\"" . $val['section'] . "\">");
										$itemCount++;
					}
				}
				
			} elseif ($grading_period == "Semester 2") {
				foreach ($class_schedule as $key => $val) {
					if($val['grading_period'] == "Semester 2") {
										$section = "section" . $itemCount;
										echo("<input type=\"hidden\" name=\"" . $section . "\" value=\"" . $val['section'] . "\">");
										$itemCount++;
					}
				}
			} elseif ($grading_period == "Summer") {
				foreach ($class_schedule as $key => $val) {
					if($val['grading_period'] == "Summer") {
										$section = "section" . $itemCount;
										echo("<input type=\"hidden\" name=\"" . $section . "\" value=\"" . $val['section'] . "\">");
										$itemCount++;
					}
				}
			}
			
			echo("<strong>Step 2:</strong>  <input type=\"submit\" value=\"List My Textbooks\" name=\"list_books_submit\"></form>");
		
			echo("</center></td></tr></table>");
			
		// Print Schedule
			echo("<center>
					<br />");
			echo("<h3>Class Schedule</h3>");
			//echo("<br />");
			
			$table_row = 1;
		
			echo('<table width="700" border="0" cellspacing="0" cellpadding="0">
					<tr class="tableHeader">
					<td><strong class="white">Course</strong></td>
					<td><strong class="white">Section</strong></td>
					<td><strong class="white">Teacher</strong></td>
					<td><strong class="white">Period</strong></td>
					<td><strong class="white">Room</strong></td>
					<td><strong class="white">Grading Period</strong></td>
					</tr>');
			
			if($grading_period == "ALL") {
				
				foreach ($class_schedule as $key => $val) {
			
								if ($table_row % 2 != 0) {
									$rowStyle = "tableRow"; 
								} else {
									$rowStyle = "tableRowAlt";
								}
								
								echo('<tr class="' . $rowStyle . '">
									<td><a href="https://portals.veracross.com/da/course/' . $val['internalid'] . '/website" target="_blank">' . $val['course'] . '</a></td>
									<td>' . $val['section'] . '</td>
									<td>' . $val['teacher'] . '</td>
									<td>' . $val['period'] . '</td>
									<td>' . $val['room'] . '</td>
									<td>' . $val['grading_period'] . '</td>
									</tr>');
								
								$table_row++;
				}
			} elseif ($grading_period == "Semester 2") {
					
					foreach ($class_schedule as $key => $val) {
						
						if($val['grading_period'] == "Semester 2") {
			
								if ($table_row % 2 != 0) {
									$rowStyle = "tableRow"; 
								} else {
									$rowStyle = "tableRowAlt";
								}
								
								echo('<tr class="' . $rowStyle . '">
									<td><a href="https://portals.veracross.com/da/course/' . $val['internalid'] . '/website" target="_blank">' . $val['course'] . '</a></td>
									<td>' . $val['section'] . '</td>
									<td>' . $val['teacher'] . '</td>
									<td>' . $val['period'] . '</td>
									<td>' . $val['room'] . '</td>
									<td>' . $val['grading_period'] . '</td>
									</tr>');
								
								$table_row++;
						}
					}
			} elseif ($grading_period == "Summer") {
					
					foreach ($class_schedule as $key => $val) {
						
						if($val['grading_period'] == "Summer") {
			
								if ($table_row % 2 != 0) {
									$rowStyle = "tableRow"; 
								} else {
									$rowStyle = "tableRowAlt";
								}
								
								echo('<tr class="' . $rowStyle . '">
									<td><a href="https://portals.veracross.com/da/course/' . $val['internalid'] . '/website" target="_blank">' . $val['course'] . '</a></td>
									<td>' . $val['section'] . '</td>
									<td>' . $val['teacher'] . '</td>
									<td>' . $val['period'] . '</td>
									<td>' . $val['room'] . '</td>
									<td>' . $val['grading_period'] . '</td>
									</tr>');
								
								$table_row++;
						}
					}
			}
			
			// Close if not schedule
			}
			
			if($_GET["schedule"]) {
				echo("<center>
					<br />");
				echo("<h1>Class Schedule</h1>");
				echo("<br />");
				echo("<h3>" . $person_bio->first_name . ' ' . $person_bio->last_name . "</h3>");
					 
				echo("<br /><br /><input type=\"button\" value=\"Back\" onclick=\"window.history.back()\"><br /><br />");
				
				$table_row = 1;
			
				echo('<table width="700" border="0" cellspacing="2" cellpadding="2">
						<tr class="tableHeader">
						<td><strong class="white">Course</strong></td>
						<td><strong class="white">Section</strong></td>
						<td><strong class="white">Teacher</strong></td>
						<td><strong class="white">Period</strong></td>
						<td><strong class="white">Room</strong></td>
						<td><strong class="white">Grading Period</strong></td>
						</tr>');
				
				foreach ($class_schedule as $key => $val) {
					
					if ($table_row % 2 != 0) {
						$rowStyle = "tableRow"; 
					} else {
						$rowStyle = "tableRowAlt";
					}
					
					echo('<tr class="' . $rowStyle . '">
						<td>' . $val['course'] . '</td>
						<td>' . $val['section'] . '</td>
						<td>' . $val['teacher'] . '</td>
						<td>' . $val['period'] . '</td>
						<td>' . $val['room'] . '</td>
						<td>' . $val['grading_period'] . '</td>
						</tr>');
					
					$table_row++;
				}
			
			// Close if Schedule
			}
			
			// Close parent table
			echo("			</center>
						</td>
					</tr>
				</table>");
			
			} else {
				// If key is invalid then tell the visitor
				echo("<span>Invalid or no security key specified!</span>");
			}
		
		} else {
			echo("<span>No person specified!</span>");
		}
		
		//Close If no Grading Period
		}
?>

</center>
