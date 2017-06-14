<?php

namespace Dater\Locale;
use Dater\Dater;

class It extends \Dater\Locale {

	protected static $months = array('Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre');
	protected static $monthsShort = array('Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic');
	protected static $weekDays = array('Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica');
	protected static $weekDaysShort = array('Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom');

	protected static $formats = array(
		Dater::USER_DATE_FORMAT => 'j F Y',
		Dater::USER_TIME_FORMAT => 'H:i',
		Dater::USER_DATETIME_FORMAT => 'd/m/Y H:i',
	);
}