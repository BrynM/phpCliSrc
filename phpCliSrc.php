<?PHP
/**
* phpCliSrc.php - an informal command line framework for PHP in the form of a class
*
* About phpCliSrc - an informal command line framework for PHP
* 
* phpCliSrc is a framework for writing PHP command line utilities.
* There are advantages and disadvantages to using PHP for custom utilities.
* The biggest advantage is if you're creating utilities for an internet company already using PHP.
* Any engineer on staff should be able to inherit the code's legacy.
* This helps avoid having to hammer several utilites together to perform redundant tasks (making your admins happier) or hiring programmers that write code in languages outside of your company's scope.
*
* Additionally, the resulting utility will be ready to run on several platforms from a single codebase and build.
* If the platform can run PHP, it can run your utility though testing for Windows support is no longer carried out (see below).
* For webservers using the utility, there is no need to recompile or build installation packages.
*
* To use phpCliSrc, create a PHP file for your utility and include phpCliSrc.php. Extend the class phpCliSrc replacing the usr_*() functions with your own then instanciat your extended class.
* The built-in __construct() will automatically parse the command line and execute your code in usr_cliMain().
* Your new uyility can be put into a directory with phpCliSrc.phpand run using 'php -f UTILITYNAME.php' or by utilizing on of the the included command line wrappers just like it was a binary.
* The file phpCliSrc_example.php is a utility created in this manner. It is also the default utility for the command line wrapper files.
*
* Now for the downsides.
* <ul>
* <li>PHP is not the most efficient language for some tasks, such as handling very large arrays.
* PERL will beat it computationally on the command line.
* Perhaps one day in the future the PHP Group will catch up for these uses. Perhaps not.
* I'm not going to debate the worthiness of either for a task.
* I'm presenting this code as an option for people to use, not an absolute solution.</li>
* <li>Windows support is deprecated.
* I have no incentive to build in Windows support or even continue testing Windows support for the entire script actively.
* Sorry, but I just don't have much need for it anymore.
* If you run into a bug, let me know and I'll try to fix it, but I'm not looking for them.
* Thus, the new portions of this code such as dealing with daemons apply to *nix and OS X environments only.</li>
* </ul>
*
* <b>Version Info</b>
* <ul>
* <li>$Id: phpCliSrc.php 51 2007-10-15 05:34:52Z bryn $</li>
* </ul>
* @package phpCliSrc
* @author Bryn Mosher
* @filesource
* @see phpCliSrc_example.php
*/

// We used to keep this util away from web apache here by checking SERVER_SIGNATURE.
// Now there is a constant named ALLOW_CLI_HTTP which will allow use via http access (default FALSE).
// When run a web server is detected, the function phpCliSrc::is_http() will return true.

/**
* version number
* @var str $PHPCLISRC_VERSION
* @filesource
*/
define( "PHPCLISRC_VERSION", '1.2' );
/**
* build version number
* @var str $PHPCLISRC_BUILD
* @filesource
*/
define( "PHPCLISRC_BUILD", trim( trim( str_replace( "LastChangedRevision:", "", '$LastChangedRevision: 51 $'), '$' ) ) );
if ( ! defined( 'ALLOW_CLI_HTTP' ) ) {
	/**
	* Whether to allow access to this script via a web server. 
	* If you define this as TRUE before including this file, access via a web server will be allowed.
	* For some damn reason phpDocumentor ignores this doc block because a conditional surrounds it. F'en phpDoc.
	* @var bool $ALLOW_CLI_HTTP
	* @filesource
	*/
	define( 'ALLOW_CLI_HTTP', false );
}

