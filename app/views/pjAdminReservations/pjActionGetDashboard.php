<?php
if (!isset($tpl['arr']) || empty($tpl['arr']))
{
	$titles = __('error_titles', true);
	$bodies = __('error_bodies', true);
	pjUtil::printNotice(@$titles['AR20'], @$bodies['AR20']);
} else {
	if (isset($_GET['month']) && isset($_GET['year']))
	{
		$time = mktime(0, 0, 0, (int) $_GET['month'], 1, (int) $_GET['year']);
		if(isset($_GET['direction']))
		{
			switch ($_GET['direction'])
			{
				case 'next':
					$time = strtotime("+31 day", $time);
					break;
				case 'prev':
					$time = strtotime("-1 day", $time);
					break;
			}
		}
	} else {
		$time = time();
	}
	list($year, $month, $numOfDaysInCurrentMonth) = explode("-", date("Y-n-t", $time));

	$next_month = $month + 1 <= 12 ? $month + 1 : $month + 1 - 12;
	$next_year = $month + 1 <= 12 ? $year : $year + 1;
	$prev_month = $month - 1 >= 1 ? $month - 1 : $month - 1 + 12;
	$prev_year = $month - 1 >= 1 ? $year : $year - 1;
	?>
	<div class="cal-container">
		<div class="cal-calendars">
			<div class="cal-title" style="height: 64px"></div>
			<?php
			foreach ($tpl['arr'] as $k => $calendar)
			{
				?><div class="cal-title"><?php
				if ($controller->isAdmin())
				{
					?><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionRedirect&amp;nextController=pjAdminCalendars&amp;nextAction=pjActionView&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;nextParams=<?php echo urlencode('id='. $calendar['id']); ?>"><?php echo pjSanitize::html($calendar['title']); ?></a><?php
				} else {
					echo pjSanitize::html($calendar['title']);
				}
				?></div><?php
			}
			?>
		</div>
		<div class="cal-dates">
			<div class="cal-scroll">
			<?php
			$haystack = array(
				'calendarStatus1' => 'abCalendarDate',
				'calendarStatus2' => 'abCalendarReserved',
				'calendarStatus3' => 'abCalendarPending',
				'calendarStatus_1_2' => 'abCalendarReservedNightsStart',
				'calendarStatus_1_3' => 'abCalendarPendingNightsStart',
				'calendarStatus_2_1' => 'abCalendarReservedNightsEnd',
				'calendarStatus_2_3' => 'abCalendarNightsReservedPending',
				'calendarStatus_3_1' => 'abCalendarPendingNightsEnd',
				'calendarStatus_3_2' => 'abCalendarNightsPendingReserved'
			);
			
			$months = __('months', true);
			foreach ($tpl['arr'] as $k => $calendar)
			{
				if ($k == 0)
				{
					?>
					<div class="cal-head">
						<div class="cal-head-row">
							<span style="width: <?php echo 44 * $numOfDaysInCurrentMonth - 3; ?>px">
								<a href="#" class="cal-prev" data-year="<?php echo $prev_year; ?>" data-month="<?php echo $prev_month; ?>"><?php __('lblReservationPrevMonth'); ?></a>
								<?php echo $months[$month]; ?> <?php echo $year; ?>
								<a href="#" class="cal-next" data-year="<?php echo $next_year; ?>" data-month="<?php echo $next_month; ?>"><?php __('lblReservationNextMonth'); ?></a>
							</span>
						</div>
						<div class="cal-head-row">
						<?php
						# Current month
						foreach (range(1, $numOfDaysInCurrentMonth) as $i)
						{
							$timestamp = mktime(0, 0, 0, $month, $i, $year);
    	    				$suffix = date("S", $timestamp);
							?><span><?php echo $i . $suffix; ?></span><?php
						}
						?>
						</div>
					</div>
					<?php
				}
				?>
				<div class="cal-program cal-id-<?php echo $calendar['id']; ?>">
				<?php
				$date_arr = $calendar['date_arr'];
				if ((int) $calendar['o_bookings_per_day'] === 1)
				{
					$date_arr = pjUtil::fixSingleDay($date_arr);
				}
				
				# Current month
				foreach (range(1, $numOfDaysInCurrentMonth) as $d)
				{
					$timestamp = mktime(0, 0, 0, $month, $d, $year);
	    	    	$suffix = date("S", $timestamp);
	    	    	$tomorrow = $timestamp + 86400;
	    	    	$yesterday = $timestamp - 86400;
	    	    	$iso_date = date("Y-m-d", $timestamp);
	    	    	$class = pjUtil::getClass($date_arr, $timestamp, $tomorrow, $yesterday, $calendar['o_bookings_per_day'], $haystack);
	    	    	if (isset($date_arr[$timestamp]['status']) && $date_arr[$timestamp]['status'] != 1)
	    	    	{
						?><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminReservations&amp;action=pjActionIndex&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;date=<?php echo $iso_date; ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
	    	    	} else {
	    	    		$params = sprintf("date_from=%s&calendar_id=%u", $iso_date, $calendar['id']);
	    	    		?><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionRedirect&amp;nextController=pjAdminReservations&amp;nextAction=pjActionCreate&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;nextParams=<?php echo urlencode($params); ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
	    	    	}
				}
				?>
				</div>
				<?php
			}
			?>
			</div>
		</div>
	</div>
	<?php
}
?>