<?php
namespace PPTXenigma;
class Extracts {
	/**
	 * The name of the file
	 * @var string
	 */
	private $fileName;
	/**
	 * The location of the file
	 * @var string
	 */
	private $location;
	/**
	 * The temporary directory for work on the file
	 * @var string
	 */
	private $dirTmp;
	/**
	 * The sign to catch
	 * @var string
	 */
	private $sign;

	/**
	 * The MIME TYPE which the file has to correspond to.
	 * @var string
	 */
	protected $mimeType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

	/**
	 * The MIME TYPE which the file has to correspond to.
	 * @var string
	 */
	protected $_regex = array(
		'sign' => '/a:t(?:.*)>(.{1}(?:[^<]))<\/a:t/',
		'voiceOver' => '/__SIGN__(?:.*?)\((.*?)\)(.*?)<\/a:p>(.*?)__SIGN__/',
		'clean' => '/(<(?:[^>])*>)/',
		'lines' => 'a:p><a:p',
		'isList' => 'a:buFont',
		'listType' => 'buChar',
		'listLvl' => '/lvl="([\d]+)"/',
		'partText' => '/<a:r(?:.*?)>(.*?)<a:t(?:.*?)>(.*?)<\/a:t><\/a:r>/',
		'baseline' => '/baseline="([-\d]+)"/'
	);

	/**
	 * Know if a liste exists to close it
	 * @var boolean
	 */
	protected $listActive = false;

	/**
	 * The level of the list. Default -1.
	 * @var integer
	 */
	protected $listNumber = -1;

	/**
	 * The order of the list for a good closing
	 * @var array
	 */
	protected $listOrder = array();

	/**
	 * The styles to convert
	 * @var array
	 */
	protected $styles = array(
		'b="1"' => 'b',
		'i="1"' => 'i',
		'u="sng"' => 'u',
		'strike="sngStrike"' => 's'
	);

	/**
	 * The data of all the voice over when it's converted
	 * The format is
	 * [ 'name_of_actor' ][ 'reference' ] = 'voicOver'
	 * @var array
	 */
	public $voiceOverConverted = array();
	public $aaa = false;

	/**
	 * The constructor of the class which make multiple check
	 * @param array $params The various transferable parameters
	 */
	public function __construct( $params = array() ) {

		// Set the params
		foreach( $params as $key => $value ) {
			$this->$key = $value;
		}

		// Check if the requiredparams was not set
		if ( is_null( $this->fileName ) && is_null( $this->location ) && is_null( $this->dirTmp ) ) {
			throw new \Exception( 'The required parameters: "fileName", "location" and "dirTmp" are not all supplied.' );
		}

		// Check if the pptx file exist
		if ( !is_file( $this->location . $this->fileName . '.pptx' ) ) {
			throw new \Exception( 'The file was not a PPTX extensions.' );
		}

		// Get the MIME Type of the pptx file and check if the pptx file is a real one
		$finfo = new \finfo();
		if ( $finfo->file( $this->location . $this->fileName . '.pptx', FILEINFO_MIME_TYPE ) != $this->mimeType ) {
			throw new \Exception( 'The file was not a Microsoft PowerPoint 2007+.' );
		}
		unset( $finfo );

		// Check is the dirTmp is writable
		if( !is_writable( $this->dirTmp ) ) {
			throw new \Exception( 'The dirTmp is not writable. Chmod 0700 is sufficient.' );
		}
	}

	/**
	 * The launcher, it while extract the notes of the pptx and launch the others functions to get all the datas
	 * @return $this
	 */
	public function extractFiles() {
		// Try to UnZip the pptx in a tmp directory
		try{
			$zip = new \ZipArchive;
			$zip->open( $this->location . $this->fileName . '.pptx' );
			$zip->extractTo( $this->dirTmp . $this->fileName . '/tmp' );
			$zip->close();
			unset( $zip );
		}
		catch( \Exceptions $e ) {
			die( 'The PPTX file can\'t be extract - <em>' . basename( __FILE__ ) . ' (Line: ' . __LINE__ . ')</em>' );
		}

		// Try to move all the XML files of the notes out of the tmp directory and remove it
		try{
			$tmp = glob( $this->dirTmp . $this->fileName . '/tmp/ppt/notesSlides/*.xml' );
			$this->nbrSlides = count( $tmp );
			$i = 1;
			foreach ( $tmp as $file ) {
				rename( $file, $this->dirTmp . $this->fileName . '/' . $i++ . '.xml' );
			}
			unset( $tmp, $i );

			// Remove the tmp directory
			$this->clearDirectory( $this->dirTmp . $this->fileName . '/tmp' );
		}
		catch( \Exceptions $e ) {
			die( 'The PPTX file can\'t be extract - <em>' . basename( __FILE__ ) . ' (Line: ' . __LINE__ . ')</em>' );
		}

		// Check if a sign has already been set
		if ( is_null( $this->sign ) ) {
			$this->catchSign();
		}

		// Browse slides
		$this->browseVoiceOver();

		// Return instance for chaining
		return $this;
	}

