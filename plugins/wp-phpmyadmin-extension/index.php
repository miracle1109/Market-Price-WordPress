<?php
/*
 * Plugin Name:		WP phpMyAdmin
 * Description:		The famous database browser & manager (for MySQL & MariaDB) - use it inside WordPress Dashboard without an extra hassle.
 * Text Domain:		wp-phpmyadmin-extension
 * Domain Path:		/languages
 * Version:		5.1.1
 * WordPress URI:	https://wordpress.org/plugins/wp-phpmyadmin-extension/
 * Plugin URI:		https://puvox.software/software/wordpress-plugins/?plugin=wp-phpmyadmin-extension
 * Contributors: 	puvoxsoftware,ttodua
 * Author:		Puvox.software
 * Author URI:		https://puvox.software/
 * Donate Link:		https://paypal.me/Puvox
 * License:		GPL-3.0
 * License URI:		https://www.gnu.org/licenses/gpl-3.0.html
 
 * @copyright:		Puvox.software
*/

declare(strict_types=1);
namespace WpPhpMyAdminExtension
{
  if (!defined('ABSPATH')) exit;
  require_once( __DIR__."/library_default_puvox.php" );
  
  if (!trait_exists( '\\'.__NAMESPACE__.'\\PluginClass_extension')){
	trait PluginClass_extension{}
  }


  class PluginClass extends \Puvox\default_plugin
  {
	use PluginClass_extension;

	protected $required_version = "7.2.5";

	public function declare_settings()
	{
		$this->initial_static_options	=
		[
			'has_pro_version'		=>0, 
			'show_opts'				=>true, 
			'show_rating_message'	=>true, 
			'show_donation_popup'	=>true, 
			'display_tabs'			=>[],
			'required_role'			=>'install_plugins', 
			'default_managed'		=>'network',			// network | singlesite
			'menu_button_level'		=>'mainmenu', 
			"menu_icon"				=>$this->helpers->baseURL.'/assets/media/menu_icon.png" style="width:30px;',   
			'menu_button_name'		=>'WP-phpMyAdmin' 
		];
	
		$this->initial_user_options	= 
		[		
			'randomCookieName'		=> "pma_".$this->helpers->randomString(16), 
			'randomCookieValue'		=> "pma_".$this->helpers->randomString(16), 
			'RandomFolderSuffix'	=> "_".$this->helpers->randomString(23), 
			'manual_pma_login_url'	=> '',
			'require_ip'			=> true,
			'hide_phma_errors'		=> false,
			'strip_slashes'			=> true,
			'use_https'				=> false,
			'is_localhost'			=> $this->helpers->is_localhost
		];
		
		$this->is_new_php = $this->helpers->above_version($this->required_version);
	}
 
	//by default, this will is disabled per requirements. Users can enable only for themselves.
	private $pma_installed_using_ajax = false;
	
	public function __construct_my()
	{
		add_action('init', [$this, 'setup_files'], 2 ); 
	}

	// ============================================================================================================== //
	// ============================================================================================================== //

	public function is_writable(){
		if ( ! is_writable(dirname(__DIR__)))
		{
			return false; 
		}
		return true;
	}

