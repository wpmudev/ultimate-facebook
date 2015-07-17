<li>
	<div class="wdfb_event_title">
		<a class="wdfb_event_title_link" href="http://www.facebook.com/events/<?php echo esc_attr( $event['id'] ); ?>/"><?php echo $event['name']; ?></a>
	</div>
	<?php if ( $show_image ) { ?>
		<img src="https://graph.facebook.com/<?php echo esc_attr( $event['id'] ); ?>/picture"/>
	<?php } ?>
	<?php if ( $show_location ) { ?>
		<div class="wdfb_event_location"><?php echo esc_html( $event['location'] ); ?></div>
	<?php } ?>
	<div class="wdfb_event_time">
		<?php if ( $show_start_date ) { ?>
			<div class="wdfb_event_start_time"><?php echo __( 'Starts at:', 'wdfb' ) . ' ' . date( $timestamp_format, strtotime( $event['start_time'] ) ); ?></div>
		<?php } ?>
		<?php if ( $show_end_date ) {
			$end_time = ! empty( $event['end_time'] ) ? $event['end_time'] : strtotime( '+12 hours', strtotime($event['start_time'] ) ); ?>
			<div class="wdfb_event_end_time"><?php echo __( 'Ends at:', 'wdfb' ) . ' ' . date( $timestamp_format, $end_time ); ?></div>
		<?php } ?>
	</div>
</li>