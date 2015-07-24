<?php

/**
 * Class satUser
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class satUser {

	/**
	 * @var array
	 */
	protected static $grades_cabin = array(
		'M/C',
		'M/CSH',
		'FCG',
		'FC',
		'F/A2',
		'F/A1',
		'F/A',
		'FOR',
		'TMP'
	);
	/**
	 * @var array
	 */
	protected static $grades_cockpit = array(
		'CMD',
		'F/O',
		'S/O',
		'CRP'
	);
	/**
	 * @var array
	 */
	protected static $grades_special = array(
		'TIG',
		'FSE',
		'FDT',
		'CST'
	);


	/**
	 * @param satUserSync $satUserSync
	 */
	public function __construct(satUserSync $satUserSync) {
		$this->conf = $satUserSync->props();
	}


	/**
	 * @return array
	 */
	public function getRoleIdsForGrades() {
		$role_ids = array();
		foreach ($this->getGrades() as $grade) {
			if (in_array($grade, self::$grades_cabin)) {
				$role_ids[] = (int)$this->conf()->get('role_id_cabin');
			}
			if (in_array($grade, self::$grades_cockpit)) {
				$role_ids[] = (int)$this->conf()->get('role_id_cockpit');
			}
			if (in_array($grade, self::$grades_special)) {
				$role_ids[] = (int)$this->conf()->get('role_id_special');
			}
		}

		return $role_ids;
	}


	/**
	 * @var string
	 */
	protected $lastname;
	/**
	 * @var string
	 */
	protected $matriculation;
	/**
	 * @var string
	 */
	protected $firstname;
	/**
	 * @var string
	 */
	protected $email;
	/**
	 * @var string
	 */
	protected $fourlc;
	/**
	 * @var array
	 */
	protected $grades;
	/**
	 * @var string
	 */
	protected $birthday;
	/**
	 * @var
	 */
	protected $gender;
	/**
	 * @var
	 */
	protected $matriculation;


	/**
	 * @param string $birthday
	 */
	public function setBirthday($birthday) {
		$this->birthday = $birthday;
	}


	/**
	 * @return string
	 */
	public function getBirthday() {
		return $this->birthday;
	}


	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param string $matriculation
	 */
	public function setMatriculation($matriculation) {
		$this->matriculation = $matriculation;
	}


	/**
	 * @return string
	 */
	public function getMatriculation() {
		return $this->matriculation;
	}


	/**
	 * @param string $firstname
	 */
	public function setFirstname($firstname) {
		$this->firstname = $firstname;
	}


	/**
	 * @return string
	 */
	public function getFirstname() {
		return $this->firstname;
	}


	/**
	 * @param string $fourlc
	 */
	public function setFourlc($fourlc) {
		$this->fourlc = $fourlc;
	}


	/**
	 * @return string
	 */
	public function getFourlc() {
		return $this->fourlc;
	}


	/**
	 * @param string $gender
	 */
	public function setGender($gender) {
		$this->gender = $gender;
	}


	/**
	 * @return string
	 */
	public function getGender() {
		return $this->gender;
	}


	/**
	 * @param array $grades
	 */
	public function setGrades($grades) {
		$this->grades = $grades;
	}


	/**
	 * @return array
	 */
	public function getGrades() {
		return $this->grades;
	}


	/**
	 * @param string $lastname
	 */
	public function setLastname($lastname) {
		$this->lastname = $lastname;
	}


	/**
	 * @return string
	 */
	public function getLastname() {
		return $this->lastname;
	}


	/**
	 * @param mixed $matriculation
	 */
	public function setMatriculation($matriculation) {
		$this->matriculation = $matriculation;
	}


	/**
	 * @return mixed
	 */
	public function getMatriculation() {
		return $this->matriculation;
	}
}

?>
