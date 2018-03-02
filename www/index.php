<?php
	try {
		require_once '../includes/Autoloader.php';
		Autoloader::register();
	}
	catch ( Exception $e ) {
		die( 'Autoloader couldn\'t be loaded - <em>' . basename( __FILE__ ) . ' (Line: ' . __LINE__ . ')</em>' );
	}

	$config = new Core\Config();

	$extractor = new PPTXenigma\Extracts( [
		'fileName' => 'exemple',
		'location' => $config->getVar( 'dirName' ) . 'contents/exemples/',
		'dirTmp' => $config->getVar( 'dirName' ) . 'contents/tmp/'
	] );
	// $extractor->extractFiles();
	// echo '<pre>';var_dump( $extractor->voiceOverConverted );echo '</pre>';
	// echo '<hr><pre>';var_dump( $extractor->getVoiceOver() );echo '</pre>';
	// echo '<pre>';var_dump( $extractor->extractFiles()->getVoiceOver() );echo '</pre>';
	$data = $extractor->extractFiles()->getVoiceOver();
	foreach ( $data as $k => $v ) {
		echo '<h2>' . $k . '</h2>';
		foreach ( $v as $r => $t ) {
			echo '<h3>' . $r . '</h3>' . $t;
		}
	}
?>
<style>pre { white-space: pre-wrap; word-wrap: break-word; }</style>
