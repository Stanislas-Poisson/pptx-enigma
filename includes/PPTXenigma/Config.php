<?php
namespace PPTXenigma;
##########
# Configuration
# I   - Var, init & close
# II  - Acces and validation
##########
class Config extends Acces {
	##############################
	# I - Var, init & close #
	##############################
	/**
	 * HTMl compressed Y/n
	 * @var boolean Compression[true] No-compression[false]
	 */
	private $compression = true;
	/**
	 * Production type
	 * @var boolean Developement[true] Production[false]
	 */
	private $prodType = true;

	public function __construct( $params = null ) {

		# Show error in dev
		if( $this->prodType ) {
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(E_ALL);
		}

		# Params for SESSION
		session_save_path( $this->direname . 'sessions/' );
		ini_set( 'session.gc_probability', '1' );
		ini_set( 'session.gc_divisor', '100' );
		ini_set( 'session.gc_maxlifetime', 7200 );

		header( 'Content-Type: text/html; charset=utf-8' );

		if( isset( $_POST[ 'uuid' ] ) && !empty( trim( $_POST[ 'uuid' ] ) ) ) {
			session_id( trim( $_POST[ 'uuid' ] ) );
		}
		session_start();

		ob_start();
	}

	public function __destruct() {
		$tmp = ob_get_contents();
		ob_end_clean();

		if( $this->compression ) {
			# Cleaning
			# Delete comments
			$tmp = preg_replace( '/(<!--(.*)-->)/', '', $tmp );
			# Delete tabs
			$tmp = preg_replace( '/[\t]+/', '', $tmp );
			# Delete line break
			$tmp = preg_replace( '/[\n\r]+/', '', $tmp );
			# Delete spaces more than or equal at two
			$tmp = preg_replace( '/[^Ã]([\s]{2,})/', '', $tmp );
			# Delete span betwen html tag
			$tmp = preg_replace( '/> </', '><', $tmp );
		}

		# Show code
		echo $tmp;
	}

	##############################
	# II - Acces and validation  #
	##############################

	/**
	 * Token validation
	 * @param  string $getToken  Token from the form
	 * @param  string $nameToken Token name
	 * @return boolean           True if good
	 */
	public function validToken( $getToken, $nameToken = 'token' ) {
		if( !isset( $_SESSION[ $nameToken ] ) ) { return false; }
		return ( $getToken == $_SESSION[ $nameToken ] ) ? true : false;
	}

	/**
	 * Creat toekn
	 * @param  string $nameToken Token name [default=token]
	 * @return string            The token
	 */
	public function token( $nameToken = 'token' ) {
		$tmp = sha1( time() * rand( 1, 10 ) );
		$_SESSION[ $nameToken ] = $tmp;
		return $tmp;
	}

	public function getVar( $var ) {
		return $this->$var;
	}
}
?>