	public function setup_files()
	{
		if ( ! $this->is_writable() ) 
			return;

		// Don't save in DB table ! this is for multi-instance installations
		if ( file_exists($a=__DIR__.'/lib/name.php') )
		{
			$suffix = str_replace('<?php //','', file_get_contents($a));
		}
		else
		{
			$suffix = $this->opts['RandomFolderSuffix'];
			file_put_contents($a, '<?php //'.$suffix);
		}

		$this->lib_relpath			= '/lib';    
		$this->lib_absPath			= __DIR__ . $this->lib_relpath;      
		$this->pma_name				= "phpMyAdmin". $suffix;   
		$this->pma_name_real		= "phpMyAdmin"; 
		$this->pma_relpath			= $this->lib_relpath . '/'. $this->pma_name;
		$this->pma_relpath_real		= $this->lib_relpath . '/'. $this->pma_name_real;
		$this->pma_abspath			= __DIR__ . $this->pma_relpath;     
		$this->pma_abspath_real		= __DIR__ . $this->pma_relpath_real;
		 
		$this->pma_mainpage_from_plugin	= $this->pma_relpath.'/index.php' ;
		$this->pma_mainpage_url		= $this->helpers->baseURL	. substr($this->pma_mainpage_from_plugin,1);
		$this->pma_mainpage_path	= $this->pma_abspath	. '/index.php';
		
		$this->pma_zipPath			= $this->lib_absPath	. '/phpMyAdmin.zip'; 
		$this->pma_sessionfile		= $this->pma_abspath	. '/_session_temp.php'; 
		$this->pma_sessionAllowfile	= $this->pma_abspath	. '/_session_temp_allow.php';
		$this->pma_sessionDbfile	= $this->pma_abspath	. '/_session_temp_db_name_'.$_SERVER['HTTP_HOST'].'.php';
		$this->path_to_pma_config	= $this->pma_abspath	. '/config.inc.php';
		$this->path_to_def_config	= __DIR__ . '/default_config.php';
		$this->path_to_pma_common	= $this->pma_abspath	. '/libraries/common.inc.php';
		$this->path_to_def_common	= __DIR__ . '/default_common_inc_code.php';
		//deleted targets //details: https://goo.gl/tCWdEv
		$this->pma_delete_dirs		= ['/vendor/tecnickcom/tcpdf', '/locale', '/themes/original', '/doc', '/setup', '/examples', '/install', '/js/vendor/openlayers' ];	  //vendor\phpmyadmin\sql-parser\locale
		$this->pma_create_files		= ['/vendor/tecnickcom/tcpdf/tcpdf.php'];	
		$this->conflict_file_1		= $this->pma_abspath . '/vendor/phpmyadmin/motranslator/src/functions.php';
		$this->old_pma_zip			= 'https://files.phpmyadmin.net/phpMyAdmin/4.9.4/phpMyAdmin-4.9.4-all-languages.zip';
		
		$this->if_redirect_to_pma();
	}
		
	private function getPMA_FolderName(){
		$x = glob($this->lib_absPath.'/phpMyAdmin*',  GLOB_ONLYDIR);  return (!empty($x) ? $x[0] : "");
	}

