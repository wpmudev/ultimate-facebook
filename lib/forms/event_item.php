<li>
<div class="wdfb_event_title"><?php echo $event['name'];?></div>
<?php if ($show_image) { ?>
	<img src="https://graph.facebook.com/<?php echo $event['id']; ?>/picture" />
<?php  } ?>
<?php if ($show_location) { ?>
	<div class="wdfb_event_location"><?php echo $event['location'];?></div>
<?php } ?>
<div class="wdfb_event_time">
<?php if ($show_start_date) { ?>
	<div class="wdfb_event_start_time"><?php echo __('Starts at:') . ' ' . date($timestamp_format, strtotime($event['start_time']));?></div>
<?php } ?>
<?php if ($show_end_date) { ?>
	<div class="wdfb_event_end_time"><?php echo __('Ends at:') . ' ' . date($timestamp_format, strtotime($event['end_time']));?></div>
<?php } ?>
</div>
</li>