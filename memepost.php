<?php
/**
Plugin Name: Memepost
Plugin URI: http://gofedora.com/memepost
Description: Adds a 'Post to Yahoo! Meme' button to your wordpress pages/post.
Author: Kulbir Saini
Version: 0.1
Author URI: http://gofedora.com/
Text Domain: memepost
 */

/**
 * Memepost Plugin Class
 */
class Memepost {

  /**
   * Initalize the plugin by registering the hooks
   */
  function __construct() {

    // Load localization domain
    load_plugin_textdomain( 'memepost', false, dirname(plugin_basename(__FILE__)) . '/languages' );

    // Register hooks
    add_action( 'admin_menu', array(&$this, 'register_settings_page') );
    add_action( 'admin_init', array(&$this, 'add_settings') );

    /* Use the admin_menu action to define the custom boxes */
    add_action('admin_menu', array(&$this, 'add_custom_box'));

    /* Use the save_post action to do something with the data entered */
    add_action('save_post', array(&$this, 'save_postdata'));

    // Enqueue the script
    add_action('template_redirect', array(&$this, 'add_script'));

    // Register filters
    add_filter('the_content', array(&$this, 'append_memepost_button') , 99);

    // register short code
    add_shortcode('memepost', array(&$this, 'shortcode_handler'));

    $plugin = plugin_basename(__FILE__);
    add_filter("plugin_action_links_$plugin", array(&$this, 'add_action_links'));

    // for outputing js code
    $this->deliver_js();
  }

  /**
   * Register the settings page
   */
  function register_settings_page() {
    add_options_page( __('Memepost', 'memepost'), __('Memepost', 'memepost'), 8, 'memepost', array(&$this, 'settings_page') );
  }

  /**
   * add options
   */
  function add_settings() {
    // Register options
    register_setting( 'memepost', 'memepost-style');
  }

  /**
   * Enqueue the Memepost script
   */
  function add_script() {
    // Enqueue the script
    wp_enqueue_script('memepost', get_option('home') . '/?memepostjs');
  }

  /**
   * Deliver the js through PHP
   * Thanks to Sivel http://sivel.net/ for this code
   */
  function deliver_js() {
    if ( array_key_exists('memepostjs', $_GET) ) {
      $options = get_option('memepost-style');

      $options['username'] = ($options['username'] == "")? "memepostjs" : $options['username'];
      $options['apikey'] = ($options['apikey'] == "") ? "R_00236a1e56326dbe30a6ea6885de4be6" : $options['apikey'];
      $options['text'] = ($options['text'] == "")? "MemePost" : $options['text'];

      header('Content-Type: text/javascript');
      print_memepost_js($options);

      // die after printing js
      die();
    }
  }

  /**
   * Adds the custom section in the Post and Page edit screens
   */
  function add_custom_box() {

    add_meta_box( 'memepost_enable_button', __( 'Memepost Button', 'memepost' ),
      array(&$this, 'inner_custom_box'), 'post', 'side' );
    add_meta_box( 'memepost_enable_button', __( 'Memepost Button', 'memepost' ),
      array(&$this, 'inner_custom_box'), 'page', 'side' );
  }

  /**
   * Prints the inner fields for the custom post/page section
   */
  function inner_custom_box() {
    global $post;
    $post_id = $post->ID;

    $option_value = '';

    if ($post_id > 0) {
      $enable_memepost = get_post_meta($post->ID, 'enable_memepost_button', true);
      if ($enable_memepost != '') {
        $option_value = $enable_memepost;
      }
    }
    // Use nonce for verification
?>
        <input type="hidden" name="memepost_noncename" id="memepost_noncename" value="<?php echo wp_create_nonce( plugin_basename(__FILE__) );?>" />

        <label><input type="radio" name="memepost_button" value ="1" <?php checked('1', $option_value); ?> /> <?php _e('Enabled', 'memepost'); ?></label>
        <label><input type="radio" name="memepost_button" value ="0"  <?php checked('0', $option_value); ?> /> <?php _e('Disabled', 'memepost'); ?></label>
<?php
  }

