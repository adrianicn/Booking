<!doctype html>
<html>
	<head>
		<title>Corfirmacion de Pago</title>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<?php
		foreach ($controller->getCss() as $css)
		{
			echo '<link type="text/css" rel="stylesheet" href="'.(isset($css['remote']) && $css['remote'] ? NULL : PJ_INSTALL_URL).$css['path'].$css['file'].'" />';
		}

		foreach ($controller->getJs() as $js)
		{
			echo '<script src="'.(isset($js['remote']) && $js['remote'] ? NULL : PJ_INSTALL_URL).$js['path'].$js['file'].'"></script>';
		}
		?>

	<script language="JavaScript">
		//javascript:window.history.forward(1);
		//javascript:window.history.back(1);
	</script>
	<script language="JavaScript">
		function nobackbutton(){
			window.location.hash="no-back-button";
	   		window.location.hash="Again-No-back-button" //chrome
			window.onhashchange=function(){window.location.hash="no-back-button";}
		}
	</script>
	</head>

	<body onload="nobackbutton();">
		<div id="container">
			<div id="header">
				<!--<div id="logo">
					<a href="https://www.phpjabbers.com/availability-booking-calendar/" target="_blank" rel="nofollow">Availability Booking Calendar</a>
					<span>v<?php echo PJ_SCRIPT_VERSION;?></span>
				</div> -->
			</div>
			<div id="middle">

				<div id="login-content">
				<h1 style="font-size: 2vw !important;text-align: center;font-weight: bold;margin-bottom: 10%;">EL Pago de Ha Realizado Exitosamente </h1>
				<h2 style="font-size: 1vw !important; text-align: center;font-weight: bold;">Gracias Por Utilizar el Sistema de Booking</h2>
				<center>
				<br><br>
				<a href="http://localhost:8000/detalleServicios" style="text-align: center;font-size: 1vw !important;font-weight: bold;margin-top: 5%;">Volver</a>
				<!-- <a href="http://localhost:8000/detalleServicios" target="_blank" style="text-align: center;font-size: 1vw !important;font-weight: bold;margin-top: 5%;">Volver</a> -->
				</center>

				</div>

			</div>
		</div>
	<div id="footer-wrap">
			<div id="footer">
			   	<p>Copyright &copy; <?php echo date("Y"); ?> <a href="https://www.PHPJabbers.com" target="_blank">PHPJabbers.com</a></p>
	        </div>
        	</div>
	</body>
</html>

