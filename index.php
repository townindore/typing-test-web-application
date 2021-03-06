<?php
include("session.php");
?>
<html>

<head>
<link rel="stylesheet" href="css/style.css" media="screen" type="text/css" />
<title>CSC428 - Typing Test for Various Input Methods</title>

<SCRIPT LANGUAGE="JavaScript">

// session control of the participant id
var p_id = <?php echo "\"".$_SESSION["participant"]."\";"; ?>

//Holds whether or not we have already started the first typing test or now
//	True = The test has already started
//	False = The test hasn't started yet
var hasStarted = false;

//strToTest is an array object that holds various strings to be used as the base typing test
//	- If you update the array, be sure to update the intToTestCnt with the number of ACTIVE testing strings

var intToTestCnt = 4;

var strToTest = new Array(

<?php
//read from file to get all the paragraphs

for ($x=1; $x<=12; $x++) 
{
	$myfile = fopen("paragraphs/".$x.".txt", "r") or die("Unable to open file!");
	echo "\"".fread($myfile,filesize("paragraphs/".$x.".txt"))."\"";
	if($x!=12)echo ",";
	fclose($myfile);
}

?>

)

var strToTestType = "";

var checkStatusInt;

//General functions to allow for left and right trimming / selection of a string
function Left(str, n){
	if (n <= 0)
	    return "";
	else if (n > String(str).length)
	    return str;
	else
	    return String(str).substring(0,n);
}


function Right(str, n){
    if (n <= 0)
       return "";
    else if (n > String(str).length)
       return str;
    else {
       var iLen = String(str).length;
       return String(str).substring(iLen, iLen - n);
    }
}


function SwitchParagraph(value){
	
	strToTestType = strToTest[value-1];
	
	document.JobOp.given.value = strToTestType;
	
}

function resetTestUI(){
	
	var tProg = document.getElementById("stProg");
	
	document.JobOp.reset.style.display="none";
	document.JobOp.start.style.display="block";
	
	//Enable the area where the user types the test input
	document.JobOp.typed.disabled=false;
	document.JobOp.typed.value="";
	
	// when test is begun, the user can no longer change paragraph
	document.getElementById("paragraph_id").disabled = false;
	
	//reset the status bar
	document.getElementById("stat_wpm").innerText = "Not Started";
	document.getElementById("stat_timeleft").innerText = "0.00";
	
	//reset progress bar
	tProg.width="0%";
	
	
}


function resetTest( ){
	
	if(p_id=="T"){
	
		alert("In training session, test data is NOT saved.");
		
		resetTestUI();
	
	}else{
	
	if (confirm("SAVE DATA for this test and RESET?\n(\"OK\" to SAVE & RESET, \"Cancel\" to RESET)")) {
		
		 var paragraph_id = document.getElementById("paragraph_id").value;
		 var wpm = document.getElementById("stat_wpm").innerText.replace(" WPM","");
		 var total_time = document.getElementById("stat_timeleft").innerText.replace(" sec.","");;
		 var user_input = document.JobOp.typed.value;
			
			
			if(user_input==""){
			
				alert("Test data NOT saved because user input is empty.");
				resetTestUI();
				
			}else{
			
				var xmlhttp;
				
				if (window.XMLHttpRequest)
				  {// code for IE7+, Firefox, Chrome, Opera, Safari
				  xmlhttp=new XMLHttpRequest();
				  }
				else
				  {// code for IE6, IE5
				  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
				  }
				  
				xmlhttp.onreadystatechange=function()
				  {
				  if (xmlhttp.readyState==4 && xmlhttp.status==200)
					{
					alert("Test data has been saved!");
					resetTestUI();
					}
				  }
				  
				xmlhttp.open("POST","store_data.php",true);
				xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				
				var send_value = "paragraph_id="+paragraph_id+"&total_time="+total_time+"&wpm="+wpm+"&user_input="+user_input;
				
				xmlhttp.send(send_value);
				
				}
		
		} else {
			
			//reload anyway
			resetTestUI();
		}
	
	}

}


