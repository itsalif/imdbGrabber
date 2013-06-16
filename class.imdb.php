<?php
/**
 * A simple class which parses IMDB Webpage and extracts Details from it.
 * Requires PHP 5.1 (uses DomXPath, DomDocument)
 * 
 * @author Abdullah Rubiyath <http://www.itsalif.info/>
 * @version 2.3
 * @license MIT
 * 
 * Release Date:  May 21, 2009
 * Last Updated:  August 10, 2012
 * 
 * 
 * How to Use (no knowledge of XPath required): 
 * --------------
 * Initialize the Class and then just pass the URL to get the basic Information
 * 
 * + BASIC USAGE: 
 * $imdbObj    = new Imdb();
 * $movieInfo  = $imdbObj->get('http://www.imdb.com/title/tt0367882/');
 * 
 * $movieInfo should contain all the details of movie in associative Array Format
 * 
 * You can also grab based on a search string like:
 * $movieInfo  = $imdbObj->get('The Matrix');
 * 
 * 
 * CHANGE LOG (Jan 20, 2010)
 * --------------------------
 * Version 2
 * > Added the showCast Method. When this method is invoked with 'true' parameter,
 *   the cast in the movie is also grabbed.
 * 
 * CHANGE LOG (July 8, 2010)
 * --------------------------
 * Version 2.1
 * > Added a simple regex check for validity of URL on isValidURL method.
 * > Added a simple search query check on getImdbURL method.
 * 
 * CHANGE LOG (Oct 10, 2010)
 * --------------------------
 * > Rewrote the XPath expression as IMDB Changed their Layout completely.
 * 
 * CHANGE LOG (Dec 9, 2010)
 * -------------------------
 * > Fixed a Bug on Cast and added Budget Info.
 * 
 * CHANGE LOG (May 29, 2011)
 * -------------------------
 * > Version 2.2
 * > Added Genres - Provided by Greg Fitzgerald (Github: https://github.com/gregf)
 * 
 * CHANGE LOG (August 10, 2012)
 * ----------------------------
 * > Version 2.3
 * > Replaced DomDocument->load with CURL for loading IMDB Page
 * > Replaced title grabbing with regex (as XPath doesn't seem to work?)
 * > Updated the xpath expression for Runtime
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
	 * @var $genres
	 */
	protected $genres;
	
	/**
	 * This Constructor of the Class
	 * @return
	 */
	public function __construct() {
		/* the suffix of xpath expression for each item to be grabbed */
		$this->defaultList  = array (
			'Country:'      => '/a',
			'Language:'     => "/a",
			'Runtime:'      => '/time',
			'Aspect Ratio:' => '/text()',
			'Release Date:' => '/text()',
			'Budget:'       => '/text()'
		);
		
		$this->cast     = true;
		$this->castList = array();
		$this->grabList = $this->defaultList;
		$this->csv      = true;
		$this->rating   = true;
		$this->genres   = true;
	}
	
	/**
	 * The destructor of the Class
	 */
	public function __destruct() {
		unset($this->defaultList);
		unset($this->grabList);
	}
	
	/**
	 * This Method Grabs the info from IMDB website and returns it in an 
	 * associative array format, false on failure
	 * 
	 * @param  string|$url   The Url or Search Query of the IMDB movie to be grabbed
	 * @return array|boolean Associative array in key=>value pairs on success, 
	 *                       False on failure
	 */
	public function get($url) {
		if (!$this->isValidURL($url)) {
			$url = $this->getImdbURL($url);
		}
		
		$imdbDom    = new DomDocument();
		$pageOutput = $this->getURLContent($url);
		
		$imdbLoad = $imdbDom->loadHTML($pageOutput);
		if ($imdbLoad === false) {
			return false;
		}
		
		$xpath = new DomXPath($imdbDom);
		
		$grabValue = array (
			'title:' => $this->removeNewLines($xpath
					 ->query('//h1[@class="header"]/span/text()')
					 ->item(0)
					 ->nodeValue),
			
			'year:'  => $this->removeNewLines($xpath
					 ->query('//h1[@class="header"]/span/a/text()')
					 ->item(0)
					 ->nodeValue),
					
			'url:'   => $url
		);
		
		$imageNode = $xpath->query("//td[@id='img_primary']/div/a/img");
		if ($imageNode->length != 0) {
			$grabValue['image'] = $imageNode->item(0)->getAttribute('src');
		}
		
		// grab director, writer, (Dont get run time from here)
		$nodeList = $xpath->query("//td[@id='overview-top']/div[@class='txt-block']");
		for($i = 0; $i < $nodeList->length-1; $i++) {
			$nodeNameList         = $xpath->query('h4', $nodeList->item($i)); 
			$grabName             = trim($nodeNameList->item(0)->nodeValue);
			$nodeValueList        = $xpath->query('a', $nodeList->item($i));
			$grabValue[$grabName] = $this->getValue('a', $nodeValueList);
		}
		
		// grab story line:
		$grabValue['Storyline:'] = $this->removeNewLines($xpath
						->query("//div[@class='article'][h2='Storyline']/div/p")
						->item(0)
						->firstChild
						->nodeValue
					);
		
		/* check if cast needs to be grabbed */
		if ($this->cast) {
				$castNameList  = $xpath->query("//td[@itemprop='actor']/a/span/text()");
				$castThumbList = $xpath->query("//td[@class='primary_photo']/a/img");
				$castCharList  = $xpath->query("//td[@class='character']/div/a/text()");
				
				$totalElem = $castNameList->length; 
				for ($i = 0; $i < $totalElem; $i++) {
					$this->castList[$i] = array (
						 "name"  => $castNameList->item($i)->nodeValue ,
						 "thumb" => $castThumbList->item($i)->getAttribute('loadlate'),
						 "cast"  => $castCharList->item($i)->nodeValue
					 );
				}
				$grabValue['Cast:'] = $this->castList;
				
		}
		
		if ($this->rating) {
			$grabValue['User Rating:'] = $xpath->query("//span[@itemprop='ratingValue']/text()")->item(0)->nodeValue;
			$grabValue['Total Votes:'] = $xpath->query("//span[@itemprop='ratingCount']/text()")->item(0)->nodeValue;
		}
		
		if ($this->genres) {
			$genresNameList = $xpath->query("//div[@class='infobar']/a");
			$totalElem = $genresNameList->length;
			$this->genresList = $this->getValue('/a', $genresNameList);
			
			$grabValue['Genres:'] = $this->genresList;
		}
		
		foreach($this->grabList as $k=>$v) {
			$xpathString   = "//div[@class='article']/div[@class='txt-block'][h4='{$k}']{$v}";	
			$grabValue[$k] = $this->getValue($v, $xpath->query($xpathString));
		}
		
		return $grabValue;
	}
	
	/**
	 * An Internal Method that calculates the values for each nodes. Its only accessed 
	 * internally from within the class.
	 * 
	 * @param  string $nodeType  The type of node (/text(), /a etc.)
	 * @param  array  $nodeList  The List of Nodes from where query is to be made
	 * 
	 * @return string|array
	 */
	protected function getValue($nodeType, $nodeList) {
		/* strpos is used instead of regex, because strpos is faster, and === has not been used */
		$totalItem = $nodeList->length;
		$nodeValue = '';
		if (strpos($nodeType, '/text()') !== false) {
			for($i = 0; $i < $totalItem; $i++) {
				$nodeValue .= trim($nodeList->item($i)->nodeValue);
			}
		} else {
			// if user wants a csv value of nodes, return csv, else return an array
			if ($this->csv) {
				for ($i = 0; $i < $totalItem; $i++) {
					$nodeValue .= trim($nodeList->item($i)->nodeValue).',';
				}
				$nodeValue = substr($nodeValue, 0, -1);
			} else {
				$nodeValue = array();
				for ($i = 0; $i < $totalItem; $i++) {
					$nodeVal = trim($nodeList->item($i)->nodeValue);
					if($nodeVal != '') {
						$nodeValue[] = $nodeVal;
					}
				}
			}
		}
		return $nodeValue;
	}
	
	/**
	 * Removes new lines in a string
	 * 
	 * @param  $str  The string to be processed
	 * @return $str  the clean string
	 */
	protected function removeNewLines($str) {
		return str_replace("\n", "", $str);
	}
	
	/**
	 * This Method sets whether to grab cast or not.
	 * 
	 * @param  boolean $t
	 * @return object
	 */
	public function showCast($t) {
		$this->cast = $t;
		return $this;
	}
	
	/**
	 * Set the Rating (whether rating should be shown or not)
	 * 
	 * @param  boolean $t
	 * @return object  Current Object
	 */
	public function showRating($t) {
		$this->rating = $t;
		return $this;
	}
	
	/**
	 * Set the Genres (whether genres should be grabbed or not)
	 * 
	 * @param  boolean $t
	 * @return object
	 */
	public function showGenres($t) {
		$this->genres = $t;
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
		// a simple regex to check if the url matches imdb.com domain style
		$matchCount = preg_match("/(www\.)?imdb\.com\/title\/[A-Za-z0-9]+/i", $url);
		return !($matchCount < 1);
	}
	
	/**
	 * Grab a proper URL from a search query
	 * 
	 * @param  string           $url The imdb url to be grabbed
	 * @return string/boolean   Returns the url on success and false on failure
	 */
	public function getImdbURL($url) {
		$queryStr      = '//p[@style]/b/a';
		$validTitleStr = "//head/link[@rel='canonical']";
		$searchURL     = 'http://www.imdb.com/find?q='.urlencode($url);
		
		$output        = $this->getURLContent($searchURL);
		
		/* replacing XPath with regex as regex doesn't work with the new URL */
		preg_match_all('/link rel="canonical" href="([^"]+)?"/', $output, $matches);
		
		/* ensure there was a match */
		if (count($matches[1]) < 1) {
			return false;
		}
		
		// See if Imdb redirected to a page. It happens for some movies (Shutter Island)
		$linkHrefURL = $matches[1][0];
		if($this->isValidURL($linkHrefURL)) {
			return $linkHrefURL;
		}
		
		$searchDom  = new DomDocument();
		$searchLoad = $searchDom->loadHTML($output);
		$xpath      = new DomXPath($searchDom);
		
		if($xpath->query($queryStr)->length > 0) {
			return 'http://www.imdb.com'.$xpath->query($queryStr)->item(0)->getAttribute('href');
		} else {
			return false;
		}
	}
	
	/**
	 * Uses CURL to extract content from the imdb url
	 * 
	 * @param  $url  The imdb url to be grabbed
	 * @return       The contents of the page (from $url) as string
	 */
	private function getURLContent($url) {
		// create curl resource
		$options = array( 
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_CONNECTTIMEOUT => 120,
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_MAXREDIRS      => 2,        // stop after 2 redirects?
		);
		
		$ch      = curl_init( $url ); 
		curl_setopt_array( $ch, $options ); 
		
		/* store the output */
		$output  = curl_exec( $ch );
		$err     = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
		curl_close( $ch ); 
		
		return $output;
	}
}
