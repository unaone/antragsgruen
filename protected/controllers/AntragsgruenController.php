<?php

class AntragsgruenController extends CController
{
	public $layout = '//layouts/column1';
	public $menu = array();
	public $breadcrumbs = array();
	public $multimenu = null;
	public $menus_html = null;
	public $breadcrumbs_topname = null;
	public $text_comments = true;
	public $shrink_cols = false;

	/** @var null|Veranstaltung */
	public $veranstaltung = null;

	/** @var null|Veranstaltungsreihe */
	public $veranstaltungsreihe = null;


	private $_assetsBase;

	/**
	 *
	 */
	public function testeWartungsmodus()
	{
		if ($this->veranstaltung == null) return;
		/** @var VeranstaltungsEinstellungen $einstellungen */
		$einstellungen = $this->veranstaltung->getEinstellungen();
		if ($einstellungen->wartungs_modus_aktiv && !$this->veranstaltung->isAdminCurUser()) $this->redirect($this->createUrl("veranstaltung/wartungsmodus"));
	}

	/**
	 *
	 */
	protected function setStdVeranstaltung()
	{
		$veranstaltung_id    = (isset($_REQUEST["id"]) ? IntVal($_REQUEST["id"]) : Yii::app()->params['standardVeranstaltung']);
		$this->veranstaltung = Veranstaltung::model()->findByPk($veranstaltung_id);
	}

	/**
	 * @param string $route
	 * @param array $params
	 * @param string $ampersand
	 * @return string
	 */
	public function createUrl($route, $params = array(), $ampersand = '&')
	{
		$p = explode("/", $route);
		if ($p[0] != "infos") {
			if (!isset($params["veranstaltung_id"]) && $this->veranstaltung !== null) $params["veranstaltung_id"] = $this->veranstaltung->url_verzeichnis;
			if (MULTISITE_MODE && !isset($params["veranstaltungsreihe_id"]) && $this->veranstaltungsreihe != null) $params["veranstaltungsreihe_id"] = $this->veranstaltungsreihe->subdomain;
			if ($route == "veranstaltung/index" && !is_null($this->veranstaltungsreihe) && strtolower($params["veranstaltung_id"]) == strtolower($this->veranstaltungsreihe->aktuelle_veranstaltung->url_verzeichnis)) unset($params["veranstaltung_id"]);
			if (in_array($route, array(
				"veranstaltung/ajaxEmailIstRegistriert", "veranstaltung/benachrichtigungen", "veranstaltung/impressum", "veranstaltung/login", "veranstaltung/logout", "/admin/index/reiheAdmins", "/admin/index/reiheVeranstaltungen"
			))
			) unset($params["veranstaltung_id"]);
		}
		return parent::createUrl($route, $params, $ampersand);
	}

