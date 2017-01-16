<!doctype html>
<html>
	<head>
		<title>Principal</title>
		<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
		<!--<script src="http://localhost/Booking/app/web/js/dropzone.js"></script> -->
		<script src="http://localhost/Booking/app/web/js/dropzone2.js"></script>
		<link type="text/css" rel="stylesheet" href="http://localhost/Booking/app/web/css/dropzone2.css">
		<?php
		foreach ($controller->getCss() as $css)
		{
			echo '<link type="text/css" rel="stylesheet" href="'.(isset($css['remote']) && $css['remote'] ? NULL : PJ_INSTALL_URL).$css['path'].htmlspecialchars($css['file']).'" />';
		}
		foreach ($controller->getJs() as $js)
		{
			echo '<script src="'.(isset($js['remote']) && $js['remote'] ? NULL : PJ_INSTALL_URL).$js['path'].htmlspecialchars($js['file']).'"></script>';
		}
		?>
		<!--[if gte IE 9]>
  		<style type="text/css">.gradient {filter: none}</style>
		<![endif]-->

	</head>
	<body>
		<div id="container">
    		<div id="header">
    			<!--<div id="logo">
					<a href="https://www.phpjabbers.com/availability-booking-calendar/" target="_blank" rel="nofollow">Availability Booking Calendar</a>
					<span>v<?php echo PJ_SCRIPT_VERSION;?></span>
				</div>-->
			</div>

			<div id="middle">
				<div id="leftmenu">
					<?php require PJ_VIEWS_PATH . 'pjLayouts/elements/leftmenu.php'; ?>
				</div>
				<div id="right">
					<div class="content-top"></div>
					<div class="content-middle" id="content">
					<?php require $content_tpl; ?>
					</div>
					<div class="content-bottom"></div>
				</div> <!-- content -->
				<div class="clear_both"></div>
			</div> <!-- middle -->

		</div> <!-- container -->
		<div id="footer-wrap">
			<div id="footer">
			   	<p>Copyright &copy; <?php echo date("Y"); ?> <a href="https://www.PHPJabbers.com" target="_blank">PHPJabbers.com</a></p>
	       		 </div>
        		</div>
	</body>
</html>