  /**
   * When the post is saved, saves our custom data
   * @param string $post_id
   * @return string return post id if nothing is saved
   */
  function save_postdata( $post_id ) {

    // verify this came from the our screen and with proper authorization,
    // because save_post can be triggered at other times

    if ( !wp_verify_nonce( $_POST['memepost_noncename'], plugin_basename(__FILE__) )) {
      return $post_id;
    }

    if ( 'page' == $_POST['post_type'] ) {
      if ( !current_user_can( 'edit_page', $post_id ))
        return $post_id;
    } else {
      if ( !current_user_can( 'edit_post', $post_id ))
        return $post_id;
    }

    // OK, we're authenticated: we need to find and save the data

    if (isset($_POST['memepost_button'])) {
      $choice = $_POST['memepost_button'];
      $choice = ($choice == '1')? '1' : '0';
      update_post_meta($post_id, 'enable_memepost_button', $choice);
    }
  }

  /**
   * hook to add action links
   * @param <type> $links
   * @return <type>
   */
  function add_action_links( $links ) {
    // Add a link to this plugin's settings page
    $settings_link = '<a href="options-general.php?page=memepost">' . __("Settings", 'memepost') . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
  }

  /**
   * Adds Footer links
   */
  function add_footer_links() {
    $plugin_data = get_plugin_data( __FILE__ );
    printf('%1$s ' . __("plugin", 'memepost') .' | ' . __("Version", 'memepost') . ' %2$s | '. __('by', 'memepost') . ' %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']);
  }

  /**
   * Dipslay the Settings page
   */
  function settings_page() {
?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e( 'Memepost Settings', 'memepost' ); ?></h2>

            <form id="smer_form" method="post" action="options.php">
                <?php settings_fields('memepost'); ?>
                <?php $options = get_option('memepost-style'); ?>
                <?php $options['username'] = ($options['username'] == "")? "memepostjs" : $options['username'];?>
                <?php $options['apikey'] = ($options['apikey'] == "") ? "R_00236a1e56326dbe30a6ea6885de4be6" : $options['apikey']; ?>
                <?php $options['align'] = ($options['align'] == "")? "hori" : $options['align'];?>
                <?php $options['position'] = ($options['position'] == "")? "after" : $options['position'];?>
                <?php $options['text'] = ($options['text'] == "")? "Memepost" : $options['text'];?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e( 'Bit.ly Username', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="text" name="memepost-style[username]" value="<?php echo $options['username']; ?>" /></label></p>
                            <p><?php _e("A default account will be used if left blank.", 'memepost');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Bit.ly API Key', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="text" name="memepost-style[apikey]" value="<?php echo $options['apikey']; ?>" /></label></p>
                            <p><?php _e("You can get it from <a href = 'http://bit.ly/account/' target = '_blank'>http://bit.ly/account/</a>.", 'memepost');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Display Preferences', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="checkbox" name="memepost-style[display-page]" value="1" <?php checked("1", $options['display-page']); ?> /> <?php _e("Display the button on pages", 'memepost');?></label></p>
                            <p><label><input type="checkbox" name="memepost-style[display-archive]" value="1" <?php checked("1", $options['display-archive']); ?> /> <?php _e("Display the button on archive pages", 'memepost');?></label></p>
                            <p><label><input type="checkbox" name="memepost-style[display-home]" value="1" <?php checked("1", $options['display-home']); ?> /> <?php _e("Display the button in home page", 'memepost');?></label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Position', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="memepost-style[position]" value="before" <?php checked("before", $options['position']); ?> /> <?php _e("Before the content of your post", 'memepost');?></label></p>
                            <p><label><input type="radio" name="memepost-style[position]" value="after" <?php checked("after", $options['position']); ?> /> <?php _e("After the content of your post", 'memepost');?></label></p>
                            <p><label><input type="radio" name="memepost-style[position]" value="both" <?php checked("both", $options['position']); ?> /> <?php _e("Before AND After the content of your post", 'memepost');?></label></p>
                            <p><label><input type="radio" name="memepost-style[position]" value="manual" <?php checked("manual", $options['position']); ?> /> <?php _e("Manually call the memepost button", 'memepost');?></label></p>
                            <p><?php _e("You can manually call the <code>memepost_button</code> function. E.g. <code>if (function_exists('memepost_button')) echo memepost_button();.", 'memepost'); ?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Type', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="radio" name="memepost-style[align]" value="vert" <?php checked("vert", $options['align']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/vert.png" /> (<?php _e("Vertical button", 'memepost');?>)</label></p>
                            <p><label><input type="radio" name="memepost-style[align]" value="hori" <?php checked("hori", $options['align']); ?> /> <img src ="<?php echo plugin_dir_url(__FILE__); ?>images/hori.png" /> (<?php _e("Horizontal button", 'memepost');?>)</label></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Button Text', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="text" name="memepost-style[text]" value="<?php echo $options['text']; ?>" /></label></p>
                            <p><?php _e("The text that you enter here will be displayed in the button.", 'memepost');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Message Prefix', 'memepost' ); ?></th>
                        <td>
                            <p><label><input type="text" name="memepost-style[prefix]" value="<?php echo $options['prefix']; ?>" /></label></p>
                            <p><?php _e("The text that you want to be added in front of each message. eg: <code>New Post : </code>", 'memepost');?></p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e( 'Link Attributes', 'memepost' ); ?></th>
                        <td>
                      <p><label><input type="checkbox" name="memepost-style[blank]" value="1" <?php checked("1", $options['blank']); ?> /> <?php _e("Open a new window/tab when Memepost button is clicked", 'memepost');?></label></p>
                      <p><?php _e("<code>target = '_blank'</code> will be added to link. Link will open in new window.", 'memepost');?></p>
                      <p><label><input type="checkbox" name="memepost-style[nofollow]" value="1" <?php checked("1", $options['nofollow']); ?> /> <?php _e("Nofollow Memepost Button Links", 'memepost');?></label></p>
                      <p><?php _e("<code>rel='nofollow'</code> will be added to link for SEO.", 'memepost');?></p>
                      <p><label><input type="checkbox" name="memepost-style[noindex]" value="1" <?php checked("1", $options['noindex']); ?> /> <?php _e("Noindex Memepost Buttons", 'memepost');?></label></p>
                      <p><?php _e("<code>rel='noindex'</code> will be added to link for SEO.", 'memepost');?></p>
                        </td>
                    </tr>

                </table>

                <p class="submit">
                    <input type="submit" name="memepost-submit" class="button-primary" value="<?php _e('Save Changes', 'memepost') ?>" />
                </p>
            </form>
        </div>
<?php
    // Display credits in Footer
    add_action( 'in_admin_footer', array(&$this, 'add_footer_links'));
  }

  /**
   * Append the memepost_button
   * 
   * @global object $post Current post
   * @param string $content Post content
   * @return string modifiyed content
   */
  function append_memepost_button($content) {

    global $post;
    $options = get_option('memepost-style');

    $enable_memepost = get_post_meta($post->ID, 'enable_memepost_button', true);

    if ($enable_memepost != "") {
      // if option per post/page is set
      if ($enable_memepost == "1") {
        // Memepost button is enabled

        $content = $this->build_memepost_button($content, $options['position']);

      } elseif ($enable_memepost == "0") {
        // Memepost button is disabled
        // Do nothing
      }

    } else {
      //Option per post/page is not set
      if (is_single()
        || ($options['display-page'] == "1" && is_page())
        || ($options['display-archive'] == "1" && is_archive())
        || ($options['display-home'] == "1" && is_home())) {

          $content = $this->build_memepost_button($content, $options['position']);
        }
    }
    return $content;
  }

  /**
   * Helper function for append_memepost_button
   *
   * @param string $content The post content
   * @param string $position Position of the button
   * @return string Modifiyed content
   */
  function build_memepost_button($content, $position) {
    $button = memepost_button(false);

    switch ($position) {
    case "before":
      $content = $button . $content;
      break;
    case "after":
      $content = $content . $button;
      break;
    case "both":
      $content = $button . $content . $button;
      break;
    case "manual":
      default:
        // nothing to do
        break;
    }
    return $content;
  }

  /**
   * Short code handler
   * @param <type> $attr
   * @param <type> $content 
   */
  function shortcode_handler($attr, $content) {
    return memepost_button(false);
  }

  // PHP4 compatibility
  function Memepost() {
    $this->__construct();
  }
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'Memepost' ); function Memepost() { global $Memepost; $Memepost = new Memepost(); }

