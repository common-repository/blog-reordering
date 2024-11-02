<?php
/*  Copyright 2008 The HungryCoder

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

/*
Plugin Name: Blog Reordering
Plugin URI: http://hungrycoder.xenexbd.com/node/656
Description: Reorder your blog posts in multiple ways including custom ordering instead of typical datewise post display. You can also mark any post as sticky that will be always placed at top. <br />This concept is mainly taken from Joomla. Much thanks to Joomla Team. The two (up and down) images are taken from Joomla package. Credit goes to original author/designer/owner of that images. The idea to add sticky post in this plugin is given by Austin (austin@warminster.co.uk). Thanks to him. Special thanks to Jobaer Shuman (http://shumanbd.info), Arafat Rahman (http://arafatbd.net) for helping me while developing the plugin by giving idea, bug testing etc. Lifetime grateful :-) to Hasin Hayder (http://hasin.wordpress.com) from whom I knew about WordPress :). 

Version: 1.01 RC1
Author: The HungryCoder
Author URI: http://hungrycoder.xenexbd.com/
Email: thcoder@gmail.com
Please let me know any bugs here. For any help, please visit plugin URI. It will be easy for me to support there and it will save my time in case of same support request from other users.

Disclaimer: This concept is mainly taken from Joomla. Much thanks to Joomla Team. The two (up and down) images are taken from Joomla package. Credit goes to original author/designer/owner of that images. 
The idea to add sticky post in this plugin is given by Austin (austin@warminster.co.uk). Thanks to him.

Special thanks to Jobaer Shuman (http://shumanbd.info), Arafat Rahman (http://arafatbd.net) for helping me while developing the plugin by giving idea, bug testing etc.

Lifetime grateful :-) to Hasin Hayder (http://hasin.wordpress.com) from whom I knew about WordPress :). 
*/
	
	$mypath=plugins_url().'/'.basename(dirname(__FILE__));
	$myurl=basename($_SERVER['SCRIPT_FILENAME']).'?page=bo.php';
	//echo $myurl;
	
	//independent hooks
	register_activation_hook(basename(dirname(__FILE__)).'/bo.php','bo_install');
	register_deactivation_hook(basename(dirname(__FILE__)).'/bo.php','bo_uninstall');
	add_action('admin_menu','bo_settings');
	add_filter('posts_orderby','bo_reorder',null,1);

	function bo_install(){		
		add_option('bo_enable',1);
		add_option('bo_ordering1','orderingA');
		add_option('bo_ordering2','postdateZ');
		add_option('bo_ordering3','alphaA');
		global $wpdb;
		$wpdb->query("ALTER TABLE $wpdb->posts ADD `ordering` INT NOT NULL default 1");
		$wpdb->query("ALTER TABLE $wpdb->posts ADD `sticky` varchar(6) default NULL");
		
		//put default/initial ordering based on post_date descending
		$sql="SELECT `ID`, `ordering` FROM $wpdb->posts WHERE `post_type`='post' ORDER BY `post_date` DESC LIMIT 50000";
		$sql=$wpdb->get_results($sql);
		$i=1;
		if(is_array($sql)){
			$wpdb->show_errors;
			foreach ($sql as $row){
				$wpdb->query("UPDATE `$wpdb->posts` SET `ordering`='$i' WHERE `ID`='".$row->ID."' LIMIT 1");
				$i++;
			}
		}	
	}
	
	function bo_uninstall(){
		delete_option('bo_enable');
		delete_option('bo_ordering1');
		delete_option('bo_ordering2');
		delete_option('bo_ordering3');
		global $wpdb;
		$wpdb->query("ALTER TABLE $wpdb->posts DROP `ordering` ");
		$wpdb->query("ALTER TABLE $wpdb->posts DROP `sticky` ");
	}
		
	function bo_reorder($query){
		$opt=get_option('bo_enable');
		
		if($opt){
			$ordering1=get_option('bo_ordering1');
			$ordering2=get_option('bo_ordering2');
			$ordering3=get_option('bo_ordering3');
			global $wpdb;
			$x = "`$wpdb->posts`.`sticky` ASC,".bo_orderingtype($ordering1).','.bo_orderingtype($ordering2).','.bo_orderingtype($ordering3);
			//echo $x;
			return $x;
		} else {
			return $query;
		} 
	}
	
	function bo_orderingtype($order){
		global $wpdb;
		$col='';
		switch ($order){
			case 'orderingA':
				$col='`ordering` ASC';
				break;
			case 'orderingZ':
				$col='`ordering` DESC';
				break;
			case 'alphaA':
				$col='`post_title` ASC';
				break;
			case 'alphaZ':
				$col='`post_title` DESC';
				break;
			case 'postdateA':
				$col='`post_date` ASC';
				break;
			case 'postdateZ':
				$col='`post_date` DESC';
				break;
			case 'postidA':
				$col='`ID` ASC';
				break;
			case 'postidZ':
				$col='`ID` DESC';
				break;
			default:
				$col='`ordering` ASC';
				break;
		}
		return "`$wpdb->posts`.$col";
	}
	
	function bo_settings(){
		add_options_page('Blog Reordering','Blog Reordering',9,basename(__FILE__),'bo_settings_page');
	}
	
	function bo_settings_page(){
		global $wpdb;
		if(isset($_POST['bo_update'])){
			$enable= $_POST['bo_enable']; //0 disable, 1 enable
			$ordering1=$_POST['ordering1'];
			$ordering2=$_POST['ordering2'];
			$ordering3=$_POST['ordering3'];
			
			update_option('bo_enable',$enable);
			update_option('bo_ordering1',$ordering1);
			update_option('bo_ordering2',$ordering2);
			update_option('bo_ordering3',$ordering3);
			echo '<div class="updated"><p><strong>Settings saved</strong></p></div>';
			 
		} elseif(isset($_POST['bo_reorder'])){
			//save the ordering
			$numid=count($_POST['txtid']);
			$wpdb->show_errors;
			print_r($_POST['sticky']);
			for($i=0;$i<$numid;$i++){
				$sticky=$_POST['st'.$_POST['txtid'][$i]];
				
				$sticky=(1==$sticky) ? '1' : 'null';
				//echo $sticky;
				$wpdb->query("UPDATE $wpdb->posts SET `ordering`='".$wpdb->escape($_POST['txtordering'][$i])."', `sticky`='$sticky' WHERE `ID`='".$wpdb->escape($_POST['txtid'][$i])."' LIMIT 1");
			}
			echo '<div class="updated"><p><strong>Ordering updated</strong></p></div>';
		}
		
		if(!empty($_GET['pid']) AND !empty($_GET['order'])){
			//individual order is set. update the database.
			$pid=(int) $_GET['pid'];
			$order=(int) $_GET['order'];
			$res=$wpdb->query("UPDATE $wpdb->posts SET `ordering`='".$wpdb->escape($order)."' WHERE `ID`='".$wpdb->escape($pid)."' LIMIT 1");
			if($res){
				echo '<div class="updated"><p><strong>Settings saved</strong></p></div>';
			} else {
				echo '<div class="updated"><p><strong>Settings could not be saved</strong></p></div>';
			}
		}
		
		//rearrange by preset
		if(isset($_POST['bo_rearrange'])){
			//we need a trick to arrang the post. we fetch from database by ORDER BY and then store on same order :-).
			$sql="SELECT `ID`, `ordering` FROM $wpdb->posts WHERE `post_type`='post' ORDER BY ".$wpdb->escape(bo_orderingtype($_POST['preset']))." LIMIT 50000";
			$sql=$wpdb->get_results($sql);
			$i=1;
			if(is_array($sql)){
				$wpdb->show_errors;
				foreach ($sql as $row){
					$wpdb->query("UPDATE `$wpdb->posts` SET `ordering`='$i' WHERE `ID`='".$row->ID."' LIMIT 1");
					$i++;
				}
				echo '<div class="updated"><p><strong>Blogs reordered.</strong></p></div>';
			}
			
		}
		
		//get the current option from database
		$enable = get_option('bo_enable');
		$ordering1=get_option('bo_ordering1');
		$ordering2=get_option('bo_ordering2');
		$ordering3=get_option('bo_ordering3');
	?>
		<div class="wrap">
		<h3>Blog Reordering Settings</h3>
		<div class="inside">
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<table class="form-table">
					<tbody><tr>
					<th>Enable/Disable:</th>
					<td><label><input type="radio" name="bo_enable" id="enb" value="1" <?php if($enable) echo 'checked="checked"';?>  /> Enable</label> &nbsp;&nbsp;
					<label><input type="radio" id="dsb" name="bo_enable" value="0" <?php if(!$enable) echo 'checked="checked"';?> /> Disable</label></td>
					</tr><tr><th>Primary Ordering:</th>
					<td>
						<select name="ordering1">
							<option value="orderingA" <?php echo ($ordering1=='orderingA') ? 'selected="selected"' : '';?>>Ordering - Ascending</option>
							<option value="orderingZ" <?php echo ($ordering1=='orderingZ') ? 'selected="selected"' : '';?>>Ordering - Descending</option>
							<option value="alphaA" <?php echo ($ordering1=='alphaA') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Ascending</option>
							<option value="alphaZ" <?php echo ($ordering1=='alphaZ') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Descending</option>
							<option value="postdateA" <?php echo ($ordering1=='postdateA') ? 'selected="selected"' : '';?>>Post Date - Ascending</option>
							<option value="postdateZ" <?php echo ($ordering1=='postdateZ') ? 'selected="selected"' : '';?>>Post Date - Descending</option>
							<option value="postidA" <?php echo ($ordering1=='postidA') ? 'selected="selected"' : '';?>>Post ID - Ascending</option>
							<option value="postidZ" <?php echo ($ordering1=='postidZ') ? 'selected="selected"' : '';?>>Post ID - Descending</option>
						</select>
					</td></tr><tr><th>Secondary Ordering:</th>
					<td>
						<select name="ordering2">
							<option value="orderingA" <?php echo ($ordering2=='orderingA') ? 'selected="selected"' : '';?>>Ordering - Ascending</option>
							<option value="orderingZ" <?php echo ($ordering2=='orderingZ') ? 'selected="selected"' : '';?>>Ordering - Descending</option>
							<option value="alphaA" <?php echo ($ordering2=='alphaA') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Ascending</option>
							<option value="alphaZ" <?php echo ($ordering2=='alphaZ') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Descending</option>
							<option value="postdateA" <?php echo ($ordering2=='postdateA') ? 'selected="selected"' : '';?>>Post Date - Ascending</option>
							<option value="postdateZ" <?php echo ($ordering2=='postdateZ') ? 'selected="selected"' : '';?>>Post Date - Descending</option>
							<option value="postidA" <?php echo ($ordering2=='postidA') ? 'selected="selected"' : '';?>>Post ID - Ascending</option>
							<option value="postidZ" <?php echo ($ordering2=='postidZ') ? 'selected="selected"' : '';?>>Post ID - Descending</option>
						</select>
					</td></tr><tr><th>Tertiary Ordering:</th><td>
						<select name="ordering3">
							<option value="orderingA" <?php echo ($ordering3=='orderingA') ? 'selected="selected"' : '';?>>Ordering - Ascending</option>
							<option value="orderingZ" <?php echo ($ordering3=='orderingZ') ? 'selected="selected"' : '';?>>Ordering - Descending</option>
							<option value="alphaA" <?php echo ($ordering3=='alphaA') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Ascending</option>
							<option value="alphaZ" <?php echo ($ordering3=='alphaZ') ? 'selected="selected"' : '';?>>Alphabatically (Title) - Descending</option>
							<option value="postdateA" <?php echo ($ordering3=='postdateA') ? 'selected="selected"' : '';?>>Post Date - Ascending</option>
							<option value="postdateZ" <?php echo ($ordering3=='postdateZ') ? 'selected="selected"' : '';?>>Post Date - Descending</option>
							<option value="postidA" <?php echo ($ordering2=='postidA') ? 'selected="selected"' : '';?>>Post ID - Ascending</option>
							<option value="postidZ" <?php echo ($ordering2=='postidZ') ? 'selected="selected"' : '';?>>Post ID - Descending</option>
						</select>
					</td></tr>
					</tbody>
					</table>
				<p class="submit">
					<input type="submit" name="bo_update" value="Update Options &raquo;" />
				</p>
			</form>
		</div>
		</div>
		<br /><br />
		
		<div class="wrap">
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
			<h3>Quick Reorder by Field:</h3>
			<table class="form-table"><tbody><tr><th>
			Reorder By: </th>
			<td><select name="preset">
							<option value="alphaA" <?php echo ($ordering3=='alphaA') ? 'selected="selected"' : '';?>>Alphabatically (Post Title) - Ascending</option>
							<option value="alphaZ" <?php echo ($ordering3=='alphaZ') ? 'selected="selected"' : '';?>>Alphabatically (Post Title) - Descending</option>
							<option value="postdateA" <?php echo ($ordering3=='postdateA') ? 'selected="selected"' : '';?>>Post Date - Ascending</option>
							<option value="postdateZ" <?php echo ($ordering3=='postdateZ') ? 'selected="selected"' : '';?>>Post Date - Descending (WordPress Default)</option>
							<option value="postidA" <?php echo ($ordering2=='postidA') ? 'selected="selected"' : '';?>>Post ID - Ascending</option>
							<option value="postidZ" <?php echo ($ordering2=='postidZ') ? 'selected="selected"' : '';?>>Post ID - Descending</option>
			</select></td></tr></table>
			<br /><span>Just make a quick reorder based on selected option. <font color="red">It will replace all previous ordering</font>. But the display will be still controlled Primary, Secondary and Tertiary Ordering priorities.</span>
			<p class="submit">
				<input type="submit" name="bo_rearrange" value="Reorder &raquo;" />
			</p>
		</form>
		</div><br /><br />
		<div class="wrap">
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
			<fieldset>
				<h3>Blog Ordering:</h3>
		<p class="submit">
			<input type="submit" name="bo_reorder" value="Update Ordering &raquo;" />
			<input type="reset" name="reset" value="Reset" />
		</p>
				<?php
				global $wpdb;
				$sql=$wpdb->get_results("SELECT `ID`, `post_date`, `post_title`,`ordering`,`sticky` FROM $wpdb->posts WHERE `post_type`='post' ORDER BY `ordering`");
				if(is_array($sql)){
					echo '<table class="widefat"><thead><tr><th scope="col">Sticky</th><th scope="col">ID</th><th scope="column">Title</th><th scope="col">Date/Time</th><th scope="column">Ordering</th></tr></thead>';
					$i=0;
					$total=count($sql);
					foreach ($sql as $blog){
						$class=(fmod($i,2)==0) ? '' : 'class ="alternate"';
						$order=!empty($blog->ordering) ? $blog->ordering : $i+1;
						$sticky=(1==$blog->sticky) ? 'checked="checked"' : '';
						echo '<tr '.$class.'>';
						echo '<td><input type="checkbox"  value="1" name="st'.$blog->ID.'" '.$sticky.' /></td>';
						echo '<td>'.$blog->ID.'</td><td>'.$blog->post_title.'</td><td>'.date('d-M-Y h:i A',strtotime($blog->post_date)).'</td><td>';
						echo '<input type="hidden" name="txtid[]" value="'.$blog->ID.'" />';
						echo '<input name="txtordering[]" tabindex="'.($i+1).'" type="text" size="5" value="'.$order.'" style="text-align:center" />';
						//show the UP arrow only if it is not first row/post
						if($i){
							global $mypath,$myurl;
							echo '<a href="'.$myurl.'&pid='.$blog->ID.'&order='. ($order - 1).'"><img src="'.$mypath.'/up.png" alt="up" /></a>';
						}
						
						//show the Down arrow if if it is not the last row/post
						if(($total-1)!==$i){
							global $mypath,$myurl;
							echo '<a href="'.$myurl.'&pid='.$blog->ID.'&order='.( $order + 1).'"><img src="'.$mypath.'/down.png" alt="down" /></a>';
						}
						
						echo '</td></tr>'."\r\n";
						$i++;
					}
					echo '</table>';
				}
				?>
				<p class="submit">
					<input type="submit" name="bo_reorder" value="Update Ordering &raquo;" />
					<input type="reset" name="reset" value="Reset" />
				</p>
			</fieldset>
		</form>
		
		</div>
	<?php	
	}
	
?>