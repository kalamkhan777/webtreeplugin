<?php
/*
*	Plugin name: Webtree Registration
*	Description: This plugin will provide the possibility to do registration
*	Version: 1.0
*	Author: Abdul Kalam
*	Author URI: 
*	License: 
*/

class Webtree {
    private
    $_ASSETS_VERSION;

    //Function for calling all the hooks
    function __construct() 
    {
        $this->_ASSETS_VERSION = "1.0";
        
        //Hook for creating database table on activation
        register_activation_hook( __FILE__,array( $this, "webtreeActivation" ));
        
        // Hook for registration and creating shortcodes
        add_action('init', array( $this, "webtree_registration" ));
        
        //Create Pages if not Exists
        register_activation_hook( __FILE__,array( $this, "webtree_page_creation" ));
        
        //Add role for contributor
        add_role('contributor', __(
           'Contributor'),
           array(
               'read'  => true // Allows a user to read
               )
        );
        
        //Add admin menu and page
        add_action('admin_menu',array( $this, 'webtreeAdminMenu' ));
        
        // Add page template
        add_filter( 'page_template', array( $this, 'webtree_page_template'  ));
        
        //Enqueue Frontend Scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'webtree_frontend_scripts' ) );
        
        //Enqueue Backend Scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'webtree_admin_scripts' ));
    }
    

    function __destruct() {}
    