/**
* Class version of phpCliSrc that can be included and extended
*
* @todo Remove max_execution_time stuff as it's no longer adhered to on the CLI
* @package phpCliSrc
* @filesource
* @see phpCliSrc_example.php
* @see phpCliSrc.php
*/
class phpCliSrc {
	/**
	* This is a container for the original command line argument count.
	* @var string $argc
	* @filesource
	* @see phpCliSrc::cmd_line()
	* @see phpCliSrc::cmd_args()
	*/
	private $argc = FALSE;
	/**
	* This is a container for the original command line array.
	* @var string $argv
	* @filesource
	* @see phpCliSrc::cmd_line()
	* @see phpCliSrc::cmd_args()
	*/
	private $argv = FALSE;
	/**
	* This is a container for the result of parsing the command line.
	* @var string $cmdout
	* @filesource
	* @see phpCliSrc::cmd_line()
	* @see phpCliSrc::cmd_args()
	*/
	public $cmdOut = FALSE;
	/**
	* Whether or not use of daemon functionality is allowed
	* @var int $daemonAllow
	* @filesource
	*/
	public $daemonAllow = FALSE;
	/**
	* Whether or not to use daemon functionality
	* @var int $daemonAllow
	* @filesource
	*/
	public $daemonEnabled = FALSE;
	/**
	* Whether or not daemon functionality has been requested by the user
	* @var int $daemonIzed
	* @filesource
	*/
	public $daemonIzed = FALSE;
	/**
	* Whether or not multiple daemons can be run at once.
	* Very inadvisable for network related daemons
	* @var int $daemonAllow
	* @filesource
	*/
	public $daemonNoClobber = FALSE;
	/**
	* Location of the daemon's pid file
	* @var int $daemonAllow
	* @filesource
	*/
	public $daemonPidFile = ".";
	/**
	* Debug mode for development
	* @var bool $debug
	* @filesource
	*/
	public $debug = FALSE;
	/**
	* Description of our utility for the help text
	* @var string $description
	* @filesource
	*/
	public $description = "This is where you put text describing your utility in the abstract. To enter descriptions for your command line arguments, see phpCliSrc::usr_cmd_args().";
	/**
	* Maximum execution time in seconds
	* @var int $maxExecutionTime
	* @filesource
	*/
	public $maxExecutionTime = 600;
	/**
	* Maximum memory usage in megabytes
	* @var int $maxMemory
	* @filesource
	*/
	public $maxMemory = "8M";
	/**
	* Maximum memory usage in bytes - gets set automatically
	* @var int $maxMemoryBytes
	* @filesource
	*/
	public $maxMemoryBytes = FALSE;
	/**
	* peak memory usage in megabytes
	* @var int $peakMemory
	* @filesource
	* @see phpCliSrc::memUse()
	*/
	private $peakMemory = FALSE;
	/**
	* Command line Quiet Mode
	* @var bool $quiet
	* @filesource
	* @see phpCliSrc::cmd_args()
	*/
	public $quiet = FALSE;
	/**
	* An array of our used command line parameters
	* @var array $usedParms
	* @filesource
	* @see phpCliSrc::cmd_args()
	* @see cmd_args()
	*/
	public $usedParms = array();
	/**
	* The name of our "command"
	*
	* We have to actually set this during __construct()
	* @var string $utilityName
	* @filesource
	*/
	public $utilityName = FALSE;
	/**
	* Command line Verbose console output
	* @var bool $verbose
	* @filesource
	* @see phpCliSrc::cmd_args()
	*/
	public $verbose = FALSE;

	/**
	* The phpCliSrc class constructor
	*
	* Upon construction, the command line arguments will be parsed and their appropriate code executed.
	* @filesource
	* @see phpCliSrc::cmd_args()
	* @see phpCliSrc::print_c()
	* @see phpCliSrc::cmdOut
	*/
	public function __construct() {
		// keep this utility away from web servers
		if ( !ALLOW_CLI_HTTP ) {
			if ( $this->is_http() ) {
				header( "HTTP/1.0 501 Not Implemented" );
				print "HTTP/1.0 501 Not Implemented";
				exit(501);
			}
		}
		// now we set $utilityName
		$this->utilityName = rtrim( basename( $_SERVER['SCRIPT_NAME'] ), ".php" );
		// set some surefire things that need to be done
		ini_set( "html_errors", 0 );
		ini_set( "display_errors", 1 );
		error_reporting( E_ALL );
		define( "MEMORY_LIMIT_NOT_IMPLEMENTED", "Not configured" );
		// Make sure we've got command line arguments
		if ( array_key_exists( 'argc', $_SERVER ) ){
			// The call to cmd_line sets a bunch of globals then either returns true or kills the application with an error.
			// We check it this way just in case something weird happens.
			// cmd_line() should return an array containing the original command line and a version with only the used command line parameters.
			if ( !$this->cmdOut = $this->cmd_line() ) {
				// A call to our console printing function handing it parameters for verbosity and error level
				phpCliSrc::print_c( "Could not parse arguments! Could not set command line.", -1, 1 );
				// By default, you should try to display the help text after erroring out.
				// Handing helpThem() a parameter tells it to exit after displaying the help with that exit value.
				$this->helpThem( 1 );
			} else if ( is_array( $this->cmdOut ) ) {
				// we've got a valid command line here, so lets set the max execution and max memory limits.
				ini_set( "max_execution_time", (int)$this->maxExecutionTime );
				$memLimit = phpCliSrc::setMaxMem( $this->maxMemory );
				if ( $memLimit == FALSE ) {
					phpCliSrc::print_c( "Format for max memory could not be read (\"" . $this->maxMemory . "\" returned \"" . var_export( $memLimit, 1 ) . "\").", -1, 1 );
					$this->helpThem( 1 );
				}
				$this->maxMemory = $memLimit;
				$this->maxMemoryBytes = $this->maxMemory == MEMORY_LIMIT_NOT_IMPLEMENTED ? FALSE : rtrim( phpCliSrc::convertSize( $this->maxMemory, "B" ), "B" );
				// Print the results of our call to cmd_line() if we are in verbose mode.
				if ( $this->verbose == TRUE ) {
					phpCliSrc::print_c( "Verbose mode enabled." . PHP_EOL );
				}
				phpCliSrc::print_c( "Command Line: " . $this->cmdOut[0] . PHP_EOL );
				phpCliSrc::print_c( "Used Parameters: " . $this->cmdOut[1] . PHP_EOL );
				phpCliSrc::print_c( "Max Memory: " . $this->maxMemory . PHP_EOL );
				phpCliSrc::print_c( "Max Execution Time: " . ini_get( "max_execution_time" ) . PHP_EOL );
				phpCliSrc::memUse();
				if ( method_exists( $this, "usr_cliMain" ) ) {
					phpCliSrc::print_c( "Starting main procedure." . PHP_EOL );
					$this->usr_cliMain();
				}
			} else {
				// The dreaded unknown happened.
				phpCliSrc::print_c( "Could not get arguments!", -1, 1 );
				$this->helpThem( 1 );
			}
		} else {
			// $_SERVER[['argc'] didn't exist
			phpCliSrc::print_c( "Could not get arguments! Arguments didn't exist.", -1, 1 );
			$this->helpThem( 1 );
		}
	
	} // end __construct()

