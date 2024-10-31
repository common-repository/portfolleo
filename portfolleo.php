<?php
/*
	Plugin Name: Portfolleo
	Plugin URI: http://jameseggers.com/portfolleo
	Description: Portfolleo is a Simple, Intuitive Portfolio Plugin for wordpress that creates a very good-looking portfolio that uses JQuery. It is very very very easy to use and is quite fast. I created it because there really wasn't any plugin that just created a whole portfolio, that looks amazing. So, thats what Portfolleo does, it creates an amazing portfolio almost without any input from the user. Its also highly customizable.	
	Version: 1.2
	Author Name: James Eggers
	Author URI: http://jameseggers.com/
	
	Copyright 2009  James Eggers  (email : james@jameseggers.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


*/

//Start session.
session_start();
				
//Create our home-file for images. 
$dir = ABSPATH."wp-content/portfolleo/";
if(!is_dir ( $dir ))
{
	@mkdir ($dir) or die ("<p><strong>".	
	__('Unable to create directory', 'portfolleo')." ".$dir."!</strong></p><p>".__('You can create it your self or do a chmod 755 wp-content and try again','portfolleo').".</p></div>");
}

if (!defined("WP_CONTENT_URL")) define("WP_CONTENT_URL", get_option("siteurl") . "/wp-content");
if (!defined("WP_PLUGIN_URL"))  define("WP_PLUGIN_URL",  WP_CONTENT_URL        . "/plugins");

//Include SimpleHTMLDomParser.
require_once('static/simplehtmldom/simple_html_dom.php');

//Add our stylesheet.
function portfolleo_head()
{
	$css_url = WP_PLUGIN_URL . "/portfolleo/static/css/style.css";
	if (file_exists(TEMPLATEPATH . "/static/css/style.css"))
	{
		$css_url = get_bloginfo("template_url") . "/static/css/style.css";
	}

	echo "\n".'<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>';
	echo "\n".'<script type="text/javascript" src="' . WP_PLUGIN_URL . '/portfolleo/static/js/portfolleo.js"></script>'."\n"; 
	echo "\n".'<link rel="stylesheet" href="' . $css_url . '" type="text/css" media="screen" />'."\n";
}

//Hook for adding admin pages.
add_action('admin_menu', 'portfolleo');

function portfolleo()
{
	global $wpdb;
	$dir = ABSPATH."wp-content/portfolleo/";
	add_menu_page('Portfolleo Options', 'Portfolleo', 'administrator', 'portfolleo', 'portfolio_options');
	
	//Process the form.
	if ($_POST['submit'])
	{
		$table_name = $wpdb->prefix . "posts";
		if ($wpdb->get_var("SHOW TABLES LIKE '". $table_name. "'") != $table_name)
		{
			echo "Database Error";
			
		} else {
			
			//Validate the form. 
			if ($_POST['name'] == "" || $_POST['name'] == " ")
			{
				$_SESSION['error_name'] = "Please fill in the name field.<br/>"; 
			} else if (preg_match("~^[a-z]*$~iD", $_POST['dob']) > 0 || $_POST['dob'] == "" || $_POST['dob'] == " ")
			{
				$_SESSION['error_dob'] = "Please enter a numerical date into the Date Completed field. E.G. dd/mm/yy.<br/>";
			} else if ($_POST['desc'] == "" || $_POST['desc'] == " ")
			{
				$_SESSION['error_desc'] = "Please enter a description in the description field.<br/>";
			} else {
			
				//Get the current contents of the portfolio page.
				$portfolio_current = $wpdb->get_results( "SELECT post_content, post_status, post_name, post_type FROM ". $table_name ." WHERE post_status = 'publish' AND post_name = 'portfolio' AND post_type = 'page'");
				
				//Create an Object, from the data extracted from the db above.
				$portfolio_curr = $portfolio_current[0]->post_content;
				$portfolio_curr = str_get_html($portfolio_curr); 
				
				//Set some important variables.
				$name = $_POST['name'];
				$dob = $_POST['dob'];
				$desc = $_POST['desc'];
				
				//Upload the image.
				foreach ($_FILES as $file_name => $file_array)
				{
					if (is_uploaded_file($file_array["tmp_name"]))
					{
						//Move the image to its permantent home.
						move_uploaded_file($file_array["tmp_name"], $dir.$file_array["name"]) or die ($_SESSION['error_copy'] = "Couldn't Copy File, Do I have correct permissions for wp-content/portfolleo?<br/>");
						$_SESSION['file_upload_okay'] = 'Added to Portoflio'; 
					} else {
						$_SESSION['error_upload'] = "There was a problem with the file you tried to upload.<br/>";
					}
				}						
				
				$new_slide = "
				 			<div class=\"slide\">
				 			   		<p class=\"desc\" style=\"color: #363636; font-size: 2em;position: absolute; top: 20px;left: 12px;\"> ".$name." </p>
				 			<p class=\"desc\" style=\"width: 400px;color: 1F1F1F; font-size: 1.2em;position: absolute; top: 120px;left: 12px;\">
				 				".$desc." 						
				 			</p>
				 			<p class=\"desc\" style=\"color: #363636; font-size: 0.8em;position: absolute; top: 50px;left: 12px;\"> <b>Date Completed: </b><br/><em>.".$dob."</em> </p>
				 				<img class=\"opacit\" src=\"".get_bloginfo('wpurl')."/wp-content/portfolleo/".$_FILES['fileupload']['name'] ."\" alt=\" \" /> 
				 									
				 			  </div>
				 			  
				 		  ";
				
				//Isolate the div to put the slideshow data into.
				foreach ($portfolio_curr->find('div[id=slidesContainer]') as $slidesContainer)
				{
					
					$slidesContainer->innertext .= $new_slide;
					
				 
				 	//Put this data into the database.
					$update = "UPDATE ". $table_name ." SET post_content = '<span class=\"control\" id=\"rightControl\">right</span><span class=\"control\" id=\"leftControl\">left</span><div id=\"slideshow\">". $slidesContainer ."' WHERE post_name = \"portfolio\" ";
					
										
					$wpdb->query( $update );
					
					$_SESSION['added'] = TRUE;	
					
				
				}
			}
		}
	}
}





