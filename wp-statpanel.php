<?php
/*
Plugin Name: Stat Panel
Plugin URI: http://koke.amedias.org/statpanel
Description: Adds internal statistics to your admin section
Version: 0.1
Author: Jorge Bernal
Author URI: http://koke.amedias.org/
*/


add_action('admin_menu', 'sp_add_pages');

function sp_add_pages()
{
	add_menu_page(__('Stats', 'statpanel'), __('Stats', 'statistics'), 7, __FILE__, 'render_panel');   
	add_submenu_page(__FILE__, __('Dashboard', 'statpanel'), __('Dashboard', 'statistics'), 7, __FILE__, 'render_panel');
}

function render_panel()
{
	global $wpdb;
	
	$posts_last7 = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_date >= CURRENT_DATE - INTERVAL 7 DAY");
	$posts_last30 = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts WHERE post_date >= CURRENT_DATE - INTERVAL 30 DAY");
	$posts_total = $wpdb->get_var("SELECT COUNT(ID) FROM $wpdb->posts");
	
	$wpdb->query("SELECT date(post_date) as post_date, COUNT(ID) as count FROM $wpdb->posts WHERE post_parent = 0 AND post_status = 'publish' AND post_date >= CURRENT_DATE - INTERVAL 90 DAY GROUP BY date(post_date)");
	$post_stats = $wpdb->get_results(NULL, ARRAY_A);	
	$start_date = strtotime($post_stats[0]["post_date"]);
	$chart_values = array();
	$char_dates = array();
	while ($start_date < time()) {
		$cdate = date("Y-m-d", $start_date);
		$chart_values[$cdate] = "0.0";
		$start_date += 86400;
	}
	
	$max_value = 1;
	foreach ($post_stats as $post_day) {
		$max_value = ($max_value > $post_day["count"]) ? $max_value : $post_day["count"];
		$chart_values[$post_day["post_date"]] = sprintf("%d.0",$post_day["count"]);
	}
	
	foreach ($chart_values as $date => $count) {
		$chart_values[$date] = sprintf("%.1f", 100 * $count / $max_value);
	}
	
	$label_each = count($chart_values) / 5;
	$labels = array_keys($chart_values);
	foreach ($labels as $idx => $label) {
		if (($idx % $label_each) != 0) {
			unset($labels[$idx]);
		}
	}
	
	$chart_url = "http://chart.apis.google.com/chart?chs=800x375&cht=lc&chxt=x,y&chxr=1,0,$max_value&chd=t:";
	$chart_url.= implode(",", array_values($chart_values));
	$chart_url.= "&chxl=0:|";
	$chart_url.= implode("|", $labels);
	$chart_url.= "|";
	
	// echo $chart_url;
	// echo "<pre>";var_dump($chart_values);echo "</pre>";
	
	?>
<div class='wrap'>
<h2><?php _e('Internal statistics','statpanel'); ?></h2>
<table>
	<tr>
		<th>&nbsp;</th>
		<th>Last 7 days</th>
		<th>Last 30 days</th>
		<th>Total</th>
	</tr>
	<tr>
		<th>Posts</th>
		<td><?php echo $posts_last7; ?></td>
		<td><?php echo $posts_last30; ?></td>
		<td><?php echo $posts_total; ?></td>
	</tr>
</table>

<img src="<?php echo $chart_url; ?>" />
</div>
	<?php
}

?>