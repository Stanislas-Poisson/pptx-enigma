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
	$extractor->extractFiles();
	die();

	// Recuperer les autres slides
	$tmp = file_get_contents( '../contents/exemples/exemple/ppt/notesSlides/notesSlide5.xml' );

	// Récupérer les occurences avec la voix off et la référence unique
	preg_match_all( '/' . $symbole . '(?:.*?)\((?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\)\s(?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\s:(.*?)' . $symbole . '/', $tmp, $datas );

	// Parcourire les enregistrements
	// foreach ( array_keys( $datas[ 0 ] ) as $key ) {
	// 	echo'<div>
	// 		<p><b>Acteur :</b> ' . $datas[ 1 ][ $key ] . '</p>
	// 		<p><b>Référence :</b> ' . $datas[ 2 ][ $key ] . '</p>
	// 		<p><b>Texte :</b> ' . htmlspecialchars( $datas[ 3 ][ $key ] ) . '</p>
	// 	</div><hr>';
	// }

	// Récuperer tous les a:p
	$tmp = explode( 'a:p><a:p', $datas[ 3 ][ 0 ] );
	array_shift( $tmp );
	array_pop( $tmp );

	$listeActive = false;
	$level = null;
	$listeType = array();
	$ligne = array( 'begin'=> '', 'end' => '' );
	foreach ( $tmp as $value ) {
		// var_dump( $value );
		if( strpos( $value, 'a:buFont' ) !== false ){
			$listeActive = true;
			preg_match( '/lvl="([\d]+)"/', $value, $lvl );
			if( !empty( $lvl ) ) {
				if( $level < intval( $lvl[ 1 ] ) ) {}
				else if( $level > intval( $lvl[ 1 ] ) ) {
					echo '</li></' . $listeType[ $level ] . '></li>';
				}
				else{ echo '</li>'; }
				$level = intval( $lvl[ 1 ] );
			}
			else{
				$level = 0;
			}
			if( !isset( $listeType[ $level ] ) ){
				if( strpos( $value, 'buChar' ) !== false ){ $listeType[ $level ] = 'ul'; }
				else{ $listeType[ $level ] = 'ol'; }
				$ligne[ 'begin' ] = '<' . $listeType[ $level ] . '><li>';
				$ligne[ 'end' ] = '';
			}
			else{
				$ligne[ 'begin' ] = '...<li>';
				$ligne[ 'end' ] = '';
			}
		}
		else{
			continue;
			// if( $listeActive === true && $level !== null ){
			// 	for ( $i = $level; $i > 0 ; $i-- ) {
			// 		echo '</' . $listeType[ $i ] . '></li>';
			// 	}
			// 	echo '</li></' . $listeType[ 0 ] . '>';
			// 	$listeActive = false;
			// 	$level = null;
			// 	$listeType = array();
			// }
			$ligne[ 'begin' ] = '<p>';
			$ligne[ 'end' ] = '</p>';
		}
		echo $ligne[ 'begin' ];
		// continue;
		// Capture des éléments
		preg_match_all( '/<a:r(?:.*?)>(.*?)<a:t(?:.*?)>(.*?)<\/a:t><\/a:r>/', $value, $chars );

		// Parcourir les elements
		foreach ( array_keys( $chars[0] ) as $key ) {
			$begin = $end = $beginSpec = $endSpec = '';
			// echo'<pre>';var_dump( $chars[ 1 ][ $key ] );echo'</pre>';
			$styles = array(
				'b="1"' => 'b',
				'i="1"' => 'i',
				'u="sng"' => 'u',
				'strike="sngStrike"' => 's'
			);
			foreach ( $styles as $needle => $style ) {
				if( strpos( $chars[ 1 ][ $key ], $needle ) !== false ) {
					$begin .= '<' . $style . '>';
					$end = '</' . $style . '>' . $end;
				}
			}
			if( preg_match( '/baseline="([-\d]+)"/', $chars[ 1 ][ $key ], $spec ) != 0 ) {
				if( $spec[ 1 ] > 0 ) {
					$beginSpec = '<sup>';
					$endSpec = '</sup>';
				}
				else if( $spec[ 1 ] < 0 ) {
					$beginSpec = '<sub>';
					$endSpec = '</sub>';
				}
			}
			echo $begin . $beginSpec . $chars[ 2 ][ $key ] . $endSpec . $end;
		}
		echo $ligne[ 'end' ];
	}

	if( $listeActive === true && $level !== null ){
		for ( $i = $level; $i > 0 ; $i-- ) {
			echo '</' . $listeType[ $i ] . '></li>';
		}
		echo '</li></' . $listeType[ 0 ] . '>';
		$listeActive = false;
		$level = null;
		$listeType = array();
	}
	// echo'<pre>';var_dump( $chars );echo'</pre>';
	// echo'<pre>';var_dump( $datas );echo'</pre>';
?>
<style>pre { white-space: pre-wrap; word-wrap: break-word; }</style>