function portfolio_options()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "posts";
	
	//Get the post_name data from the db.
	$portfolio_check = $wpdb->get_results("SELECT post_title FROM ". $table_name ." WHERE post_type = 'page' AND post_title = 'portfolio'");
	
	//Check if we need to create a new page, e.g. is the portfolio page present?
	if ($portfolio_check[0]->post_title == 'portfolio' || $portfolio_check[0]->post_title == 'Portfolio')
	{
		//Check for the html in the page.
		//Get the current contents of the portfolio page.
		$portfolio_current = $wpdb->get_results( "SELECT post_content, post_status, post_name, post_type FROM ". $table_name ." WHERE post_status = 'publish' AND post_name = 'portfolio' AND post_type = 'page'");
				
		//Create an Object, from the data extracted from the db above.
		$portfolio_curr = $portfolio_current[0]->post_content;
		$portfolio_curr = str_get_html($portfolio_curr); 
		
		/*
		* Portfolio Page present, add the new slide. 
		*/
	
		$div_error2 = htmlentities("<div id='slidesContainer'></div>");
		echo "<div class='wrap'>	
				<h2> Portfolleo Options </h2><br/>		
				";
				if ($_SESSION['added'] === TRUE)
				{
					echo '<img src="'. WP_PLUGIN_URL . '/portfolleo/static/images/accept.png" alt="Added to Portfolio" /> <p style="margin-top: -15px;margin-left:20px;">New Portoflio Item Added</p>';	
				} else {
					echo "<p style='margin-top:-20px;'>Note: This will not work unless you have ". $div_error2 ." in your portfolio page.<br/><o style='margin-left:37px;'>You might need to play around with the css to get it looking right.</o>  </p>";
				}
					  
				echo "<h3> Add a new Portfolio Item: <br/>";
			
				if ($_SESSION['error_name'] || $_SESSION['error_dob'] || $_SESSION['error_desc'] || $_SESSION['error_copy'])
				{
					echo "
					<div class='error'>
						". $_SESSION['error_name'] ."<br/>
						". $_SESSION['error_dob'] ."<br/>
						". $_SESSION['error_desc'] ."<br/>
						". $_SESSION['error_copy'] ."<br/>
					</div>";
					
				}
				echo "
				<img src='". WP_PLUGIN_URL . "/portfolleo/static/images/portfolleo.png' alt='Portfolleo, A Simple, Intuitive Portfolio Plugin for Wordpress' style='width:auto;margin-left:600px;margin-top:-170px;'/>
			  	<form method='post' action='options.php' enctype='multipart/form-data'>
			  		". wp_nonce_field('update-options') ."
				  	<p> Name of Item: </p>
			  		<input type='text' name='name' style='width: 370px;height:30px;border:1px black solid;font-size:120%;margin-top:-10px;font-family: times;' />
				  	<p style='margin-top: 15px;'>Date Completed: </p>
				  		<input type='text' name='dob' style='width: 370px;height:30px;border:1px black solid;font-size:120%;margin-top:-10px;font-family: times;' />		
				  	<p style='margin-top: 15px;'> Description: </p> 
				  	<textarea name='desc' cols='38' rows='5' style='border:1px black solid;font-size:120%;margin-top:-10px;font-family: times;'></textarea>	
				  	<p style='margin-top: 15px;'>Choose an Image: </p>
				  	<input type='file' name='fileupload' style='width: 370px;height:30px;font-size:120%;margin-top:-10px;border: none;outline:none;margin-left:-3px;' />
				  	<br/>
				  	<input type='hidden' name='action' value='update' />
				  	<input type='hidden' name='page_options' value='name,dob,desc,datafile' />
				 	<input type='submit' name='submit' value='Add To Portfolio' style='margin-left: 270px;' />
				  	
			  	
				  </form>
			  </div>";
		
		} else {
			$div_error = htmlentities("<div id='slidesContainer'></div>");
			$_SESSION['error_page'] = "You Need to create a Page called Portfolio.<br/>Put ". $div_error ." in it.";
			if ($_SESSION['error_page'])
			{
				echo "
				<div style=\"margin-top: 50px;font-size:130%;\" class='error'>
					". $_SESSION['error_page'] ."				
				</div>";
				
				session_destroy();
			}
		}	
			

}

//Add Styling.
add_action('wp_head', 'portfolleo_head');


?>