//beginTest Function/Sub initializes the test and starts the timers to determine the WPM and Accuracy
function beginTest()
{	
	
	//We're starting the test, so set the variable to true
	hasStarted = true;
	
	//Generate a date value for the current time as a baseline
	day = new Date();
	
	//Count the number of valid words in the testing baseline string
	cnt = strToTestType.split(" ").length + 1;
	
	//Set the total word count to the number of valid words that need to be typed
	word = cnt;
	
	//Set the exact time of day that the testing has started
	startType = day.getTime();
	
	calcStat();
	
	//Initialize the testing objects by setting the values of the buttons, what to type, and what is typed
	document.JobOp.start.value = "-- Typing Test Started --";
	document.JobOp.start.disabled = true;
	document.JobOp.given.value = strToTestType;
	
	//Enable the area where the user types the test input
	document.JobOp.typed.disabled=false;
	
	document.JobOp.typed.value = "";
	
	// when test is begun, the user can no longer change paragraph
	document.getElementById("paragraph_id").disabled = true;
	
	//Apply focus to the text box the user will type the test into
	document.JobOp.typed.focus();
	document.JobOp.typed.select();
}

//User to deter from Copy and Paste, also acting as a testing protection system
//	Is fired when the user attempts to click or apply focus to the text box containing what needs to be typed
function deterCPProtect()
{
	document.JobOp.typed.focus();
}

//The final call to end the test -- used when the user has completed their assignment
//	This function/sub is responsible for calculating the accuracy, and setting post-test variables
function endTest()
{

	//Clear the timer that tracks the progress of the test, since it's complete
	clearTimeout(checkStatusInt);
	
	//Initialize an object with the current date/time so we can calculate the difference	
	eDay = new Date();
	endType = eDay.getTime();
	totalTime = ((endType - startType) / 1000)
	
	//Calculate the typing speed by taking the number of valid words typed by the total time taken and multiplying it by one minute in seconds (60)
	//***** 1A *************************************************************************************************************************** 1A *****
	//We also want to disregard if they used a double-space after a period, if we didn't then it would throw everything after the space off
	//Since we are using the space as the seperator for words; it's the difference between "Hey.  This is me." versus "Hey. This is me." and
	//Having the last three words reporting as wrong/errors due to the double space after the first period, see?
	//*********************************************************************************************************************************************
	wpmType = Math.round(((document.JobOp.typed.value.replace(/  /g, " ").split(" ").length)/totalTime) * 60)
	
	//Set the start test button label and enabled state
	document.JobOp.start.value = ">> Start Typing Test <<";
	document.JobOp.start.disabled = false;
	
	//Flip the starting and stopping buttons around since the test is complete
	document.JobOp.stop.style.display="none";
	document.JobOp.reset.style.display="block";
	
	//Declare an array of valid words for what NEEDED to be typed and what WAS typed
	//Again, refer to the above statement on removing the double spaces globally (1A)	
	var typedValues = document.JobOp.typed.value.replace(/  /g, " ");
	var neededValues = Left(document.JobOp.given.value, typedValues.length).replace(/  /g, " ").split(" ");
	typedValues = typedValues.split(" ");
		
	//Disable the area where the user types the test input
	document.JobOp.typed.disabled=true;
	
	//Declare variable references to various statistical layers
	var tErr = document.getElementById("stat_errors");
	var tscore = document.getElementById("stat_score");
	var tStat = document.getElementById("stat_wpm");
	var tTT = document.getElementById("stat_timeleft");
	
	var tArea = document.getElementById("TypeArea");
	var aArea = document.getElementById("AfterAction");
	var eArea = document.getElementById("expectedArea");
		
	//Initialize the counting variables for the good valid words and the bad valid words
	var goodWords = 0;
	var badWords = -1;
	
	//Declare a variable to hold the error words we found and also a detailed after action report
	var errWords = "";
	var aftReport = "<b>Detailed Summary:</b><br><font color=\"DarkGreen\">";
	
	//Loop through the valid words that were possible (those in the test baseline of needing to be typed)
	var str;
	var i = 0;
	for (var i = 0; i < word; i++)
	{
		//If there is a word the user typed that is in the spot of the expected word, process it
		if (typedValues.length > i)
		{
			//Declare the word we expect, and the word we recieved
			var neededWord = neededValues[i];
			var typedWord = typedValues[i];
			
			//Determine if the user typed the correct word or incorrect
			if (typedWord != neededWord)
			{
				//They typed it incorrectly, so increment the bad words counter
				badWords = badWords + 1;
				errWords += typedWord + " = " + neededWord + "\n";
				aftReport += "<font color=\"Red\"><u>" + neededWord + "</u></font> ";
			}
			else
			{
				//They typed it correctly, so increment the good words counter
				goodWords = goodWords + 1;
				aftReport += neededWord + " ";
			}
		}
		else
		{
			//They didn't even type this word, so increment the bad words counter
			//Update: We don't want to apply this penalty because they may have chosen to end the test
			//		  and we only want to track what they DID type and score off of it.
			//badWords = badWords + 1;
		}	
	}
	
	//Finalize the after action report variable with the typing summary at the beginning (now that we have the final good and bad word counts)
	aftReport += "</font>";
	aftReport = "<b>Typing Summary:</b><br>You typed " + (document.JobOp.typed.value.replace(/  /g, " ").split(" ").length) + " words in " + totalTime + " seconds, a speed of about " + wpmType + " words per minute.\n\nYou also had " + badWords + " errors, and " + goodWords + " correct words, giving scoring of " + ((goodWords / (goodWords+badWords)) * 100).toFixed(2) + "%.<br><br>" + aftReport;
	
	//Set the statistical label variables with what we found (errors, words per minute, time taken, etc)
	tErr.innerText = badWords + " Errors";
	tStat.innerText = (wpmType-badWords) + " WPM / " + wpmType + " WPM";
	tTT.innerText = totalTime.toFixed(2) + " sec. elapsed";
	
	//Calculate the accuracy score based on good words typed versus total expected words -- and only show the percentage as ###.##
	tscore.innerText = ((goodWords / (goodWords+badWords)) * 100).toFixed(2) + "%";
	
	//Flip the display of the typing area and the expected area with the after action display area
	aArea.style.display = "block";
	tArea.style.display = "none";
	eArea.style.display = "none";
	
	//Set the after action details report to the summary as we found; and in case there are more words found than typed
	//Set the undefined areas of the report to a space, otherwise we may get un-needed word holders
	aArea.innerHTML = aftReport.replace(/undefined/g, " ");
	
	//Notify the user of their testing status via a JavaScript Alert
	//Update: There isn't any need in showing this popup now that we are hiding the typing area and showing a scoring area
	//alert("You typed " + (document.JobOp.typed.value.split(" ").length) + " words in " + totalTime + " seconds, a speed of about " + wpmType + " words per minute.\n\nYou also had " + badWords + " errors, and " + goodWords + " correct words, giving scoring of " + ((goodWords / (goodWords+badWords)) * 100).toFixed(2) + "%.");
}

