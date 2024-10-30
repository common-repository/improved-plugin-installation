<?php
/*
Plugin Name: Improved Plugin Installation
Plugin URI: http://www.improvingtheweb.com/wordpress-plugins/improved-plugin-installation/
Description: Allows for plugin installation simply by submitting the wordpress plugin name or URL. Also includes a bookmarklet.
Author: ImprovingTheWeb
Version: 1.1
Author URI: http://www.improvingtheweb.com/
*/

if (!defined('WP_CONTENT_URL')) {
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if (!defined('WP_PLUGIN_URL')) {
	define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
}

if (is_admin()) {	
	global $wp_version;
	
	if (strpos($wp_version, '-')) { //beta versions, etc
		define('IPI_WP_VERSION', substr($wp_version, 0, strpos($wp_version, '-')));
	} else {
		define('IPI_WP_VERSION', $wp_version);
	}
	
	register_activation_hook(__FILE__, 'ipi_activate');

	add_action('install_plugins_dashboard', 'ipi_install_dashboard', 1);
	add_filter('install_plugins_nonmenu_tabs', 'ipi_extra_tabs');
	add_action('install_plugins_ipi_url', 'ipi_url');
		
	if (!empty($_REQUEST['ipi_bookmarklet']) && empty($_REQUEST['iframe']) && !empty($_REQUEST['url'])) {
		add_action('init', 'ipi_bookmarklet', 1);
	} 
} else if (!empty($_REQUEST['redirect_to']) && strpos(strtolower($_REQUEST['redirect_to']), 'ipi_bookmarklet') !== false) {
	header('P3P: CP="CAO PSA OUR'); //for IE6/7 since otherwise they ignore the cookies

	add_action('init', 'ipi_bookmarklet_login', 1);
	add_action('login_head', 'ipi_bookmarklet', 1);
}

function ipi_activate() {
	if (version_compare(IPI_WP_VERSION, '2.7', '<')) {
		deactivate_plugins(__FILE__); 
		wp_die(__('Wordpress 2.7 or greater is required for this plugin to function.'));		
	}
	
	add_option('ipi_code', wp_rand());
}

function ipi_extra_tabs($tabs) {
	$tabs[] = 'ipi_url';
	
	return $tabs;
}

function ipi_install_dashboard() {
	remove_all_actions('install_plugins_dashboard');
	
	if (version_compare(IPI_WP_VERSION, '2.8', '<')) {
		ipi_install_dashboard_old();
	} else {
		ipi_install_dashboard_new();
	}
}

function ipi_install_dashboard_old() {
	?>
	<p><?php _e('Plugins extend and expand the functionality of WordPress. You may automatically install plugins from the <a href="http://wordpress.org/extend/plugins/">WordPress Plugin Directory</a> or upload a plugin in .zip format via this page.') ?> <?php _e('Alternatively, simply enter the plugin name or URL in the text box below.', 'improved_plugin_installation'); ?></p>

	<p style="background:#e7f7d3;margin:2px;padding:2px;border:1px solid #ccc;">
	 <?php _e('You may also want to install our bookmarklet', 'improved_plugin_installation'); ?>
	 <sup><a href="http://www.improvingtheweb.com/wordpress-plugins/improved-plugin-installation/#bookmarklet" title="<?php _e('What is a bookmarklet?', 'improved_plugin_installation'); ?>" style="text-decoration:none;">[?]</a></sup>. 
	 <?php _e('This allows you to easily install plugins directly from within the wordpress.org repository or offsite URLs.', 'improved_plugin_installation'); ?> (<a href="http://www.improvingtheweb.com/wordpress/improved-plugin-installation/#bookmarklet"><?php _e('more info', 'improved_plugin_installation'); ?></a>)
	</p>

    <p>
	 <?php _e('Firefox/Safari/Chrome: Drag', 'improved_plugin_installation'); ?> "<a href="<?php echo attribute_escape(ipi_bookmarklet_code()); ?>" onclick="alert('<?php _e('Don\'t click on this, drag it onto your browser bookmarks toolbar', 'improved_plugin_installation'); ?>');return false;" style="font-weight:bold;"><?php _e('Install Plugin', 'improved_plugin_installation'); ?></a>" <?php _e('to your bookmarks toolbar. Internet Explorer: Right click', 'improved_plugin_installation'); ?> "<a href="<?php echo attribute_escape(ipi_bookmarklet_code()); ?>" onclick="alert('<?php _e('You must right click on this link if you use IE.', 'improved_plugin_installation'); ?>');return false;" style="font-weight:bold;"><?php _e('Install Plugin', 'improved_plugin_installation'); ?></a>" 
	 &raquo; "<?php _e('add to favorites', 'improved_plugin_installation'); ?>" &raquo; "<?php _e('create in: Links', 'improved_plugin_installation'); ?>".
	 <?php _e('For detailed instructions, see', 'improved_plugin_installation'); ?> <a href="http://www.improvingtheweb.com/wordpress-plugins/improved-plugin-installation/#bookmarklet"><?php _e('here', 'improved_plugin_installation'); ?></a>.
	</p>

	<h4><?php _e('Search') ?></h4>
	<?php install_search_form('<a href="' . add_query_arg('show-help', !isset($_REQUEST['show-help'])) .'" onclick="jQuery(\'#search-help\').toggle(); return false;">' . __('[need help?]') . '</a>') ?>
	<div id="search-help" style="display: <?php echo isset($_REQUEST['show-help']) ? 'block' : 'none'; ?>;">
	<p>	<?php _e('You may search based on 3 criteria:') ?><br />
		<?php _e('<strong>Term:</strong> Searches plugins names and descriptions for the specified term') ?><br />
		<?php _e('<strong>Tag:</strong> Searches for plugins tagged as such') ?><br />
		<?php _e('<strong>Author:</strong> Searches for plugins created by the Author, or which the Author contributed to.') ?></p>
	</div>
	
	<h4><?php _e('Install a plugin in .zip format') ?></h4>
	<p><?php _e('If you have a plugin in a .zip format, You may install it by uploading it here.') ?></p>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url('plugin-install.php?tab=upload') ?>">
		<?php wp_nonce_field( 'plugin-upload') ?>
		<input type="file" name="pluginzip" />
		<input type="submit" class="button" value="<?php _e('Install Now') ?>" />
	</form>
	
	<h4><?php _e('Install plugins from URL/name', 'improved_plugin_installation') ?></h4>
	<p><?php _e('Type the plugin names, the wordpress plugin page URLs, or the direct URLs to the zip files. (One on each line)', 'improved_plugin_installation') ?></p>
	<form method="post" action="<?php echo admin_url('plugin-install.php?tab=ipi_url') ?>">
		<?php wp_nonce_field( 'plugin-ipi_url') ?>
		<textarea name="pluginurls" rows="5" cols="40"></textarea>
		<input type="submit" class="button" value="<?php _e('Install Now') ?>" style="display:block;margin-top:5px;" />
	</form>
			
	<div style="background:#efefef;padding:3px;margin-top:5px;">
	 Functionality added by <a href="http://www.improvingtheweb.com/" style="font-weight:bold;">Improving The Web</a>: <a href="http://www.improvingtheweb.com/feed/">RSS</a> / <a href="http://www.twitter.com/improvingtheweb">Twitter</a>
	</div>
				
	<h4><?php _e('Popular tags') ?></h4>
	<p><?php _e('You may also browse based on the most popular tags in the Plugin Directory:') ?></p>
	<?php

	$api_tags = install_popular_tags();

	//Set up the tags in a way which can be interprated by wp_generate_tag_cloud()
	$tags = array();
	foreach ( (array)$api_tags as $tag )
		$tags[ $tag['name'] ] = (object) array(
								'link' => clean_url( admin_url('plugin-install.php?tab=search&type=tag&s=' . urlencode($tag['name'])) ),
								'name' => $tag['name'],
								'id' => sanitize_title_with_dashes($tag['name']),
								'count' => $tag['count'] );
	echo wp_generate_tag_cloud($tags, array( 'single_text' => __('%d plugin'), 'multiple_text' => __('%d plugins') ) );
}

function ipi_install_dashboard_new() {
	?>
	<p><?php _e('Plugins extend and expand the functionality of WordPress. You may automatically install plugins from the <a href="http://wordpress.org/extend/plugins/">WordPress Plugin Directory</a> or upload a plugin in .zip format via this page.') ?> <?php _e('Alternatively, simply enter the plugin name or URL in the text box below.', 'improved_plugin_installation'); ?></p>

	<p style="background:#e7f7d3;margin:2px;padding:2px;border:1px solid #ccc;">
	 <?php _e('You may also want to install our bookmarklet', 'improved_plugin_installation'); ?>
	 <sup><a href="http://www.improvingtheweb.com/wordpress-plugins/improved-plugin-installation/#bookmarklet" title="<?php _e('What is a bookmarklet?', 'improved_plugin_installation'); ?>" style="text-decoration:none;">[?]</a></sup>. 
	 <?php _e('This allows you to easily install plugins directly from within the wordpress.org repository or offsite URLs.', 'improved_plugin_installation'); ?> (<a href="http://www.improvingtheweb.com/wordpress/improved-plugin-installation/#bookmarklet"><?php _e('more info', 'improved_plugin_installation'); ?></a>)
	</p>

    <p>
	 <?php _e('Firefox/Safari/Chrome: Drag', 'improved_plugin_installation'); ?> "<a href="<?php echo attribute_escape(ipi_bookmarklet_code()); ?>" onclick="alert('<?php _e('Don\'t click on this, drag it onto your browser bookmarks toolbar', 'improved_plugin_installation'); ?>');return false;" style="font-weight:bold;"><?php _e('Install Plugin', 'improved_plugin_installation'); ?></a>" <?php _e('to your bookmarks toolbar. Internet Explorer: Right click', 'improved_plugin_installation'); ?> "<a href="<?php echo attribute_escape(ipi_bookmarklet_code()); ?>" onclick="alert('<?php _e('You must right click on this link if you use IE.', 'improved_plugin_installation'); ?>');return false;" style="font-weight:bold;"><?php _e('Install Plugin', 'improved_plugin_installation'); ?></a>" 
	 &raquo; "<?php _e('add to favorites', 'improved_plugin_installation'); ?>" &raquo; "<?php _e('create in: Links', 'improved_plugin_installation'); ?>".
	 <?php _e('For detailed instructions, see', 'improved_plugin_installation'); ?> <a href="http://www.improvingtheweb.com/wordpress-plugins/improved-plugin-installation/#bookmarklet"><?php _e('here', 'improved_plugin_installation'); ?></a>.
	</p>

	<h4><?php _e('Search') ?></h4>
	<p class="install-help"><?php _e('Search for plugins by keyword, author, or tag.') ?></p>
	<?php install_search_form(); ?>
		
	<h4><?php _e('Install plugins from URL/name', 'improved_plugin_installation') ?></h4>
	<p><?php _e('Type the plugin names, the wordpress plugin page URLs, or the direct URLs to the zip files. (One on each line)', 'improved_plugin_installation') ?></p>
	<form method="post" action="<?php echo admin_url('plugin-install.php?tab=ipi_url') ?>">
		<?php wp_nonce_field( 'plugin-ipi_url') ?>
		<textarea name="pluginurls" rows="5" cols="40"></textarea>
		<input type="submit" class="button" value="<?php _e('Install Now') ?>" style="display:block;margin-top:5px;" />
	</form>
			
	<div style="background:#efefef;padding:3px;margin-top:5px;">
	 Functionality added by <a href="http://www.improvingtheweb.com/" style="font-weight:bold;">Improving The Web</a>: <a href="http://www.improvingtheweb.com/feed/">RSS</a> / <a href="http://www.twitter.com/improvingtheweb">Twitter</a>
	</div>

	<?php
	$api_tags = install_popular_tags();

	//Set up the tags in a way which can be interprated by wp_generate_tag_cloud()
	$tags = array();
	foreach ( (array)$api_tags as $tag )
		$tags[ $tag['name'] ] = (object) array(
								'link' => esc_url( admin_url('plugin-install.php?tab=search&type=tag&s=' . urlencode($tag['name'])) ),
								'name' => $tag['name'],
								'id' => sanitize_title_with_dashes($tag['name']),
								'count' => $tag['count'] );
	echo '<p class="popular-tags">';
	echo wp_generate_tag_cloud($tags, array( 'single_text' => __('%d plugin'), 'multiple_text' => __('%d plugins') ) );
	echo '</p><br class="clear" />';
}


function ipi_bookmarklet_code() {
	$javascript = WP_PLUGIN_URL . '/improved-plugin-installation/bookmarklet.js?ipi_bookmarklet=' . get_option('ipi_code');
	
	if (force_ssl_admin()) {
		$javascript = str_replace('http://', 'https://', $javascript);
	}
	
	if (version_compare(phpversion(), '4.2.0', '>')) {
		$code = '(function(){var%20e=document.createElement("script");e.setAttribute("type","text/javascript");e.setAttribute("src","' . $javascript . '");e.setAttribute("id", "ipi_javascript");document.body.appendChild(e)})()';	
	} else {
		$code = '(function(){location.href="' . admin_url() . 'plugin-install.php?ipi_bookmarklet=' . get_option('ipi_code') . '&iframe=0&url="%20+%20encodeURIComponent(location.href)}()';		
	}

	if (!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/MSIE 6.0/i', $_SERVER['HTTP_USER_AGENT'])) {
		return 'javascript:void(' . $code . ')';	
	} else {
		return 'javascript:try{if(document.location.host=="' . $_SERVER['SERVER_NAME'] . '")throw(0);' . $code . '}catch(z){alert("Congratulations you have installed the button, click on it when you are on a plugin download page to install that plugin.")}void(0)';
	}
}

function ipi_bookmarklet_login() {
	$_REQUEST['failed_login'] = 1;
	ob_start();
}

function ipi_bookmarklet() {
	if (!empty($_REQUEST['failed_login'])) {
		ob_clean();
	}
		
	?>
	<html>
	<head>
	
	<style type="text/css">
	html, body, div, span, p, a, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td {
		margin: 0;
		padding: 0;
		border: 0;
		outline: 0;
		font-weight: inherit;
		font-style: inherit;
		font-size: 100%;
		font-family: inherit;
		vertical-align: baseline;
	}
	
	body, td { 
		font:normal 13px 'Lucida Grande', Arial, sans-serif;
	}
	
	#footer{
		border-top:1px solid #CCC;
		padding:5px;
		background:#ffffcc;
		position:absolute;
		bottom:0;
		left:0;
		width:100%;
		height:<length>;
	}
	
	a, a:visited { 
		color:blue;
	}
	
	@media screen{
		body>div#footer{
	   		position: fixed;
	  	}	
	}
	
	* html body{
		overflow:hidden;
	} 
	
	* html div#content{
		height:100%;
		overflow:auto;
	}
	
	p { 
		margin-top:5px;
		margin-bottom:5px;
	}
	
	.button { 
		margin-top: 5px !important; 
	}
	
	#content { 
		margin: 5px; 
	}
		
	td { vertical-align:middle !important; }
	</style>
	</head>
	<body>
	<div id="content">
	
	<?php
	
	if (!empty($_REQUEST['failed_login'])) {
		?>
		<p><?php _e('Couldn\'t log you in. Please try', 'improved_plugin_installation'); ?> <a href="<?php get_option('site_url'); ?>wp-login.php" target="_blank"><?php _e('here'); ?></a> <?php _e('instead, or', 'improved_plugin_installation'); ?> <a href="<?php echo attribute_escape($_REQUEST['redirect_to']); ?>"><?php _e('reload', 'improved_plugin_installation'); ?></a> <?php _e('this page.', 'improved_plugin_installation'); ?></p>
		<?php
	} else if (empty($_REQUEST['ipi_bookmarklet']) || $_REQUEST['ipi_bookmarklet'] != get_option('ipi_code')) {
		?>
		<p><?php _e('Incorrect bookmarklet code, please remove the bookmarklet from your browser and re-add it.', 'improved_plugin_installation'); ?></p>
		<?php
	} else if (!is_user_logged_in()) { 
		if ((force_ssl_login() || force_ssl_admin()) && !is_ssl()) {
			?>
			<p><?php _e('Please log in', 'improved_plugin_installation'); ?> <a href="<?php echo get_option('site_url'); ?>wp-login.php"><?php _e('here', 'improved_plugin_installation'); ?></a> <?php _e('instead, to login over SSL.', 'improved_plugin_installation'); ?></p>
			<?php
		} else {
			$redirect_to = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], 'wp-admin/'));

			?>

			<form name="loginform" id="loginform" action="<?php echo site_url('wp-login.php', 'login_post') ?>" method="post">
		
			<table>
			<tr>
				<td style="padding-right:10px;"><?php _e('Username') ?></td>
				<td><input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10" style="width:150px;" /></td>
			</tr>
			<tr>
				<td><?php _e('Password') ?></td>
				<td><input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" style="width:150px;" /></td>
			</tr>
			<tr>
				<td>
					<input type="submit" class="button" name="wp-submit" id="wp-submit" value="<?php _e('Log In'); ?>" tabindex="100" />
					<input type="hidden" name="redirect_to" value="<?php echo attribute_escape($redirect_to); ?>" />
					<input type="hidden" name="testcookie" value="1" />
				</td>
				<td>
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> <?php _e('Remember Me'); ?>
				</td>
			</tr>
			</table>
			</form>
			<?php
		}		
	} else if (!current_user_can('install_plugins')) {
		?>
		<p><?php _e('You do not have the necessary administrative rights to be able to install plugins.', 'improved_plugin_installation'); ?></p>
		<?php
	} else if (strpos(strtolower($_REQUEST['url']), 'plugin-install.php') !== false && strpos(strtolower($_REQUEST['url']), $_SERVER['SERVER_NAME']) !== false) {
		//user clicked it instead of dragging it... show link to explanation..
		?>
		<h4><?php _e('Sorry, you did something wrong.', 'improved_plugin_installation') ?></h4>
		<p><?php _e('You\'re not supposed to click on this link, for installation instructions, see', 'improved_plugin_installation'); ?> <a href="http://www.improvingtheweb.com/">Improving The Web</a></p>
		<?php
	} else if (preg_match('/downloads\.wordpress\.org\/plugin\/([^\.]+)(.*)\.zip/i', $_REQUEST['url'], $match) || preg_match('/wordpress\.org\/extend\/plugins\/([^\/]*)\/?/i', $_REQUEST['url'], $match)) {
		?>
		<p><?php _e('Are you sure you want to install this plugin?', 'improved_plugin_installation'); ?></p>
	
		<form method="post" action="<?php echo admin_url('plugin-install.php?tab=ipi_url') ?>" target="_top">
			<?php wp_nonce_field( 'plugin-ipi_url') ?>
			<input type="hidden" name="pluginurl" value="<?php echo attribute_escape($_REQUEST['url']); ?>" />
			<input type="submit" class="button" value="<?php _e('Yes, install the plugin', 'improved_plugin_installation'); ?>" />
		</form>
		<?php
	} else {
		?>
		<?php if (!$_REQUEST['url'] || $_REQUEST['url'] == 'notfound'): ?>
		<p><?php _e('Please paste the URL below.', 'improved_plugin_installation') ?></p>
		<?php else: ?>
		<p><?php _e('Please confirm the path below is correct.', 'improved_plugin_installation'); ?></p>
		<?php endif; ?>
		<form method="post" action="<?php echo admin_url('plugin-install.php?tab=ipi_url') ?>" target="_top">
			<?php wp_nonce_field( 'plugin-ipi_url') ?>
			<input type="text" name="pluginurl" style="width:99%;margin-bottom:5px;" value="<?php if ($_REQUEST['url'] != 'notfound'): ?><?php echo attribute_escape($_REQUEST['url']); ?><?php endif; ?>" />
			<input type="submit" class="button" value="<?php _e('Install Now') ?>" />
		</form>
		<?php
	}
		
	?>
	</div>
	
	<div id="footer">By <a href="http://www.improvingtheweb.com/" target="_top">Improving The Web</a></div>
	</body>
	</html>
	
	<?php
	die();
}

