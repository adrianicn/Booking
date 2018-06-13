<style type="text/css">
[title]{
	position:relative;
}

[title]:after{
	content:attr(title);
	color:#fff;
	background:#333;
	background:rgba(51,51,51,0.75);
	padding:4px;
	position:absolute;
	left:-9999px;
	opacity:0;
	bottom:100%;
	white-space:nowrap;
	-webkit-transition:0.25s linear opacity;
}
[title]:hover:after{
	left:5px;
	opacity:1;
}


</style>
<?php
 function check_in_range($start_date, $end_date, $date_from_user){
        return $date_from_user >= $start_date && $date_from_user <= $end_date;
 }

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
			$fechasArray = array();
			$arrayPrueba = array();
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

	                                        $conn = new mysqli(PJ_DB_HOST, PJ_DB_USERNAME, PJ_DB_PASS, PJ_DB_NAME);
	                                        if($mysqli->connect_errno){
	                                             echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	                                        }
	                                       $sqlRango = "SELECT foreign_id,date_from,date_to FROM booking_abcalendar_plugin_price where tab_id > 1 and foreign_id = ".$calendar['id'];
	                                        $queryRango = $conn->query($sqlRango);
	                                        $row_cnt = mysqli_num_rows($queryRango);

	                                        $arrayPrueba[] = $row = mysqli_fetch_row($queryRango);
				//$row = mysqli_fetch_row($queryRango);
                                    		$results = print_r($arrayPrueba,true);
		                          $file = "/var/www/html/pruebaFacturas.txt";
		                          $open = fopen($file,"a");
                                                    if ( $open ) {
                                                        fwrite($open,$results);
                                                        fclose($open);
                                                    }

				# Current month
	                                        $arrayDeClases = array();
				foreach (range(1, $numOfDaysInCurrentMonth) as $d)
				{

					while ($row = mysqli_fetch_object($queryRango)) {
						$resultado_limite = $row;
					}

					$timestamp = mktime(0, 0, 0, $month, $d, $year);
					$originalDate = $year."-".$month."-".$d;
					$newDate = date("D", strtotime($originalDate));
					$diaLetras = strtolower($newDate);

					$pjPriceModel = pjPriceModel::factory();
					$resultado = $pjPriceModel->select($diaLetras)
								    ->where('season','Default price')
						        		     ->where('foreign_id', $calendar['id'])
						         		      ->findAll()->getData();

					$primera = date("Y-m-d", $timestamp);

			    	    	$suffix = date("S", $timestamp);
			    	    	$tomorrow = $timestamp + 86400;
			    	    	$yesterday = $timestamp - 86400;
			    	    	$iso_date = date("Y-m-d", $timestamp);
			    	    	$class = pjUtil::getClass($date_arr, $timestamp, $tomorrow, $yesterday, $calendar['o_bookings_per_day'], $haystack);

		                                        $idCalendario = $calendar['id'];
		                                        $fecha = date("Y-m-d", $timestamp);
		                                        /*CONSUALTA DE RESERVAS CONFIRMADAS*/
		                                       $sqlConfirmada = "SELECT SUM(c_adults + c_children) as confirmed FROM booking_abcalendar_reservations where date_from = '".$fecha."' and status = 'Confirmed' and calendar_id = ".$idCalendario;
		                                        $confirmada = $conn->query($sqlConfirmada);
		                                        foreach ($confirmada as $row1) {
		                                            $ccC = $row1['confirmed'];
		                                        }

		                                        /*CONSUALTA DE RESERVAS PENDIENTES*/
		                                        $sqlPendiente = "SELECT SUM(c_adults + c_children) as confirmed FROM booking_abcalendar_reservations where date_from = '".$fecha."' and status = 'Pending' and calendar_id = ".$idCalendario;
		                                        $pendiente = $conn->query($sqlPendiente);
		                                        foreach ($pendiente as $row1) {
		                                            $ccP = $row1['confirmed'];
		                                        }

		                                        $disponibles  = $calendar['o_bookings_per_day'] - ($ccC + $ccP);
		                                        /**************************************************/
		                                        /*     PARA CALENDARIOS QUE TIENEN  LIMITES    */
					/***************************************************/
		                                        if($resultado_limite->foreign_id == $calendar['id']){
		                                        	$isUserOld = check_in_range($resultado_limite->date_from, $resultado_limite->date_to, $primera);
		                                        	if($isUserOld){
			                                       	$class1 ='abCalendarReserved';
							$arrayDeClases[] = ['id' => $calendar['id'], 'fecha'=>$primera, 'clase' => $class1 ];
							$class1 ='abCalendarReserved';
							?>
								<a href="#" title="<?php echo $primera.' ORAvailability: 0'; ?>"  class="<?php echo $class1; ?>">&nbsp;</a>
							<?php
		                                        	}else{

					    	    	if (isset($date_arr[$timestamp]['status']) && $date_arr[$timestamp]['status'] != 1){
								?><a title="<?php echo $primera.' Availability: '.$disponibles; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminReservations&amp;action=pjActionIndex&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;date=<?php echo $iso_date; ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
					    	    	} else {
					    	    		$params = sprintf("date_from=%s&calendar_id=%u", $iso_date, $calendar['id']);

					    	    		?><a title="<?php echo $primera.' Availability: '.$disponibles; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionRedirect&amp;nextController=pjAdminReservations&amp;nextAction=pjActionCreate&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;nextParams=<?php echo urlencode($params); ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
					    	    	}
		                                        	}

	                                        		/*$results = print_r("  Fecha del Dia:  ".$primera."Pertenece al Calendario que corre: ".$calendar['id']."   ==> y es ".$isUserOld." ((()))",true);
				                          $file = "/var/www/html/pruebaFacturas.txt";
				                          $open = fopen($file,"a");
		                                                    if ( $open ) {
		                                                        fwrite($open,$results);
		                                                        fclose($open);
		                                                    } */

		                                        }
		                                        /**************************************************/
		                                        /*     PARA CALENDARIOS QUE NO TIENEN  LIMITES    */
					/***************************************************/
					if($row_cnt === 0){
				    	    	 if($resultado[0][$diaLetras] == 0.16){
							$class1='abCalendarReserved';
					    	    	if (isset($date_arr[$timestamp]['status']) && $date_arr[$timestamp]['status'] != 1){
								?><a href="#" title="<?php echo 'Availability: 0'; ?>" class="<?php echo $class1; ?>">&nbsp;</a><?php
					    	    	} else {
					    	    		$params = sprintf("date_from=%s&calendar_id=%u", $iso_date, $calendar['id']);
					    	    		?><a href="#" title="<?php echo 'Availability: 0'; ?>" class="<?php echo $class1; ?>">&nbsp;</a><?php
					    	    	}
				    	    	}else{
				    	    		if($disponibles == 0){
								$class1 ='abCalendarReserved';
						    	    	if (isset($date_arr[$timestamp]['status']) && $date_arr[$timestamp]['status'] != 1){
									?><a href="#" title="<?php echo 'Availability: 0'; ?>" class="<?php echo $class1; ?>">&nbsp;</a><?php
						    	    	} else {
						    	    		$params = sprintf("date_from=%s&calendar_id=%u", $iso_date, $calendar['id']);
						    	    		?><a href="#" title="<?php echo 'Availability: 0'; ?>" class="<?php echo $class1; ?>">&nbsp;</a><?php
						    	    	}
				    	    		}else{
						    	    	if (isset($date_arr[$timestamp]['status']) && $date_arr[$timestamp]['status'] != 1){
									?><a title="<?php echo $primera.' Availability: '.$disponibles; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminReservations&amp;action=pjActionIndex&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;date=<?php echo $iso_date; ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
						    	    	} else {
						    	    		$params = sprintf("date_from=%s&calendar_id=%u", $iso_date, $calendar['id']);

						    	    		?><a title="<?php echo $primera.' Availability: '.$disponibles; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionRedirect&amp;nextController=pjAdminReservations&amp;nextAction=pjActionCreate&amp;calendar_id=<?php echo $calendar['id']; ?>&amp;nextParams=<?php echo urlencode($params); ?>" class="<?php echo $class; ?>">&nbsp;</a><?php
						    	    	}
				    	    		}
				    	    	}
					}else{
						/*foreach ($queryRango as $key => $row3) {
				                                       $fecha_desde = $row3['date_from'];
				                                       $fecha_hasta = $row3['date_to'];
				                                       $calendario_valido = $row3['foreign_id'];
				                                       $isUserOld = check_in_range($fecha_desde, $fecha_hasta, $primera);

				                                       if($isUserOld && $calendario_valido == $calendar['id']){
				                                       	$class1 ='abCalendarReserved';
								$arrayDeClases[] = ['id' => $calendar['id'], 'fecha'=>$primera, 'clase' => $class1 ];
								$class1 ='abCalendarReserved';
								?>
									<a href="#" title="<?php echo $primera.' ORAvailability: 0'; ?>"  class="<?php echo $class1; ?>">&nbsp;</a>
								<?php
				                                       }
						}*/
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