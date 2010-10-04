<?php
/**
 * A simple class which parses IMDB Webpage and extracts Details from it. 
 * Requires PHP 5.1 (uses DomXPath, DomDocument)
 * 
 * @author Abdullah Rubiyath <http://www.itsalif.info/>
 * @version 2.1
 * @license MIT, 
 * Release Date:  May 21, 2009
 * Last Updated:  July 8, 2010
 * 
 * Brief Explanation of its Logic (requires Knowledge of XPath, Skip to 'How to use' below to see its usage):
 * ---------------------------------- 
 * Looking at the IMDB Webpage, it seems, the tags for storing information are in the Two format
 *  
 * Format 1:
 * <div class="info">
 * 		<h5>Release Date:</h5> 
 *      <div class="info-content">
 * 		22 May 2008 (Canada)
 * 		<a ..>more</a>
 *      </div>
 * </div>
 *
 * Format 2:
 * <div id="director-info" class="info">
 * 		<h5>Director:</h5>
 *      <div class="info-content">
 * 		<a href="/name/nm0000229/">Steven Spielberg</a>
 *      </div>
 * </div>
 * 
 * So, all the Div we want to grab has class='info' wrapper on it.
 * - For Format 1, we want to Grab: 22 May 2008 (Canada)
 * - For Format 2, we want to Grab: Steven Spielberg 
 *
 * So, XPath thats used:
 * Format 1: //div[@class='info'][h5='Release Date:']/text()
 * Format 2: //div[@class='info'][h5='Director:']/a
 * 
 * 
 * How to Use (no knowledge of XPath required): 
 * --------------
 * Initialize the Class and then just pass the URL to get the basic Information
 * 
 * + BASIC USAGE: 
 * $imdbObj 	= new Imdb();
 * $movieInfo 	= $imdbObj->get('http://www.imdb.com/title/tt0367882/');
 * 
 * $movieInfo should contain all the details of that movie in associative Array Format
 * 
 * You can also grab based on a search string like:
 * $movieInfo  = $imdbObj->get('The Matrix');
 * 
 * 
 * 
 * + CUSTOMIZED USAGE:
 * If you wish to add you own fields.. just look through the source code of the page and call this function
 * 
 *  - EXAMPLE 1:
 * If you want to get all the writers for that Movie
 * The source code of this page 'http://www.imdb.com/title/tt0367882/' shows this tags about Writers:
 * ///
 * <div class="info">
 * 		<h5>Writers <a href="/wga">(WGA)</a>:</h5>
 *      <div class="info-content">
 * 		<a href="/name/nm0462895/">David Koepp</a> (screenplay)<br/><a href="/name/nm0000184/">George Lucas</a> (story) ...<br/><a class="tn15more" href="fullcredits#writers">more</a>
 *      </div>
 * </div>
 * ///
 * So, all the Writers are inside an 'a' tag , so do the following:
 * $imdbObj 	= new Imdb();
 * $movieInfo 	= $imdbObj->add('Writers :', '/div/a')->get('http://www.imdb.com/title/tt0367882/');
 * 
 *   - EXAMPLE 2:
 * Lets say I also want to grab the Awards for that movie, I look at the source code of that page and see this:
 * ///
 * <div class="info">
 * 	<h5>Awards:</h5> 
 *  <div class="info-content">
 *  Nominated for BAFTA Film Award.
 *  Another 3 wins &amp; 25 nominations
 *  <a class="tn15more..">more</a>
 *  </div>
 * </div>
 * ///
 * $imdbObj 	= new Imdb();
 * $movieInfo	= $imdbObj->add('Awards:','/div/text()')->get('http://www.imdb.com/title/tt0367882/');
 * 
 * To get both records, do this:
 * $movieInfo  = $imdbObj->add( array( 'Writers :' =>  '/div/a', 'Awards:' => '/div/text()' ) )->get('http://www.imdb.com/title/tt0367882/');
 * 
 * 
 * CHANGE LOG (Jan 20, 2010)
 * --------------------------
 * Version 2
 * > Added the showCast Method. When this method is invoked with a 'true' parameter, the cast in the movie is also grabbed.
 * 
 * CHANGE LOG (July 8, 2010)
 * --------------------------
 * Version 2.1
 * > Added a simple regex check for validity of URL on isValidURL method.
 * > Added a simple search query check on getImdbURL method.
 */