	/**
	 * CLI Main Proc
	 *
	 * This is where you would start executing your own code.
	 * This is an empty method which you need to create in your extension of this class if you want to use it.
	 * It's contents are executed at the end of __construct() for automatic startup.
	 * {@source}
	 * @filesource
	 * @see phpCliSrc::__construct()
	 */
	public function usr_cliMain () {
	}

	/**
	 * Custom command line arguments
	 *
	 * This is an empty method which you need to create in your extension of this class if you want to use it.
	 * The result of this array is added to the default command line arguments to be usedin processing and displaying help text.
	 * {@source}
	 * @filesource
	 * @return array Returns an associated array of valid command line argument arrays. Each argument array consists of the following elements:
	 * <br>[0] PHP code that will initialize the argument for use or throw an error on bad input
	 * <br>[1] Help text usage example
	 * <br>[2] Help text for argument
	 * <br>[3] Optional additional required argument
	 * <br>[4] Whether to print the argument as an optional parameter even if it has an additional argument
	 * @see phpCliSrc::cmd_args()
	 */
	public function usr_cmd_args () {
		$retval = array();
		return $retVal;
	}

	/**
	* Used to announce a function call for verbose mode
	* @return string Information about location in application.
	* @param mixed $traceBack Can be a string message or can be passed full output of a debug_backtrace() operation (or FALSE) to generate function calling announcements
	*/
	public function announce( $announceMessage = FALSE, $toStdOut = TRUE ) {
		$retVal = FALSE;
		if ( $this->debug == TRUE ) {
			$thePlace = 1;
			if ( $announceMessage == FALSE ) {
				$announceMessage = "";
			}
			$traceBack = debug_backtrace();
			$calledBy = array_key_exists( 'function', $traceBack[$thePlace + 1] ) ? $traceBack[$thePlace + 1]['function'] . "()" : FALSE;
			$calledBy = ( $calledBy == TRUE ) && array_key_exists( 'class', $traceBack[$thePlace + 1] ) ? $traceBack[$thePlace + 1]['class'] . $traceBack[$thePlace + 1]['type'] . $calledBy : FALSE;
			$calledBy = ( $calledBy == TRUE ) ? " called by " . $calledBy : "";
			if ( $announceMessage == FALSE ) {
				$classIn = array_key_exists( 'class', $traceBack[$thePlace] ) ? $traceBack[$thePlace]['class'] . $traceBack[$thePlace]['type'] : "";
				$announcement = "**DEBUG: " . $classIn . $traceBack[$thePlace]['function'] . "() executed '" . basename( $traceBack[$thePlace]['file'] ) . "' LINE " . $traceBack[$thePlace]['line'] . $calledBy . PHP_EOL;
			} else {
				$announcement = "**DEBUG: " . $announceMessage . " ('" . basename( $traceBack[$thePlace]['file'] ) . "' LINE " . $traceBack[$thePlace]['line'] . $calledBy . ")" . PHP_EOL;
			}
			if ( $toStdOut == TRUE ) {
				phpCliSrc::print_c( $announcement );
			}
			$retVal = $announcement;
		}
		return $retVal;
	}