function ipi_url() {
	check_admin_referer('plugin-ipi_url');
	
	if (!is_user_logged_in()) {
		wp_die(__('You are not logged in.', 'improved_plugin_installation')); 	
	} else if (!current_user_can('install_plugins')) {
		wp_die(__('You do not have the necessary administrative rights to be able to install plugins.', 'improved_plugin_installation'));
	} 
	
	if (!empty($_REQUEST['pluginurls'])) {
		if (is_array($_REQUEST['pluginurls'])) {
			$urls = $_REQUEST['pluginurls'];
		} else {
			$urls = explode("\n", $_REQUEST['pluginurls']);
		}
	} else if (!empty($_REQUEST['pluginurl'])) {
		$urls = array($_REQUEST['pluginurl']);
	} else {
		wp_die(__('No data supplied.'));
	}
	
	$urls = array_unique($urls);
	
	$correct = $errors = 0;
	
	if (get_filesystem_method() != 'direct') {
		global $wp_filesystem;
		
		$credentials_url = 'plugin-install.php?tab=ipi_url&';
		
		foreach ($urls as $url) {
			$credentials_url .= '&pluginurls[]=' . urlencode($url);
		}
				
		$credentials_url = wp_nonce_url($credentials_url, 'plugin-ipi_url');
				
		if ( false === ($credentials = request_filesystem_credentials($credentials_url)) ) //preload the credentials in $_POST.. 
			return;
					
		if ( ! WP_Filesystem($credentials) ) {
			request_filesystem_credentials($credentials_url, '', true); //Failed to connect, Error and request again
			return;
		}
		
		if ( $wp_filesystem->errors->get_error_code() ) {
			foreach ( $wp_filesystem->errors->get_error_messages() as $message )
				show_message($message);
			return;
		}
	}
		
	foreach ($urls as $url) {
		if (!$url = trim(stripslashes(trim($url, "\r")))) {
			continue;
		}
		
		//name of plugin
		if (!preg_match('/http:\/\//i', $url, $match)) {
			$plugin_name = $url;
		//url of plugin on wordpress.org	
		} else if (preg_match('/downloads\.wordpress\.org\/plugin\/([^\.]+)(.*)\.zip/i', $url, $match) || preg_match('/wordpress\.org\/extend\/plugins\/([^\/]*)\/?/i', $url, $match)) {
			$plugin_name = stripslashes($match[1]);
		} else {
			$plugin_name = false;
		}
		
		if ($plugin_name) {
			$plugin = ipi_get_plugin_information($plugin_name);
			
			if (is_wp_error($plugin)) {
				$errors++;
				
				$code    = $plugin->get_error_code();
				$message = $plugin->get_error_message();
				
			
				if (count($urls) == 1) {
					if ($code == 'plugins_api_failed') {
						echo '<p>' . __('Couldn\'t install plugin, perhaps you misspelled the name?', 'improved_plugin_installation') . '</p>';
					} else {
						echo '<p>' . $message . '</p>';
					}
				} else {
					echo '<div class="wrap">';
					echo '<h2>', sprintf( __('Installing Plugin: %s'), attribute_escape($url)), '</h2>';
				
					if ($code == 'plugins_api_failed') {
						echo '<p>' . __('Couldn\'t install plugin, perhaps you misspelled the name?', 'improved_plugin_installation') . '</p>';
					} else {
						echo '<p>' . $message . '</p>';
					}
					echo '</div>';
				}
			} else {
				$correct++;
				
				$_REQUEST['plugin_name']  = $plugin->name;
				$_REQUEST['download_url'] = $plugin->download_link;

				echo '<div class="wrap">';
				echo '<h2>', sprintf( __('Installing Plugin: %s'), $plugin->name . ' ' . $plugin->version ), '</h2>';

				ipi_do_plugin_install($plugin->download_link, $plugin);
				echo '</div>';
			}
		//URL of plugin on third party site
		} else {
			$correct++;
			
			echo '<div class="wrap">';
			echo '<h2>', sprintf( __('Installing Plugin: %s'), attribute_escape($url)), '</h2>';

			ipi_do_external_plugin_install($url);
			echo '</div>';
		} 
	}
	
	if (!$correct && !$errors) {
		echo '<p>' . __('No valid data supplied.', 'improved_plugin_installation') . '</p>';
	}
}

