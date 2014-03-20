<?php
	require_once("./include/membersite_config.php");
	if(!$fgmembersite->CheckLogin()){
		$fgmembersite->RedirectToURL("index.php");
		exit;
	}
	
	if(isset($_POST['submitted'])){
		/*if($fgmembersite->RegisterUser()){
			$fgmembersite->RedirectToURL("thank-you.html");
		}*/
	}
	
	$nameOfPerson = $fgmembersite->UserEmail();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<link rel="shortcut icon" href="images/logo.png">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>VisKo</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="css/style.css" media="screen" />
		<style type="text/css">
		table.bottomBorder { border-collapse:collapse; }
		table.bottomBorder td, table.bottomBorder th { border-bottom:1px dotted black;padding:5px; }
		</style>
		<!--[if IE 6]>
		<link rel="stylesheet" type="text/css" href="css/iecss.css" />
		<![endif]-->
	</head>
	<body>
	<div id="main_container">
		<div class="header">
			<div id="logo">
				<a href="http://cs4311.cs.utep.edu/team3/"><img src="images/logo.png" alt="" border="0" /></a>
				<font size="10" color="white"><b> VisKo</b></font>
			</div>			
			<div class="welcome">
			<br><br><br>
			<font size="2" color="white"><b>Welcome back <?PHP echo $nameOfPerson ?>!&nbsp;&nbsp;</b></font>
			<form id="form1" action="logout.php" method="get">
				<input type="submit" value="Logout">
			</form>
			</div>
		</div>

		<div id="middle_box">
			<div class="middle_box_content">
				<div align = "center">
					Option 1: Submit Visualization Query
					<br><br>
					<textarea rows = '5' cols = '107'></textarea>
					<br><br>
					<div style = "float:right;">
							<a href="http://cs4311.cs.utep.edu/team3/SelectPipelines.php">
								<button>Submit</button>
							</a>
					</div>
				</div>	
				<br><br><br><br><br>
				<div align = "center">
					Option 2: 
					<br><br>
					<html lang="en">
						<head>
							<meta charset="utf-8">
							<title>jQuery UI Accordion - Default functionality</title>
							<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
							<script src="//code.jquery.com/jquery-1.9.1.js"></script>
							<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
							<link rel="stylesheet" href="/resources/demos/style.css">
							<script>
								$(function() {
								$( "#accordion" ).accordion();
								});
							</script>
						</head>
						<body>
							<div id="accordion">
								<h3>1D Abstractions</h3>
								<div>
									<p>
									PIC & Description here
									</p>
								</div>
								<h3>2D Abstractions</h3>
								<div>
									<p>
									PIC & Description here
									</p>
								</div>
								<h3>3D Abstractions</h3>
								<div>
									<p>
									PIC & Description here
									</p>
								</div>
							</div>
						</body>
					</html>
				</div>
			</div>
		</div> 
		<br><br><br>
		<footer>&copy; Developmental Technologies Team. All Rights Reserved</footer>
	</div>
	</body>
	
	
<div id="nav_table" style="position: fixed; left:0px; right: 31px; top: 120px; bottom: auto; display: block">
    <ul id="sidebar-nav" class="Menu">
<body>
	<br>
	<table class="bottomBorder">
		<tr>
		  <td><center></td>
		</tr>
		<tr>
		  <td><center><br><a href="http://cs4311.cs.utep.edu/team3/RegularUserHome.php" style="text-decoration:none;"><font size="5" color="black">Home</font></a><br><br></td>
		</tr>
		<tr>
		  <td><center><br><a href="http://cs4311.cs.utep.edu/team3/SpecifyCriteria.php" style="text-decoration:none;"><font size="5" color="black">Search <br> History</font></a><br><br></td>
		</tr>
		<tr>
		  <td><center><br><a href="http://cs4311.cs.utep.edu/team3/ChooseQueryStyle.php" style="text-decoration:none;"><font size="5" color="black">Visualize</font></a><br><br></td>
		</tr>
		<tr>
		  <td><center><br><a href="http://cs4311.cs.utep.edu/team3/SelectOperation.php" style="text-decoration:none;"><font size="5" color="black">Manage <br> Services</font></a><br><br></td>
		</tr>
		<tr>
		  <td><center><br><a href="http://cs4311.cs.utep.edu/team3/ConfigureAccount.php" style="text-decoration:none;"><font size="5" color="black">Configure <br> Account</font></a><br><br></td>
		</tr>
	</table>
    </ul>
</div>
</body>
</html>