    // Function for crating database on plugin activaton
    function webtreeActivation() {	
    	global $wpdb;
    	
    	//Create table for webtree_customers
    	$charset_collate = $wpdb->get_charset_collate();
    	$table_name = $wpdb->prefix . "webtree_customers";
    	$sql = "CREATE TABLE $table_name (
    		`id` int(11) NOT NULL AUTO_INCREMENT,
    		`name` varchar(100) NOT NULL,
    		`email` varchar(100) NOT NULL,
    		`dob` datetime DEFAULT NULL,
    		`phone` varchar(100) NOT NULL,
    		`gender` varchar(100) NOT NULL,
    		`cr_number` varchar(100) NOT NULL,
    		`address` varchar(100) NOT NULL,
    		`city` varchar(100) NOT NULL,
    		`country` varchar(100) NOT NULL,
    		`status` int(11) NOT NULL,
    		PRIMARY KEY(id)
    	) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    	";
    	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    		//echo ABSPATH . "wp-admin/includes/upgrade.php"; exit;
    		require_once(ABSPATH . "wp-admin/includes/upgrade.php");
    		dbDelta($sql);
    	}else{
    		//echo "error"; exit;
    	}
    }

    //Function for creating admin pages
    function webtreeAdminMenu() 
    {
        add_menu_page('Customer Management','Customer Management','manage_options','customerManagement',array( $this, "customerManagementPage"),'dashicons-wordpress');
        add_submenu_page( 'customerManagement', 'Customer Management', 'All Customers','manage_options', 'customerManagement');
        add_submenu_page( 'customerManagement', 'Add Customer', 'Add Customer','manage_options', 'addCustomer',array( $this, "addCustomerPage"));
        add_submenu_page( 'customerManagement', '', '','manage_options', 'editCustomer',array( $this, "editCustomerPage"));
    }
    
    // Enqueue Frontend Scripts
    function webtree_frontend_scripts() {

		wp_enqueue_script('jquery');
		wp_enqueue_script( 'custom-js', plugins_url( "/js/custom.js" , __FILE__ ),  $this->_ASSETS_VERSION, true );
		
		wp_localize_script( 'common-registration-js', 'my_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        if(is_page('webtree-customers') || is_page('webtree-add-customer') || is_page('webtree-edit-customer')){
            wp_register_style('prefix_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
            wp_enqueue_style('prefix_bootstrap');
        
            //CSS - DATATABLES
            wp_register_style('datatables_css', 'https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css');
            wp_enqueue_style('datatables_css');
        
            // JS
            wp_register_script('prefix_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
            wp_enqueue_script('prefix_bootstrap');
    		//adding datatables
		
			wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js' );
			wp_enqueue_script('datatables-bst-js', 'https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js' );
		}
    }
    
    // Enqueue Backend Scripts
    function webtree_admin_scripts()
    {
        // CSS
        wp_register_style('prefix_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css');
        wp_enqueue_style('prefix_bootstrap');
    
        //CSS - DATATABLES
        wp_register_style('datatables_css', 'https://cdn.datatables.net/1.10.22/css/dataTables.bootstrap4.min.css');
        wp_enqueue_style('datatables_css');
    
        // JS
        wp_register_script('prefix_bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js');
        wp_enqueue_script('prefix_bootstrap');
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js' );
    	wp_enqueue_script('datatables-bst-js', 'https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js' );
    	wp_enqueue_script('custom-admin-js', plugins_url( "/js/custom-admin.js" , __FILE__ ),  $this->_ASSETS_VERSION, true);
    
    }

    // Function for insert and update the data and declaring shortcodes
    function webtree_registration() 
    {
            global $wpse_email_exists;
            global $current_user, $wp_roles;
            $error = array(); 
            
            
            //Shortcode for user registration
           	add_shortcode('wt-custom-registration',array( $this, 'webtree_custom_registration'));
           	
           	//Shortcode for user Edit
           	add_shortcode('wt-custom-edit',array( $this, 'webtree_edit_profile'));

            // Shortcode for customers listing in backend
           	add_shortcode('wt-all-customers',array( $this, 'webtree_all_customers'));
           	
           	//Shortcode for active customers listing in frontend
           	add_shortcode('wt-all-customers-front',array( $this, 'webtree_all_customers_frontend'));
           	
           	global $wpdb;
            //Update profile
           	if ( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $_POST['webtree-update'] ) && $_POST['webtree-update'] == 'Update' ) 
            {
                
                $current_user_id = sanitize_text_field($_POST['id']);
                
                $name          = sanitize_text_field($_POST['user_name']);
        	    $email         = sanitize_email($_POST['email']);
        		$password      = sanitize_text_field($_POST['phone']);
        		$phone      = sanitize_text_field($_POST['phone']);
        		$dob      = sanitize_text_field($_POST['dob']);
        		$gender      = sanitize_text_field($_POST['gender']);
        		$cr_number      = sanitize_text_field($_POST['cr_number']);
        		$address       = sanitize_text_field($_POST['address']);
        		$city       = sanitize_text_field($_POST['city']);
        		$country       = sanitize_text_field($_POST['country']);
        		$status       = sanitize_text_field($_POST['status']);
        		
        		
        	    /* Update user password. */
        	    if ( !empty($_POST['phone'] ) ) 
                {
        	        wp_update_user( array( 'email' => $email, 'user_pass' => esc_attr( $_POST['phone'] ) ) );
        	    }
        	    
        	    $table_name = $wpdb->prefix . "webtree_customers";       
        	    $wpdb->query("UPDATE $table_name SET name='$name',phone='$phone',dob='$dob',gender='$gender',cr_number='$cr_number',address='$address',city='$city',country='$country',status='$status' WHERE id='$current_user_id'");
    			
    			$user = get_user_by( 'email', $email );
                $user_id = $user->ID;
    			
    			update_user_meta($user_id, 'phone', $phone);
    	        update_user_meta($user_id, 'dob', $dob);
    	        update_user_meta($user_id, 'gender', $gender);
    	        update_user_meta($user_id, 'cr_number', $cr_number);
    	        update_user_meta($user_id, 'address', $address);
    	        update_user_meta($user_id, 'city', $city);
    	        update_user_meta($user_id, 'country', $country);
    			
            }
            
        	//register user
            if(isset($_POST['registerCustomer']) && $_POST['registerCustomer'] == 'registerCustomer')
        	{
        	    $name          = sanitize_text_field($_POST['user_name']);
        	    $email         = sanitize_email($_POST['email']);
        		$password      = sanitize_text_field($_POST['phone']);
        		$phone      = sanitize_text_field($_POST['phone']);
        		$dob      = sanitize_text_field($_POST['dob']);
        		$gender      = sanitize_text_field($_POST['gender']);
        		$cr_number      = sanitize_text_field($_POST['cr_number']);
        		$address       = sanitize_text_field($_POST['address']);
        		$city       = sanitize_text_field($_POST['city']);
        		$country       = sanitize_text_field($_POST['country']);
        		$status       = sanitize_text_field($_POST['status']);
        		
        	    
        	    // Construct a username from the user's name
        	    $username = str_replace(' ', '', $name);
        
        	    if ( !email_exists( $email ) ) 
        	    {
        	        // Find an unused username
        	        $username_tocheck = $username;
        	        $i = 1;
        	        while ( username_exists( $username_tocheck ) ) 
                    {
        	            $username_tocheck = $username . $i++;
        	        }
        	        $username = $username_tocheck;
        	        // Create the user      
        	    	$userdata = array(
        	            'user_login'  => $username,
        	            'user_pass'   => $password,
        	            'user_email'  => $email,
        	            'nickname'    => $username,
        	            'display_name'=> $username,
        	            'first_name'  => $name,
        	            'role'        => 'contributor'
        	        );
        
        	        $user_id = wp_insert_user( $userdata );
        
        	        update_user_meta($user_id, 'phone', $phone);
        	        update_user_meta($user_id, 'dob', $dob);
        	        update_user_meta($user_id, 'gender', $gender);
        	        update_user_meta($user_id, 'cr_number', $cr_number);
        	        update_user_meta($user_id, 'address', $address);
        	        update_user_meta($user_id, 'city', $city);
        	        update_user_meta($user_id, 'country', $country);
        	        
        	        //Save data in custom table
        	        if(!empty($user_id))
        	        {
        	            global $wpdb;
        	            $table_name = $wpdb->prefix . "webtree_customers";       
        	            $wpdb->query("INSERT INTO $table_name(name,email,dob,phone,gender,cr_number,address,city,country,status) VALUES('$name','$email','$dob','$phone','$gender','$cr_number','$address','$city','$country','$status')");
        	        }
        	    }
        	    else 
        	    {
        	    	?>
        	    	<script type="text/javascript" >
                         alert('Email Already Exists!!')
        	    	</script>
        	    	<?php
        	    }
        	}
        	
        	//Delete user from custom table and user table
        	if(isset($_GET["del"])) 
        	{
        		$del_id = $_GET["del"];
        		global $wpdb;
        	    
        	    $table_name = $wpdb->prefix . "webtree_customers";
        		$result = $wpdb->get_row("SELECT email FROM $table_name WHERE id = '$del_id'");
        		$user = get_user_by( 'email', $result->email );
                $user_id = $user->ID;
        		
        		$wpdb->query("DELETE FROM $table_name WHERE id='$del_id'");
        		//delete wp user
                wp_delete_user($user_id);
        	}
        }
    
    // Page creation on Plugin activation
    function webtree_page_creation()
    {
        if( empty(get_page_by_title( 'Webtree Customers', 'page' ))) 
        {
            $createPage1 = array(
              'post_title'    => 'Webtree Customers',
              'post_content'  => '',
              'post_status'   => 'publish',
              'post_author'   => 1,
              'post_type'     => 'page',
              'post_name'     => 'webtree-customers'
            );
            // Insert the post into the database
            wp_insert_post( $createPage1 );
        }
        if(empty(get_page_by_title( 'Webtree Add Customer', 'page' ) )) 
        {
            $createPage2 = array(
              'post_title'    => 'Webtree Add Customer',
              'post_content'  => '[wt-custom-registration]',
              'post_status'   => 'publish',
              'post_author'   => 1,
              'post_type'     => 'page',
              'post_name'     => 'webtree-add-customer'
            );
            // Insert the post into the database
            wp_insert_post( $createPage2 );
        }
        if(empty(get_page_by_title( 'Webtree Edit Customer', 'page' ))) 
        {
            $createPage3 = array(
              'post_title'    => 'Webtree Edit Customer',
              'post_content'  => '[wt-custom-edit]',
              'post_status'   => 'publish',
              'post_author'   => 1,
              'post_type'     => 'page',
              'post_name'     => 'webtree-edit-customer'
            );
            // Insert the post into the database
            wp_insert_post( $createPage3 );
        }
    }
    
    
    //Custom template creation using plugin to display all user records
    function webtree_page_template( $page_template )
    {
        if ( is_page( 'webtree-customers' ) ) {
            echo $page_template = dirname( __FILE__ ) . '/templates/webtree-template.php';
        }
        return $page_template;
    }
    
    
    //Display all users in backend
    function customerManagementPage() 
    {
	    global $wpdb;
	    echo do_shortcode('[wt-all-customers]');
    }
    
    // Add customer page backend
    function addCustomerPage() 
    {
	    global $wpdb;
	    echo do_shortcode('[wt-custom-registration]');
    }
    
    //edit customer page backend
    function editCustomerPage() 
    {
	    global $wpdb;
	    echo do_shortcode('[wt-custom-edit]');
    }
    
    //registration form
    function webtree_custom_registration()
    {
    	$html = '';
    	$html .= '<div><h3>Add New Customer</h3></div>
    	<form action="" class="wt_form userregistration-form" id="wt_profileEdit" method="post" enctype="multipart/form-data">
    				<div class="bh-formouter-block">
    					<div class="row">
    						<div class="col-md-6">
    							<div class="form-group">
    								<label for="user_name">'. __('Name','webtree').'*</label>
    								<input type="text" class="form-control pf_textbox_signup" id="user_name" placeholder="'.__('Name','webtree').'" name="user_name" required="required">
    							</div>
    						</div>
    						<div class="col-md-6">
    						    <div class="form-group">
    							    <label for="email">'.__('Email','webtree').'*</label>
    							    <input type="email" class="form-control pf_textbox_signup" id="email" placeholder="'.__('Email','webtree').'" name="email" required="required">  
    						    </div>
    						</div>
    						<div class="col-md-6">
    						    <div class="form-group">
                                    <label for="phone">'.__('Phone','webtree').'*</label>
    							    <input type="number" class="form-control pf_textbox_signup" id="phone" placeholder="'. __('Phone','webtree').'" name="phone" required="required">
    						    </div>
    					    </div>
    					    <div class="col-md-6">
    					        <div class="form-group">
    						        <label for="dob">'. __('Date Of Birth','webtree').'*</label>
    						        <input type="date" class="form-control pf_textbox_signup" id="dob" placeholder="'. __('Date Of Birth','webtree').'" name="dob">
    					        </div>
    					    </div>
    					    <div class="col-md-6">
                                <div class="form-group">
    						        <label for="gender">'. __('Gender','webtree').'</label>
    						        <input type="radio" name="gender" value="male">Male
    						        <input type="radio" name="gender" value="female">Female
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="CR Number">'. __('CR Number','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="cr_number" placeholder="'. __('CR Number','webtree').'" name="cr_number">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="address">'. __('Address','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="address" placeholder="'. __('Address','webtree').'" name="address">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="city">'. __('City','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="city" placeholder="'. __('City','webtree').'" name="city">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="country">'. __('Country','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="country" placeholder="'. __('Country','webtree').'" name="country">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="status">'. __('Status','webtree').'</label>
    						        <input type="checkbox" class="pf_textbox_signup" value = "1" name="status"> Is Active
        						</div>
    						</div>
    					    <div class="col-md-12">
    					        <input type="submit" class="btn btn-default" id= ""  name="register-submit" value="'.__('Register','webtree').'">
    					        <input type="hidden" name="registerCustomer" value="registerCustomer">
    					    </div>
    					</div>
            		</form>';
    	return $html;
    }
    
    //to list all users
    function webtree_all_customers()
    {
        $allUsersHtml = '';
        if(is_user_logged_in()) 
        {
    		global $current_user, $wp_roles;
    		global $wpdb;
    		
    		$allUsersHtml .= '
    		<div><h3>All Customers</h3></div>
    		<table id="webtree_all_customers" class="table table-striped table-bordered" style="width:100%">
	    		<thead>
	           		<tr>
						<th ><strong>Id.</strong></th>
						<th ><strong>Name</strong></th>
						<th ><strong>Email</strong></th>
						<th ><strong>Phone</strong></th>
						<th ><strong>Date Of Birth</strong></th>
						<th ><strong>Age</strong></th>
						<th ><strong>Gender</strong></th>
						<th ><strong>CR Number</strong></th>
						<th ><strong>Address</strong></th>
						<th ><strong>City</strong></th>
						<th ><strong>Country</strong></th>
						<th ><strong>Status</strong></th>
						<th ><strong>Edit</strong></th>
						<th ><strong>Delete</strong></th>
					</tr>
	        	</thead>
	        	<tbody>';
	        	$table_name = $wpdb->prefix . "webtree_customers";
			    	$result = $wpdb->get_results("SELECT * FROM $table_name");
			    	$i = 1;

					foreach ($result as $optionName) {
					    
                    $from = new DateTime($optionName->dob);
                    $to   = new DateTime('today');
                    $age = $from->diff($to)->y;
                    
                    if($optionName->status == 1)
                    {
                        $status = 'Active';
                    }
                    else
                    {
                        $status = 'Inactive';
                    }
					
					$allUsersHtml .= '
					<tr>
					    <td>'.$optionName->id.'</td>
					    <td>'.$optionName->name.'</td>
					    <td>'.$optionName->email.'</td>
					    <td>'.$optionName->phone.'</td>
					    <td>'.$optionName->dob.'</td>
					    <td>'.$age.'</td>
					    <td>'.$optionName->gender.'</td>
					    <td>'.$optionName->cr_number.'</td>
					    <td>'.$optionName->address.'</td>
					    <td>'.$optionName->city.'</td>
					    <td>'.$optionName->country.'</td>
					    <td>'.$status.'</td>
					    <td><a href="'.admin_url( 'admin.php?page=editCustomer&cID='.$optionName->id).'">Edit</a></td>
					    <td><a href="'.admin_url( 'admin.php?page=customerManagement&del='.$optionName->id).'"  onclick="return confirm(\'Are you sure, you want to delete the customer\')">DELETE</a></td>
					</tr>
					';
					    
					}
	        	
	        	$allUsersHtml .= '</tbody>
	        </table>';
    		
        }
        return $allUsersHtml;
    }
    
    //to list all active users in frontend
    function webtree_all_customers_frontend()
    {
        $allUsersHtml = '';
        if(is_user_logged_in()) 
        {
    		global $current_user, $wp_roles;
    		global $wpdb;
    		
    		$allUsersHtml .= '
    		<div><h3>All Active Customers</h3></div>
    		<table id="webtree_active_customers" class="table table-striped table-bordered" style="width:100%">
	    		<thead>
	           		<tr>
						<th ><strong>Id.</strong></th>
						<th ><strong>Name</strong></th>
						<th ><strong>Email</strong></th>
						<th ><strong>Phone</strong></th>
						<th ><strong>Date Of Birth</strong></th>
						<th ><strong>Age</strong></th>
						<th ><strong>Gender</strong></th>
						<th ><strong>CR Number</strong></th>
						<th ><strong>Address</strong></th>
						<th ><strong>City</strong></th>
						<th ><strong>Country</strong></th>
						<th ><strong>Status</strong></th>
						<th ><strong>Edit</strong></th>
						<th ><strong>Delete</strong></th>
					</tr>
	        	</thead>
	        	<tbody>';
	        	$table_name = $wpdb->prefix . "webtree_customers";
			    	$result = $wpdb->get_results("SELECT * FROM $table_name where status = 1");
			    	$i = 1;

					foreach ($result as $optionName) {
					    
                    $from = new DateTime($optionName->dob);
                    $to   = new DateTime('today');
                    $age = $from->diff($to)->y;
                    
                    if($optionName->status == 1)
                    {
                        $status = 'Active';
                    }
                    else
                    {
                        $status = 'Inactive';
                    }
					
					$allUsersHtml .= '
					<tr>
					    <td>'.$optionName->id.'</td>
					    <td>'.$optionName->name.'</td>
					    <td>'.$optionName->email.'</td>
					    <td>'.$optionName->phone.'</td>
					    <td>'.$optionName->dob.'</td>
					    <td>'.$age.'</td>
					    <td>'.$optionName->gender.'</td>
					    <td>'.$optionName->cr_number.'</td>
					    <td>'.$optionName->address.'</td>
					    <td>'.$optionName->city.'</td>
					    <td>'.$optionName->country.'</td>
					    <td>'.$status.'</td>
					    <td><a href="'.site_url().'/webtree-edit-customer?cID='.$optionName->id.'">Edit</a></td>
					    <td><a href="'.site_url().'/webtree-customers?del='.$optionName->id.'"  onclick="return confirm(\'Are you sure, you want to delete the customer\')">DELETE</a></td>
					</tr>
					';
					    
					}
	        	
	        	$allUsersHtml .= '</tbody>
	        </table>';
    		
        }
        return $allUsersHtml;
    }
    
    
    //edit profile
    
    function webtree_edit_profile()
    {
    	if(is_user_logged_in()) {
    		global $current_user, $wp_roles;
    		global $wpdb;
    		$customer_id = $_GET['cID'];
    		$table_name = $wpdb->prefix . "webtree_customers";
	    	$result = $wpdb->get_row("SELECT * FROM $table_name WHERE id = '$customer_id'");
	    	
    		$user_info   = get_userdata($current_user->ID);
    		$userName    = $user_info->user_login;
    		$userEmail   = $user_info->user_email;
    		$userPass    = $user_info->user_pass;
    		$genderChecked = '';
    		$selectedGender = $result->gender;
    		if($selectedGender == 'male')
    		{
                $radio = '<input type="radio" name="gender" checked value="male">Male
    						        <input type="radio" name="gender" value="female">Female';    		    
    		}
    		else
    		{
    		    $radio = '<input type="radio" name="gender"  value="male">Male
    						        <input type="radio" name="gender" checked value="female">Female';
    		}
    		$statusChecked='';
    		
    		$dob = $result->dob;
    		$dob = strtotime($dob);
    		$dob = date('Y-m-d',$dob);
    		if($result->status == 1)
    		{
    		    $statusChecked='checked';    
    		}
    		$html = '';
    		$html .= '
    		<div><h3>Edit Customer</h3></div>
    		<form action="" class="wt_form userregistration-form" id="wt_user_registration" method="post" enctype="multipart/form-data">
    					<div class="bh-formouter-block">
    						<div class="col-md-6">
    							<div class="form-group">
    								<label for="user_name">'. __('Name','webtree').'*</label>
    								<input type="text" class="form-control pf_textbox_signup" value = "'.$result->name.'" id="user_name" placeholder="'.__('Name','webtree').'" name="user_name" required="required">
    							</div>
    						</div>
    						<div class="col-md-6">
    						    <div class="form-group">
    							    <label for="email">'.__('Email','webtree').'*</label>
    							    <input type="email" class="form-control pf_textbox_signup" id="email" value = "'.$result->email.'" readonly placeholder="'.__('Email','webtree').'" name="email" required="required">  
    						    </div>
    						</div>
    						<div class="col-md-6">
    						    <div class="form-group">
                                    <label for="phone">'.__('Phone','webtree').'*</label>
    							    <input type="number" class="form-control pf_textbox_signup" id="phone" value = "'.$result->phone.'" placeholder="'. __('Phone','webtree').'" name="phone" required="required">
    						    </div>
    					    </div>
    					    <div class="col-md-6">
    					        <div class="form-group">
    						        <label for="dob">'. __('Date Of Birth','webtree').'*</label>
    						        <input type="date" class="form-control pf_textbox_signup" value = "'.$dob.'" id="dob" placeholder="'. __('Date Of Birth','webtree').'" name="dob">
    					        </div>
    					    </div>
    					    <div class="col-md-6">
                                <div class="form-group">
    						        <label for="gender">'. __('Gender','webtree').'</label>
    						        '.$radio.'
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="CR Number">'. __('CR Number','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" value = "'.$result->cr_number.'" id="cr_number" placeholder="'. __('CR Number','webtree').'" name="cr_number">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="address">'. __('Address','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" value = "'.$result->address.'" id="address" placeholder="'. __('Address','webtree').'" name="address">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="city">'. __('City','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="city" value = "'.$result->city.'" placeholder="'. __('City','webtree').'" name="city">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="country">'. __('Country','webtree').'</label>
    						        <input type="text" class="form-control pf_textbox_signup" id="country" value = "'.$result->country.'" placeholder="'. __('Country','webtree').'" name="country">
        						</div>
    						</div>
    						<div class="col-md-6">
                                <div class="form-group">
    						        <label for="status">'. __('Status','webtree').'</label>
    						        <input type="checkbox" class="pf_textbox_signup" value = "1" '.$statusChecked.' name="status">Is Active
        						</div>
    						</div>
    						<input type="hidden" class="form-control pf_textbox_signup" id="id" value = "'.$result->id.'"  name="id">
    						<input type="submit" class="btn btn-default" id="webtree-update-submit" name="update-submit" value="'.__('Update','webtree').'">
    						<input type="hidden" name="webtree-update" value="Update">
    						
    					</div>
    				</form>';
    		return $html;
    	}
    	else
    	{
    		$html .= "<h3>Log in to access this page</h3>";
    		return $html;
    	}
    }
    

}
$_Webtree = new Webtree();