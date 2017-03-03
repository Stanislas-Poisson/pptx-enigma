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
		'voiceOver' => '/__SIGN__(?:.*?)\((?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\)\s(?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\s:(.*?)__SIGN__/',
		'lines' => 'a:p><a:p',
		'isList' => 'a:buFont'
	);

	/**
	 * The constructor of the class which make multiple check
	 * @param array $params The various transferable parameters
	 */
	public function __construct( $params = array() ) {
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$mtime = $mtime[ 1 ] + $mtime[ 0 ];
		$this->starttime = $mtime;

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
		$dir_iterator = new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS );
		$iterator = new \RecursiveIteratorIterator( $dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST );
		foreach( $iterator as $file ){
			$file->isDir() ? rmdir( $file ) : unlink( $file );
		}
		rmdir( $dir );
		unset( $dir_iterator, $iterator );

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
		if ( is_null( $this->sign ) ) {
			return $this->_regex[ $key ];
		}
		else{
			return str_replace( '__SIGN__', $this->sign, $this->_regex[ $key ] );
		}
	}

	private function browseVoiceOver() {
		for ( $i=2; $i <= $this->nbrSlides; $i++ ) {
			// Recuperer les autres slides
			$tmp = file_get_contents( $this->dirTmp . $this->fileName . '/' . $i . '.xml' );

			// Récupérer les occurences avec la voix off et la référence unique
			preg_match_all( $this->regex( 'voiceOver' ), $tmp, $data );

			if( !empty( $data[ 0 ] ) ) {
				$this->browseParagraphs( $data[ 3 ][ 0 ] );
			}
		}
	}

	private function browseParagraphs( $voicOver ) {
		// Separating paragraphes
		$tmp = explode( $this->regex( 'lines' ), $voicOver );

		// Remove the first and last entry ($this->sign)
		array_shift( $tmp );
		array_pop( $tmp );

		$content = '';
		foreach ( $tmp as $value ) {
			$content .= $this->convertParagraph( $value );
		}
		return $content;
	}

	private function convertParagraph( $paragraph ) {
		echo'<pre>';var_dump( $this->isList( $paragraph ) );echo'</pre>';
	}

	private function isList( $paragraph ) {
		return ( strpos( $paragraph, $this->regex( 'isList' ) ) ) ? true : false;
	}

	public function __destruct() {
		$mtime = microtime();
		$mtime = explode( ' ', $mtime );
		$mtime = $mtime[ 1 ] + $mtime[ 0 ];
		$endtime = $mtime;
		$totaltime = ( $endtime - $this->starttime );
		echo '<div>Executed in ' . $totaltime . ' seconds</div>';
	}
}
?>
