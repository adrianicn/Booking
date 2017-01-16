<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdminCalendars extends pjAdmin
{
	public function pjActionCreate()
	{
		$this->checkLogin();

		if ($this->isAdmin())
		{
			if (isset($_POST['calendar_create']))
			{
				$id = pjCalendarModel::factory($_POST)->insert()->getInsertId();
				if ($id !== false && (int) $id > 0)
				{
					$locale_arr = pjLocaleModel::factory()->select('t1.*, t2.file')
						->join('pjLocaleLanguage', 't2.iso=t1.language_iso', 'left')
						->where('t2.file IS NOT NULL')
						->orderBy('t1.sort ASC')->findAll()->getData();

					$this->models['Option']->init($id);
					$this->models['Option']->initConfirmation($id, $locale_arr);
					$err = 'ACR03';
					if (isset($_POST['i18n']))
					{
						pjMultiLangModel::factory()->saveMultiLang($_POST['i18n'], $id, 'pjCalendar');
					}

					$data = $this->models['Option']->reset()->getAllPairs($id);
					pjUtil::pjActionGenerateImages($id, $data);
				} else {
					$err = 'ACR04';
				}
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCalendars&action=pjActionIndex&err=$err");
			} else {
				$locale_arr = pjLocaleModel::factory()->select('t1.*, t2.file')
					->join('pjLocaleLanguage', 't2.iso=t1.language_iso', 'left')
					->where('t2.file IS NOT NULL')
					->orderBy('t1.sort ASC')->findAll()->getData();

				$lp_arr = array();
				foreach ($locale_arr as $v)
				{
					$lp_arr[$v['id']."_"] = $v['file']; //Hack for jquery $.extend, to prevent (re)order of numeric keys in object
				}
				$this->set('lp_arr', $locale_arr);
				$this->set('locale_str', pjAppController::jsonEncode($lp_arr));

				$this->set('user_arr', pjUserModel::factory()->orderBy('t1.name ASC')->findAll()->getData());

				$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
				$this->appendJs('jquery.multilang.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
				$this->appendJs('jquery.tipsy.js', PJ_THIRD_PARTY_PATH . 'tipsy/');
				$this->appendCss('jquery.tipsy.css', PJ_THIRD_PARTY_PATH . 'tipsy/');
				$this->appendJs('pjAdminCalendars.js');
				$this->appendJs('index.php?controller=pjAdmin&action=pjActionMessages', PJ_INSTALL_URL, true);
			}
		} else {
			$this->set('status', 2);
		}
	}

	public function pjActionFotos()
	{

	$this->checkLogin();
	if(!empty($_FILES)){
		//*****************************************************//
		//                SUBO EL ARCHIVO A LA CARPETA                     //
		//*****************************************************//
		$directorio = '/var/www/html/Booking/app/web/uploads/';
		$targetDir = $directorio;
		$usuarioServ = trim($_POST['id_usuario_servicio']);
		$nombreFoto =  $_FILES['file']['name'];
		$fileName = $usuarioServ.'-'.$_FILES['file']['name'];
		$targetFile = $targetDir.$fileName;

		if(move_uploaded_file($_FILES['file']['tmp_name'],$targetFile)){

			//*****************************************************//
			//           GUARDO EN LA BASE DE DATOS IMAGES                //
			//*****************************************************//
			$dbHost = 'localhost';
			$dbUsername = 'root';
			$dbPassword = '12345';
			$dbName = 'igtrip';

			$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			if($mysqli->connect_errno){
			        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			}

			$id_auxiliar = 45;
			$conn->query("INSERT INTO images (filename, original_name, created_at, updated_at, id_auxiliar, id_catalogo_fotografia, id_usuario_servicio, estado_fotografia)
				VALUES('".$fileName."','".$nombreFoto."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."',
					45, 10, ".$usuarioServ.", 1 )");

		}
	}

	}

	public function pjActionDeleteCalendar()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isAdmin())
		{
			$resp = array();
			if (pjCalendarModel::factory()->where('id', $_GET['id'])->where('id !=', $this->getForeignId())->limit(1)->eraseAll()->getAffectedRows() == 1)
			{
				pjMultiLangModel::factory()->where('model', 'pjCalendar')->where('foreign_id', $_GET['id'])->eraseAll();
				pjOptionModel::factory()->where('foreign_id', $_GET['id'])->eraseAll();
				if (pjObject::getPlugin('pjPrice') !== NULL)
				{
					pjPriceModel::factory()->where('foreign_id', $_GET['id'])->eraseAll();
				}
				if (pjObject::getPlugin('pjPeriod') !== NULL)
				{
					$pjPeriodModel = pjPeriodModel::factory();
					$pid = $pjPeriodModel->where('foreign_id', $_GET['id'])->findAll()->getDataPair(null, 'id');
					if (!empty($pid))
					{
						$pjPeriodModel->eraseAll();
						pjPeriodPriceModel::factory()->whereIn('period_id', $pid)->eraseAll();
					}
				}
				$this->pjActionDeleteImages($_GET['id']);

				pjReservationModel::factory()->where('calendar_id', $_GET['id'])->eraseAll();
				$resp['code'] = 200;
			} else {
				$resp['code'] = 100;
			}
			pjAppController::jsonResponse($resp);
		}
		exit;
	}

	private function pjActionDeleteImages($cid)
	{
		$arr = array(
			PJ_UPLOAD_PATH . '%u_reserved_start.jpg',
			PJ_UPLOAD_PATH . '%u_reserved_end.jpg',
			PJ_UPLOAD_PATH . '%u_pending_pending.jpg',
			PJ_UPLOAD_PATH . '%u_reserved_pending.jpg',
			PJ_UPLOAD_PATH . '%u_pending_reserved.jpg',
			PJ_UPLOAD_PATH . '%u_reserved_reserved.jpg',
			PJ_UPLOAD_PATH . '%u_pending_start.jpg',
			PJ_UPLOAD_PATH . '%u_pending_end.jpg',
		);

		if (is_array($cid))
		{
			foreach ($cid as $id)
			{
				foreach ($arr as $img)
				{
					@unlink(sprintf($img, $id));
				}
			}
		} else {
			foreach ($arr as $img)
			{
				@unlink(sprintf($img, $cid));
			}
		}
	}

	public function pjActionDeleteCalendarBulk()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isAdmin())
		{
			if (isset($_POST['record']) && count($_POST['record']) > 0)
			{
				pjCalendarModel::factory()->whereIn('id', $_POST['record'])->where('id !=', $this->getForeignId())->eraseAll();
				pjMultiLangModel::factory()->where('model', 'pjCalendar')->whereIn('foreign_id', $_POST['record'])->where('foreign_id !=', $this->getForeignId())->eraseAll();
				pjOptionModel::factory()->whereIn('foreign_id', $_POST['record'])->where('foreign_id !=', $this->getForeignId())->eraseAll();
				if (pjObject::getPlugin('pjPrice') !== NULL)
				{
					pjPriceModel::factory()->whereIn('foreign_id', $_POST['record'])->where('foreign_id !=', $this->getForeignId())->eraseAll();
				}
				if (pjObject::getPlugin('pjPeriod') !== NULL)
				{
					$pjPeriodModel = pjPeriodModel::factory();
					$pid = $pjPeriodModel->whereIn('foreign_id', $_POST['record'])
						->where('foreign_id !=', $this->getForeignId())
						->findAll()->getDataPair(null, 'id');
					if (!empty($pid))
					{
						$pjPeriodModel->eraseAll();
						pjPeriodPriceModel::factory()->whereIn('period_id', $pid)->eraseAll();
					}
				}
				$this->pjActionDeleteImages($_POST['record']);
				pjReservationModel::factory()->whereIn('calendar_id', $_POST['record'])->where('calendar_id !=', $this->getForeignId())->eraseAll();
			}
		}
		exit;
	}

	public function pjActionGetCalendar()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isAdmin())
		{
			$pjCalendarModel = pjCalendarModel::factory()
				->join('pjMultiLang', "t2.foreign_id = t1.id AND t2.model = 'pjCalendar' AND t2.locale = '".$this->getLocaleId()."' AND t2.field = 'name'", 'left')
				->join('pjUser', "t3.id=t1.user_id", 'left');

			if (isset($_GET['q']) && !empty($_GET['q']))
			{
				$q = $pjCalendarModel->escapeString($_GET['q']);
				$q = str_replace(array('%', '_'), array('\%', '\_'), trim($q));
				$pjCalendarModel->where('t2.content LIKE', "%$q%");
			}

			$column = 'name';
			$direction = 'ASC';
			if (isset($_GET['direction']) && isset($_GET['column']) && in_array(strtoupper($_GET['direction']), array('ASC', 'DESC')))
			{
				$column = $_GET['column'];
				$direction = strtoupper($_GET['direction']);
			}

			$total = $pjCalendarModel->findCount()->getData();
			$rowCount = isset($_GET['rowCount']) && (int) $_GET['rowCount'] > 0 ? (int) $_GET['rowCount'] : 10;
			$pages = ceil($total / $rowCount);
			$page = isset($_GET['page']) && (int) $_GET['page'] > 0 ? intval($_GET['page']) : 1;
			$offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}

			$data = $pjCalendarModel->select('t1.id, t1.user_id, t2.content AS name, t3.name AS user_name, t1.descripcion')
				->orderBy("$column $direction")->limit($rowCount, $offset)->findAll()->getData();

			pjAppController::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
		}
		exit;
	}

	public function pjActionIndex()
	{
		$this->checkLogin();

		if ($this->isAdmin())
		{
			$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
			$this->appendJs('pjAdminCalendars.js');
			$this->appendJs('index.php?controller=pjAdmin&action=pjActionMessages', PJ_INSTALL_URL, true);
		} else {
			$this->set('status', 2);
		}
	}

	public function pjActionSaveCalendar()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isAdmin())
		{
			$pjCalendarModel = pjCalendarModel::factory();
			if (!in_array($_POST['column'], $pjCalendarModel->getI18n()))
			{
				$pjCalendarModel->where('id', $_GET['id'])->limit(1)->modifyAll(array($_POST['column'] => $_POST['value']));
			} else {
				pjMultiLangModel::factory()->updateMultiLang(array($this->getLocaleId() => array($_POST['column'] => $_POST['value'])), $_GET['id'], 'pjCalendar');
			}
		}
		exit;
	}

	private function __getCalendar($cid, $year, $month, $view=1)
	{
		$ABCalendar = new pjABCalendar();
		$ABCalendar
			->setShowNextLink((int) $view > 1 ? false : true)
			->setShowPrevLink((int) $view > 1 ? false : true)
			->setPrevLink("")
			->setNextLink("")
			->set('calendarId', $cid)
			->set('reservationsInfo', pjReservationModel::factory()
				->getInfo(
					$cid,
					date("Y-m-d", mktime(0, 0, 0, $month, 1, $year)),
					date("Y-m-d", mktime(23, 59, 59, $month + $view, 0, $year)),
					$this->option_arr, NULL,
					1
				)
			)
			->set('options', $this->option_arr)
			->set('weekNumbers', (int) $this->option_arr['o_show_week_numbers'] === 1 ? true : false)
			->setStartDay($this->option_arr['o_week_start'])
			->setDayNames(__('day_names', true))
			->setMonthNames(__('months', true))
		;
		if (pjObject::getPlugin('pjPeriod') !== NULL && $this->option_arr['o_price_plugin'] == 'period')
		{
			$ABCalendar->set('periods', pjPeriodModel::factory()->getPeriodsPerDay($cid, $month, $year, $view, $this->option_arr['o_price_based_on'] == 'days'));
		}
		if ((int) $this->option_arr['o_show_prices'] === 1)
		{
			if (pjObject::getPlugin('pjPrice') !== NULL && $this->option_arr['o_price_plugin'] == 'price')
			{
				$price_arr = pjPriceModel::factory()->getPricePerDay(
					$cid,
					date("Y-m-d", mktime(0, 0, 0, $month, 1, $year)),
					date("Y-m-d", mktime(0, 0, 0, $month + $view, 1, $year)),
					$this->option_arr
				);
				$ABCalendar
					->set('prices', $price_arr['priceData'])
					->set('showPrices', true);
			}
		}

		$this->set('ABCalendar', $ABCalendar);
	}

	public function pjActionGetCal()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isAdmin())
		{
			$this->__getCalendar($_GET['cid'], $_GET['year'], $_GET['month']);
		}
	}

	public function pjActionView()
	{
		$this->checkLogin();

		if ($this->isAdmin())
		{
			if (isset($_GET['id']) && (int) $_GET['id'] > 0)
			{
				if ((int) pjCalendarModel::factory()->where('t1.id', $_GET['id'])->findCount()->getData() !== 1)
				{
					pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjAdminCalendars&action=pjActionIndex");
				}
				$this->setForeignId($_GET['id']);
			}

			$this->__getCalendar($this->getForeignId(), date("Y"), date("n"));

			$this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
			$this->appendCss('index.php?controller=pjFront&action=pjActionLoadCss&cid=' . $this->getForeignId() . '&' . rand(1,99999), PJ_INSTALL_URL, true);
			$this->appendJs('pjAdminCalendars.js');
			$this->appendJs('index.php?controller=pjAdmin&action=pjActionMessages', PJ_INSTALL_URL, true);
		} else {
			$this->set('status', 2);
		}
	}

	public function pjActionPrices()
	{
		if ($this->option_arr['o_price_plugin'] == 'price' && pjObject::getPlugin('pjPrice') !== NULL)
		{
			pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjPrice&action=pjActionIndex");
		} elseif ($this->option_arr['o_price_plugin'] == 'period' && pjObject::getPlugin('pjPeriod') !== NULL) {
			pjUtil::redirect(PJ_INSTALL_URL . "index.php?controller=pjPeriod&action=pjActionIndex");
		}
		exit;
	}
}
?>