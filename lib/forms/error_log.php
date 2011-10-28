<?php
	$date_fmt = get_option('date_format');
	$date_fmt = $date_fmt ? $date_fmt : 'Y-m-d';
	$time_fmt = get_option('time_format');
	$time_fmt = $time_fmt ? $time_fmt : 'H:i:s';
	$datetime_format = "{$date_fmt} {$time_fmt}";
?>
<div class="wrap">
	<h2><?php _e('Error Log', 'wdfb');?></h2>

<?php if ($errors) { ?>
<a href="<?php echo admin_url('admin.php?page=wdfb_error_log&action=purge');?>">Purge log</a>
<table class="widefat">
	<thead>
		<tr>
			<th><?php _e('Date', 'wdfb')?></th>
			<th><?php _e('User', 'wdfb')?></th>
			<th><?php _e('Area', 'wdfb')?></th>
			<th><?php _e('Type', 'wdfb')?></th>
			<th><?php _e('Info', 'wdfb')?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th><?php _e('Date', 'wdfb')?></th>
			<th><?php _e('User', 'wdfb')?></th>
			<th><?php _e('Area', 'wdfb')?></th>
			<th><?php _e('Type', 'wdfb')?></th>
			<th><?php _e('Info', 'wdfb')?></th>
		</tr>
	</tfoot>
	<tbody>
	<?php foreach ($errors as $error) { ?>
		<?php $user = get_userdata(@$error['user_id']);?>
		<tr>
			<td><?php echo date($datetime_format, $error['date']);?></td>
			<td><?php echo ($user->user_login ? $user->user_login : __('Unknown', 'wdfb'));?></td>
			<td><?php echo $error['area'];?></td>
			<td><?php echo $error['type'];?></td>
			<td><?php echo $error['info'];?></td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<?php } else { ?>
	<p><i>Your error log is empty.</i></p>
<?php } ?>

</div>