//calcStat is a function called as the user types to dynamically update the statistical information
function calcStat()
{
//If something goes wrong, we don't want to cancel the test -- so fallback error proection (in a way, just standard error handling)
try {
	//Reset the timer to fire the statistical update function again in 250ms
	//We do this here so that if the test has ended (below) we can cancel and stop it
	checkStatusInt=setTimeout('calcStat();',5);
	
	//Declare reference variables to the statistical information labels
	var tStat = document.getElementById("stat_wpm");
	var tTT = document.getElementById("stat_timeleft");
	
	var tProg = document.getElementById("stProg");
	
	var tArea = document.getElementById("TypeArea");
	var aArea = document.getElementById("AfterAction");
	var eArea = document.getElementById("expectedArea");
			
	//Refer to 1A (above) for details on why we are removing the double space
	var thisTyped = document.JobOp.typed.value.replace(/  /g, " ");
	
	//Create a temp variable with the current time of day to calculate the WPM
	eDay = new Date();
	endType = eDay.getTime();
	totalTime = ((endType - startType) / 1000)

	//Calculate the typing speed by taking the number of valid words typed by the total time taken and multiplying it by one minute in seconds (60)
	wpmType = Math.round(((thisTyped.split(" ").length)/totalTime) * 60)

	//Set the words per minute variable on the statistical information block
	tStat.innerText=wpmType + " WPM";
	
	//The test has started apparantly, so disable the stop button
	document.JobOp.stop.disabled = false;
		
	//Flip the stop and start button display status
	document.JobOp.stop.style.display="block";
	document.JobOp.start.style.display="none";
	
	//Calculate and show the time taken to reach this point of the test and also the remaining time left in the test
	//Colorize it based on the time left (red if less than 5 seconds, orange if less than 15)
	<!--CSC428 Comment this whole section out, it calculates the remaining time***********************************************************************
	if (Number(60-totalTime) < 5)
	{
		tTT.innerHTML="<font color=\"Red\">" + String(totalTime.toFixed(2)) + " sec. / " + String(Number(60-totalTime).toFixed(2)) + " sec.</font>";
	}
	else
	{
		if (Number(60-totalTime) < 15)
		{
			tTT.innerHTML="<font color=\"Orange\">" + String(totalTime.toFixed(2)) + " sec. / " + String(Number(60-totalTime).toFixed(2)) + " sec.</font>";
		}
		else
		{
			tTT.innerHTML=String(totalTime.toFixed(2)) + " sec. / " + String(Number(60-totalTime).toFixed(2)) + " sec.";
		}
	}
	-->
	<!--CSC428 ADD THIS LINE ********************************************-->
	tTT.innerHTML=String(totalTime.toFixed(2)) + " sec."
		
	//Determine if the user has typed all of the words expected
	if ((((thisTyped.split(" ").length+1)/word)*100).toFixed(2) >= 100)
	{
		tProg.width="100%";
	}
	else
	{
		//Set the progress bar with the exact percentage of the test completed
		tProg.width=String((((thisTyped.split(" ").length)/word)*100).toFixed(2))+"%";
	}
	
	//Determine if the test is complete based on them having typed everything exactly as expected
	// we don't need this for our test in CSC428
	// if (thisTyped.value == document.JobOp.given.value)
	// {
		// endTest();
	// }
	
	<!-- CSC428 REMOVE THIS IF STATEMENT Because it counts the white space********************************************************
	//Determine if the test is complete based on whether or not they have typed exactly or exceeded the number of valid words (determined by a space)
	
	// if (word <= (thisTyped.split(" ").length))
	// {
		// endTest();
	// }
	-->
	<!--CSC428 REMOVE THE EXTRA IF STATEMENT HERE THAT SAY IF WORD <=60**********************************************-->
	
//Our handy error handling
} catch(e){};
}

