<?php
	// try {
	// 	require_once '../includes/Autoloader.php';
	// 	PPTXenigma\Autoloader::register();
	// }
	// catch ( Exception $e ) {
	// 	die( 'PPTXenigma\Autoloader couldn\'t be loaded - <em>' . basename( __FILE__ ) . ' (' . __LINE__ . ')</em>' );
	// }

	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	// Compte nombre de fichiers
	// $tmp = glob( '../contents/exemples/exemple/ppt/notesSlides/*.xml' );

	// recupere le contenu du fichier
	// $tmp = file_get_contents( '../contents/exemples/exemple/ppt/notesSlides/notesSlide1.xml' );

	// Match le symbole de capture
	// preg_match( '/a:t(?:.*)>(.{1}(?:[^<]))<\/a:t/', $tmp, $export );
	// $symbole = $export[ 1 ];

	// Recuperer les autres slides
	// $tmp = file_get_contents( '../contents/exemples/exemple/ppt/notesSlides/notesSlide5.xml' );

	// Récupérer les occurences avec la voix off et la référence unique
	// preg_match_all( '/' . $symbole . '(?:.*?)\((?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\)\s(?:(?:<.*?)<a:t>|)([\d\w\_\-\.]+)(?:(?:<.*?)<a:t>|)\s:(.*?)' . $symbole . '/', $tmp, $datas );

	// Parcourire les enregistrements
	// foreach ( $datas[ 0 ] as $k=>$v ) {
	// 	echo'<div>
	// 		<p><b>Acteur :</b> ' . $datas[ 1 ][ $k ] . '</p>
	// 		<p><b>Référence :</b> ' . $datas[ 2 ][ $k ] . '</p>
	// 		<p><b>Texte :</b> ' . htmlspecialchars( $datas[ 3 ][ $k ] ) . '</p>
	// 	</div><hr>';
	// }

	// Récuperer tous les a:p
	// $tmp = explode( 'a:p><a:p', $datas[ 3 ][ 0 ] );
	// array_shift( $tmp );
	// array_pop( $tmp );

	// foreach ( $tmp as $value ) {
	// 	echo htmlspecialchars( $value ) . '<hr>'; // ICI ON DOIT TRAITER LES ELEMENTS
	// }

	$tmp = '><a:r><a:rPr lang="fr-FR" b="1" dirty="0" err="1" smtClean="0"/><a:t>Itaque</a:t></a:r><a:r><a:rPr lang="fr-FR" b="1" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" b="1" dirty="0" err="1" smtClean="0"/><a:t>tum</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" i="1" dirty="0" err="1" smtClean="0"/><a:t>Scaevola</a:t></a:r><a:r><a:rPr lang="fr-FR" i="1" dirty="0" smtClean="0"/><a:t> cum</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" u="sng" dirty="0" smtClean="0"/><a:t>in </a:t></a:r><a:r><a:rPr lang="fr-FR" u="sng" dirty="0" err="1" smtClean="0"/><a:t>eam</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" strike="sngStrike" baseline="0" dirty="0" err="1" smtClean="0"/><a:t>ipsam</a:t></a:r><a:r><a:rPr lang="fr-FR" strike="sngStrike" baseline="0" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" strike="sngStrike" baseline="0" dirty="0" err="1" smtClean="0"/><a:t>mentionem</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="30000" dirty="0" err="1" smtClean="0"/><a:t>incidisset</a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="30000" dirty="0" smtClean="0"/><a:t>, </a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="30000" dirty="0" err="1" smtClean="0"/><a:t>exposuit</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="-25000" dirty="0" err="1" smtClean="0"/><a:t>nobis</a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="-25000" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" baseline="-25000" dirty="0" err="1" smtClean="0"/><a:t>sermonem</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>Laeli</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> de </a:t></a:r><a:r><a:rPr lang="fr-FR" b="1" i="1" u="sng" strike="sngStrike" baseline="30000" dirty="0" err="1" smtClean="0"/><a:t>amicitia</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" b="1" i="1" u="sng" strike="sngStrike" baseline="-25000" dirty="0" err="1" smtClean="0"/><a:t>habitum</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> ab </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>illo</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>secum</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> et cum </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>altero</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>genero</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>, C. </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>Fannio</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>Marci</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>filio</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>, </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>paucis</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>diebus</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> post mortem </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>Africani</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>. </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>Eius</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>disputationis</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>sententias</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>memoriae</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>mandavi</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>, </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>quas</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> hoc libro </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>exposui</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>arbitratu</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>meo</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>; quasi </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>enim</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>ipsos</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>induxi</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>loquentes</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>, ne \'</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>inquam</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>\' et \'</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>inquit</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>\' </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>saepius</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>interponeretur</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>, </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>atque</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> ut </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>tamquam</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> a </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>praesentibus</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> coram </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>haberi</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>sermo</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t> </a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" err="1" smtClean="0"/><a:t>videretur</a:t></a:r><a:r><a:rPr lang="fr-FR" dirty="0" smtClean="0"/><a:t>.</a:t></a:r></';
	// Capture des éléments
	preg_match_all( '/<a:r(?:.*?)>(.*?)<a:t(?:.*?)>(.*?)<\/a:t><\/a:r>/', $tmp, $chars );

	echo'<pre>';var_dump( $chars );echo'</pre>';
	// echo'<pre>';var_dump( $datas );echo'</pre>';
?>
<style>pre { white-space: pre-wrap; word-wrap: break-word; }</style>
