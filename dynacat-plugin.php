<?php
/*
Plugin Name: Dynacat - Dynamic Category Filter
Plugin URI: 
Description: Dynamic filtering of Categories
Author: Engage Web - Steven Morris
Version: 1.2
Author URI: http://www.engageweb.co.uk/about-us/meet-the-team#Steven
License: GPL2
*/

/*************************************************************/
	
 	add_action('wp_ajax_check_cat', 'dynacat_ajax_check_cat');
	
	function dynacat_ajax_check_cat()
		{	global $wpdb;
			$query = $_POST['data'];
			$catname = $wpdb->get_results("SELECT term_id, name from $wpdb->terms WHERE name LIKE '%{$query}%' ORDER BY term_id LIMIT 0,30 ");
			foreach ($catname as $cat) {
			  $parent = get_category_parents($cat->term_id, FALSE, ' &raquo; ');
			  if ($parent == $cat->name){$parent = '';}
			  echo "<li class='dynaparentoption'><a class=\"catlink\" style=\"cursor:pointer;\" catname='".$cat->name . "' catid='" . $cat->term_id . "'>".$parent. $cat->name . "</a></li>";
			 
			}
			echo '<script language="javascript" type="text/javascript">
					jQuery(".catlink").bind(\'click\', function() {
					var catid = jQuery(this).attr(\'catid\');
					var catname = jQuery(this).attr(\'catname\');
					jQuery(this).css(\'font-weight\',900);
					jQuery(".catlink").parent(\'li\').hide("slow");
					jQuery(this).parent(\'.dynaparentoption\').show();
					jQuery("#filbox").val(catname);
					jQuery("#post_category").val(catid);
					});
			</script>';
			die();
		}	
	function dynacat_register_head() {
		$url = plugins_url( '/dynacatstyle.css' , __FILE__ );
		echo "<link rel='stylesheet' type='text/css' href='$url' />\n";	
	}
	 	add_action('admin_head', 'dynacat_register_head');
		
	function dynacat_add_theme_box() {
		add_meta_box('categorydiv', __('Category'), 'dynacat_box', 'post', 'side', 'core');
		
	}  
	 
	function dynacat_add_theme_menus() {
		if ( ! is_admin() )
			return;
		add_action('admin_menu', 'dynacat_add_theme_box');
	}
	 
	dynacat_add_theme_menus();
	   
	function dynacat_box($post) {
		echo '<input type="hidden" name="taxonomy_noncename" id="taxonomy_noncename" value="' .
					wp_create_nonce( 'taxonomy_theme' ) . '" />';
		 
		$pluginurl = plugins_url( '/' , __FILE__ );	
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script('jquery-effects-core');
		$currentcat = '';	
		?>
			<script type="text/javascript">
				var $j = jQuery.noConflict();
			</script>
			
		<?php
			global $post;
			$id = get_the_ID();
			$post_tmp = get_post($id);
			$post_categories = wp_get_post_categories( $id );
			//print_r($post_categories);
			foreach ($post_categories as $cat)
			{
				$currentcat.=get_the_category_by_ID( $cat )." ";
				$currcatno = $cat;
			}
			
			
			
			
		?> 	
			
		<label><strong>Current Category:</strong></label>
		<div><?php echo $currentcat; ?></div>
		<label><strong>New Category:</strong></label><br>
		<input type="text" name="filterbox" id="filbox" autocomplete="off">	<input type="hidden" name="post_category[]" id="post_category" readonly="readonly" value="<?php echo $currcatno; ?>">	
		<div id="result"></div>
		
			<script language="javascript" type="text/javascript">
			jQuery(document).ready(function()
				{
				  var timer;
				  var checkparent = function () {
						jQuery.post(
							ajaxurl, 
							{
							   'action':'check_cat',
							   'data':jQuery("#filbox").val()
							}, 
							function(response){
							   jQuery( "#result" ).empty().append(response);
							}
					 );				
				  };
					jQuery("#filbox").keyup(function()
						{
						jQuery( "#result" ).empty().append( '<img src="<?php echo $pluginurl; ?>" alt="" class="loading">');
						timer && clearTimeout(timer);
						timer = setTimeout(checkparent, 1000);
						});		
					jQuery(".catlink").click(function() {
					var catid = jQuery(this).attr('catid');
					var catname = jQuery(this).attr('catname');
					jQuery("#filbox").val(catname);
					jQuery("#post_category").val(catid);
				});

			});
		</script>			
	<?php
	}   
	   
	 
