<?php 
	error_reporting(E_ERROR);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
<title>Demo of IMDB Grabber</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">

<style type="text/css">
body {
	font-family:        Verdana;
	font-size:          12px;
	margin:             0 10px;
	background-color:   #f1f1f1;
}

h2, h3 {
	padding:            5px 0;
	margin:             0;
	font-family:        Cambria, Georgia, Helvetica;
	font-size:          17px;
}

form {
	padding:            2px;
	border-bottom:      solid 2px #ccc;
}

p {
	margin:             0 2px;
	padding:            5px;
}

input {
	padding:                 4px;
	border-radius:           5px;
	-moz-border-radius:      5px;
	-webkit-border-radius:   5px;
}

.wrap {
	width:              980px;
	margin:             0 auto;
	background-color:   #fff;
	padding:            10px;
}

.movieInfo {
	width:              600px;
	line-height:        25px;
	padding-left:       5px;
}

.shadow {
	-moz-box-shadow:    5px 5px 5px #ccc;
	-webkit-box-shadow: 5px 5px 5px #ccc;
	box-shadow:         5px 5px 5px #ccc;
}

tr.one{
	background-color:   rgba(88, 93, 95, 0.1);
}

tr.two{
	background-color:   rgba(250, 253, 255, 0.1);
}
</style>
</head>

<body>

<div class="wrap shadow">
<h2 style="color: #069;">IMDB Grabber</h2>

<form action="demo.php" method="post">
<p>
	Enter a movie name: 
</p>
<p>
	<input type="text" name="movie" value="" size="35"/>
</p>
<p>
	<input type="submit" name="submit" value="Grab" /> &nbsp;
	<input type="reset" name="reset" value="Reset" />
</p>
</form>

<div class="movieInfo">
<?php
include 'class.imdb.php';
$imdb = new Imdb();

if (isset($_POST['movie'])) {
	$movieInfo = $imdb->showCast(true)->get(trim($_POST['movie']));
	if ($movieInfo == false) { 
		$movieInfo = array ('error' => 'Invalid Search Term or URL'); 
	}
	else {
	foreach($movieInfo as $k=>$v) {	
		$k = ucfirst($k);
		if ($k != 'Cast:') {
			echo <<<_INFO_
<p>
<h3><b>{$k}</b></h3>
{$v}
</p>
_INFO_
;
		}
		else {
			echo '
<p>
<h3><b>Cast:</b></h3>
<table width="100%" style="font: normal 12px Verdana; line-height:20px;">';

			$i = 0;
			foreach($v as $eachCast) {
				$c = ($i++ % 2) ? ('two') : ('one');
				echo <<<_E_
<tr class={$c}>
<td width="50%">{$eachCast['name']}</td>
<td>{$eachCast['cast']}</td>
</tr>
_E_
;
			} //end foreach
			
			echo '</table>'."\n".'</p>';
		} // end else;
	} // end for-each
	}
}
?>
</div>
</div>
</body>
</html>