</SCRIPT>

<!--CSC428********************************************************** REMOVES THE ZOOMING FUNCTION IMPORTANT!!!!!!********************************************************** -->
<meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0' name='viewport' />

</head>

<body>
	
	<div id="testform" align="center">
												<table border="0" cellpadding="0" cellspacing="0" width="100%">
												<h1>CSC428 Typing Test for Various Input Methods</h1>
													<tr>
														<td style="border-bottom: 2px solid #354562; padding: 4px" class="titlec" align="center">
														<label><?php 
														
														if($_SESSION["participant"]=="T") echo "Training Session";
														else {echo "Participant ID: &nbsp;".$_SESSION["participant"];}
														
														?></label>
														<select name="paragraph_id" id="paragraph_id"  onchange="SwitchParagraph(this.value)">
														
														<?php
															
															//print all the option choices for the paragraphs
															for ($x=1; $x<=12; $x++) 
																{
																	echo "<option value=\"".$x."\" id=\"paragraph".$x."\">Phrase ".$x."</option>";
																}
														?>
													</select>
														</td>
													</tr>
													<tr><td style="border-bottom: 2px solid #354562; padding: 4px" class="titlec" align="center">
													<a href="logout.php" value="Log out" >Log out</a></td></tr>
												</table>
											
<!--CSC428********************************************************** Got rid of this to make space for the big mobile keyboard**********************************************************	
	
											
											<table border="0" cellpadding="0" cellspacing="0" width="100%">
												<tr>
													<td style="border-bottom: 1px dotted #860E36; padding: 4px" class="titlea" background="Images/Lt_Red_Back.gif" width="460">
													Accurately and precisely 
													evaluate your typing speed 
													and accuracy.</td>
													<td style="border-bottom: 1px dotted #860E36; padding: 4px" class="titlea" background="Images/Lt_Red_Back.gif" width="190">
													<p align="right">v1.0</td>
												</tr>
												<tr>
													<td style="padding: 4px" class="bodya" colspan="2">-->
<FORM name="JobOp">