function ipi_get_plugin_information($plugin) {
	$plugin = strtolower(trim(preg_replace("/\s+/", ' ', $plugin)));
			
	$api = plugins_api('plugin_information', array('slug' => $plugin, 'fields' => array('sections' => false, 'description' => false) ) ); 
	
	if (is_wp_error($api)) {
		$api = plugins_api('query_plugins', array('search' => $plugin, 'per_page' => 1, 'fields' => array('sections' => false, 'description' => false ) ) ); 
		
		if (!is_wp_error($api)) {
			if (!empty($api->plugins[0])) {	
				$api = $api->plugins[0];
				
				if (preg_match('/^' . preg_quote(trim($plugin), '/') . '/i', trim($api->name) )) {		
					/*
					//cant use this, download link not always in the same format.. Have to do another request.
					if (strpos($api->version, '.') === 0) {
						$api->version = '0' . $api->version;
					}
					
					$api->download_link = 'http://downloads.wordpress.org/plugin/' . $api->slug . '.' . trim($api->version, '.') . '.zip';
					*/
					
					$plugin = $api->slug;
										
					$api = plugins_api('plugin_information', array('slug' => $plugin, 'fields' => array('sections' => false, 'description' => false) ) ); 
				} else {
					$api = new WP_Error('plugins_api_failed');
				}
			} else {
				$api = new WP_Error('plugins_api_failed' );
			}
		}
	}
		
	return $api;
}

function ipi_do_plugin_install($download_url, $api) {
	if (function_exists('do_plugin_install')) {
		do_plugin_install($download_url, $api);
	} else {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	
		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
		$success = $upgrader->install($download_url);
	}
}

function ipi_do_external_plugin_install($download_url) {
	global $wp_filesystem;

	if ( empty($download_url) ) {
		show_message( __('No plugin Specified') );
		return;
	}
	
	if (function_exists('wp_install_plugin')) {
		$result = wp_install_plugin( $download_url, 'show_message' );
	} else {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		
		$upgrader = new Plugin_Upgrader( new Plugin_Installer_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
	 	$upgrader->install($download_url);
	}
	
	if ( is_wp_error($result) ) {
		show_message($result);
		show_message( __('Installation Failed') );
	} else {
		show_message( sprintf(__('Successfully installed the plugin <strong>%s </strong>.'), $download_url) );
	}
}
?>