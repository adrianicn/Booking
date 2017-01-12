<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjCalendarModel extends pjAppModel
{
	protected $primaryKey = 'id';

	protected $table = 'calendars';

	protected $schema = array(
		array('name' => 'id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'user_id', 'type' => 'int', 'default' => ':NULL'),
		array('name' => 'id_usuario_servicio', 'type' => 'int', 'default' => ':NULL')
	);

	public $i18n = array('name', 'confirm_subject', 'confirm_tokens', 'payment_subject', 'payment_tokens', 'terms_url', 'terms_body');

	public static function factory($attr=array())
	{
		return new pjCalendarModel($attr);
	}
}
?>