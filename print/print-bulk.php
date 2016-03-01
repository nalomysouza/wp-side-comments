<?php

/**
 * Based on http://www.foxrunsoftware.net/articles/wordpress/add-custom-bulk-action/
 * 
 */


// PHP 5.3 and later:
namespace CTLT_WP_Side_Comments;

class BulkPrint
{
	public function __construct()
	{
		//Bulk actions
		
		add_action('admin_footer-edit.php', array( $this, 'admin_scripts'));
		
		add_action('load-edit.php',         array( $this, 'bulk_action'));
		add_action('admin_notices',         array( $this, 'bulk_admin_notices'));
	}
	
	/**
	 * add Bulk Action to post list
	 */
	function admin_scripts()
	{
		global $post_type;
			
		if($post_type == 'post' || $post_type == 'page')
		{
			wp_enqueue_script('ctlt-side-comments-bulk-print', CTLT_WP_SIDE_COMMENTS_PLUGIN_URL."/print/js/admin.js", array('jquery'));
	   	}
	}
			
			
	/**
	 * handle the Bulk Action
	 * 
	 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
	 */
	function custom_bulk_action()
	{
		global $typenow;
		$post_type = $typenow;
		
		if($post_type == 'post' || $post_type == 'page')
		{
			// get the action
			$wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
			$action = $wp_list_table->current_action();
			
			$allowed_actions = array("export");
			if(!in_array($action, $allowed_actions)) return;
			
			// security check
			check_admin_referer('bulk-posts');
			
			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if(isset($_REQUEST['post'])) {
				$post_ids = array_map('intval', $_REQUEST['post']);
			}
			
			if(empty($post_ids)) return;
			
			// this is based on wp-admin/edit.php
			$sendback = remove_query_arg( array('exported', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
			if ( ! $sendback )
				$sendback = admin_url( "edit.php?post_type=$post_type" );
			
			$pagenum = $wp_list_table->get_pagenum();
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );
			
			switch($action) {
				case 'export':
					
					// if we set up user permissions/capabilities, the code might look like:
					//if ( !current_user_can($post_type_object->cap->export_post, $post_id) )
					//	wp_die( __('You are not allowed to export this post.') );
					
					$exported = 0;
					foreach( $post_ids as $post_id ) {
						
						if ( !$this->perform_export($post_id) )
							wp_die( __('Error exporting post.') );
		
						$exported++;
					}
					
					$sendback = add_query_arg( array('exported' => $exported, 'ids' => join(',', $post_ids) ), $sendback );
				break;
				
				default: return;
			}
			
			$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
			
			wp_redirect($sendback);
			exit();
		}
	}
	
	
	/**
	 * Display an admin notice on the Posts page after print
	 */
	function custom_bulk_admin_notices()
	{
		global $post_type, $pagenow;
		
		if($pagenow == 'edit.php' && $post_type == 'post' && isset($_REQUEST['exported']) && (int) $_REQUEST['exported']) {
			$message = sprintf( _n( 'Post exported.', '%s posts exported.', $_REQUEST['exported'] ), number_format_i18n( $_REQUEST['exported'] ) );
			echo "<div class=\"updated\"><p>{$message}</p></div>";
		}
	}
	
	function perform_export($post_id)
	{
		// do whatever work needs to be done
		return true;
	}
	
}

$BulkPrint = new BulkPrint();