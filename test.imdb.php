<?php
/**
 * Test script for class.imdb.php
 * This script shows sample usage of the class.imdb.php  (IMDB Grabber Script)
 * 
 * How to use:
 * 
 * $imdbObj    = new Imdb();
 * $movieInfo  = $imdbObj->get('http://www.imdb.com/title/tt0367882/');
 * 
 * $movieInfo should contain all the details of that movie in associative Array Format
 * 
 * You can also grab based on a search string like:
 * $movieInfo  = $imdbObj->get('The Matrix');
 * 
 * For Full Details, refer to:
 * http://www.itsalif.info/content/imdb-details-grabber-using-php-dom-xpath-extract-movie-details
 */
include 'class.imdb.php';

// only showing error, i.e turning off warning as new imdb layout throws bunch of warnings
error_reporting(E_ERROR);

$imdbObj = new Imdb();

// a helper function to echo movie details
function echoMovieDetails(&$movieInfo) {
	if($movieInfo != false) {
		echo '<pre>';
		print_r($movieInfo);
		echo '</pre>';
	} else {
		echo '<pre>Invalid Search Term or Invalid Imdb URL. No such Movie exists on IMDB</pre>';
	}
}

$path = 'http://www.imdb.com/title/tt0133093/'; // the Matrix Movie
$matrixInfo = $imdbObj->get($path);
echoMovieDetails($matrixInfo);

$Title = 'The Italian Job (1969)';
$IMDB  = $imdbObj->showCast(true)->get($Title);
echoMovieDetails($IMDB);

?>