	/**
	 * Function to remove all the directory and itself
	 * @param  string $dir The directory to wipe out
	 * @return $this
	 */
	private function clearDirectory( $dir ) {

		// Create the recursive iterator whithout the dot or double dot
		$dir_iterator = new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS );

		// Create the iterator for the directory iterator in the mode Child first
		$iterator = new \RecursiveIteratorIterator( $dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST );

		// For all items
		foreach( $iterator as $file ){

			// Remove the item
			$file->isDir() ? rmdir( $file ) : unlink( $file );
		}

		// Remove the directory when it's whip
		rmdir( $dir );

		// Release the variables
		unset( $dir_iterator, $iterator );

		// Return instance for chaining
		return $this;
	}

	/**
	 * No sign has be set so we have to catch one
	 * @return $this
	 */
	private function catchSign() {
		// Try to catch the sign
		try{
			// Get the content of the first notes
			$tmp = file_get_contents( $this->dirTmp . $this->fileName . '/1.xml' );

			// Match le symbole de capture
			preg_match( $this->regex( 'sign' ), $tmp, $export );

			// Check if a sign has been catched
			if( empty( $export ) && !isset( $export[ 1 ] ) ) {
				throw new \Exception( 'No sign has been catched.' );
			}

			// Set the sign
			$this->setSign( $export[ 1 ] );
		}
		catch( \Exception $e ) {
			die( 'The sign can\'t be catch - <em>' . basename( __FILE__ ) . ' (Line: ' . __LINE__ . ')</em>' );
		}

		// Return instance for chaining
		return $this;
	}

	/**
	 * The possibility to change the sign
	 * @param string $sign The sign for the catch
	 * @return $this
	 */
	public function setSign( $sign ) {
		// Try to set the sign
		try{
			$this->sign = $sign;
		}
		catch( \Exception $e ) {
			die( 'The sign can\'t be set - <em>' . basename( __FILE__ ) . ' (Line: ' . __LINE__ . ')</em>' );
		}

		// Return instance for chaining
		return $this;
	}

	/**
	 * The possibility to get the sign
	 * @return string The sign for the catch
	 */
	public function getSign() {
		return $this->sign;
	}

	/**
	 * Return the regex asked
	 * @param  string $key The key ask
	 * @return string      The regex
	 */
	private function regex( $key ) {

		// If no sign return the regex
		if ( is_null( $this->sign ) ) {
			return $this->_regex[ $key ];
		}

		// If a sign was set return the regex with __SIGN__ replaced by $this->sign
		else{
			return str_replace( '__SIGN__', $this->sign, $this->_regex[ $key ] );
		}
	}

	/**
	 * Catch all the voice-over over the slides
	 */
	private function browseVoiceOver() {
		// For all the slides whithout the fire one (catch sign)
		for ( $i = 2; $i <= $this->nbrSlides; $i++ ) {
			// Recuperer les autres slides
			$tmp = file_get_contents( $this->dirTmp . $this->fileName . '/' . $i . '.xml' );

			// Récupérer les occurences avec la voix off et la référence unique
			preg_match_all( $this->regex( 'voiceOver' ), $tmp, $data );

			// If a data was captured
			if( !empty( $data[ 0 ] ) ) {

				// For all the data captured (multiple voice over in the same note)
				for ( $j = 0; $j < count( $data[ 0 ] ); $j++ ) {

					// Clean the variable from the xml
					$actor = preg_replace( $this->regex( 'clean' ), '', $data[ 1 ][ $j ] );
					$reference = preg_replace( $this->regex( 'clean' ), '', $data[ 2 ][ $j ] );

					// If the actor was not existing, create it
					if( !isset( $this->voiceOverConverted[ $actor ] ) ) {
						$this->voiceOverConverted[ $actor ] = array();
					}

					// If the reference was not already in the data, set it
					if( !isset( $this->voiceOverConverted[ $actor ][ $reference ] ) ) {
						$this->voiceOverConverted[ $actor ][ $reference ] = $this->browseParagraphs( $data[ 3 ][ $j ] );
					}

					// If the reference already exist, throw error (Not possible to have the same reference for the same actor)
					else{
						throw new \Exception( 'The reference ' . $reference . ' of the page ' . $i . ' for the voice-over ' . $actor . ' is already existing.' );
					}
				}
			}
		}
	}

	/**
	 * Catch all the paragraphes/listes-item
	 * @param  string $voicOver The voice-over to be worked
	 * @return string           The voice-over converted
	 */
	private function browseParagraphs( $voicOver ) {
		// Separating paragraphes
		$lines = explode( $this->regex( 'lines' ), $voicOver );

		// Remove the first and last entry ($this->sign)
		array_shift( $lines );
		array_pop( $lines );

		// Set the var to empty string
		$content = '';

		// For all items converte it and stored in the previous variable
		foreach ( $lines as $value ) {
			$content .= $this->convertParagraph( $value );
		}

		// Return the voice-over converted and closed
		return $content . $this->closeList();
	}

	/**
	 * The convertor of each lines for list/paragraphe
	 * @param  string $paragraph The item which has been to convert
	 * @return string            The item converted
	 */
	private function convertParagraph( $paragraph ) {

		// Convert the item
		$newText = $this->convertStyle( $paragraph );

		// If the item is not empty and it's a liste
		if ( $newText != '' && $this->isList( $paragraph ) ) {

			// Set the listeActive at true
			$this->listActive = true;

			// Get the liste details
			$listDetails = $this->getListDetails( $paragraph );

			// If the details direction is true (move up)
			if( $listDetails[ 'direction' ] === true ) {
				// Add new balise and item
				$text = '<' . $listDetails[ 'type' ] . '><li>' . $newText;

				// Set the details in variables
				$this->listNumber = $listDetails[ 'lvl' ];
				$this->listOrder[ $listDetails[ 'lvl' ] ] = $listDetails[ 'type' ];
			}
			// Else if the details direction is false (move down)
			else if( $listDetails[ 'direction' ] === false ) {

				// Close the balise opened and add new item
				$text = $this->closeList( $listDetails[ 'move' ] ) . '<li>' . $newText;

				// Set the details in variables
				$this->listNumber = $listDetails[ 'lvl' ];
				$this->listOrder[ $listDetails[ 'lvl' ] ] = $listDetails[ 'type' ];
			}

			// Else add new item
			else{
				$text = '</li><li>' . $newText;
			}
		}

		// Else if the text is not a empty and not a liste
		else if( $newText != '' ) {
			// Closed the previous liste if it was and add the new paragraphe
			$text = $this->closeList() . '<p>' . $newText . '</p>';
		}

		// Else only close the liste if it was
		else {
			$text = $this->closeList();
		}

		// Return the item converted
		return $text;
	}

	/**
	 * Test if the item is a list or a paragraph
	 * @param  string  $paragraph The item to test
	 * @return boolean            Return true if it's a list, false for a paragraph
	 */
	private function isList( $paragraph ) {
		return ( strpos( $paragraph, $this->regex( 'isList' ) ) !== false ) ? true : false;
	}

	/**
	 * Get the details of the list item to compar it after
	 * @param  string $paragraph The item list to work on
	 * @return array             The details of the item
	 */
	private function getListDetails( $paragraph ) {
		// Initialise the variable
		$listDetails = array();

		// Set the type ol/ul of the liste
		$listDetails[ 'type' ] = ( strpos( $paragraph, $this->regex( 'listType' ) ) !== false ) ? 'ul' : 'ol';

		// Set the lvl of the the liste
		preg_match( $this->regex( 'listLvl' ), $paragraph, $_lvl );
		$listDetails[ 'lvl' ] = ( !empty( $_lvl ) ) ? intval( $_lvl[ 1 ] ) : 0;

		// Set the direction up/down/same of the liste
		$listDetails[ 'direction' ] = null;
		if ( $listDetails[ 'lvl' ] > $this->listNumber ) { $listDetails[ 'direction' ] = true; }
		else if ( $listDetails[ 'lvl' ] < $this->listNumber ) { $listDetails[ 'direction' ] = false; }

		// Set if the liste has to move
		$listDetails[ 'move' ] = ( ( !is_null( $listDetails[ 'direction' ] ) ) ? ( $listDetails[ 'lvl' ] - $this->listNumber ) : 0 );

		// Set the detail of the switch
		$nbrOrder = count( $this->listOrder ) - 1;
		$nbrOrder = ( ( $nbrOrder < 0 ) ? 0 : $nbrOrder );

		$listDetails[ 'switch' ] =  ( ( !empty( $this->listOrder ) && $this->listOrder[ $nbrOrder ] !== $listDetails[ 'type' ] ) ? true : false);

		// Return the array
		return $listDetails;
	}

	/**
	 * Closes the number or all the opened liste
	 * @param  int    $nbr The number of list to close
	 * @return string      The text of the closed elements
	 */
	private function closeList( $nbr = null ) {
		// Initialise the variable
		$text = '';

		// If the listeActive is set at true
		if ( $this->listActive ) {
			// We reverse the order off all the liste to close it easier
			$reverseOrder = array_reverse( $this->listOrder );

			// If the variable nbr is not set we closed all the items
			if( $nbr === null ) {

				// For each item, close the liste
				foreach ( $reverseOrder as $key => $val ) {
					$text .= '</li></' . $val . '>';

					// remove the last item in the list order
					array_pop( $this->listOrder );
				}

				// Set the variables at their original states
				$this->reinitaliseDataListe();
			}
			else {
				// Initalise the variable
				$i = 0;

				// Initialmise with the positive integer
				$nbr = abs( $nbr );

				// For each item, close the liste
				foreach ( $reverseOrder as $val ) {
					$text .= '</li></' . $val . '>';

					// remove the last item in the list order
					array_pop( $this->listOrder );

					// If the loop was the nbr, break it
					if( $nbr == ++$i ) {
						break;
					}
				}

				// If the list is empty
				if( empty( $this->listOrder ) ) {
					// Set the variables at their original states
					$this->reinitaliseDataListe();
				}
			}
		}

		// Return the text for the closeListe
		return $text;
	}

	/**
	 * Set the class variables at their original states
	 */
	private function reinitaliseDataListe() {
		$this->listActive = false;
		$this->listNumber = -1;
		$this->listOrder = array();
	}

	/**
	 * Converted the XML item into an html item for the styles
	 * @param  string $paragraph The paragraphe to convert
	 * @return string            the paragraphe converted
	 */
	private function convertStyle( $paragraph ) {

		// Initialise the variable
		$tmp = '';

		// Get the details style and text in the XML
		preg_match_all( $this->regex( 'partText' ), $paragraph, $chars );

		// For all the text captured
		foreach ( array_keys( $chars[0] ) as $key ) {
			// Initialise the variables
			$begin = $end = $beginSpec = $endSpec = '';

			// For all the style capturable
			foreach ( $this->styles as $needle => $style ) {
				// If the style is set
				if( strpos( $chars[ 1 ][ $key ], $needle ) !== false ) {
					// Store the balises
					$begin .= '<' . $style . '>';
					$end = '</' . $style . '>' . $end;
				}
			}

			// If the baseline is set
			if( preg_match( $this->regex( 'baseline' ), $chars[ 1 ][ $key ], $spec ) != 0 ) {
				// If the baseline is strictly greater than 0 store the balise
				if( $spec[ 1 ] > 0 ) {
					$beginSpec = '<sup>'; $endSpec = '</sup>';
				}
				// Else if the baseline is strictly less than 0 store the balise
				else if( $spec[ 1 ] < 0 ) {
					$beginSpec = '<sub>'; $endSpec = '</sub>';
				}
			}

			// Store the balise and the text
			$tmp .= $begin . $beginSpec . $chars[ 2 ][ $key ] . $endSpec . $end;
		}

		// Return the styliched text
		return $tmp;
	}

	/**
	 * Get the voic over captured and converted
	 * in the ascendante order by the key
	 * @return array the voic over reorder by keys
	 */
	public function getVoiceOver() {
		$tmp = $this->voiceOverConverted;
		ksort( $tmp, SORT_STRING );
		return $tmp;
	}

	/**
	 * The destructor of the class
	 */
	public function __destruct() {}
}
?>