	/**
	 * @param string $veranstaltungsreihe_id
	 * @param string $veranstaltung_id
	 * @param null|Antrag $check_antrag
	 * @param null|Aenderungsantrag $check_aenderungsantrag
	 * @return null|Veranstaltung
	 */
	public function loadVeranstaltung($veranstaltungsreihe_id, $veranstaltung_id = "", $check_antrag = null, $check_aenderungsantrag = null)
	{

		if ($veranstaltungsreihe_id == "") $veranstaltungsreihe_id = Yii::app()->params['standardVeranstaltungsreihe'];

		if ($veranstaltung_id == "") {
			/** @var Veranstaltungsreihe $reihe */
			$reihe = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $veranstaltungsreihe_id));
			if ($reihe) {
				$veranstaltung_id = $reihe->aktuelle_veranstaltung->url_verzeichnis;
			} else {
				$this->render('error', array(
					"code"    => 404,
					"html"    => true,
					"message" => "Die angegebene Veranstaltung wurde nicht gefunden. Höchstwahrscheinlich liegt da an einem Tippfehler in der Adresse im Browser.<br>
					<br>
					Auf der <a href='http://www.antragsgruen.de/'>Antragsgrün-Startseite</a> siehst du rechts eine Liste der aktiven Veranstaltungen."
				));
				Yii::app()->end();
			}
		}

		if (is_null($this->veranstaltungsreihe)) {
			if (is_numeric($veranstaltungsreihe_id)) {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByPk($veranstaltungsreihe_id);
			} else {
				$this->veranstaltungsreihe = Veranstaltungsreihe::model()->findByAttributes(array("subdomain" => $veranstaltungsreihe_id));
			}
		}

		if (is_null($this->veranstaltung)) {
			$this->veranstaltung = Veranstaltung::model()->findByAttributes(array("url_verzeichnis" => $veranstaltung_id));
		}

		if (strtolower($this->veranstaltung->veranstaltungsreihe->subdomain) != strtolower($veranstaltungsreihe_id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - die Veranstaltung gehört nicht zur Veranstaltungsreihe.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (is_object($check_antrag) && strtolower($check_antrag->veranstaltung->url_verzeichnis) != strtolower($veranstaltung_id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Antrag gehört nicht zur Veranstaltung.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if ($check_aenderungsantrag != null && ($check_antrag == null || $check_aenderungsantrag->antrag_id != $check_antrag->id)) {
			Yii::app()->user->setFlash("error", "Fehlerhafte Parameter - der Änderungsantrag gehört nicht zum Antrag.");
			$this->redirect($this->createUrl("veranstaltung/index", array("veranstaltung_id" => $veranstaltung_id)));
			return null;
		}

		if (!is_a($this->veranstaltung, "Veranstaltung") || $this->veranstaltung->policy_kommentare == Veranstaltung::$POLICY_NIEMAND) $this->text_comments = false;

		return $this->veranstaltung;
	}


	public function getAssetsBase()
	{
		if ($this->_assetsBase === null) {
			$this->_assetsBase = Yii::app()->assetManager->publish(
				Yii::getPathOfAlias('application.assets'),
				false,
				-1,
				defined('YII_DEBUG') && YII_DEBUG
			);
		}
		return $this->_assetsBase;
	}

	/**
	 * @param string $success_redirect
	 * @param string $username
	 * @param string $password
	 * @throws Exception
	 */
	protected function performLogin_username_password($success_redirect, $username, $password)
	{
		/** @var Person[] $users */
		if (strpos($username, "@")) {
			$sql_where1 = "auth = 'email:" . addslashes($username) . "'";
			if ($this->veranstaltungsreihe) {
				$sql_where2 = "(auth = 'ns_admin:" . addslashes($username) . "' AND veranstaltungsreihe_namespace = " . IntVal($this->veranstaltungsreihe->id) . ")";
				$users      = Person::model()->findAllBySql("SELECT * FROM person WHERE $sql_where1 OR $sql_where2");
			} else {
				$users = Person::model()->findAllBySql("SELECT * FROM person WHERE $sql_where1");
			}

		} else {
			$auth  = "openid:https://service.gruene.de/openid/" . $username;
			$users = Person::model()->findBySql("SELECT * FROM person WHERE auth = '" . addslashes($auth) . "' OR (auth LIKE 'openid:https://service.gruene.de%' AND email = '" . addslashes($username) . "')");
		}

		if (count($users) == 0) {
			throw new Exception("BenutzerInnenname nicht gefunden.");
		}
		$correct_user = null;
		foreach ($users as $try_user) {
            if ((defined("IGNORE_PASSWORD_MODE") && IGNORE_PASSWORD_MODE === true) || $try_user->validate_password($password)) {
                $correct_user = $try_user;
            }
        }
        if ($correct_user) {
			$x = explode(":", $correct_user->auth);
			switch ($x[0]) {
				case "email":
				case "ns_admin":
					$identity = new AntragUserIdentityPasswd($x[1], $correct_user->auth);
					break;
				case "openid":
					if ($correct_user->istWurzelwerklerIn()) $identity = new AntragUserIdentityPasswd($correct_user->getWurzelwerkName(), $correct_user->auth);
					else throw new Exception("Keine Passwort-Authentifizierung mit anderen OAuth-Implementierungen möglich.");
					break;
				default:
					throw new Exception("Ungültige Authentifizierungsmethode. Wenn dieser Fehler auftritt, besteht ein Programmierfehler.");
			}
			Yii::app()->user->login($identity);

			if ($correct_user->admin) {
				//$openid->setState("role", "admin");
				Yii::app()->user->setState("role", "admin");
			}

			Yii::app()->user->setState("person_id", $correct_user->id);
			Yii::app()->user->setFlash('success', 'Willkommen!');
			if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;

			$this->redirect($success_redirect);
		} else {
			throw new Exception("Falsches Passwort.");
		}

		//Yii::app()->user->login($us);
		die();
	}

	/**
	 * @param OAuthLoginForm $model
	 * @param array $form_params
	 * @throws Exception
	 */
	protected function performLogin_OAuth_init(&$model, $form_params)
	{
		$model->attributes = $form_params;


		if (stripos($model->openid_identifier, "yahoo") !== false) {
			throw new Exception("Leider ist wegen technischen Problemen ein Login mit Yahoo momentan nicht möglich.");
		} else {
			/** @var LightOpenID $loid */
			$loid = Yii::app()->loid->load();
			//if ($model->wurzelwerk != "") $loid->identity = "https://" . $model->wurzelwerk . ".netzbegruener.in/";
			if ($model->wurzelwerk != "") $loid->identity = "https://service.gruene.de/openid/" . $model->wurzelwerk;
			else $loid->identity = $model->openid_identifier;

			$loid->required  = array('namePerson/friendly', 'contact/email'); //Try to get info from openid provider
			$loid->realm     = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$loid->returnUrl = $loid->realm . yii::app()->getRequest()->requestUri;
			if (empty($err)) {
				try {
					$url = $loid->authUrl();
					$this->redirect($url);
				} catch (Exception $e) {
					throw new Exception($e->getMessage());
				}
			}
		}
		if (!empty($err)) Yii::app()->user->setFlash("error", $err);
	}


	/**
	 * @param AntragUserIdentityOAuth $user_identity
	 */
	protected function performLogin_OAuth_create_user($user_identity)
	{
		$email = $user_identity->getEmail();

		$user                   = new Person;
		$user->auth             = $user_identity->getId();
		$user->name             = $user_identity->getName();
		$user->email            = $email;
		$user->email_bestaetigt = 0;
		$user->angelegt_datum   = date("Y-m-d H:i:s");
		$user->status           = Person::$STATUS_CONFIRMED;
		$user->typ              = Person::$TYP_PERSON;

		$password      = substr(md5(uniqid()), 0, 8);
		$user->pwd_enc = Person::create_hash($password);

		if (Person::model()->count() == 0) {
			$user->admin = 1;
			Yii::app()->user->setState("role", "admin");
		} else {
			$user->admin = 0;
		}

		$user->save();

		if (trim($email) != "") {
			$user->refresh();
			$send_text = "Hallo!\n\nDein Zugang bei Antragsgrün wurde eben eingerichtet.\n\n" .
				"Du kannst dich mit folgenden Daten einloggen:\nBenutzerInnenname: $email\nPasswort: %passwort%\n\n" .
				"Das Passwort kannst du hier ändern:\n" .
				yii::app()->getBaseUrl(true) . yii::app()->createUrl("infos/passwort") . "\n\n" .
				"Außerdem ist auch weiterhin ein Login über deinen Wurzelwerk-Zugang möglich.\n\n" .
				"Liebe Grüße,\n  Das Antragsgrün-Team";
			AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_REGISTRIERUNG, $email, $user->id, "Dein Antragsgrün-Zugang", $send_text, null, array(
				"%passwort%" => $password,
			));
		}

	}


	/**
	 * @param string $success_redirect
	 * @param string $openid_mode
	 * @throws Exception
	 */
	protected function performLogin_OAuth_callback($success_redirect, $openid_mode)
	{
		/** @var LightOpenID $loid */
		$loid = Yii::app()->loid->load();
		if ($openid_mode != 'cancel') {
			try {
				$us = new AntragUserIdentityOAuth($loid);
				if ($us->authenticate()) {
					Yii::app()->user->login($us);
					$user = Person::model()->findByAttributes(array("auth" => $us->getId()));
					if (!$user) {
						$this->performLogin_OAuth_create_user($us);
					} else {
						if ($user->admin) {
							//$openid->setState("role", "admin");
							Yii::app()->user->setState("role", "admin");
						}
					}
					Yii::app()->user->setState("person_id", $user->id);
					Yii::app()->user->setFlash('success', 'Willkommen!');
					if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;
					$this->redirect($success_redirect);
				} else {
					throw new Exception("Leider ist beim Einloggen ein Fehler aufgetreten.");
				}
			} catch (Exception $e) {
				throw new Exception("Leider ist beim Einloggen ein Fehler aufgetreten:<br>" . $e->getMessage());
			}
		}

		if (!empty($err)) Yii::app()->user->setFlash("error", $err);
	}

	/**
	 * @param string $success_redirect
	 * @param string $login
	 * @throws Exception
	 */
	protected function performLogin_from_email_params($success_redirect, $login)
	{
		/** @var Person $user */
		$user = Person::model()->findByAttributes(array("id" => $login));
		if ($user === null) {
			throw new Exception("BenutzerInnenname nicht gefunden");
		}
		$identity = new AntragUserIdentityPasswd($user->getWurzelwerkName(), $user->auth);
		Yii::app()->user->login($identity);

		if ($user->admin) {
			//$openid->setState("role", "admin");
			Yii::app()->user->setState("role", "admin");
		}

		Yii::app()->user->setState("person_id", $user->id);
		Yii::app()->user->setFlash('success', 'Willkommen!');
		if ($success_redirect == "") $success_redirect = Yii::app()->homeUrl;

		$this->redirect($success_redirect);
	}


	/**
	 * @param string $success_redirect
	 * @throws Exception
	 * @return OAuthLoginForm
	 */
	protected function performLogin($success_redirect)
	{

		$model = new OAuthLoginForm();

		if (isset($_REQUEST["password"]) && $_REQUEST["password"] != "" && isset($_REQUEST["username"])) {
			$this->performLogin_username_password($success_redirect, $_REQUEST["username"], $_REQUEST["password"]);
		} elseif (isset($_REQUEST["openid_mode"])) {
			$this->performLogin_OAuth_callback($success_redirect, $_REQUEST['openid_mode']);
		} elseif (isset($_REQUEST["OAuthLoginForm"])) {
			$this->performLogin_OAuth_init($model, $_REQUEST["OAuthLoginForm"]);
		} elseif (isset($_REQUEST["login"]) && $_REQUEST["login_sec"] == AntiXSS::createToken($_REQUEST["login"])) {
			$this->performLogin_from_email_params($success_redirect, $_REQUEST["login"]);
		}
		return $model;
	}

	/**
	 * @param string $email
	 * @param string|null $password
	 * @param string|null $bestaetigungscode
	 * @return array
	 */
	protected function loginOderRegistrieren_backend($email, $password = null, $bestaetigungscode = null)
	{
		$msg_ok         = $msg_err = "";
		$correct_person = null;

		$person = Person::model()->findAll(array(
			"condition" => "email='" . addslashes($email) . "' AND pwd_enc != ''"
		));
		if (count($person) > 0) {
			/** @var Person $p */
			$p = $person[0];
			if ($p->email_bestaetigt) {
				if ($p->validate_password($password)) {
					$correct_person = $p;

					if ($p->istWurzelwerklerIn()) $identity = new AntragUserIdentityPasswd($p->getWurzelwerkName(), $p->auth);
					else $identity = new AntragUserIdentityPasswd($p->email, $p->auth);
					Yii::app()->user->login($identity);
				} else {
					$msg_err = "Das angegebene Passwort ist leider falsch.";
				}
			} else {
				if ($p->checkEmailBestaetigungsCode($bestaetigungscode)) {
					$p->email_bestaetigt = 1;
					if ($p->save()) {
						$msg_ok   = "Die E-Mail-Adresse wurde freigeschaltet. Ab jetzt wirst du entsprechend deinen Einstellungen benachrichtigt.";
						$identity = new AntragUserIdentityPasswd($p->email, $p->auth);
						Yii::app()->user->login($identity);
					} else {
						$msg_err = "Ein sehr seltsamer Fehler ist aufgetreten.";
					}
				} else {
					$msg_err = "Leider stimmt der angegebene Code nicht";
				}
			}
		} else {
			$email                    = trim($email);
			$passwort                 = Person::createPassword();
			$person                   = new Person;
			$person->auth             = "email:" . $email;
			$person->name             = "";
			$person->email            = $email;
			$person->email_bestaetigt = 0;
			$person->angelegt_datum   = date("Y-m-d H:i:s");
			$person->status           = Person::$STATUS_UNCONFIRMED;
			$person->typ              = Person::$TYP_PERSON;
			$person->pwd_enc          = Person::create_hash($passwort);
			$person->admin            = 0;

			if ($person->save()) {
				$person->refresh();
				$best_code = $person->createEmailBestaetigungsCode();
				$link      = Yii::app()->getBaseUrl(true) . $this->createUrl("veranstaltung/benachrichtigungen", array("code" => $best_code));
				$send_text = "Hallo,\n\num Benachrichtigungen bei Antragsgrün zu erhalten, klicke entweder auf folgenden Link:\n%best_link%\n\n"
					. "...oder gib, wenn du auf Antragsgrün danach gefragt wirst, folgenden Code ein: %code%\n\n"
					. "Das Passwort für den Antragsgrün-Zugang lautet: %passwort%\n\n"
					. "Liebe Grüße,\n\tDas Antragsgrün-Team.";
				AntraegeUtils::send_mail_log(EmailLog::$EMAIL_TYP_REGISTRIERUNG, $email, $person->id, "Anmeldung bei Antragsgrün", $send_text, null, array(
					"%code%"      => $best_code,
					"%best_link%" => $link,
					"%passwort%"  => $passwort,
				));
				$correct_person = $person;

				$identity = new AntragUserIdentityPasswd($email, $person->auth);
				Yii::app()->user->login($identity);
			} else {
				$msg_err = "Leider ist ein (ungewöhnlicher) Fehler aufgetreten.";
				$errs    = $person->getErrors();
				foreach ($errs as $err) foreach ($err as $e) $msg_err .= $e;
			}

		}
		return array($correct_person, $msg_ok, $msg_err);
	}


	/**
	 * @static
	 * @param array $submit_data
	 * @param int $submit_status
	 * @param bool $andereAntragstellerInErlaubt
	 * @return Person
	 */
	public static function getCurrenPersonOrCreateBySubmitData($submit_data, $submit_status, $andereAntragstellerInErlaubt)
	{
		if (Yii::app()->user->isGuest) {
			$person_id = Yii::app()->user->getState("person_id");
			if ($person_id) {
				$model_person = Person::model()->findByAttributes(array("id" => $person_id));
			} else {
				$model_person                 = new Person();
				$model_person->attributes     = $submit_data;
				$model_person->admin          = 0;
				$model_person->angelegt_datum = new CDbExpression('NOW()');
				$model_person->status         = $submit_status;

				if (!$model_person->save()) {
					foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Person konnte nicht angelegt werden: $key: $val2");
					$model_person = null;
				} else {
					Yii::app()->user->setState("person_id", $model_person->id);
				}
			}
		} elseif ($andereAntragstellerInErlaubt && isset($_REQUEST["andere_antragstellerIn"])) {
			$model_person                 = new Person();
			$model_person->attributes     = $submit_data;
			$model_person->admin          = 0;
			$model_person->angelegt_datum = new CDbExpression('NOW()');
			$model_person->status         = $submit_status;

			if (!$model_person->save()) {
				foreach ($model_person->getErrors() as $key => $val) foreach ($val as $val2) Yii::app()->user->setFlash("error", "Person konnte nicht angelegt werden: $key: $val2");
				$model_person = null;
			}
		} else {
			$model_person = Person::model()->findByAttributes(array("auth" => Yii::app()->user->id));
		}
		return $model_person;
	}


}