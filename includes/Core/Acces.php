<?php
namespace Core;
class Acces{
	# Use of debug
	protected $debug = true;

	# project place
	protected $dirName = '/home/myworkflcv/laboratoires/pptx-enigma/';

	# Name of the website
	protected $siteName = 'PPTX-Enigma';

	# URI of project
	protected $uriApp = 'http://http://pptx-enigma.my-workflow.fr/';

	# Maintenance
	public $maintenance = false;

	public function __construct() {
	}

	public function urlAuth( $key = null ) {
		$l = [
			'base' => $this->uriApp,
			'static' => 'http://static.http://pptx-enigma.my-workflow.fr/'
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
