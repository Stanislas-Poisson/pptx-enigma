<?php
	try {
		require_once '../includes/Autoloader.php';
		PPTXenigma\Autoloader::register();
	}
	catch ( Exception $e ) {
		die( 'PPTXenigma\Autoloader couldn\'t be loaded - <em>' . basename( __FILE__ ) . ' (' . __LINE__ . ')</em>' );
	}
?>
