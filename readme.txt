=== Memepost ===
Contributors: kulbirsaini
Tags: posts, yahoo meme, memepost, yahoo, yahoomeme, meme
Requires at least: 2.1
Tested up to: 3.0
Stable tag: trunk
Donate link: http://gofedora.com/donate/

Adds a 'Post to Yahoo! Meme' button to your wordpress pages/post.

== Description ==

Memepost is a wordpress plugin which lets you add a 'Memepost' or 'Post to Meme' or 'MemeThis' or 'MemeIt' button to your wordpress posts.

**Demo:** Live demo of this plugin can be seen [here](http://gofedora.com/).

**Features**

    *  Easy to setup. Copy, Activate, Setup and Go :)
    *  No dependencies.
    *  Option to configure button text.
    *  Option to control the location of button on a post.
    *  Option to enable/disable button per post/page.
    *  Options to noindex/nofollow memepost button.
    *  Supports internationalization (i18n).


**Usage**

There are three ways you can add the memepost button. Automatic way, manual way and using shortcodes.

#### Automatic way

Install the Plugin and choose the type and position of the button from the Plugin’s settings page. You can also specifically enable/disable the button for each post or page from the write post/page screen.

#### Manual way

If you want more control over the way the button should be positioned, then you can manually call the button using the following code.

if (function_exists('memepost_button')) echo memepost_button();

#### Using shortcodes

You can also place the shortcode [memepost] anywhere in your post. This shortcode will be replaced by the button when the post is rendered.

More information available on the [plugin home page](http://gofedora.com/memepost).
	

== Installation ==

1. Unzip memepost.zip.
2. Copy memepost folder to wp-content/plugins/ folder.
3. Activate the plugin by visiting wp-admin/plugins.php.
4. Go to wp-admin/options-general.php?page=memepost .


== Screenshots ==
1. Memepost settings page
2. Enable/Disable button in the write post/page interface.


== Credits ==

[Kulbir Saini](http://gofedora.com/) - This plugin is completely inspired from [Easy Retweet](http://wordpress.org/extend/plugins/easy-retweet/). Basically the code was just copied and modified to support Yahoo! Meme and then a few more features were added.

== Contact ==

Suggestion, fixes, rants, congratulations, gifts et al to kulbirsaini25 [at] gmail [dot] com

