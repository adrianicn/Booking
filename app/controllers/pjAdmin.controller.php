<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjAdmin extends pjAppController
{

	public $defaultUser = 'admin_user';

	public $requireLogin = true;

	public function __construct($requireLogin=null)
	{
		$this->setLayout('pjActionAdmin');

		if (!is_null($requireLogin) && is_bool($requireLogin))
		{
			$this->requireLogin = $requireLogin;
		}

		if ($this->requireLogin)
		{
			if (!$this->isLoged() && !in_array(@$_GET['action'], array('pjActionLogin', 'pjActionForgot', 'pjActionExportFeed')))
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin");
			}
		}
	}

	public function beforeRender()
	{

	}

	public function pjActionForgot()
	{
		$this->setLayout('pjActionAdminLogin');

		if (isset($_POST['forgot_user']))
		{
			$pjUserModel = pjUserModel::factory();

			$user = $pjUserModel
				->where('t1.email', $_POST['forgot_email'])
				->limit(1)
				->findAll()
				->getData();

			if (count($user) != 1)
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionForgot&err=AA10");
			} else {
				$user = $user[0];

				$Email = new pjEmail();
				if ($this->option_arr['o_send_email'] == 'smtp')
				{
					$pjEmail
						->setSmtpHost($this->option_arr['o_smtp_host'])
						->setSmtpUser($this->option_arr['o_smtp_user'])
						->setSmtpPass($this->option_arr['o_smtp_pass'])
						->setSmtpPort($this->option_arr['o_smtp_port']);
				}
				$Email
					->setTo($user['email'])
					->setFrom($user['email'])
					->setSubject(__('emailForgotSubject', true));

				$body = str_replace(
					array('{Name}', '{Password}'),
					array($user['name'], $user['password']),
					__('emailForgotBody', true)
				);

				if ($Email->send($body))
				{
					$err = "AA11";
				} else {
					$err = "AA12";
				}
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionForgot&err=$err");
			}
		} else {
			$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
			$this->appendJs('pjAdmin.js');
		}
	}

	public function pjActionIndex()
	{
		$this->checkLogin();
		echo "<br>";
		echo "Usuario Servicio Sesion: ".$_SESSION['usuario_servicio'] ;
		echo "<br>";
		if($_SESSION['usuario_servicio'] == ""){
			echo "salir del sistema";
			$this->setLayout('pjActionSalir');
		}else{

		if ($this->isAdmin() || $this->isEditor() || $this->isOwner())
		{
			$pjCalendarModel = pjCalendarModel::factory();
			$pjReservationModel = pjReservationModel::factory();
			$pjUserModel = pjUserModel::factory();

			$pjReservationModel
				->select("t1.id, t1.c_name, t1.created, t1.date_from, t1.date_to, t1.status, t3.content AS `calendar_name`")
				->join('pjCalendar', 't2.id=t1.calendar_id', 'inner')
				->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.calendar_id AND t3.field='name' AND t3.locale='".$this->getLocaleId()."'", 'left')
				->limit(3)
				->orderBy('t1.created DESC');
			if ($this->isOwner())
			{
				$pjReservationModel->where('t2.user_id', $this->getUserId());
			}
			$reservation_arr = $pjReservationModel->findAll()->getData();
			$this->set('reservation_arr', $reservation_arr);

			$user_arr = $pjUserModel
				->select(sprintf("t1.id, t1.name, t1.email, t1.last_login,
					(SELECT COUNT(*) FROM `%s` WHERE `user_id` = `t1`.`id` LIMIT 1) AS `calendars`",
					$pjCalendarModel->getTable()))
				->orderBy('calendars DESC')
				->limit(4)->findAll()->getData();
			$this->set('user_arr', $user_arr);

			$condition1 = NULL;
			$condition2 = NULL;
			if ($this->isOwner())
			{
				//$condition1 = " AND `user_id` = :user_id";
				//$condition2 = " AND t2.`user_id` = :user_id";
				$condition1 = " AND `id_usuario_servicio` = ".$_SESSION['usuario_servicio'];
				$condition2 = " AND t2.`id_usuario_servicio` = ".$_SESSION['usuario_servicio'];
			}

			//*****************************************************************************************//
			//	                                 CONTADORES DEL DASHBOARD                                                           //
			//*****************************************************************************************//
			$info_arr = $pjCalendarModel->reset()->prepare(sprintf("SELECT 1,
				(SELECT COUNT(*) FROM `%1\$s` WHERE 1 %4\$s LIMIT 1) AS `calendars`,
				(SELECT COUNT(*) FROM `%2\$s` INNER JOIN `%1\$s` AS t2 ON t2.id = `calendar_id` WHERE 1 %5\$s LIMIT 1) AS `reservations`,
				(SELECT COUNT(*) FROM `%3\$s` WHERE 1 LIMIT 1) AS `users`",
				$pjCalendarModel->getTable(), $pjReservationModel->getTable(), $pjUserModel->getTable(), $condition1, $condition2)
			//)->exec(array('user_id' => $this->getUserId()))->getData();
			)->exec(array('id_usuario_servicio' => $_SESSION['usuario_servicio'] ))->getData();

			$this->set('info_arr', $info_arr);

			//*****************************************************//
			//         OBTENGO EL NOMBRE DEL USUARIO SERVICIO       //
			//*****************************************************//
			$dbHost = 'localhost';
			$dbUsername = 'root';
			$dbPassword = '12345';
			$dbName = 'igtrip';

			$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			if($mysqli->connect_errno){
			     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			}
			$sql = "SELECT nombre_servicio FROM usuario_servicios WHERE id = ".$_SESSION['usuario_servicio']."";
			$conn->query($sql);
			foreach ($conn->query($sql) as $row) {
        			$_SESSION['nombre_servicio'] = $row['nombre_servicio'] ;
        			}
        			//*****************************************************//
			//*****************************************************//

		} else {
			$this->set('status', 2);
		}

		}

	}

	//*************************************************************************************************************//
	// FUNCION QUE SE UTILIZA CUANDO SE LE DA CLICK AL LOGIN      						          //
	//*************************************************************************************************************//
	public function pjActionLogin()
	{

	            $this->setLayout('pjActionAdminLogin');

	            if(isset($_GET['verify'])){
	                    echo "<br>";
	                    echo "Identificador: ".$_GET['verify'];
	                    echo "<br>";

	                    $buscar = trim($_GET['verify']);
	                    $arr = pjVerificarModel::factory()->where('uuid', $buscar)->findAll()->getData();
	                    //print_r($arr);
	                    $data = array();
	                    echo "Id de la tabla: ".$data['id'] = $arr[0]['id'];
	                    echo "<br>";
	                    echo "UUID: ".$data['uuid'] = $arr[0]['uuid'];
	                    echo "<br>";
		       echo "Id Usuario Servicio: ".$data['id_usuario_servicio'] = $arr[0]['id_usuario_servicio'];
	                    echo "<br>";
	                     echo "<br>";
		       echo "Id Usuario: ".$data['id_usuario'] = $arr[0]['id_usuario'];
	                    echo "<br>";
	                    echo "Consumido: ".$data['consumido'] = $arr[0]['consumido'];
	                    echo "<br>";
	                    $idTablaVerificar = $data['id'];
	                    $usuarioServicio = $data['id_usuario_servicio'];
	                    $consumido = $data['consumido'];
	                    $idUsuarioLaravel = $data['id_usuario'] ;

	                    if ($data['uuid'] != $_GET['verify'] ){
	                    	echo "no existe el parametro";
	                    	$this->setLayout('pjActionSalir');

	                    }else {

	                    	if($consumido == 1){
	                    	//echo "sale de la Pagina";
	                    	//ME ENVIA A LA PAGINA DE ERROR
	                    	$this->setLayout('pjActionSalir');

	                    }else{

	                    	echo "se hace el logueo en la pagina";
	                    	echo "<br>";
	                    	echo $_SESSION['usuario_servicio'] = $usuarioServicio;
	                    	echo "<br>";
	                    	echo "se actualiza el consumido a true";

	                    	//HAGO EL UPDATE DEL CONSUMIDO A TRUE
	                    	$pjVerificarModel = pjVerificarModel::factory();
	                    	$data = array();
	    		$data['consumido'] = 1;
	                    	$pjVerificarModel->reset()->setAttributes(array('id' => $idTablaVerificar))->modify($data);

	                    	 //****************************************************************//
	                    	 //                       CODIGO PARA HACER EL LOGUEO                                  //
	                    	//****************************************************************//
	                    	$_POST['login_user'] = 1;
	                    	$pjUserModel = pjUserModel::factory();
	                    	$userGet = $pjUserModel->where('id', $idUsuarioLaravel)
					          ->limit(1)
					          ->findAll()
					          ->getData();

			echo "<br>";
			echo "Email de Usuario: ". $userGet[0]['email'];
			echo "<br>";
			echo "Password de Usuario: ". $userGet[0]['password'];
			echo "<br>";
			print_r($userGet);
			echo "<br>";
	                    	$_POST['login_password'] = "AY6LN9QM" ;
	                    	$_POST['login_email'] = "info@iwannatrip.com";
	                    	//$_POST['login_password'] = "12345";
	                    	//$_POST['login_email']  = "fabio@gmail.com";
	                    	if (isset($_POST['login_user']))
			{
				if (!pjValidation::pjActionNotEmpty($_POST['login_email']) || !pjValidation::pjActionNotEmpty($_POST['login_password']) || !pjValidation::pjActionEmail($_POST['login_email']))
				{
					pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin&err=4");
				}
				$pjUserModel = pjUserModel::factory();

				$user = $pjUserModel
					->where('t1.email', $_POST['login_email'])
					->where(sprintf("t1.password = AES_ENCRYPT('%s', '%s')", $pjUserModel->escapeString($_POST['login_password']), PJ_SALT))
					->limit(1)
					->findAll()
					->getData();

				if (count($user) != 1)
				{
					pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin&err=1");
				} else {
					$user = $user[0];
					unset($user['password']);

					if (!in_array($user['role_id'], array(1,2,3)))
					{
						pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin&err=2");
					}

					if ($user['role_id'] == 3 && $user['is_active'] == 'F')
					{
						pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin&err=2");
					}

					if ($user['status'] != 'T')
					{
						pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin&err=3");
					}

					$last_login = date("Y-m-d H:i:s");
	    			$_SESSION[$this->defaultUser] = $user;

	    			$data = array();
	    			$data['last_login'] = $last_login;
	    			//UPDATE PARA EL VERIFICAR EL ULTIMO LOGIN
	    			$pjUserModel->reset()->setAttributes(array('id' => $user['id']))->modify($data);

	    			//***************************************************************************//
	    			//      QUERY PARA SELECCIONAR LOS CALENDARIOS POR USUARIO SERVICIO         //
	    			//***************************************************************************//
	    			/*$calendar = pjCalendarModel::factory()->where('t1.user_id', $user['id'])
	    			->limit(1)->findAll()->getDataPair(NULL, 'id');*/

				$calendar = pjCalendarModel::factory()->where('t1.id_usuario_servicio', $usuarioServicio)
	    			->limit(1)->findAll()->getDataPair(NULL, 'id');

	    			if (count($calendar) === 1)
	    			{
	    				$this->setForeignId($calendar[0]);
	    			}

	    			if ($this->isAdmin())
	    			{
		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}

				if ($this->isEditor())
	    			{
		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}

				if ($this->isOwner())
	    			{
		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}
				}
			} else {
				$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
				$this->appendJs('pjAdmin.js');
			}
	                    	//****************************************************************//
			//                               FIN DEL CODIGO DE LOGUEO                                  //
	                    	//****************************************************************//
	                    }

	                    }


	            }else{
	                    echo "no esta el parametro";
	            	       //ME ENVIA A LA PAGINA DE ERROR
	                    $this->setLayout('pjActionSalir');

	            }

	}

	public function pjActionLogout()
	{
		if ($this->isLoged()){
        			unset($_SESSION[$this->defaultUser]);
        		}
        		//ME LLEVA A LA PAGINA DE ADMINISTRACION DE LARAVEL
       		//pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionLogin");
       		pjUtil::redirect("http://localhost:8000/detalleServicios");

	}

	public function pjActionMessages()
	{
		$this->setAjax(true);
		header("Content-Type: text/javascript; charset=utf-8");
	}

	public function pjActionProfile()
	{
		if (isset($_POST['profile_update']))
		{
			$pjUserModel = pjUserModel::factory();
			$arr = $pjUserModel->find($this->getUserId())->getData();
			$data = array();
			$data['role_id'] = $arr['role_id'];
			$data['status'] = $arr['status'];
			$post = array_merge($_POST, $data);
			if (!$pjUserModel->validates($post))
			{
				pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&err=AA14");
			}
			$pjUserModel->set('id', $this->getUserId())->modify($post);
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&err=AA13");
		} else {
			$this->set('arr', pjUserModel::factory()->find($this->getUserId())->getData());
			$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
			$this->appendJs('pjAdmin.js');
		}
	}

	public function pjActionRedirect()
	{
		if (isset($_GET['calendar_id']) && (int) $_GET['calendar_id'] > 0)
		{
			if ((int) pjCalendarModel::factory()->where('t1.id', $_GET['calendar_id'])->findCount()->getData() == 1)
			{
				$this->setForeignId($_GET['calendar_id']);
			}
		}

		$qs = NULL;
		if (isset($_GET['nextParams']) && !empty($_GET['nextParams']))
		{
			parse_str($_GET['nextParams'], $params);
			if (!empty($params))
			{
				$qs = http_build_query($params);
				$qs = "&" . $qs;
			}
		}

		pjUtil::redirect(sprintf("%sindex.php?controller=%s&action=%s%s", PJ_INSTALL_URL, $_GET['nextController'], $_GET['nextAction'], $qs));
		exit;
	}
}
?>