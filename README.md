# Follett Bookstore Veracross Integration

This is a simple PHP script that will help populate the Follett 
Bookstore with Veracross class schedules.

## Setup
1) You will need to setup a few variables in process.php before you can begin.

```
$pageEnable: Enables the integration.  If disabled, just redirects to the redirectURL.
$vcapiusername: Username for the Veracross API
$vcipassword: Password for the Veracross API
$vcschoolshortname: The short name for your school.  Same as http://axiom.veracross.com/XX
$follet_url: No need to change this.
$follett_mernbr: Your Follett Client ID Number
$follett_termid_ALL: The ID provided by follett for your Fall/All semester term.
$follett_termid_S2:  The ID Provided by follett for your Spring/Second semester term.
$follett_termid_SUM: The ID provided by follett for you Summer term.
$semesterOneBegin & $semesterOneEnd: Date range for your first semester.
$follett_exceptions: Veracross Class Names to ignore in the schedule when creating the book list.
$booklist: Link to file that may contain a list of all your books.

```

2) Once this is complete, you will need to edit index.php so that the full path to the process.php script is 
correct.  See makeRequest() function.

3) Finally, request Veracross add a link to this page in your portal.  The link should be formatted as:
```
https://path.to.fqdn/index.php?id=veracross_student_id&key=MD5Hash_of_student_lastname
```
To check the MD5 Hash use the command: /usr/bin/php -r 'echo md5( "Smith" );'