<table border="0" width="100%">
	<tr>
		<td>
		<table border="0" cellpadding="0" width="100%">
			<tr>
				<td align="center" >
				<b><h2>Word Per Minute (WPM)</h2></b></td>
				<!-- Comment out these two sections, we won't use it*****************************************************************
				<td align="center" style="border-left: 1px solid #344270; border-right: 2px solid #344270; border-top: 1px solid #344270; border-bottom: 2px solid #344270; padding: 5px; background-color: #CED3E8" background="Images/Blue_Back.gif">
				<b><font face="Arial" size="2" color="#FFFFFF">Entry Errors</font></b></td>
				<td align="center" style="border-left: 1px solid #344270; border-right: 2px solid #344270; border-top: 1px solid #344270; border-bottom: 2px solid #344270; padding: 5px; background-color: #CED3E8" background="Images/Blue_Back.gif">
				<b><font face="Arial" size="2" color="#FFFFFF">Accuracy</font></b></td>-->
				<td align="center" >
				<!--CSC428 Change the message here to Time Elapsed***********************************************************************-->
				<b><h2>Time Elapsed</h2></b></td>
			</tr>
			<tr>
				<td style="font-family: 'Open Sans',sans-serif; font-size: 9pt" align="center">
				<div id="stat_wpm">Not Started</div></font></td>
				<!-- CSC428 REMOVE THIS TOO********************************************************
				<td style="border-left: 1px dotted #8794C7; border-right: 1px dotted #8794C7; border-top-width: 1px; border-bottom-width: 1px" align="center">
				<font size="2" face="Arial"><div id="stat_errors">Waiting...</div></font></td>
				<td style="border-left-width: 1px; border-right: 1px dotted #8794C7; border-top-width: 1px; border-bottom-width: 1px" align="center">
				<font size="2" face="Arial"><div id="stat_score">Waiting...</div></font></td>-->
				<td style="font-family: 'Open Sans',sans-serif; font-size: 9pt" align="center">
				<div id="stat_timeleft">0:00</div></font></td>
			</tr>
		</table>
		</td>
	</tr>
	<tr>
		<td style="border-left-width: 1px; border-right-width: 1px; border-top: 1px solid #344270; border-bottom-width: 1px">
		<div id="expectedArea" style="display:block">
		<p style="margin-top: 0; margin-bottom: 0">
		<font color="#7A88C0" face="Arial" size="1">
			
	<textarea name="given" cols=53 rows=4 wrap=on onFocus="deterCPProtect();" >Click on the button below to start the typing test.  What you will be expected to type will appear here.</textarea></font>
		</div>
		</td>
	</tr>
	<tr>
		<td>
		<p align="center" style="margin-top: 0; margin-bottom: 2px">
		<input type=button value="&gt;&gt; Start Typing Test &lt;&lt;" name="start" onClick="beginTest()" style="font-family:'Open Sans',sans-serif; display:block; border-left:1px solid #293358; border-right:2px solid #293358; border-top:1px solid #293358; font-size:150%; border-bottom:2px solid #293358;height: 60px;width: 100%; background-color: #00CC00; color:#FFFFFF;"><p align="center" style="margin-top: 0; margin-bottom: 0">
		<input disabled type=button value="&gt;&gt; End Typing Test &lt;&lt;" name="stop" onClick="endTest()" style="font-family:'Open Sans',sans-serif; display:none; border-left:1px solid #293358; border-right:2px solid #293358; border-top:1px solid #293358; font-size:150%; border-bottom:2px solid #293358; height:60px ;width: 100%; background-color: #F05959; color:#FFFFFF;">
		<input reset type=button value="&gt;&gt; SAVE DATA and RESET &lt;&lt;" name="reset" onClick="resetTest()" style="font-family:'Open Sans',sans-serif; display:none; border-left:1px solid #293358; border-right:2px solid #293358; border-top:1px solid #293358; font-size:150%; border-bottom:2px solid #293358; height:60px ;width: 100%; background-color: #F05959; color:#FFFFFF;"></td>
	</tr>
	<tr>
		<td style="font-family: Arial; font-size: 9pt">
		<div id="typeArea" style="display:block">
		<table border="0" width="100%" cellspacing="1">
			<tr>
				<td style="border: 1px solid #9CA8D1; background-color: #EAECF4">
				                                                                                                                                                                                                                                                                                                                                                                   
				</td>
			</tr>
		</table>
		<p style="margin-top: 0; margin-bottom: 0">
		<font color="#7A88C0" face="Arial" size="1">
		<textarea onkeydown="//calcStat()" name="typed" cols=53 rows=4 wrap=on ></textarea></font>
		</div>
		</td>
	</tr>
	<script>
	<!-- randNum = Math.floor((Math.random() * 10)) % intToTestCnt>


	phraseNum = 0
	<!--This is where you change the phrases for testing!-- >
	strToTestType = strToTest[phraseNum];
	
	document.JobOp.given.value = strToTestType;
	document.JobOp.typed.focus();
	</script>
	
	</table>
</FORM>
													</td>
												</tr>
											</table>

<!---->
</div>

</body>

</html>