<?php
/**
 * A simple class which parses IMDB Webpage and extracts Details from it. 
 * Requires PHP 5.1 (uses DomXPath, DomDocument)
 * 
 * @author Abdullah Rubiyath <http://www.itsalif.info/>
 * @version 2.2
 * @license MIT
 *  
 * Release Date:  May 21, 2009
 * Last Updated:  Dec 9, 2010 
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
 * 
 * CHANGE LOG (Oct 10, 2010)
 * --------------------------
 * > Rewrote the XPath expression as IMDB Changed their Layout completely.
 * 
 * CHANGE LOG (Dec 9, 2010)
 * -------------------------
 * > Fixed a Bug on Cast and added Budget Info.
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
			'Country:'			=> '/a',
			'Language:' 		=> "/a",
			'Runtime:'			=> '/text()' ,
			'Aspect Ratio:'		=> '/text()' ,
			'Release Date:'		=> '/text()' ,
			'Budget:'			=> '/text()'
			
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
			'title:' => $this->removeNewLines($xpath->query('//h1')->item(0)->nodeValue)	,
			'url:'	 => $url
		);
		
		$imageNode = $xpath->query("//td[@id='img_primary']/a/img"); 
		if ($imageNode->length != 0)	{
			$grabValue['image'] = $imageNode->item(0)->getAttribute('src');
		}
		
		// grab director, writer, (Dont get run time from here)
		$nodeList = $xpath->query("//td[@id='overview-top']/div[@class='txt-block']");
		for($i=0;$i<$nodeList->length-1;$i++) {
			$nodeNameList			= $xpath->query('h4', $nodeList->item($i));	
			$grabName				= trim($nodeNameList->item(0)->nodeValue);
			$nodeValueList 			= $xpath->query('a', $nodeList->item($i));
			$grabValue[$grabName] 	= $this->getValue('a', $nodeValueList);			
		}
		
		// grab story line:
		$grabValue['Storyline:'] = $this->removeNewLines($xpath->query("//div[@class='article'][h2='Storyline']/p")->item(0)->firstChild->nodeValue);
		
		if( $this->cast ) {
		    $castNameList  = $xpath->query("//td[@class='name']/a/text()");
		    $castThumbList = $xpath->query("//td[@class='primary_photo']/a/img");
		    $castCharList  = $xpath->query("//td[@class='character']/div");
            
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
			$grabValue['User Rating:'] = $xpath->query("//div[@class='rating rating-big']/span[@class='rating-rating']/text()")->item(0)->nodeValue;
			$grabValue['Total Votes:'] = $xpath->query("//div[@class='star-box']/a[@href='ratings']/text()")->item(0)->nodeValue;
		}
		
		foreach($this->grabList as $k=>$v) {
			$xpathString = "//div[@class='article']/div[@class='txt-block'][h4='{$k}']{$v}";	
			$grabValue[$k] = $this->getValue($v, $xpath->query($xpathString));
		}
		
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
	 * Removes new lines in a string 
	 * @param $str The string to be processed
	 * @return $str the clean string
	 */
	protected function removeNewLines($str) {
		return str_replace("\n", "", $str);
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