	private function initialize_unpacked_pma()
	{
		if( is_dir($this->pma_abspath_real) && !is_dir($this->pma_abspath) )
		{
			// avoid simultaneous re-creations caused by WP load from other instances
			$this->lockFile=__DIR__."/install_lock.txt";
			$this->locker	= fopen( $this->lockFile, "w+"); 
			if (flock($this->locker,LOCK_EX))  
			{ 
				if( is_dir($this->pma_abspath_real) && !is_dir($this->pma_abspath) ) //check again after LOCK 
				{
					$this->helpers->try_increase_exec_time(120);

					//rename files
					$dir = $this->pma_abspath_real; // $this->getPMA_FolderName();
					if(!empty($dir) && !rename($dir, $this->pma_abspath )) {
						exit(__('Failure: can\'t rename <code>'.$dir.'</code> to <code>'.$this->pma_abspath.'</code>. Either do it manually from FTP, or try completely re-install the plugin.', 'wp-phpmyadmin-extension') );
						usleep(500000);
					}
					
					// delete extra directories
					foreach($this->pma_delete_dirs as $eachDir){
						$fullPath = $this->pma_abspath.'/'.$eachDir.'/';
						if( is_dir($fullPath) ){
							$this->helpers->rmdir_recursive($fullPath);
						}
					}
					// create extra directories & files
					foreach($this->pma_create_files as $eachFile){
						$file = $this->pma_abspath.'/'.$eachFile; $this->helpers->mkdir( dirname($file) );
						file_put_contents($file,""); 
					}


					// create config
					if(is_admin())
					{
						$force =  false;
						if( $this->helpers->is_localhost != $this->opts['is_localhost']){
							$force =  true;
							$this->opts['is_localhost']=$this->helpers->is_localhost;
							$this->update_opts();
						}
						// MY NOTE: config.inc.php should alwyas be in pma folder 
						// include_once( dirname( dirname(dirname( dirname(__DIR__) ) ) ).'/wp_phpmyadmin_config.inc.php' );
						if(!file_exists($this->path_to_pma_config) || $force)
						{
							$content = file_get_contents($this->path_to_def_config);
							$content = str_replace('___ALLOWNOPASS___',			($this->helpers->is_localhost ? "true" : "false"),				$content);
							$content = str_replace('___BLOWFISHSECRET___',		'\''. addslashes($this->create_blowfish_secret()).'\'',	$content);
							$content = str_replace('___LANG___',				'\''.$this->static_settings['lang'].'\'',				$content);
							$content = str_replace('___DBARRAY___',				'[file_get_contents(__DIR__."/_session_temp_db_name_".$_SERVER["HTTP_HOST"].".php")]',	$content);   //DB_NAME //$_COOKIE["pma_DB_NAME"]
										$path_to_index =str_replace( $this->helpers->domain,'', $this->helpers->OneSlash($this->helpers->baseURL . $this->pma_relpath))."/";
							$content = str_replace('___PmaAbsoluteUri___',	"'$path_to_index'",	$content);
							$content = str_replace('___SignOnUri___',	"'/'",	"$content");
							//$content = str_replace( '___RELATIVEPATHTOFOLDER___',	'\'/plugins/wp-phpmyadmin/'.$this->pma_dirname.'\'',	$content);  
							//
							//$content = str_replace('___RESTRICTORCOOKIENAME___','\''.$this->opts['randomCookieName'].'\'',		$content);  
							//$content = str_replace('___RESTRICTORCOOKIEVALUE___','\''.$this->opts['randomCookieValue'].'\'',	$content);  
							
							//solution for socket connections too , like : 'localhost:/run/mysqld/mysqld10.sock'
							$dbhost	= DB_HOST;
							$dbport	= '';
							$connectionType	='tcp';
							$socket			='';
							// if custom format, i.e. .sock/mysql or 123.123.123.123:xxx
							if( stripos($dbhost, ':')!==false )
							{
								if ( stripos($dbhost, '.sock')!==false ||  stripos($dbhost, '/mysql')!==false )
								{
									preg_match('/(.*?):(.*)/', $dbhost, $n);
									if (!empty($n[2]))
									{
										$dbhost = $n[1];
										$connectionType = 'socket';
										$socket	= $n[2];
									}
								}
								else
								{
									preg_match('/(.*?):(.*)/', $dbhost, $n);
									if (!empty($n[2]))
									{
										$dbhost = $n[1];
										$dbport	= $n[2];
									}
								}
							}
							$content = str_replace('___HOSTADR___', 			"'$dbhost'", 							$content);
							$content = str_replace('___PORTADR___', 			"'$dbport'", 							$content);
							$content = str_replace('___CONNECTIONTYPE___', 		"'$connectionType'", 					$content);
							$content = str_replace('___SOCKET___', 				"'$socket'", 							$content);
							file_put_contents($this->path_to_pma_config, $content);
						}
					}
					
					//add content into common.inc
					$cont = file_get_contents($this->path_to_pma_common);
					$flag = "//_WPMA__REPLACED_\r\n";
					if ( stripos($cont,$flag) === false )
					{
						$addition= $flag . 'require_once(__DIR__."/../../../'.basename($this->path_to_def_common).'"); WP_PHPMYADMIN_CONFIG_ADDITION(__DIR__);';
						if (stripos($cont,$phrase='require_once ROOT_PATH')!==false) {
							$common_inc_content_new  = $this->helpers->str_replace_first( $phrase, $addition."\r\n".$phrase, $cont );
						}
						else
						{
							$common_inc_content_new  = $this->helpers->str_replace_first( '<?php', '<?php '.$addition, $cont );
						}
						
						file_put_contents( $this->path_to_pma_common, $common_inc_content_new);
					}

					// rename conflicting function named __(    //old method: pastebin_com/raw/v652Ef1A
					//$file = $this->pma_abspath .'/vendor/phpmyadmin/motranslator/src/functions.php';
					//file_put_contents( $file,   str_replace('function __(', 'if (!function_exists("__")) { function __($str) { return __RENAMED(function __RENAMED(', file_get_contents($file) ) );
					
					//$this->createHtaccessDirDisableBrowsing( $this->lib_absPath );
				}
				flock($this->locker,LOCK_UN);
			} //end locker
			fclose($this->locker);
			if (file_exists($this->lockFile)) @unlink($this->lockFile);
		}
	}
	

	
	//from PMA
	// same as:  public function generateRandom($length)

