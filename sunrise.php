<?php
/*
Plugin Name: WordPress Development Site Staging
Plugin URI: http://thecodecave/extend/plugins/WPDev
Description: Allows the configuration of a staging or development site that can run on an unaltered copy of the live database.
Version: 1.3
Author: Brian "The eHermit" Layman
Author URI: http://eHermitsInc.com
License: GPLv2
Requires: 2.9

Copyright 2012  Brian Layman  (email : plugins@thecodecave.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// constants
define( 'ORIG_URL', 'example.com'  );
define( 'STAGING_URL', 'devsite.com'  );

if ( strpos( STAGING_URL, ORIG_URL ) === false ) {
	define( 'URL_COLLISION', false );
} else {
	define( 'URL_COLLISION', true );
}

// These functions occur on the staging environment only.  Note that some staging settings occur in wp-config.php
if ( !LIVE_SITE  ) { // ADD NOTHING BUT COMMENTS BEFORE THIS IF STATEMENT
	// Force the site to the private
	function _ehi_mustbeloggedin() {
		if ( !is_user_logged_in() && !WHITE_LISTED_IP && !stripos( $_SERVER['REQUEST_URI'], 'wp-login.php' ) ) die( 'private' );
	}
	add_action( 'wp_head', '_ehi_mustbeloggedin'  );

	// An action that sends all text to the replace function when the php is done processing ( No ob_end needed )
	function buffer_start() {
		ob_start( array( &$this, '_ehi_fixurl' ) );
	}

	// Redirect the real site to the staging site by replacing all urls in the passed text.
	function _ehi_fixurl( $buffer  ) {
		// If we have an url collision, then look for the staging url in the buffer and don't replace it.
		if ( ( URL_COLLISION ) and ( strpos( $buffer, STAGING_URL ) !== false ) ) return $buffer;
		$buffer = str_replace( ORIG_URL, STAGING_URL, $buffer );
		return $buffer;
	}
	
	// Sometimes the url is retrieved from the $_server constant and used in queries.  You need to make sure 
	// the where clauses on the DB will match wath is actually stored in the DB.
	function _ehi_unfixurl( $buffer  ) {
		$buffer = str_replace( STAGING_URL, ORIG_URL, $buffer );
		return $buffer;
	}
	
	// Replace the contents of the relevant bloginfo field
	function _ehi_fixbloginfo( $result = '', $show = '' ) {
		if ( $show == 'stylesheet_url' || $show == 'template_url' || $show == 'wpurl' || $show == 'home' || $show == 'siteurl' || $show == 'url' ) {
			$result = $this->_ehi_fixurl( $result );
		}
		return $result;
	}

	// Start output buffering in order to replace the url via a callback function
	add_action( 'plugins_loaded', 'buffer_start', 10, 1  );

	// Filter all behaviors that could contain links and urls to the live site
	add_filter( 'option_url', '_ehi_fixurl', 10, 1  );
	add_filter( 'option_siteurl', '_ehi_fixurl', 10, 1  );
	add_filter( 'option_home', '_ehi_fixurl', 10, 1  );
	add_filter( 'site_option_url', '_ehi_fixurl', 10, 1  );
	add_filter( 'site_option_siteurl', '_ehi_fixurl', 10, 1  );
	add_filter( 'site_option_home', '_ehi_fixurl', 10, 1  );
	add_filter( 'page_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'post_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'category_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'get_archives_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'tag_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'search_link', '_ehi_fixurl', 10, 1  );
	add_filter( 'home_url', '_ehi_fixurl', 10, 1  );

	// The blog info calls pass arrays and need to be filtered seperate.
	add_filter( 'bloginfo', '_ehi_fixbloginfo' );
	add_filter( 'bloginfo_url', '_ehi_fixbloginfo' );
	
	// Ensure that all queries match what is in the database
	add_filter( 'query', '_ehi_unfixurl', 10, 1  );

} // SHOULD BE THE LAST LINE IN THE FILE