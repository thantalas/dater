<?php

namespace Dater\Locale;
use Dater\Dater;

class EnUk extends \Dater\Locale {

	protected static $months = array('January', 'February', 'March', 'April', 'May', 'June', 'Jule', 'August', 'September', 'October', 'November', 'December');
	protected static $monthsShort = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	protected static $weekDays = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	protected static $weekDaysShort = array('Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun');

	protected static $formats = array(
		Dater::USER_DATE_FORMAT => 'm/d/Y',
		Dater::USER_TIME_FORMAT => 'G:i',
		Dater::USER_DATETIME_FORMAT => 'm/d/Y G:i',
	);
}
