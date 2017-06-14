<?php

namespace Dater\Locale;
use Dater\Dater;

class En extends \Dater\Locale {

	protected static $months = array('January', 'February', 'March', 'April', 'May', 'June', 'Jule', 'August', 'September', 'October', 'November', 'December');
	protected static $monthsShort = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	protected static $weekDays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	protected static $weekDaysShort = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

	protected static $formats = array(
		Dater::USER_DATE_FORMAT => 'j F Y',
		Dater::USER_TIME_FORMAT => 'g:i A',
		Dater::USER_DATETIME_FORMAT => 'm/d/Y g:i A',
	);
}