	private function create_blowfish_secret(){
		$blowfishSecret = '';
		$random_func = (function_exists('openssl_random_pseudo_bytes')) ? 'openssl_random_pseudo_bytes' : 'phpseclib\\Crypt\\Random::string';
		while (strlen($blowfishSecret) < 32) {
			$byte = $random_func(1);
			// We want only ASCII chars
			if (ord($byte) > 32 && ord($byte) < 127) {
				$blowfishSecret .= $byte;
			}
		}
		return $blowfishSecret;
	}



	// ======

	public function create_session_var($force=false){ 
		$new_content = '<?php $sess_vars = ["time"=>'.time().', "name"=>"wp_pma_'.$this->helpers->randomString(14).'",  "value"=>"wp_pma_'.$this->helpers->randomString(23).'",  "require_ip"=>'.($this->opts['require_ip']? 'true':'false').', "ip"=>"'.$this->helpers->ip.'", "strip_slashes"=>'. ($this->opts['strip_slashes']? 'true':'false') .'];'; 

		$create=false;
		if($force || !file_exists($this->pma_sessionfile)){
			$create= true;
		}
		else{
			include($this->pma_sessionfile);
			//don't reset if login happens in last 30 seconds again.
			if( $sess_vars["time"] + 30 < time() ){
				$create = true;
			}
		} 
		if ($create ) file_put_contents($this->pma_sessionfile, $new_content);
	} 

	public function create_signon($login){ 
		// https://docs.phpmyadmin.net/en/latest/setup.html#signon-authentication-mode
		// Use cookies for session 
		ini_set('session.use_cookies', 'true');
		// Change this to true if using phpMyAdmin over https
		$secure_cookie = $this->helpers->is_https;
		// Need to have cookie visible from parent directory
		session_set_cookie_params(0, '/', '', $secure_cookie, true);
		// Create signon session
		$session_name = 'CustomSignonSession';
		session_name($session_name);
		// Uncomment and change the following line to match your $cfg['SessionSavePath']
		//session_save_path('/foobar');
		@session_start();
		// Store there credentials 
		$_SESSION['PMA_single_signon_user'] = $login ? DB_USER : '';
		$_SESSION['PMA_single_signon_password'] = $login ? DB_PASSWORD : '';
		//$_SESSION['PMA_single_signon_host'] = $_POST['host'];
		//$_SESSION['PMA_single_signon_port'] = $_POST['port'];
		// Update another field of server configuration 
		$_SESSION['PMA_single_signon_cfgupdate'] = ['verbose' => 'Signon test'];
		$_SESSION['PMA_single_signon_HMAC_secret'] = hash('sha1', uniqid(strval(rand()), true));

		$id = session_id();
		// Close that session 
		@session_write_close();
		//header('Location: ../index.php');
	}

	public function create_userip_file(){ 
		file_put_contents($this->pma_sessionAllowfile, $this->helpers->ip);
		file_put_contents($this->pma_sessionDbfile, DB_NAME);
		include($this->pma_sessionfile);
		if(empty($_COOKIE[$sess_vars["name"]]) || $_COOKIE[$sess_vars["name"]] != $sess_vars["value"] ){
			$hours = 3*60*60;
			$this->helpers->set_cookie( $sess_vars["name"], $sess_vars["value"], $hours);
			$this->helpers->set_cookie( "pma_DB_NAME", DB_NAME, $hours);
		}
	}


	public function replace_in_file($file, $from_pattern, $to){
		if(file_exists($file))
		{
			$cont= file_get_contents($file);
			$new_cont= preg_replace($from_pattern, $to, $cont);
			file_put_contents($file, $new_cont);
		}
	}
	