	/**
	 * Array of command line arguments to stored code and help text.
	 *
	 * A function is a handy container just in case someone wants to do more reactive things.
	 * Since this information is also only initially called from within cmd_line(), it won't stay persistant. 
	 * Thus it can grow to be as large as it needs without fear of eating RAM during other processing after startup.
	 * This is basically the same as storing this in a global variable and then calling unset to destroy that variable after use by exploiting the scope of a function.
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
	function cmd_args () {
		# each of these is an array of ( code to execute, usage, help text, required parm, optional even with parm )
		$retVal = array(
			"--debug" => array( ' // debug mode
				$this->usedParms[$parmCount] = $cmdParm;
				$this->debug = TRUE;
				',
					),
			"--daemonize" => array( ' // quiet mode
				$this->usedParms[$parmCount] = $cmdParm;
				$this->daemonIzed = TRUE;
				',
					"(--daemonize)",
					"Attempts to daemonize (run as a service) if supported.",
					),
			"-q" => array( ' // quiet mode
				$this->usedParms[$parmCount] = $cmdParm;
				$this->quiet = TRUE;
				',
					"(-q|--quiet)",
					"Quiet mode. Supresses console output of processed lines. Does not affect usage of the --verbose parameter.",
					),
			"-v" => array( ' // verbose mode
				$this->usedParms[$parmCount] = $cmdParm;
				$this->verbose = TRUE;
				',
					"(-v|--verbose)",
					"Verbose mode. Shows runtime details at console.",
					),
			"-h" => array( '# -h is used in cmd_line() as a switch example',
					"(-?|-h|--help)",
					"Display help text and exit." ),
			);
		// these are the long form equivalents of the above parameters
		$retVal["--quiet"] = array( $retVal["-q"][0] );
		$retVal["--verbose"] = array( $retVal["-v"][0] );
		$retVal["--h"] = array( $retVal["-h"][0] );
		$retVal["--help"] = array( $retVal["-h"][0] );
		$retVal["-help"] = array( $retVal["-h"][0] );
		$retVal["--?"] = array( $retVal["-h"][0] );
		$retVal["-?"] = array( $retVal["-h"][0] );
		// if you create your own usr_cmd_args() function much like this normal one that returns it's own array it will get added to this return value
		if ( method_exists( $this, "usr_cmd_args" ) ) {
			$moreArguments = $this->usr_cmd_args();
			$retVal = is_array( $moreArguments ) ? $retVal + $moreArguments : $retVal;
		}
		return $retVal;
	} // end cmd_args()
	

	/**
	* Convert HTTP parameters to argc and argv style values
	*
	* @filesource
	* @return array Returns an associated array containing argc and argv.
	* These are exactly like their $_SERVER counterparts.
	* @see phpCliSrc::cmd_line()
	* @see phpCliSrc::cmd_args()
	*/
	function cmd_args_from_http() {
		$retVal = FALSE;
		$newArgc = 0;
		$newArgv[$newArgc] = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];		
		foreach ( $_REQUEST as $viaHttpArg => $viaHttpVal ) {
			$newArgc++;
			$newArgv[$newArgc] = $viaHttpArg;
			if ( "$viaHttpVal" <> "" ) {
				$newArgc++;
				$newArgv[$newArgc] = $viaHttpVal;
			}
		}
		$retVal = array( 'argc' => $newArgc, 'argv' => $newArgv);
		return $retVal;
	}

	/**
	* Process the command line
	* @return array Returns an array containing the original command line and the accepted command line parameters
	* @filesource
	* @see phpCliSrc::cmd_args()
	* @see phpCliSrc::helpThem()
	*/
	public function cmd_line () {
		$valid = $this->cmd_args();
		$parmCount = 0;
		$retVal = FALSE;
		if ( $this->is_http() && ALLOW_CLI_HTTP ) {
			$theNewArgs = $this->cmd_args_from_http();
			$this->argc = $theNewArgs['argc'];
			$this->argv = $theNewArgs['argv'];
		} else {
			$this->argc = $_SERVER['argc'];
			$this->argv = $_SERVER['argv'];
		}
		foreach ( $this->argv as $cmdParm ) {
			switch ( $cmdParm ) { # for testing
				case "-?":
				case "--?":
				case "-h":
				case "--h":
				case "--help":
				case "-help":
					$this->helpThem();
					exit( 0 );
					break;
			}
			$lowParm = strtolower( $cmdParm );
			if ( isset( $valid[$lowParm] ) ) {
				eval( $valid[$lowParm][0]);
			}
			#catch report directves
			$parmCount++;
		}
		if ( count( $this->argc ) > 0 ) {
			$fullLine = implode( " ", $this->argv );
			$useLine = implode( " ", $this->usedParms );
			$retVal = array( $fullLine, $useLine );
		}
		return $retVal;
	} // end cmd_line()

	/**
	* Converts data sizes from one format to another
	*
	* Valid designation formats are
	* <ul>
	* <li>b, bt, bytes for various ersions of bytes</li>
	* <li>k, kb, kbytes, kilobytes, kilo-bytes for various versions of kilobytes</li>
	* <li>m, mb, mbytes, meg, megs, megabytes, mega-bytes forvarious versions of megabytes</li>
	* <li>g, gb, gbytes, gig, gigs, gigabytes, giga-bytes for various versions of gigabytes</li>
	* <li>t, tb, tbytes, terabytes, tera-bytes for various versions of terabytes</li>
	* </ul>
	* @param string $originalFormat the original formatted data size (ie: "10MB" or "5 terabytes").
	* Spaces and commas will be stripped out. If just an integer is passed bytes will be asumed.
	* Passing this parameter a NULL value will return the possible formats in an array.
	* @param string $newFormat New format of data size. If not given or format is not understood megabytes is asumed.
	* @param int $precision Decimal precision of calculations and output. The bytes format is always precision 0 and rounded up.
	* @param bool $useBaseTen Whether or not to convert sizes using bas ten and not base eight (1,000 instead of 1,024) much like hard drive companies do ;-).
	* Default is FALSE.
	* @return mixed Converted format or FALSE on failure. Passing NULL to $newFormat will convert to megabytes and return an integer
	* @filesource
	*/
	public function convertSize ( $originalFormat = FALSE, $newFormat = "MB", $precision = 2, $useBaseTen = FALSE ) {
		phpCliSrc::announce();
		$retVal = FALSE;
		$formats_b = array( "B", "BT", "BYTES" );
		$formats_kb = array( "K", "KB", "KILOBYTES", "KILO-BYTES" );
		$formats_mb = array( "M", "MB", "MBYTES", "MEG", "MEGS", "MEGABYTES", "MEGA-BYTES" );
		$formats_gb = array( "G", "GB", "GBYTES", "GIG", "GIGS", "GIGABYTES", "GIGA-BYTES" );
		$formats_tb = array( "T", "TB", "TBYTES", "TERABYTES", "TERA-BYTES" );
		if ( $originalFormat === NULL ) {
			return array_merge( $formats_b, $formats_kb, $formats_mb, $formats_gb, $formats_tb );
		}
		$precision = (int)$precision > 0 ? ceil( (int)$precision ) : 0;
		if ( $useBaseTen == TRUE ) {
			$divBase = 1000;
		} else {
			$divBase = 1024;
		}
		$newFormatClean = trim( strtoupper( $newFormat ) );
		$originalFormat = is_string( $originalFormat ) ? trim( $originalFormat ) : $originalFormat;
		$originalFormat = strstr( $originalFormat, "," ) ? str_replace( ",", "", $originalFormat ) : $originalFormat;
		$originalFormat = strstr( $originalFormat, " " ) ? str_replace( " ", "", $originalFormat ) : $originalFormat;
		$regex = implode( "|", array_merge( $formats_b, $formats_kb, $formats_mb, $formats_gb, $formats_tb ) );
		$compareSmallest = $precision > 0 ? (float)( "0." . str_repeat( 0, $precision - 1 ) . 1 ) : 1;
		if ( preg_match( "/^([0-9]{1,}\\.?[0-9]{0,})(" . $regex . "){0,1}$/i", $originalFormat, $grokdFormat ) ) {
			$origNumber = round( $grokdFormat[1], $precision );
			$origDesignation = array_key_exists( 2, $grokdFormat ) ? strtoupper( $grokdFormat[2] ) : "BYTES";
			if ( in_array( $origDesignation, $formats_kb ) ) {
				$newOrigNumber = round( $origNumber * 1024, $precision );
			} else if ( in_array( $origDesignation, $formats_mb ) ) {
				$newOrigNumber = round( ( $origNumber * 1024 ) * 1024, $precision );
			} else if ( in_array( $origDesignation, $formats_gb ) ) {
				$newOrigNumber = round( ( ( $origNumber * 1024 ) * 1024 ) * 1024, $precision );
			} else if ( in_array( $origDesignation, $formats_tb ) ) {
				$newOrigNumber = round( ( ( ( $origNumber * 1024 ) * 1024 ) * 1024 ) * 1024, $precision );
			} else {
				// default bytes
				$newOrigNumber = round( $origNumber, $precision );
			}
			if ( in_array( $newFormatClean, $formats_b ) ) {
				$retNum = ceil( $newOrigNumber );
				$retNum = $retNum > $compareSmallest ? $retNum : $compareSmallest;
				$retVal = $retNum . $newFormat;
			} else if ( in_array( $newFormatClean, $formats_kb ) ) {
				$retNum = round( $newOrigNumber / 1024, $precision );
				$retNum = $retNum > $compareSmallest ? $retNum : $compareSmallest;
				$retVal = $retNum . $newFormat;
			} else if ( in_array( $newFormatClean, $formats_gb ) ) {
				$retNum = round( ( ( $newOrigNumber / 1024 ) / 1024 ) / 1024, $precision );
				$retNum = $retNum > $compareSmallest ? $retNum : $compareSmallest;
				$retVal = $retNum . $newFormat;
			} else if ( in_array( $newFormatClean, $formats_tb ) ) {
				$retNum = round( ( ( ( $newOrigNumber * 1024 ) / 1024 ) / 1024 ) / 1024, $precision );
				$retNum = $retNum > $compareSmallest ? $retNum : $compareSmallest;
				$retVal = $retNum . $newFormat;
			} else {
				// default megabytes
				$retNum = round( ( $newOrigNumber / 1024 ) / 1024, $precision );
				$retNum = $retNum > $compareSmallest ? $retNum : $compareSmallest;
				$retVal = $newFormat == NULL ? $retNum : $retNum . $newFormat;
			}
		}
		return $retVal;
	} // end cmd_line()

	/**
	* Equivalent of C function
	* @return string Single character from STDIN
	*/
	public function getchar () {
		$retVal = FALSE;
		if ( defined( STDIN ) ) {
			$gottenChar = fgets( STDIN, 1 );
			if ( ( !$gottenChar === FALSE ) && is_string( $gottenChar ) ) {
				$retVal = $gottenChar;
			}
		}
		return $retVal;
	} // end getchar()

	/**
	* Gets writable temporary location
	*
	* get_temp() will try to write first to the system temporary locations then fall back to using it's installation directory.
	* when called, it will create a directory named as $_SERVER['PHP_SELF'] without the file's extension with _tmp appended to the end within the chosen temporary directory.
	* For example "foo.php" would have a temporary working directory of "foo_tmp".
	* If this directory already exists, get_temp() will then write and delete a test file to check writability.
	* @param bool $cleanUp Passing TRUE will cause get temp to skip writability tests and empty the contents of it's created temporary directory.
	* If the cleanup is successful or the directory is already empty, get_temp() will simply return TRUE.
	* @param bool $keepDirectory The default of this parameter is TRUE.
	* If $cleanUp is passed TRUE and $keepDirectory is also passed FALSE, the clean up will include removing the created temporary directory itself.
	* @return mixed Upon successful creation, will return a string containing the path of the temporary directory.
	* If performing a clean up of any kind, will return TRUE upon success.
	* If any of these fail, FALSE will be returned.
	*/
	public function get_temp( $cleanUp = FALSE, $keepDirectory = TRUE ) {
		phpCliSrc::announce();
		$retVal = FALSE;
		$tempDir = FALSE;
		$finalPath = FALSE;
		// one day this little sloppy if-whatever file writing bit will be replaced by sys_get_temp_dir() which only exists in CVS branches of PHP for now
		if ( !empty( $_ENV['TMP'] ) ) {
			$tempDir = realpath( $_ENV['TMP'] );
		} else if ( !empty( $_ENV['TMPDIR'] ) ) {
			$tempDir = realpath( $_ENV['TMPDIR'] );
		} else if ( !empty( $_ENV['TEMP'] ) ) {
			$tempDir = realpath( $_ENV['TEMP'] );
		} else if ( $tempFile = tempnam( NULL, $this->utilityName ) ) {
			if ( $tempFile ) {
				$tempDir = realpath( dirname( $tempFile ) );
				unlink( $tempFile );
			}
		} else {
			$tempDir = dirname( __FILE__ );
		}
		if ( $tempDir == TRUE ) {
			$tryPath = $tempDir . DIRECTORY_SEPARATOR . $this->utilityName . "_tmp";
			if ( !is_dir( $tryPath ) ) {
				if ( mkdir( $tryPath ) ) {
					$finalPath = $tryPath;
				}
			} else {
				$finalPath = $tryPath;
			}
		}
		if ( ( $cleanUp == FALSE ) && ( $finalPath == TRUE ) ) {
			if ( $tempFile = tempnam( $tryPath, $this->utilityName ) ) {
				$retVal = $finalPath;
				unlink( $tempFile );
			}
		} else if ( $finalPath == TRUE ) {
			if ( $keepDirectory === FALSE ) {
				if ( $this->removeDir( $finalPath ) ) {
					$retVal = TRUE;
				}
			} else {
				foreach ( glob( $finalPath . DIRECTORY_SEPARATOR . "*" ) as $removeThis ) {
					if ( is_file( $removeThis ) ) {
						unlink( $removeThis );
					} else if ( is_dir( $removeThis ) ) {
						$this->removeDir( $removeThis );
					}
				}
				if ( count( glob( $finalPath . DIRECTORY_SEPARATOR . "*" ) ) == 0 ) {
					$retVal = TRUE;
				}
			}
		}
		
		return $retVal;
	} // end get_temp()
	
	/**
	* Print help text and optionally exit
	* @param int $exitVal Optional exit code
	* @filesource
	* @see phpCliSrc::cmd_args()
	* @see phpCliSrc::cmd_line()
	*/
	public function helpThem ( $exitVal = FALSE ) {
		phpCliSrc::announce();
		$valid = $this->cmd_args();
		$options = "Options:" . PHP_EOL;
		$useLine = "";
		foreach ( $valid as $cArg ) {
			if ( array_key_exists( 1, $cArg ) ) {
				$text = "";
				if ( array_key_exists( 3, $cArg ) ) {
					$useLine .= ( array_key_exists( 4, $cArg ) ) ? " (" . $cArg[1] . " " . $cArg[3] . ")" : " [" . $cArg[1] . " " . $cArg[3] . "]";
				} else {
					$useLine .= " " . $cArg[1];
				}
				$optage = str_replace( "|", ", ", $cArg[1] );
				$optage = str_replace( array( "(", ")" ) , "", $optage );
				$optage = "  " . str_replace( array( "[", "]" ) , "", $optage );
				if ( array_key_exists( 2, $cArg ) ) {
					$text .= str_pad( $optage, 29, " ", STR_PAD_RIGHT );
					$firstLine = substr( $cArg[2], 0, strpos( wordwrap( $cArg[2], 50, PHP_EOL ) . PHP_EOL, PHP_EOL ) );
					$text .= $firstLine . PHP_EOL;
					$rest = str_replace( $firstLine, "",  $cArg[2] );
					if ( array_key_exists( 3, $cArg ) && is_string( $cArg[3] ) ) {
						$rest .= PHP_EOL . "Required Parameter: " . $cArg[3] . PHP_EOL;
					}
					if ( !( trim( $rest ) == "" ) ) {
						$doc = trim( wordwrap( $rest, 49, PHP_EOL ) );
						$textExp = explode( PHP_EOL, $doc );
						foreach( $textExp as $key => $textLine ) {
							$text .= str_repeat( " " , 31 ) . $textLine . PHP_EOL ;
						}
					}
				} else {
					$text .= str_pad( $optage, 29, " ", STR_PAD_RIGHT );
					$text .= "No documentation written." . PHP_EOL;
				}
				$options .= trim( $text, PHP_EOL ) . PHP_EOL;
			}
		}
		$cmdExt = array_key_exists( "OS", $_SERVER ) && stristr( $_SERVER["OS"], "windows" ) ? ".cmd" : "";
		if ( array_key_exists( "wpid", $_SERVER ) && !( $_SERVER['wpid'] == "" ) ) {
			$usage = wordwrap( "Wrapper Usage: " . $this->utilityName . $cmdExt . $useLine . PHP_EOL, 78, PHP_EOL . "  " );
			$finale = wordwrap( "Alternate Usage: This script can also be run directly from a shell with the CLI version of PHP." . PHP_EOL, 78, PHP_EOL . "  " );
			$finale .= wordwrap( "PHP CLI Usage: php -f " . basename( $_SERVER['SCRIPT_NAME'] ) . " --" . $useLine . PHP_EOL, 78, PHP_EOL . "  " );
		} else {
			$usage = wordwrap( "PHP CLI Usage: php -f " . basename( $_SERVER['SCRIPT_NAME'] ) . " --" . $useLine . PHP_EOL, 78, PHP_EOL . "  " );
			$finale = wordwrap( "Alternate Usage: This script can also be run with a command line or shell wrapper called '" . $this->utilityName . "'." . PHP_EOL, 78, PHP_EOL . "  " );
			$finale .= wordwrap( "Wrapper Usage: " . $this->utilityName . $cmdExt . $useLine . PHP_EOL, 78, PHP_EOL . "  " );
		}
		$finale .= wordwrap(	 "Built with phpCliSrc framework version " . PHPCLISRC_VERSION . " build " . PHPCLISRC_BUILD . " by Byn Mosher." . PHP_EOL, 78, PHP_EOL . "  " );
		if ( ALLOW_CLI_HTTP && $this->is_http() ) {
			print "<pre>";
		}
		print wordwrap( $this->utilityName . ": " . $this->description, 78, PHP_EOL . "  " ) . PHP_EOL;
		print $usage;
		print $options;
		if ( $finale ) {
			$finale = array_key_exists( "OS", $_SERVER ) && stristr( $_SERVER["OS"], "windows" ) ? rtrim( $finale ) : rtrim( $finale, PHP_EOL ) . PHP_EOL;
			print $finale;
		}
		if ( ALLOW_CLI_HTTP && $this->is_http() ) {
			print "</pre>";
		}
		if ( !$exitVal === FALSE ) {
			exit($exitVal);
		}
	} // end helpThem()

	/**
	* if this script is being run from a web server, this will return TRUE
	*
	* If for some reason PHP has compiled without --enable-memory-limit (sometimes Win32), this will do nothing.
	* @return mixed Converted format or FALSE on failure. Passing NULL to $newFormat will convert to megabytes and return an integer
	* @see phpCliSrc::allowCliHttp
	*/
	function is_http() {
		$retVal = false;
		if ( array_key_exists( 'SERVER_SIGNATURE', $_SERVER ) ) {
			$retVal = true;
		}
		return $retVal;
	}

	/**
	* Reports memory usage to user using print_c()
	*
	* If for some reason PHP has compiled without --enable-memory-limit (sometimes Win32), this will do nothing.
	* @see phpCliSrc::print_c()
	*/
	public function memUse () {
		if ( function_exists( "memory_get_usage" ) && !( memory_get_usage() == "" ) && ( $this->quiet == FALSE ) ) {
			$currentUsage = memory_get_usage();
			$this->peakMemory = $currentUsage > $this->peakMemory ? $currentUsage : $this->peakMemory;
			$percentage = $this->maxMemoryBytes === FALSE ? "" : " (" . round( ( $currentUsage / $this->maxMemoryBytes ) * 100, 2 ) . "%)";
			$percentagePeak = $this->maxMemoryBytes === FALSE ? "" : " (" . round( ( $this->peakMemory / $this->maxMemoryBytes ) * 100, 2 ) . "%)";
			$peakMemory = " - peak " . phpCliSrc::convertSize( $this->peakMemory . "B", "MB" ) . $percentagePeak . "";
			phpCliSrc::print_c( "Memory Usage: " . phpCliSrc::convertSize( $currentUsage . "B", "MB" ) . $percentage . $peakMemory . PHP_EOL);
		}
	} // end memUse()
	
	/**
	* Prints output to console
	*
	* This will print output to the console with some other options.
	* Despite the name, this is very different from console based printing in other programming languages.
	* If you wanted to add logging functionality to your utility, this might be a place to call your logging function as well.
	*
	* @param string $pri Text to print to console.
	* Note that if you want a newline you should add ". PHP_EOL" or similar to your string.
	* @param bool $verbose OPTIONAL Set verbosity of message. Passing -1 or TRUE will always print the message regardless of user verbosity.
	* @param int $errorPrint OPTIONAL If passed, will exit with the given exit value after printing the message
	* @return BOOL Returns TRUE if string was printed to console
	* @filesource
	*/
	public function print_c( $pri, $verbose = NULL, $errorPrint = FALSE, $isFatalCode = FALSE ) {
		$retVal = FALSE;
		if ( $verbose === NULL ) {
			$verbose = $this->verbose;
		}
		// windows seems to like putting an extra newline after we end.
		// this avoids it if you need to fix that
//		$pri = array_key_exists( "OS", $_SERVER ) && stristr( $_SERVER["OS"], "windows" ) ? $pri : rtrim( $pri, PHP_EOL ) . PHP_EOL;
		if ( $errorPrint == TRUE ) {
			$pri= PHP_EOL . "ERROR: " . $pri . PHP_EOL;
		}
		if ( ( $verbose == -1 ) or ( $verbose == TRUE ) ){ //error flag is -1 *always print*
			if ( ALLOW_CLI_HTTP && $this->is_http() ) {
				print nl2br( $pri );
			} else {
				print $pri;
			}
			$retVal = TRUE;
			if ( $isFatalCode == TRUE ) {
				exit( (int)$isFatalCode );
			}
		}
		return $retVal;
	} // end print_c()
	
	/**
	* Recursive Directory Removal
	*
	* Adapted from http://us3.php.net/manual/en/function.rmdir.php#70525 .
	* Removes a directory recursively
	*/
	public function removeDir( $removePath = FALSE ) {
		phpCliSrc::announce();
		$retVal = FALSE;
		if ( is_dir( $removePath ) ) {
			$removeHandle = opendir( $removePath );
		}
		while( $file = readdir( $removeHandle ) ) {
			if ( ( $file != "." ) && ( $file != ".." ) ) {
				if ( !is_dir( $removePath . DIRECTORY_SEPARATOR . $file ) ) {
					unlink( $removePath . DIRECTORY_SEPARATOR . $file );
				} else {
					$this->removeDir( $removePath . DIRECTORY_SEPARATOR . $file);
				}
			}
	    }
		closedir( $removeHandle );
		if ( rmdir( $removePath ) ) {
			$retVal = TRUE;
		}
		return $retVal;
	} // end removeDir

	/**
	* Set memory limit
	*
	* @see phpCliSrc::maxMemory
	*/
	public function setMaxMem( $memLimit = FALSE ) {
		phpCliSrc::announce();
		$retVal = FALSE;
		$setMem = FALSE;
		$memLimit = trim( $memLimit );
		if ( preg_match( "/^([0-9]{1,})([kmgb].*){0,1}$/i", $memLimit, $memFormat ) ) {
			$memFormat[2] = array_key_exists( 2, $memFormat ) && ( strlen( $memFormat[2] ) > 0 ) ? strtoupper( $memFormat[2] ) : FALSE;
			if ( substr( $memFormat[2], 0, 1 ) == "B" ) {
				$memFormat[2] = "";
				$retMem = $memFormat[1] . $memFormat[2];
				$setMem = rtrim( phpCliSrc::convertSize( $memFormat[1], "B" ), "B" );
			} else {
				$memFormat[2] = ( strlen( $memFormat[2] ) > 0 ) ? $memFormat[2] : "M";
				$setMem = rtrim( phpCliSrc::convertSize( $memFormat[1] . $memFormat[2], "B" ), "B" );
				$retMem = $memFormat[1] . $memFormat[2];
			}
			if ( ( $setMem == TRUE ) ) {
				ini_set( "memory_limit", $setMem );
				$retMem = ini_get( "memory_limit" );
				if ( strlen( $retMem ) >= 1 ) {
					$retVal = phpCliSrc::convertSize( $retMem, $memFormat[2] );
				} else {
					$retVal = MEMORY_LIMIT_NOT_IMPLEMENTED;
				}
			}
		}
		return $retVal;
	} // end setMaxMem

} // end class phpCliSrc

