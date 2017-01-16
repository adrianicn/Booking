<?php

if (isset($tpl['status']))
{
	$status = __('status', true);
	switch ($tpl['status'])
	{
		case 2:
			pjUtil::printNotice(NULL, $status[2]);
			break;
	}
} else {
	$locale = isset($_GET['locale']) && (int) $_GET['locale'] > 0 ? (int) $_GET['locale'] : NULL;
	if (is_null($locale))
	{
		foreach ($tpl['lp_arr'] as $v)
		{
			if ($v['is_default'] == 1)
			{
				$locale = $v['id'];
				break;
			}
		}
	}
	if (is_null($locale))
	{
		$locale = @$tpl['lp_arr'][0]['id'];
	}
	$titles = __('error_titles', true);
	$bodies = __('error_bodies', true);

	$jquery_validation = __('jquery_validation', true);
	?>


	<div class="ui-tabs ui-widget ui-widget-content ui-corner-all b10">
		<ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
			<li class="ui-state-default ui-corner-top"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionIndex"><?php __('menuCalendars'); ?></a></li>
			<li class="ui-state-default ui-corner-top ui-tabs-active ui-state-active"><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionCreate"><?php __('lblAddCalendar'); ?></a></li>
		</ul>
	</div>

	<?php pjUtil::printNotice(@$titles['ACR11'], @$bodies['ACR11']); ?>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionCreate" method="post" id="frmCreateCalendar" class="form pj-form" autocomplete="off">
		<input type="hidden" name="calendar_create" value="1" />

		<?php if ((int) $tpl['option_arr']['o_multi_lang'] === 1 && count($tpl['lp_arr']) > 1) : ?>
		<div class="multilangb b10"></div>
		<?php endif;?>

		<div class="clear_both">
		<input type="hidden" name="id_usuario_servicio" value=" <?php echo $_SESSION['usuario_servicio']; ?> " >
			<p>
				<label class="title"><?php __('lblUser'); ?></label>
				<span class="inline_block">
					<!-- data-msg-required="<?php echo $jquery_validation['required'];?>" > -->
					<select name="user_id" id="user_id" class="pj-form-field required"
					data-msg-required="Usuario es Requerido" >
						<option value="">-- <?php __('lblChoose'); ?>--</option>
						<?php
						foreach ($tpl['user_arr'] as $v)
						{
							?><option value="<?php echo $v['id']; ?>"><?php echo stripslashes($v['name']); ?></option><?php
						}
						?>
					</select>
				</span>
			</p>
			<?php
			foreach ($tpl['lp_arr'] as $v)
			{
			?>
				<p class="pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 0 ? 'none' : NULL; ?>">
					<label class="title"><?php __('lblName'); ?>:</label>
					<span class="inline_block">
						<input type="text" name="i18n[<?php echo $v['id']; ?>][name]" class="pj-form-field w300<?php echo (int) $v['is_default'] === 0 ? NULL : ' required'; ?>" data-msg-required="Nombre es Requerido" />
						<?php if ((int) $tpl['option_arr']['o_multi_lang'] === 1 && count($tpl['lp_arr']) > 1) : ?>
						<span class="pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="" /></span>
						<?php endif;?>
					</span>
				</p>
				<?php
			}
			?>
			<p>
				<label class="title"> Descripcion</label>
				<span class="inline_block">
					<textarea name="descripcion" id="descripcion" style="resize: none;" rows="4" cols="43" maxlength="250" class="pj-form-field required"
					data-msg-required="Descripcion es Requerido" placeholder="Describe yourself here...">
					</textarea>
					<!--<input type="text" name="descripcion" class="pj-form-field required" data-msg-required="Descripcion es Requerido" style="width: 300px;height:50px;"
					maxlength="250"> -->
				</span>
			</p>
			<label class="title" style="display: inline-table;"> Agregar Foto</label>
			<div class="image_upload_div" style="width: 50%;" >
				<form action="upload.php" class="dropzone">
			    	</form>
			</div>
<div class="image_upload_div" style="width: 80%;display: inline-table;margin-left: 20%">
				<form action="http://localhost/Booking/index.php?controller=pjAdminCalendars&action=pjActionFotos" class="dropzone" enctype= multipart/form-data >
				<input type="hidden" name="id_usuario_servicio" value=" <?php echo $_SESSION['usuario_servicio']; ?> " >
			    	</form>
			</div>

			<p>

			<br><br>
				<label class="title">&nbsp;</label>
				<input type="submit" value="<?php __('btnSave'); ?>" class="pj-button" />
			</p>
		</div>
	</form>


	<script type="text/javascript">
	var myLabel = myLabel || {};
	myLabel.localeId = "<?php echo $controller->getLocaleId(); ?>";
	(function ($) {
		$(function() {
			$(".multilang").multilang({
				langs: <?php echo $tpl['locale_str']; ?>,
				flagPath: "<?php echo PJ_FRAMEWORK_LIBS_PATH; ?>pj/img/flags/",
				tooltip: "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sit amet faucibus enim.",
				select: function (event, ui) {
					// Callback, e.g. ajax requests or whatever
				}
			});
			$(".multilang").find("a[data-index='<?php echo $locale; ?>']").trigger("click");
		});
	})(jQuery_1_8_2);
	</script>
	<?php
}
?>