PHP IMDB Grabber
====================
 PHP IMDB Grabber is a class that fetches details from IMDB.com. It uses XPath / DOM Traversing for scrapping 
 content from imdb.
 
How to Use
----------

  At first include PHP class file (class.imdb.php)
 
  require_once 'path/to/class.imdb.php';
  
  $imdb = new Imdb();
  
  $imdb->get('http://www.imdb.com/title/tt0103064/'); // Pass the entire path or a search query
  
  $imdb->get('The Matrix'); // or the name of Movie 
  
  
Parameters / Options
--------------------

  By default, the the method does not return the cast in the movie. If you wish to get a list of the cast,
  use the following method:
  
  $imdb->showCast(true)->get('Name OR URL of Movie');
  
  
Online Documentation / Demo
---------------------------

* (View Online Documentation)[http://www.itsalif.info/content/imdb-details-grabber-using-php-dom-xpath-extract-movie-details]
  
* (Check out Online Demo)[http://www.itsalif.info/content/example-imdb-details-grabber]


Change Log
--------------------------

* Version 1.5 (Feb 10, 2010)

   ** Added the showCast Method. When this method is invoked with a 'true' parameter, the cast in the movie is also grabbed.
	
* Version 2 (July 15, 2010)

   ** Added a simple regex check for validity of URL on isValidURL method.
   ** Added a simple search query check on getImdbURL method.

* Version 2.1 (Oct 10, 2010)

   ** Rewrote the XPath expression as IMDB Changed their Layout completely.
 	
* Version 2.2 (Dec 9, 2010)

   ** Fixed a Bug on Cast for new Layout and added Budget Info.


License & Policy
--------------------------

Copyright (c) 2010 Abdullah Rubiyath <http://www.itsalif.info/>. 
The script has been released under MIT License. 

Please note that this script is created as a demo of screen scrapping. IMDB Policy prohibits screen scrapping.    