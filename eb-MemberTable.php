<?php

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class MemberTable extends WP_List_Table {

		var $school;
		var $group;
		
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'member',     //singular name of the listed records
            'plural'    => 'members',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    
    /** ************************************************************************
     * Return the contents for all those columns not specified.
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
			return print_r($item,true); // Should not get called as all columns are defined
    }
    
    /** ************************************************************************
     * Return the contents for the name column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_name($item){
     	return $item['blogname'];
    }
    

    /** ************************************************************************
     * Return the contents for the status column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_status($item){
			//Build row actions
			if ($item['status'])
				$actions = array(
						'demote'    => sprintf('<a href="?page=%s&action=%s&selected_school=%s&selected_group=%s&member=%s">Make student</a>',$_REQUEST['page'],'demote',$this->school,$this->group,$item['blog_id']),
				);
			else 
				$actions = array(
						'promote'    => sprintf('<a href="?page=%s&action=%s&selected_school=%s&selected_group=%s&member=%s">Make teacher</a>',$_REQUEST['page'],'promote',$this->school,$this->group,$item['blog_id']),
				);
			
    	return ($item['status'] ? 'Teacher' : 'Student').$this->row_actions($actions);
    }
        

    /** ************************************************************************
     * Return the contents for the path column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_path($item){
        
        //Build rollover actions
        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&selected_school=%s&selected_group=%s&member=%s">Delete</a>',$_REQUEST['page'],'delete',$this->school,$this->group,$item['blog_id']),
            'visit'    => sprintf('<a href="%s">Visit</a>', esc_url($item['siteurl'])),
        );
        
        //Return the title contents
				$path = esc_url( network_admin_url( 'site-info.php?id='.$item['blog_id'] ) );

        return sprintf('<a href="%1$s" class="edit">%2$s</a> %3$s',
            /*$1%s*/ $path,
						/*$2%s*/ $item['path'],            
            /*$3%s*/ $this->row_actions($actions)
        );
    }


    /** ************************************************************************
     * Return the contents for the customize column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_customize($item){
			//Build row actions
			if ($item['customize'])
				$actions = array(
						'customize'    => sprintf('<a href="?page=%s&action=%s&selected_school=%s&selected_group=%s&member=%s">May NOT customize blog</a>',$_REQUEST['page'],'nocustomize',$this->school,$this->group,$item['blog_id']),
				);
			else 
				$actions = array(
						'nocustomize'    => sprintf('<a href="?page=%s&action=%s&selected_school=%s&selected_group=%s&member=%s">May customize blog</a>',$_REQUEST['page'],'customize',$this->school,$this->group,$item['blog_id']),
				);
			
    	return ($item['customize'] ? 'Yes' : 'No').$this->row_actions($actions);
    }


    /** ************************************************************************
     * Return the contents for the checkbox column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("member")
            /*$2%s*/ $item['blog_id']           //The value of the checkbox should be the blog's id
        );
    }
    

    /** ************************************************************************
     * Return the contents for the group column
     * 
     * @see WP_List_Table::::single_row_columns()
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td> (movie title only)
     **************************************************************************/
    function column_group($item){
    	$groupInfo = get_dropdown_groups($this->school);
    	$groupList = to_dropdown($groupInfo, $this->group);    	
    	
    	$selectList = '<select style="width:100px" name="update_group">'.$groupList.'</select>';    	
    	$hidden1 = sprintf('<input type="hidden" name="blog_id" value="%s" />', $item['blog_id']);
    	$hidden2 = sprintf('<input type="hidden" name="page" value ="%s" />', $_REQUEST['page']);
    	$hidden3 = '<input type="hidden" name="switch_group" value ="switch_group" />';
    	$hidden4 = sprintf('<input type="hidden" name="selected_school" value ="%s" />', $this->school);
    	$hidden5 = sprintf('<input type="hidden" name="selected_group" value ="%s" />', $this->group);
    	$submitButton = '<input type="submit" value="Set Group" />';
    	
    	$groupColumn = '<form method="post">'.$selectList.$hidden1.$hidden2.$hidden3.$hidden4.$hidden5.$submitButton.'</form>';
    	
			return ($groupColumn);
    }
    
    
    /** ************************************************************************
     * Definition of the table's columns and titles. 
     * 
     * @see WP_List_Table::::single_row_columns()
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'path'     => 'Path',
            'name'    => 'Name',
            'status'  => 'Status',
            'customize'  => 'Customize',
            'group'  => 'Group'
        );
        return $columns;
    }
    
    /** ************************************************************************
     * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
     * you will need to register it here. This should return an array where the 
     * key is the column that needs to be sortable, and the value is db column to 
     * sort by.
     * 
     * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
     **************************************************************************/
    function get_sortable_columns() {
        $sortable_columns = array(
            'path'     => array('path',false),
            'name'    => array('name',false),
            'status'  => array('status',true)     //true means its already sorted
        );
        return $sortable_columns;
    }
    
    function get_hidden_columns() {
        $hidden_columns = array(
            'siteurl'     => 'siteurl',
        );
        return $hidden_columns;
    }
    
    /** ************************************************************************
     * Definition of the bulk options available.
     * If this method returns an empty value, no bulk action will be rendered.
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }
    
    
    /** ************************************************************************
     * Delete selected sites and users attached to them. Only delete users if 
     * they have exactly one blog.
     * 
     * @see $this->prepare_items()
     **************************************************************************/
    function process_bulk_action() {
				if ( ! ( current_user_can( 'manage_sites' ) && current_user_can( 'delete_sites' ) ) )
					wp_die( __( 'You do not have permission to access this page.' ) );

				$blogList = array();
				if (is_array($_REQUEST['member'])) {
					$blogList = $_REQUEST['member'];
				} else {
					array_push($blogList, $_REQUEST['member']);
				}
        
        //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {

					foreach ($blogList as $blog_id) {
						if ( $blog_id != '0' && $blog_id != $current_site->blog_id && current_user_can( 'delete_site', $blog_id ) ) {
							$users = get_users( array( 'blog_id' => $blog_id, 'fields' => 'ids' ) );
							foreach ($users as $user_id) {
								$blogs = get_blogs_of_user($user_id);
								if (current_user_can( 'delete_user', $user_id ) && count($blogs) == 1) {
									wpmu_delete_user($user_id);
								}								
							}
							wpmu_delete_blog( $blog_id, true );
						}
					}        
       }       
    }


    /** ************************************************************************
		 * Check if a blog can be customised by a user with author role or not.
		 * 
     * @param int $blog_id A blog id
		 * @return boolean True if the author role for the given blog has edit_theme_options capability, false otherwise
     **************************************************************************/
    function canCustomize($blog_id) {
    	switch_to_blog($blog_id);		
			$author_role = get_role('author');
			$canCustomize = $author_role->has_cap('edit_theme_options');
			restore_current_blog();

			return $canCustomize;
    }

    
    /** ************************************************************************
		 * Return an array containing details of certain blogs filtered by a school and group name
		 * 
		 * @return array An array containing blog details 
     **************************************************************************/
		function get_student_blogs() {
			global $wpdb;

			//$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
			//$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
			
			switch_to_blog(1);
			$table_name = $wpdb->prefix."group_list";
			$group_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE school_name = %s AND group_name = %s", $this->school, $this->group));
			$table_name = $wpdb->prefix."group_members";
			$blogs = $wpdb->get_results($wpdb->prepare("SELECT blog_id, leader FROM $table_name WHERE group_id = %d", $group_id));
			restore_current_blog();

			$blog_details = array();

			foreach ($blogs as $blog) {
				$item = get_blog_details((int) $blog->blog_id, true);
				//$users = get_users( array( 'blog_id' => $blog->blog_id, 'fields' => 'ids' ) );
				$custom = $this->canCustomize((int) $item->blog_id);
				$blog_details[] = array('blog_id' => $item->blog_id, 'blogname' => $item->blogname, 'path' => $item->path, 'status' => $blog->leader, 'customize' => $custom, 'siteurl' => $item->siteurl);
			}

			return($blog_details);
		}


    /** ************************************************************************
     * REQUIRED! This is where you prepare your data for display. This method will
     * usually be used to query the database, sort and filter the data, and generally
     * get it ready to be displayed. At a minimum, we should set $this->items and
     * $this->set_pagination_args(), although the following properties and methods
     * are frequently interacted with here...
     * 
     * @uses $this->_column_headers
     * @uses $this->items
     * @uses $this->get_columns()
     * @uses $this->get_sortable_columns()
     * @uses $this->get_pagenum()
     * @uses $this->set_pagination_args()
     **************************************************************************/
    function prepare_items($school, $group) {

        // number of records per page to show
        $per_page = 10;

				// Remember the current filter        
        $this->school = $school;
				$this->group = $group;

        /**
         * Define the column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array(); //$this->get_hidden_columns();
        $sortable = array(); // $this->get_sortable_columns();
        
        
        /**
         * Build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        // Handle bulk actions
        $this->process_bulk_action();
        
        
        /**
         * In a real-world implementation, you will probably want to 
         * use sort and pagination data to build a custom query instead, as you'll
         * be able to use your precisely-queried data immediately.
         */        
        $data = $this->get_student_blogs();

                
        /**
         * REQUIRED for pagination. Let's figure out what page the user is currently 
         * looking at. We'll need this later, so you should always include it in 
         * your own package classes.
         */
        $current_page = $this->get_pagenum();
        
        /**
         * REQUIRED for pagination. Let's check how many items are in our data array. 
         * In real-world use, this would be the total number of items in your database, 
         * without filtering. We'll need this later, so you should always include it 
         * in your own package classes.
         */
        $total_items = count($data);
        
        
        /**
         * The WP_List_Table class does not handle pagination for us, so we need
         * to ensure that the data is trimmed to only the current page. We can use
         * array_slice() to 
         */
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        
        /**
         * REQUIRED. Now we can add our *sorted* data to the items property, where 
         * it can be used by the rest of the class.
         */
        $this->items = $data;
        
        
        /**
         * REQUIRED. We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }
    
}