	// https://docs.phpmyadmin.net/en/latest/setup.html#signon-authentication-mode 
	public function if_redirect_to_pma()
	{ 
		if( isset($_GET['goto_wp_phpmyadmin']) ){ 
			if( current_user_can('install_plugins') && current_user_can("manage_options") )
			{ 
				if(isset($_GET['logout']))
				{
					$this->create_signon(false); 
					exit("Logout done!");
				}


				if(isset($_GET['hosting_pma'])){
					$m_url	= $this->opts['manual_pma_login_url'];
					if( stripos( $m_url , '/index.php') === false){
						$m_url .=  ! $this->helpers->endsWith($m_url, '/')  ?   '/index.php' : 'index.php';
					}
					$this->chosen_server_url = $m_url;
				}
				else{
					$this->helpers->disable_cache(true);
					// when chosen installed pma-url, then use protection (which we cant use with hosting-url)
					// p.s. SESSIONS DOESNT WORK for some reasons, probably WP resets then in 'shutdown' and start... SO WE USE COOKIES... 
					$this->create_session_var();
					$this->create_userip_file(); 
					$this->create_signon(true); 
 					//debug ::	$this->helpers->set_cookie('xxxxxxx', json_encode($_SESSION) );
					$this->chosen_server_url = $this->pma_mainpage_url;
					$this->helpers->php_redirect($this->chosen_server_url);
				}

				if(isset($_GET['automatic_login']))
				{
					$this->opts['ssl_error_shown']=1; $this->update_opts();
					if ( $this->opts['use_https'] ) 
						$this->chosen_server_url = str_replace('http://','https://', $this->chosen_server_url);

					register_shutdown_function( function(){
						?>
						<script>
						</script>
						<iframe src="<?php echo  $this->chosen_server_url;?>?pma_username=<?php echo DB_USER;?>&pma_password=<?php echo DB_PASSWORD;?>" ></iframe>
						<?php
					} );
					//include($this->pma_mainpage_path);
					exit;

					/*
					this no longer works
					?>
					<form style="display:none;" method="post" action="<?php echo $chosen_server_url;?>"  name="login_form" >
						<input type="hidden" name="pma_username" value="<?php echo DB_USER;?>" />
						<input type="hidden" name="pma_password" value="<?php echo DB_PASSWORD;?>" />
						<input type="hidden" name="server" value="1">
    					<input type="hidden" name="route" value="/">
						<input value="Go" type="submit" id="input_go" />
						<input type="hidden" name="set_session" value="will_be_replaced" />
						<input type="hidden" name="token" value="will_be_replaced">
					</form>
					<script>
						//	document.querySelector("#input_username").value="<?php echo DB_USER;?>";
						//	document.querySelector("#input_password").value="<?php echo DB_PASSWORD;?>";
						//	document.getElementById("login_form").action = "<?php echo  $this->chosen_server_url;?>";
						//	document.forms["login_form"].submit();
					document.forms["login_form"].submit();
					</script>
					<?php
					*/

				}
				else {
					$this->helpers->php_redirect($this->chosen_server_url);
				}
				?>
				<?php
				exit;
			}
			else{
				exit("You do not have enough privilegges to open this page.");
			}
		}
		return;
	}