class Imdb {
	/**
	 * @var array $grabList
	 */
	protected $grabList;
	
	/**
	 * @var array $defaultList
	 */
	protected $defaultList;
	/**
	 * @var boolean $csv
	 */	
	protected $csv; 
	
	/**
	 * @var boolean $cast
	 */
	protected $cast;
	
	/**
	 * @var $castList
	 */
	protected $castList;
	
	/**
	 * @var $rating
	 */
	protected $rating;

	/**
	 * This Constructor of the Class
	 * @return 
	 */
	public function __construct() {
		$this->defaultList = array (
			'Plot:'				=> "/div/text()" ,
			'Director:' 		=> "/div/a" ,
		    'Directors:'        => "/div/a" ,
			'Release Date:'		=> "/div/text()" ,
			'Runtime:'			=> "/div/text()"
		);
		$this->cast     = false;
		$this->castList = array();
		$this->grabList = $this->defaultList;
		$this->csv 		= true;
		$this->rating   = true;
	}
	
	/**
	 * The destructor of the Class
	 * @return 
	 */
	public function __destruct() {
		unset($this->defaultList);
		unset($this->grabList);
	}
	
	/**
	 * This method allows users to add more fields that needs to be extracted from Imdb, 
	 * on top of the default ones
	 * 
	 * @return object	   this Object, This allows method Chaining.
	 * @param string|array $name
	 * @param string|null  $value[optional]
	 */
	public function add($name, $value='') {
		if( is_array($name) ) {
			foreach($name as $k=>$v) {
				$this->grabList[$k] = $v;
			}	 
		} else {
			$this->grabList[$name] = $value;
		}
		return $this;
	}
	
	
	
	/**
	 * This Method Grabs the info from IMDB website and returns it in an assoc array format, false on failure
	 * @return array|boolean
	 * @param  string $url
	 */
	public function get($url) {
		
		if( !$this->isValidURL($url) ) {
		 	$url = $this->getImdbURL($url);
		}
		
		$imdbDom  = new DomDocument();
		$imdbLoad = $imdbDom->loadHTMLFile($url);
		if( $imdbLoad === false ) {
			return false;
		}

		$xpath = new DomXPath($imdbDom);	
		
		$grabValue = array (
			'title:' => $xpath->query('/html/head/title')->item(0)->nodeValue	,
			'image:' => $xpath->query("//div[@class='photo']/a/img")->item(0)->getAttribute('src') ,
			'url:'	 => $url
		);
		
		if( $this->cast ) {
		//	$grabValue['cast'] = $xpath->query("//div[@class='info'][h3='Cast']/)
		    $castNameList  = $xpath->query("//td[@class='nm']/a/text()");
		    $castThumbList = $xpath->query("//td[@class='hs']/a/img");
		    $castCharList  = $xpath->query("//td[@class='char']/a/text()");
            
		    $totalElem = $castNameList->length; 
		    for($i=0;$i<$totalElem;$i++) {
		    	$this->castList[$i] = array (
		    	   "name"  => $castNameList->item($i)->nodeValue ,
		    	   "thumb" => $castThumbList->item($i)->getAttribute('src'),
		    	   "cast"  => $castCharList->item($i)->nodeValue
		    	 );
		    }
		    $grabValue['Cast:'] = $this->castList;
		    
		}
		
		if( $this->rating ) {
			$grabValue['User Rating:'] = $xpath->query("//div[@class='starbar-meta']/b/text()")->item(0)->nodeValue;
			$grabValue['Total Votes:'] = $xpath->query("//div[@class='starbar-meta']/a/text()")->item(0)->nodeValue;
		}
		
		foreach($this->grabList as $k=>$v) {
			$xpathString = "//div[@class='info'][h5='{$k}']{$v}";	
			$grabValue[$k] = $this->getValue($v, $xpath->query($xpathString));
		}
		
		if( $grabValue['Directors:'] != '' ) {
            $grabValue['Director:'] = $grabValue['Directors:'];
			
		} 
		
		unset($grabValue['Directors:']);
		return $grabValue;
	}
	
