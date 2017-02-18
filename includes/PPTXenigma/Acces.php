<?php
namespace PPTXenigma;
class Acces{
	# Use of debug
	protected $debug = true;

	# project place
	protected $direname = '/REAL/DIR/TO/PROJECT/';

	# Name of the website
	protected $siteName = 'PPTX-Enigma';

	# URI of project
	protected $uriApp = 'http://NDD.TLD/';

	# Maintenance
	public $maintenance = false;

	public function __construct() {
	}

	public function urlAuth( $key = null ) {
		$l = [
			'base' => $this->uriApp,
			'static' => 'http://static.NDD.TLD/'
		];
		if( ( $key !== null ) && array_key_exists( $key, $l ) ) {
			return $l[ $key ];
		}
		else {
			return $l;
		}
	}
}
?>
