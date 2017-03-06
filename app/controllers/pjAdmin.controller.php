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
		//echo "Usuario Servicio Sesion: ".$_SESSION['usuario_servicio'] ;
		$_SESSION['usuario_servicio'] ;

		if($_SESSION['usuario_servicio'] == ""){
			echo "salir del sistema";
			$this->setLayout('pjActionSalir');
		}else{

		if ($this->isAdmin() || $this->isEditor() || $this->isOwner())
		{
			$idUsuarioServio = trim($_SESSION['usuario_servicio']);
			$pjCalendarModel = pjCalendarModel::factory();
			$pjReservationModel = pjReservationModel::factory();
			$pjUserModel = pjUserModel::factory();

			$pjReservationModel
				->select("t1.id, t1.c_name, t1.created, t1.date_from, t1.date_to, t1.status, t3.content AS `calendar_name`")
				->join('pjCalendar', 't2.id=t1.calendar_id', 'inner')
				->join('pjMultiLang', "t3.model='pjCalendar' AND t3.foreign_id=t1.calendar_id AND t3.field='name' AND t3.locale='".$this->getLocaleId()."'", 'left')
				->orderBy('t1.created DESC');
			if ($this->isOwner())
			{
				//$pjReservationModel->where('t2.user_id', $this->getUserId());
				$pjReservationModel->where('t2.id_usuario_servicio',$idUsuarioServio);
			}
			$reservation_arr = $pjReservationModel->findAll()->getData();

			$this->set('reservation_arr', $reservation_arr);

			$user_arr = $pjUserModel
				->select(sprintf("t1.id, t1.name, t1.email, t1.last_login,
					(SELECT COUNT(*) FROM `%s` WHERE `id_usuario_servicio` = `t1`.`id` LIMIT 1) AS `calendars`",
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

			if($this->isOwner()){

				$info_arr[0]['users'] = 1;
			}
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
	                    	/*$pjVerificarModel = pjVerificarModel::factory();
	                    	$data = array();
	    		$data['consumido'] = 1;
	                    	$pjVerificarModel->reset()->setAttributes(array('id' => $idTablaVerificar))->modify($data);*/

	                    	 //****************************************************************//
	                    	 //                       CODIGO PARA HACER EL LOGUEO                                  //
	                    	//****************************************************************//
	                    	$_POST['login_user'] = 1;

	                    	$pjUserModel = pjUserModel::factory();
	                    	$userGet = $pjUserModel->where('id', $idUsuarioLaravel)
					          ->limit(1)
					          ->findAll()
					          ->getData();

			print_r($userGet);
			if (empty($userGet)) {

			//*****************************************************//
			//        HAGO EL INSERT DEL USUARIO  LARAVEL        //
			//*****************************************************//
			$dbHost = 'localhost';
			$dbUsername = 'root';
			$dbPassword = '12345';
			$dbName = 'igtrip';

			$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			if($mysqli->connect_errno){
			     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			}

			$sql = "SELECT * FROM users WHERE id = ".$idUsuarioLaravel."";
			$conn->query($sql);
			foreach ($conn->query($sql) as $row) {
        			$nombreUsuario = $row['username'] ;
        			$emailUsuario = $row['email'] ;
        			}

        			$data = array();
        			$data['id'] = $idUsuarioLaravel;
        			$data['role_id'] = 3;
        			$data['name'] = $nombreUsuario;
        			$data['email'] = $emailUsuario;
        			$data['password'] = 12345;
        			$data['is_active'] = 'T';
			$data['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['status'] = "T";

			$insertUser = pjUserModel::factory($data)->insert()->getInsertId();
			//echo "resultado id";
			//print_r($insertUser);

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

			}else{
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

			}

			//ADMINISTRADOR
			//$_POST['login_password'] = "AY6LN9QM" ;
	                    	//$_POST['login_email'] = "info@iwannatrip.com";

			//USUARIOSERVICIOCALENDARIO
	                    	$_POST['login_password'] = $userGet[0]['password'];
	                    	$_POST['login_email']  = $userGet[0]['email'];

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

	    			if ($this->isAdmin() ||  $this->isOwner())
	    			{

		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}

				if ($this->isEditor())
	    			{
		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}

				/*if ($this->isOwner())
	    			{
		    			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionIndex");
	    			}*/
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


	            }

                    elseif(isset($_GET['verifyCalendar'])){

                        echo "<br>";
	                echo "Identificador: ".$_GET['verifyCalendar'];
	                echo "<br>";

	                $buscar = trim($_GET['verifyCalendar']);
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

	                    if ($data['uuid'] != $_GET['verifyCalendar'] ){
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
	                    	echo "<br>";
	                    	echo "**********************************************";
	                    	echo "<br>";
	                    	echo "ID DEL CALENDARIO".$id_calendar = $_GET['calendar'];
	                    	echo "<br>";

	                    	//HAGO EL UPDATE DEL CONSUMIDO A TRUE
	                    	/*$pjVerificarModel = pjVerificarModel::factory();
	                    	$data = array();
	    		$data['consumido'] = 1;
	                    	$pjVerificarModel->reset()->setAttributes(array('id' => $idTablaVerificar))->modify($data);*/

	                    	 //****************************************************************//
	                    	 //                       CODIGO PARA HACER EL LOGUEO                                  //
	                    	//****************************************************************//
	                    	$_POST['login_user'] = 1;

	                    	$pjUserModel = pjUserModel::factory();
	                    	$userGet = $pjUserModel->where('id', $idUsuarioLaravel)
					          ->limit(1)
					          ->findAll()
					          ->getData();

			print_r($userGet);
			if (empty($userGet)) {

			//*****************************************************//
			//        HAGO EL INSERT DEL USUARIO  LARAVEL        //
			//*****************************************************//
			$dbHost = 'localhost';
			$dbUsername = 'root';
			$dbPassword = '12345';
			$dbName = 'igtrip';

			$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			if($mysqli->connect_errno){
			     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			}

			$sql = "SELECT * FROM users WHERE id = ".$idUsuarioLaravel."";
			$conn->query($sql);
			foreach ($conn->query($sql) as $row) {
        			$nombreUsuario = $row['username'] ;
        			$emailUsuario = $row['email'] ;
        			}

        			$data = array();
        			$data['id'] = $idUsuarioLaravel;
        			$data['role_id'] = 3;
        			$data['name'] = $nombreUsuario;
        			$data['email'] = $emailUsuario;
        			$data['password'] = 12345;
        			$data['is_active'] = 'T';
			$data['ip'] = $_SERVER['REMOTE_ADDR'];
			$data['status'] = "T";

			$insertUser = pjUserModel::factory($data)->insert()->getInsertId();

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

			}else{
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

			}

			//ADMINISTRADOR
			//$_POST['login_password'] = "AY6LN9QM" ;
	                    	//$_POST['login_email'] = "info@iwannatrip.com";

			//USUARIOSERVICIOCALENDARIO
	                	$_POST['login_password'] = $userGet[0]['password'];
	                	$_POST['login_email']  = $userGet[0]['email'];

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

				$calendar = pjCalendarModel::factory()->where('t1.id_usuario_servicio', $usuarioServicio)
	    			->limit(1)->findAll()->getDataPair(NULL, 'id');

	    			if (count($calendar) === 1)
	    			{
	    				$this->setForeignId($calendar[0]);
	    			}

	    			if ($this->isOwner())
	    			{
	    				$_SESSION["comprobar"] = true;
					pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdminCalendars&action=pjActionView&id=".$id_calendar);
	    			}




				}
			} else {
				$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
				$this->appendJs('pjAdmin.js');
			}
	                    	//****************************************************************//
			//       FIN DEL CODIGO DE LOGUEO  PARA SETTING CALENDAR              //
	                    	//****************************************************************//
	                    }

	                    }

                    }
                    elseif(isset($_GET["rk"])){
                 //****************************************************************//
                 //                       CODIGO PARA HACER EL LOGUEO                                 //
               //****************************************************************//
	             $_POST['login_user'] = 1;
        		//ADMINISTRADOR
		echo $_POST['login_password'] = "AY6LN9QM" ;
	             echo $_POST['login_email'] = "info@iwannatrip.com";

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
				}
                                else {
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

                                if ($this->isAdmin() ||  $this->isOwner()){
                                	$dbHost = 'localhost';
			$dbUsername = 'root';
			$dbPassword = '12345';
			$dbName = 'igtrip';

			$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
			if($mysqli->connect_errno){
			     echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			}

			  //***********************************************************************//
                                	 //  VERIFICO QUE EL MD5 ENVIADO POR PAYPAL ESTE EN LA TABLA TOKENS     //
			 //        SI ESTA EN LA TABLA VERIFICAR QUE CONSUMIDO NO SEA TRUE            //
			//        		DE LO CONTRARIO MARCO CONSUMIDO EN TRUE                      //
                                        //***********************************************************************//

                        $sqlMD5 = "SELECT id,consumido FROM tokens WHERE uuid = '".$_GET["rk"]."' ";
			$conn->query($sqlMD5);

			$id = array();
			$consumido = array();
			foreach ($conn->query($sqlMD5) as $row1) {
        				$id[] = $row1['id'];
        				$consumido[] = $row1['consumido'];
        			}
	        		 $id = implode(" ", $id);
	        		 $consumido = implode(" ", $consumido);
	        		 $id = trim($id);
	        		 $consumido = trim($consumido);

        			if($consumido == 0){
	        			$sqlUpdate = "UPDATE tokens SET consumido = true
		    		WHERE id = $id";
				$conn->query($sqlUpdate);
			}else{
  			$this->setLayout('pjActionSalir');
        			//pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&confirmacion");
                        		}
                       //***********************************************************************//
                       // 		CODIGO PARA COMPROBAR LA FORMA DE PAGO                      //
                       //***********************************************************************//

                       //ENTRA A PAYPAL
                       if(isset($_POST["custom"])){

                       $numeroReservacion = trim($_POST["custom"]);
                       $estadoPago = trim($_POST["payment_status"]);
                        //if($estadoPago === "Completed"){
			//OBTENGO EL  ID DEL CALENDARIO
			$sql1 = "SELECT calendar_id FROM booking_abcalendar_reservations WHERE id = ".$numeroReservacion."";
			$conn->query($sql1);
			$idCalendario = "";
			foreach ($conn->query($sql1) as $row1) {
        				$idCalendario = $row1['calendar_id'];
        			}

        			//OBTENGO EL  ID DEL USUARIO SERVICIO
			$sql2 = "SELECT id_usuario_servicio FROM booking_abcalendar_calendars WHERE id = ".$idCalendario."";
			$conn->query($sql2);
			$idUsuServ = "";
			foreach ($conn->query($sql2) as $row2) {
        				$idUsuServ = $row2['calendar_id'];
        			}

        			//ACTUALIZO EL ESTADO DE LA RESERVA
        			$pjReservaModel = pjReservationModel::factory();
	                    	$data = array();
                                       $data['status'] = "Confirmed";
	                    	$pjReservaModel->reset()->setAttributes(array('id' => $numeroReservacion))->modify($data);

	                    	//*******************************************************//
	                    	//          HAGO EL INSERT EN LA TABLA PAGO PAYPAL              //
	                    	//*******************************************************//
	    	              $conn->query("INSERT INTO pago_paypals (id_reserva, nombreCalendario, estadoPago, fechaPago, montoPago,consumido, created_at, updated_at)
				VALUES(".$_POST['custom'].",'".$_POST['item_name']."', '".$_POST['payment_status']."','".$_POST['payment_date']."', '".$_POST['payment_gross']."',false ,'".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')");
		             pjUtil::redirect("http://localhost:8000/confirmacionPP/".$numeroReservacion);
	                          //pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&confirmacion");

                                /*}else{
                                        pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&verificacionPagoPaypal");
                                }*/

                       //ENTRA A AUTHORIZE
                       }elseif(isset($_POST["x_invoice_num"])){

                         $numeroReservacion = trim($_POST["x_invoice_num"]);
                         $estadoPago = trim($_POST["x_response_reason_text"]);

                        //if($estadoPago === "This transaction has been approved."){
			//OBTENGO EL  ID DEL CALENDARIO
			$sql1 = "SELECT calendar_id FROM booking_abcalendar_reservations WHERE id = ".$numeroReservacion."";
			$conn->query($sql1);
			$idCalendario = "";
			foreach ($conn->query($sql1) as $row1) {
        				$idCalendario = $row1['calendar_id'];
        			}

        			//ACTUALIZO EL ESTADO DE LA RESERVA
        			$pjReservaModel = pjReservationModel::factory();
	                    	$data = array();
                                       $data['status'] = "Confirmed";
	                    	$pjReservaModel->reset()->setAttributes(array('id' => $numeroReservacion))->modify($data);

	                    	$sqlCash = "SELECT content FROM booking_abcalendar_multi_lang WHERE model = 'pjCalendar' AND  field = 'name' AND foreign_id =". $idCalendario;
					$cash = $conn->query($sqlCash);
					$nombreCalendario = "";
					foreach ($cash as $row2) {
        						$nombreCalendario = $row2['content'];
        					}


	                    	//*******************************************************//
	                    	//          HAGO EL INSERT EN LA TABLA PAGO PAYPAL              //
	                    	//*******************************************************//
	    	              $conn->query("INSERT INTO pago_authorizes (id_reserva, nombreCalendario, estadoPago, fechaPago, montoPago,consumido, created_at, updated_at)
				VALUES(".$numeroReservacion.",'".$nombreCalendario."', '".$estadoPago."','".date("Y-m-d H:i:s")."', '".$_POST['x_amount']."',false ,'".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')");

	                         pjUtil::redirect("http://localhost:8000/confirmacionTarjetaCredito/".$numeroReservacion);

                                /*}else{
                                        pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&verificacionPagoAuthorize");
                                }*/

                        }

	    		}




		}
	        }else {
				$this->appendJs('jquery.validate.min.js', PJ_THIRD_PARTY_PATH . 'validate/');
				$this->appendJs('pjAdmin.js');
		}

                                      	//****************************************************************//
			//       FIN DEL CODIGO DE LOGUEO  PARA SETTING CALENDAR              //
	                    	//****************************************************************//



                    }
                    else{
	                    //ME ENVIA A LA PAGINA DE ERROR
	                    $this->setLayout('pjActionSalir');

	            }

	}

	public function pjActionConfirmacion()
	{


		 //********************************************************//
		//                                Para Authorize                                          //
		//********************************************************//
		if(isset($_GET["auth"])){
			$authorize = $_GET["auth"];
		           $results = print_r($authorize, true);
		           $file = "/var/www/html/pruebaFacturas.txt";
			$open = fopen($file,"a");
			if ( $open ) {
			    fwrite($open,$results);
			    fclose($open);
			}
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&confirmacion");
			/*$pjReservationModel = pjReservationModel::factory();
			$data = array();
			$data['status'] = "Confirmed";
			$pjReservationModel->reset()->setAttributes(array('id' => $_GET["auth"]))->modify($data);
			pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&confirmacion");*/
		}

                //********************************************************//
		//                                Para Paypal                                               //
		//********************************************************//
		if(isset($_GET['st'])){
			if($_GET['st'] == "Completed"){
				if(isset($_GET['cm'])){
			                    echo "<br>";
			                    echo "Identificador: ".$_GET['cm'];
			                    echo "<br>";

			                     $pjReservationModel = pjReservationModel::factory();
			                     $data = array();
			    	        $data['status'] = "Confirmed";
			                     $pjReservationModel->reset()->setAttributes(array('id' => $_GET['cm']))->modify($data);

			                     echo "Ir a Pagina de Gracias por su reserva";
			                     echo "<br>";
			                     //$this->setLayout('pjActionAdmin');
			                     //$this->setLayout('pjActionConfirmar');
			                     pjUtil::redirect($_SERVER['PHP_SELF'] . "?controller=pjAdmin&action=pjActionProfile&confirmacion");


			                }
		              }else{
		              	echo "Ir a la PAgina de Error";
		              }
		 }
	}

	public function pjActionError()
	{
		echo "Action Error";
	                die();
	}



	public function pjActionLogout()
	{
		if ($this->isLoged()){
        			unset($_SESSION[$this->defaultUser]);
        			session_unset();
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

		if (isset($_GET['confirmacion']))
		{
			$this->setLayout('pjActionConfirmar');
		}

                if (isset($_GET['confirmacionAuthorize']))
		{
			$this->setLayout('pjActionConfirmarAuthorize');
		}

                 if (isset($_GET['verificacionPago']))
		{
			$this->setLayout('pjActionError');
		}

               if (isset($_GET['verificacionPagoPaypal']))
		{
			$this->setLayout('pjActionErrorPaypal');
		}

                if (isset($_GET['verificacionPagoAuthorize']))
		{
			$this->setLayout('pjActionErrorAuthorize');
		}

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