	/**
	 * An Internal Method that calculates the values for each nodes. Its only accessed internally 
	 * from within the class
	 * 
	 * @return string|array
	 * @param  string $nodeType
	 * @param  array  $nodeList
	 */
	protected function getValue($nodeType, $nodeList) {
		
		/** strpos is used instead of regex, because strpos is faster, and === has not been used **/
		$totalItem = $nodeList->length;
		$nodeValue = '';
		if( strpos($nodeType, '/text()') !== false) {
			for($i=0;$i<$totalItem;$i++) {
				$nodeValue .= trim($nodeList->item($i)->nodeValue);
			}
		} else {
			// if user wants a csv formatted value of nodes, then, return a csv, otherwise return an array
			if($this->csv) {
				for($i=0;$i<$totalItem;$i++) {
					$nodeValue .= trim($nodeList->item($i)->nodeValue).',';
				}
				$nodeValue = substr($nodeValue, 0, -1);
			} else {
				$nodeValue = array();
				for($i=0;$i<$totalItem;$i++) {
					$nodeVal = trim($nodeList->item($i)->nodeValue);
					if( $nodeVal != '' )
						$nodeValue[] = $nodeVal;
				}
			}
		}
		return $nodeValue;
	}
	
	
	/**
	 * This Method sets whether to grab cast or not.
	 * 
	 * @param boolean $t
	 * @return object
	 */
	public function showCast($t) {
		$this->cast = $t;
		return $this;
	}
	
	/**
	 * Set the Rating 
	 * @param  boolean $t
	 * @return object
	 */
	public function showRating($t) {
		$this->rating = $t;
		return $this;
	}
	
	/**
	 * Set the permission to use CSV or not
	 * 
	 * @return object 
	 * @param  string $status
	 */
	public function useCSV($status) {
		$this->csv = $status;
		return $this;
	}
	
	/**
	 * Checks if a URL is a valid URL or not
	 *
	 * @return boolean 
	 * @param  string  $url
	 **/
	public function isValidURL($url) {
		// a simple regex
		$matchCount = preg_match("/(www\.)?imdb\.com\/title\/[A-Za-z0-9]+/i", $url);
		return !($matchCount < 1);
/*
		if( strpos($url, "http://") === false || strpos($url,"www.") === false ) {
			return false;
		}
		return true;
*/
	}
	
	/**
	 * Grab a proper URL from a search query
	 *
	 * @return string/boolean 	$url on success and false on failure
	 * @param  string 			$url
	 */
	public function getImdbURL($url) {
		$queryStr   	= '//p[@style]/b/a';
		$validTitleStr  = "//head/link[@rel='canonical']"; 
		$searchURL  = 'http://www.imdb.com/find?q='.urlencode($url);
		$searchDom  = new DomDocument();
		$searchLoad = $searchDom->loadHTMLFile($searchURL);

		$xpath      = new DomXPath($searchDom);
		// check to see if Imdb has directly redirected to the movie page. It happens for some movies (Ex: Shutter Island as someone pointed out).
		$linkHrefURL  = $xpath->query($validTitleStr)->item(0)->getAttribute('href');
		if($this->isValidURL($linkHrefURL) ) {
			return $linkHrefURL;
		} 
		
		if($xpath->query($queryStr)->length > 0 ) {
			return 'http://www.imdb.com'.$xpath->query($queryStr)->item(0)->getAttribute('href');
		} else {
			return false;
		}
	}
}
?>