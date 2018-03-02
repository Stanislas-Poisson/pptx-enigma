<?php
class Autoloader {
	/**
	 * The function at call to launch the autloload
	 */
	static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Function used by the spl_autoload_register
	 * @param  string $class the name of the class and filename
	 */
	static function autoload( $class ) {
		$class = str_replace( '\\', '/', $class);
		try {
			require '../includes/' . $class . '.php';
		}
		catch ( Exception $e ) {
			die( $class . ' couldn\'t be loaded - <em>' . basename( __FILE__ ) . ' (' . __LINE__ . ')</em>' );
		}
	}
}
?>