	public function opts_page_output()
	{  
		$this->settings_page_part("start");
		?> 
		<style>
		p.submit { text-align:center; }
		.settingsTitle{display:none;}
		.myplugin {padding:10px;}
		.myplugin #old_pma_install:disabled{opacity:0.3;}
		.myplugin .enterb{font-size:1.5em; padding:10px; } 
		#mainsubmit-button{display:none; background:green;}
		.myplugin .sample_disabled{opacity:0.3;}
		body .ui-tooltip{background:pink;}
		td:nth-child(3) { width: 280px; }
		.myplugin .comingsoon{ opacity:0.4; }
		.warning_ssl_img{ text-align:center; }
		.warning_ssl_img img{ filter: sepia(0.6) contrast(1.1); }
		.installed_logins .manual_login {display:none;}
		.red_warning {color:orange;}
		.error_ {color:red;}
		</style>

		<?php
		$this->initialize_unpacked_pma();
		?>

		<?php if ($this->active_tab=="Options") 
		{ 
			//if form updated
			if( $this->checkSubmission() ) 
			{ 
				$this->opts['manual_pma_login_url']	= sanitize_text_field($_POST[ $this->plugin_slug ]['manual_pma_login_url']);
				
				$this->opts['use_https']		= isset($_POST[ $this->plugin_slug ]['use_https']); 
				$this->opts['strip_slashes']	= isset($_POST[ $this->plugin_slug ]['strip_slashes']); 
				$this->opts['require_ip']		= isset($_POST[ $this->plugin_slug ]['require_ip']); 
				$this->opts['hide_phma_errors']	= isset($_POST[ $this->plugin_slug ]['hide_phma_errors']); 
				$this->update_opts(); 
				
				//reflect changes immediately
				$this->replace_in_file($this->path_to_pma_config, '/\$cfg\[\WSendErrorReports\W\]\s+=\s+\W(.*?)\W/', '$cfg["SendErrorReports"] = \''. ($this->opts['hide_phma_errors'] ? 'never':'ask') .'\''); 
			}


			$this->ssl_notice_msg = __("This is a one-time message! <br/><br/> Seems that your site doesn\\'t use HTTPS. We strongly recommend to use HTTPS (SSL) with PhpMyAdmin (Automatic login at this moment works only for HTTPS). To use this feature, then you should bypass the SSL warning on the next page, like shown on this screenshot: ", "wp-phpmyadmin-extension") ."<br/><div class=\\'warning_ssl_img\\'><img src=\\'".$this->helpers->baseURL."/assets/media/example_warning.png\\' /></div>".__("If the next page doesn\\'t work at all, then uncheck the HTTPS checkbox on this page, or try to open your WP-Dashboard with <code>https://</code> prefix and then try to enter PhpMyAdmin", "wp-phpmyadmin-extension");

			$url_to_open =  trailingslashit(admin_url()).'?rand='.rand(1,99999999).'&goto_wp_phpmyadmin=1';
			?> 
		
			<form class="mainForm" method="post" action="">
			
			<table class="form-table">
				<tr class="installed_logins">
					<td><h3><?php _e("phpMyAdmin in your WP", 'wp-phpmyadmin-extension');?></h3></td>
					<td>
					<?php  
					
					$error=false;
					foreach ( array_filter($this->is_new_php ? ['hash','ctype'] : [] ) as $extension)
					{
						if(!extension_loaded($extension)) { 
							$error=true;
							_e('<div class="error_">extension <code>'.$extension.'</code> not enabled on your server. PhpMyAdmin can not work, unless you(or your hoster) enables it</div>', 'wp-phpmyadmin-extension');
						}
					}
					
					if (!$this->is_new_php)
					{
						_e('<div class="error_">Your server\'s PHP version is lower than required '.$this->required_version.'. The latest PhpMyAdmin can\'t work, so we <b>strongly</b> recommend to contact your hosting administrator to update your obsolete PHP version.</div>', 'wp-phpmyadmin-extension');
						if (!file_exists($this->pma_zipPath)) 
						{
							$error=true;
							//_e('<div class="error_2">If neither your hosting provider can help you, then as a temporary solution, you can <button id="old_pma_install">download & install PhpMyAdmin 4.9.4</button> from official website, but we strongly recommend to upgrate your PHP and then you will be able to use latest up-to-date version, instead of using old version.</div>', 'wp-phpmyadmin-extension');
						}
					}

					if ( ! $this->is_writable() ) 
						_e('<div class="error_">Your <code>WP-CONTENT/PLUGINS/WP-PHPMYADMIN-EXTENSION</code> directory is not writable. Correct that at first, from hosting/sFTP settings</div>');

					?>
					</td>
					<td> 
<!--
					<p class="submit manual_login"><a class="<?php if (!is_dir($this->pma_abspath)) echo "sample_disabled";?> button button-primary type_auto enterb enter_manual" target="_blank"  href="<?php echo $url_to_open;?>&manual_login"><?php _e("Login Manually", 'wp-phpmyadmin-extension');?></a></p>

					<p class="submit automatic_login"><a class="<?php if (!is_dir($this->pma_abspath)) echo "sample_disabled";?> button button-primary type_auto enterb enter_automatic" target="_blank" href="<?php echo $url_to_open;?>&automatic_login"  id="installed_automatic" ><?php _e("Login Automatically", 'wp-phpmyadmin-extension');?></a></p>
-->
					 
					<p class="submit automatic_login">
						<a class="<?php if (!is_dir($this->pma_abspath) || $error) echo "sample_disabled";?> button button-primary type_auto enterb enter_automatic" target="_blank" href="<?php echo $url_to_open;?>&automatic_login" id="installed_automatic" onclick="show_ssl_wanring1(event, this);"><?php _e("Enter phpMyAdmin", 'wp-phpmyadmin-extension');?></a> 
						<br/> (Note: due to bug in PMA, you have to manually click <a href="<?php echo $url_to_open;?>&logout" target="_blank">logout</a> after you done working in PMA)
					</p> 
					</td>
				</tr> 

				<tr class="hostinged_logins">
					<?php $pma_url = $this->helpers->domain.'/phpmyadmin/'; ?>
					<td><h3><?php _e("phpMyAdmin on hosting:", 'wp-phpmyadmin-extension');?></h3></td>
					<td>
					<?php _e('If above method doesn\'t work for you, you can use an alternative - some hostings might already have phpMyAdmin setup for customers. If so, just paste the phpMyAdmin login page url here:', 'wp-phpmyadmin-extension');?>
					<input type="text" class="regular-text" id="manual_pma_login_url" data-onchange-save="true"  data-onchange-hide=".type_manual" name="<?php echo $this->plugin_slug;?>[manual_pma_login_url]" value="<?php echo $this->opts['manual_pma_login_url'];?>" placeholder="" />  
					<br/><?php _e('( That url might be <code><a href="'.$pma_url.'" target="_blank">'.$pma_url.'</a></code> or <code>https://xyz123.yourhosting.com/phpmyadmin/</code>. You can find out that url in your hosting\'s Control-Panel &gt; <b>phpMyAdmin</b> and you will be redirected to "login" url. Then paste that base url here.)', 'wp-phpmyadmin-extension');?>
					</td>
					<td>
					<p class="submit"><a class="button button-primary type_manual enterb enter_manual" target="_blank"  href="<?php if(empty($this->opts['manual_pma_login_url'])) echo "javascript:alert('url is empty');void(0);"; else if(stripos($this->opts['manual_pma_login_url'], '//')===false) echo "javascript:alert('incorrect url format');void(0);"; else echo $url_to_open.'&hosting_pma&manual_login';?>"><?php _e("Login Manually", 'wp-phpmyadmin-extension');?></a></p>
					<p class="submit"><a class="button button-primary type_manual enterb enter_automatic comingsoon" target="_blank"  href="<?php if(empty($this->opts['manual_pma_login_url'])) echo "javascript:alert('url is empty');void(0);"; else if(stripos($this->opts['manual_pma_login_url'], '//')===false) echo "javascript:alert('incorrect url format');void(0);"; else echo $url_to_open.'&hosting_pma&automatic_url';?>" id="hosting_automatic" ><?php _e("Login Automatically", 'wp-phpmyadmin-extension');?></a></p>
					</td>
				</tr>
				
				<tr>
					<td></td>
					<td></td>
					<td>
						<b><p class="description"><?php _e("Credentials:", 'wp-phpmyadmin-extension');?></p></b> <?php _e('DB Username', 'wp-phpmyadmin-extension');?>: <input type="text" value="<?php echo DB_USER;?>" class="noinput"/>
						<br/><?php _e('DB Password', 'wp-phpmyadmin-extension');?>: <b><?php _e('Get from wp-config.php', 'wp-phpmyadmin-extension');?></b>
						<br/>
					</td>
				</tr>
				<?php if ( !is_ssl() ) { ?>
				<tr>
					<td class="red_warning"><?php _e("Use HTTPS (in case it does not automatically use)", 'wp-phpmyadmin-extension');?> <?php echo $this->helpers->question_mark($this->ssl_notice_msg, $dialogType=2);?></td> 
					<td><input type="checkbox" name="<?php echo $this->plugin_slug;?>[use_https]" <?php checked($this->opts['use_https']);?> data-onchange-save="true" /></td>
					<td></td>
				</tr>
				<?php } ?>
				<tr>
					<td><?php $ip=$this->helpers->ip; _e("Restrict access only to current IP (<code>$ip</code>) to login into PMA <br/>(in rare cases, if you have continiously changing dynamic IP address, then you will need to uncheck IP restriction)", 'wp-phpmyadmin-extension');?></td> 
					<td><input type="checkbox" name="<?php echo $this->plugin_slug;?>[require_ip]" <?php checked($this->opts['require_ip']);?> data-onchange-save="true" /> </td>
					<td></td>
				</tr>
				<tr>
					<td><?php _e('Hide errors in PMA <br/>(if you face error popup-boxes in phpMyAdmin frequently, you can hide them)', 'wp-phpmyadmin-extension');?></td> 
					<td><input type="checkbox" name="<?php echo $this->plugin_slug;?>[hide_phma_errors]" <?php checked($this->opts['hide_phma_errors']);?> data-onchange-save="true" /> </td>
					<td></td>
				</tr>
				<tr>
					<td><?php _e('Strip slashes in PMA <br/>(if you see that when you update a textfield in phpMyAdmin, and extra backslash <code>\\</code> is added in front of <code>\\</code> or <code>\'</code> or <code>"</code> characters, then check this) :', 'wp-phpmyadmin-extension');?></td> 
					<td><input type="checkbox" name="<?php echo $this->plugin_slug;?>[strip_slashes]" <?php checked($this->opts['strip_slashes']);?> data-onchange-save="true" /> </td>
					<td></td>
				</tr>
			</table>
			
			<?php $this->nonceSubmit();  ?>
			</form>
 
			
			<script> 
			// warning tooltips
			shown_error_once = 0;
			function show_ssl_wanring1(e, elem){
				lastEl = jQuery(elem); //jQuery(this) 
				if(location.protocol != 'https:')
				{
					if (shown_error_once || <?php echo ( array_key_exists('ssl_error_shown', $this->opts) ? "true" : "false")?>) return;
					shown_error_once = 1;
					jQuery('<div><?php echo $this->ssl_notice_msg;?></div>').dialog({
						modal:true,
						width:700,
						close: function(){ document.getElementById(lastEl.attr("id")).click(); }
					});
					e.preventDefault();
				}
			}



			jQuery(function(){ 
				jQuery(".comingsoon").attr("title", "Coming soon").on("click", function(e){e.preventDefault();}); 
				
				jQuery(".myplugin").tooltip({ show: { effect: "blind", duration: 800 } }); 
				
				jQuery("#old_pma_install").on("click", function(e){
					var targetEl= this;
					e.preventDefault();
					ttLibrary.backend_call
					(
						{
							'act': 'old_pma_install' 
						},
						function(response)
						{
							ttLibrary.message(response);
						}
					);
					window.setTimeout( function(){ ttLibrary.message("<?php _e('Please don\'t refresh the page untill it refreshes automatically (might need up to 45 seconds).', 'wp-phpmyadmin-extension');?>"); }, 2000);
					//window.setTimeout( function(){ jQuery('.ttDialog').dialog('close'); }, 7000);
				});

			});
			</script>

		<?php 
		} 

		$this->settings_page_part("end");
	} 
	

 
	public function backend_call($act)
    {
		/*
		if($act=="old_pma_install")
		{
			// remove if previous unpack was partial.
			$dir = $this->getPMA_FolderName();  
			if( !empty($dir) && is_dir($dir) ) {
				if( !$this->helpers->rmdir_recursive($dir) )
					exit(__("Failure: can't remove the old $dir folder, click OK to re-try (If you will see this message again, do it manually from FTP).", 'wp-phpmyadmin-extension'));
			}
	 
			// download latest pma
			//if ($this->pma_installed_using_download) if(!file_exists($this->pma_zipPath)) $this->getPmaZip();       // getPmaZip -----> removed: pastebin(dot)com/raw/r8jLN1P7
			// unzip
			wp_remote_get( $url=$this->old_pma_zip, ['timeout'=>300,  'stream'=>true,  'filename'=>$this->pma_zipPath ] );
			$this->helpers->unzip($this->pma_zipPath, $this->lib_absPath);
			usleep(500000);
			//unlink($this->pma_zipPath);
			rename( $this->lib_absPath .'/'.str_replace('.zip', '',basename($this->old_pma_zip)), $this->pma_abspath_real );
			exit(__('Installation complete.<script>location.href=location.href;</script>','wp-phpmyadmin-extension') ); 
		}
		*/
		
    }

	
	
	
	
  } // End Of Class





  $GLOBALS[__NAMESPACE__] = new PluginClass();

} // End Of NameSpace
?>