/**
 * Template function to add the Memepost button
 */
function memepost_button($display = true) {
  global $wp_query;
  $post = $wp_query->post;
  $permalink = get_permalink($post->ID);
  $title = get_the_title($post->ID);

  $enable_memepost = get_post_meta($post->ID, 'enable_memepost_button', true);

  if ($enable_memepost == "" || $enable_memepost == "1") {
    // if option per post/page is set or Memepost button is enabled

    $options = get_option('memepost-style');
    $align = ($options['align'] == "vert")? "vert": "";

    $output = "<a href='$permalink' class='memepost $align' ";

    if ($options['blank'] == '1') {
      $output .= ' target="_blank" ';
    }

    if ($options['nofollow'] == '1' && $options['noindex'] == '1') {
      $output .= ' rel="noindex,nofollow" ';
    }
    else if ($options['nofollow'] == '1') {
      $output .= ' rel="nofollow" ';
    }
    else if ($options['noindex'] == '1') {
      $output .= ' rel="noindex" ';
    }

    $output .= ">$title</a>";

  } else {
    $output = '';
  }

  if ($display) {
    echo $output;
  } else {
    return $output;
  }
}

/**
 * Print Memepost js
 * @param array $options Plugin options
 */
function print_memepost_js($options) {
?>
/*
 * Easy Retweet Button
 * http://ejohn.org/blog/retweet/
 *   by John Resig (ejohn.org)
 *
 * Licensed under the MIT License:
 * http://www.opensource.org/licenses/mit-license.php
 */

(function(){

window.MemepostJS = {
	// Your Bit.ly Username
	bitly_user: "<?php echo $options['username'];?>",

	// Your Bit.ly API Key
	// Found here: http://bit.ly/account
	bitly_key: "<?php echo $options['apikey']; ?>",

	// The text to replace the links with
	link_text: (/windows/i.test( navigator.userAgent) ? "&#9658;" : "&#9851;") +
		"&nbsp;<?php echo $options['text'];?>",

	// What # to show (Use "clicks" for # of clicks or "none" for nothing)
	count_type: "clicks",

	// Memepost Prefix text
	// "[Via Memepost]" would result in: "[Via Memepost] Link Title http://bit.ly/asdf"
	prefix: "<?php echo $options['prefix']; ?> ",

	// Style information
	styling: "a.memepost { font: 12px Helvetica,Arial; color: #000; text-decoration: none; border: 0px; }" +
		"a.memepost span { color: #FFF; background: #934B93; margin-left: 2px; border: 1px solid #863486; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; padding: 3px; }" +
		"a.vert { display: block; text-align: center; font-size: 16px; float: left; margin: 4px; }" +
		"a.memepost strong.vert { display: block; margin-bottom: 4px; background: #F5F5F5; border: 1px solid #EEE; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; padding: 3px; }" +
		"a.memepost span.vert { display: block; font-size: 12px; margin-left: 0px; }"
};

//////////////// No Need to Configure Below Here ////////////////

var loadCount = 1;

// Asynchronously load the Bit.ly JavaScript API
// If it hasn't been loaded already
if ( typeof BitlyClient === "undefined" ) {
	var head = document.getElementsByTagName("head")[0] ||
		document.documentElement;
	var script = document.createElement("script");
	script.src = "http://bit.ly/javascript-api.js?version=latest&login=" +
		MemepostJS.bitly_user + "&apiKey=" + MemepostJS.bitly_key;
	script.charSet = "utf-8";
	head.appendChild( script );

	var check = setInterval(function(){
		if ( typeof BitlyCB !== "undefined" ) {
			clearInterval( check );
			head.removeChild( script );
			loaded();
		}
	}, 10);

	loadCount = 0;
}

if ( document.addEventListener ) {
	document.addEventListener("DOMContentLoaded", loaded, false);

} else if ( window.attachEvent ) {
	window.attachEvent("onload", loaded);
}

function loaded(){
	// Need to wait for doc ready and js ready
	if ( ++loadCount < 2 ) {
		return;
	}

	var elems = [], urlElem = {}, hashURL = {};

	BitlyCB.shortenResponse = function(data) {
		for ( var url in data.results ) {
			var hash = data.results[url].userHash;
			hashURL[hash] = url;

			var elems = urlElem[ url ];

			for ( var i = 0; i < elems.length; i++ ) {
				elems[i].href += hash;
        elems[i].href += " (via <a href='http://gofedora.com/'>Memepost</a>)";
			}

			if ( MemepostJS.count_type === "clicks" ) {
				BitlyClient.stats(hash, 'BitlyCB.statsResponse');
			}
		}
	};

	BitlyCB.statsResponse = function(data) {
		var clicks = data.results.clicks, hash = data.results.userHash;
		var url = hashURL[ hash ], elems = urlElem[ url ];

		if ( clicks > 0 ) {
			for ( var i = 0; i < elems.length; i++ ) {
				var strong = document.createElement("strong");
				strong.appendChild( document.createTextNode( clicks + " " ) );
				elems[i].insertBefore(strong, elems[i].firstChild);

				if ( /(^|\s)vert(\s|$)/.test( elems[i].className ) ) {
					elems[i].firstChild.className = elems[i].lastChild.className = "vert";
				}
			}
		}

		hashURL[ hash ] = urlElem[ url ] = null;
	};

	if ( document.getElementsByClassName ) {
		elems = document.getElementsByClassName("memepost");
	} else {
		var tmp = document.getElementsByTagName("a");
		for ( var i = 0; i < tmp.length; i++ ) {
			if ( /(^|\s)memepost(\s|$)/.test( tmp[i].className ) ) {
				elems.push( tmp[i] );
			}
		}
	}

	if ( elems.length && MemepostJS.styling ) {
		var style = document.createElement("style");
		style.type = "text/css";

		try {
			style.appendChild( document.createTextNode( MemepostJS.styling ) );
		} catch (e) {
			if ( style.styleSheet ) {
				style.styleSheet.cssText = MemepostJS.styling;
			}
		}

		document.body.appendChild( style );
	}

	for ( var i = 0; i < elems.length; i++ ) {
		var elem = elems[i];

		if ( /(^|\s)self(\s|$)/.test( elem.className ) ) {
			elem.href = window.location;
			elem.title = document.title;
		}

		var origText = elem.title || elem.textContent || elem.innerText,
			href = elem.href;

		elem.innerHTML = "<span>" + MemepostJS.link_text + "</span>";
		elem.title = "";
		elem.href = "http://meme.yahoo.com/dashboard/?text=" +
      encodeURIComponent(MemepostJS.prefix + origText + " http://bit.ly/");

		if ( urlElem[ href ] ) {
			urlElem[ href ].push( elem );
		} else {
			urlElem[ href ] = [ elem ];
      BitlyClient.call('shorten', {'longUrl':href, 'history':'1'}, 'BitlyCB.shortenResponse');
    }
	}

}

})();
<?php
}
?>
