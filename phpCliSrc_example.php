<?PHP
/**
* phpCliSrc_example.php - An example of an informal command line framework for PHP in the form of a class by extending the class (whew!)
* 
* This file is an example utility which will print help text, perform in verbose mode and take the parameter --extra as a string of text to report.
*
* <b>Version Info</b>
* <ul>
* <li>$Id: phpCliSrc_example.php 51 2007-10-15 05:34:52Z bryn $</li>
* <li>$HeadURL: svn+ssh://bryn@dev.brynmosher.com/subversion/phpCliSrc/trunk/phpCliSrc_example.php $</li>
* </ul>
* 
* @package phpCliSrc
* @subpackage phpCliSrc_examples
* @author Bryn Mosher
* @filesource
*/

define( 'ALLOW_CLI_HTTP', TRUE );

/**
* Require our original class file
* @filesource
*/
require_once( dirname( __FILE__ ) . "/phpCliSrc.php" );

/**
* Example of extending the phpCliSrc class with your own functionality
* {@inheritdoc }
* @package phpCliSrc
* @subpackage phpCliSrc_examples
* @see phpCliSrc
* @filesource
*/
class phpCliSrcMine extends phpCliSrc {

	/**
	* Some example text to print from our command line parameters
	* @var string $example
	* @filesource
	* @see $GLOBALS['example']
	* @see phpCliSrcMine::usr_cmd_args()
	*/
	public $example = FALSE;

	/**
	* An example task to perform
	*
	* This is where you would start executing your own code.
	* We use usr_cliMain in this example to signify that it should start running at the end of __construct()
	* {@source}
	* @filesource
	* @see phpCliSrc::__construct()
	*/
	public function usr_cliMain () {
		// announces function call in debug mode
		phpCliSrc::announce();
		$retVal = FALSE;
		$this->print_c( "Performing task..." . PHP_EOL, -1 );
		if ( !( $this->example === FALSE ) ) {
			$this->memUse();
			$this->print_c( "The example was '" . $this->example . "'." . PHP_EOL, -1 );
			$retVal = TRUE;
			$this->memUse();
		}
		return $retVal;
	}

	/**
	 * An example of creating a usr_cmd_args() function to create your own command line arguments
	 *
	 * A function is a handy container just in case someone wants to do more reactive things.
	 * Since this information is also only initially called from within cmd_line(), it won't stay persistant. 
	 * Thus it can grow to be as large as it needs without fear of eating RAM during other processing after startup.
	 * This is basically the same as storing this in a global variable and then calling unset to destroy that variable after use by exploiting the scope of a function.
	 * As long as you don't overwrite __construct() this will automatically be called - set it and forget it.
	 *
	 * @return array Returns an associated array of valid command line argument arrays. Each argument array consists of the following elements:
	 * <br>[0] PHP code that will initialize the argument for use or throw an error on bad input
	 * <br>[1] Help text usage example
	 * <br>[2] Help text for argument
	 * <br>[3] Optional additional required argument
	 * <br>[4] Whether to print the argument as an optional parameter even if it has an additional argument
	 * @filesource
	 * @see phpCliSrc::cmd_line()
	 * @see phpCliSrc::helpThem()
	 */
	public function usr_cmd_args () {
		// announces function call in debug mode
		phpCliSrc::announce();
		/*
			We return an array of values to set up our command line options.
		*/
		$retVal = array(
			"-e" => array( ' // example text parameter
				if ( array_key_exists( $parmCount + 1, $this->argv ) ) {
					$this->usedParms[$parmCount] = $cmdParm . " " . $this->argv[$parmCount + 1];
					$this->example = trim( $this->argv[$parmCount + 1] );
					if ( !is_string( $this->example ) ) {
						$this->example = FALSE;
						$this->print_c( "Bad parameter passed to " . $cmdParm . " (" . $this->argv[$parmCount + 1] . ").", -1, 1 );
						$this->helpThem(1);
					}
				} else {
					$badParm = ( array_key_exists( $parmCount + 1, $this->argv ) ) ? $this->argv[$parmCount + 1] : "*EMPTY*";
					$this->print_c( "Bad parameter passed to " . $cmdParm . " (" . $badParm . "). Bad log file to parse.", -1, 1 );
					$this->helpThem(1);
				}',
					"[-e|--example]",
					"Some example text to print as a parameter.",
					"text" ),
			);
		$retVal["--example"] = array( $retVal["-e"][0] );
		return $retVal;
	}
}

/**
* Initiate our class
*
* This will parse the command line arguments and react according to them by running phpCliSrc::usr_cliMain()
* @filesource
* {@source}
*/
$GLOBALS['commandLineClass'] = new phpCliSrcMine;

?>