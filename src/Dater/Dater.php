<?php

namespace Dater;

/**
 * Datetime formats & timezones handler
 *
 * @see https://github.com/barbushin/dater
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @copyright © Sergey Barbushin, 2013. Some rights reserved.
 *
 * All this methods works through Dater::__call method, mapped to format date with Dater::$formats[METHOD_NAME] format:
 * @method date($dateTimeOrTimestamp = null) Get date in Dater::$formats['date'] format, in client timezone
 * @method time($dateTimeOrTimestamp = null) Get date in Dater::$formats['time'] format, in client timezone
 * @method datetime($dateTimeOrTimestamp = null) Get date in Dater::$formats['datetime'] format, in client timezone
 * @method isoDate($dateTimeOrTimestamp = null) Get date in Dater::$formats['isoDate'] format, in client timezone
 * @method isoTime($dateTimeOrTimestamp = null) Get date in Dater::$formats['isoTime'] format, in client timezone
 * @method isoDatetime($dateTimeOrTimestamp = null) Get date in Dater::$formats['isoDatetime'] format, in client timezone
 */
class Dater {

	const USER_DATE_FORMAT = 'date';
	const USER_TIME_FORMAT = 'time';
	const USER_DATETIME_FORMAT = 'datetime';
	const ISO_DATE_FORMAT = 'isoDate';
	const ISO_TIME_FORMAT = 'isoTime';
	const ISO_DATETIME_FORMAT = 'isoDatetime';

	protected $formats = array(
		self::USER_DATE_FORMAT => 'm/d/Y',
		self::USER_TIME_FORMAT => 'g:i A',
		self::USER_DATETIME_FORMAT => 'm/d/Y g:i A',
		self::ISO_DATE_FORMAT => 'Y-m-d',
		self::ISO_TIME_FORMAT => 'H:i:s',
		self::ISO_DATETIME_FORMAT => 'Y-m-d H:i:s',
	);

	/** @var Locale */
	protected $locale;
	/** @var \DateTimezone[] */
	protected $timezonesObjects = array();
	protected $clientTimezone;
	protected $serverTimezone;
	protected $formatOptionsNames = array();
	protected $formatOptionsPlaceholders = array();
	protected $formatOptionsCallbacks = array();

	public function __construct(Locale $locale, $serverTimezone = null, $clientTimezone = null) {
		$this->setLocale($locale);
		$this->setServerTimezone($serverTimezone ? : date_default_timezone_get());
		$this->setClientTimezone($clientTimezone ? : $this->serverTimezone);
		$this->initCustomFormatOptions();
	}

	/**
	 * @return Locale
	 */
	public function getLocale() {
		return $this->locale;
	}

	/**
	 * Get locale by language & country code. See available locales in /Dater/Locale/*
	 * @param string $languageCode
	 * @param null $countryCode
	 * @throws \Exception
	 * @return
	 */
	public static function getLocaleByCode($languageCode, $countryCode = null) {
		$class = 'Dater\Locale\\' . ucfirst(strtolower($languageCode)) . ($countryCode ? ucfirst(strtolower($countryCode)) : '');
		if(!class_exists($class)) {
			throw new \Exception('Unknown locale code. Class "' . $class . '" not found.');
		}
		return new $class();
	}

	public function setLocale(Locale $locale) {
		foreach($locale::getFormats() as $alias => $format) {
			$this->setFormat($alias, $format);
		}
		$this->locale = $locale;
	}

	protected function initCustomFormatOptions() {
		$dater = $this;
		$this->addFormatOption('F', function (\DateTime $dateTime) use ($dater) {
			return $dater->getLocale()->getMonth($dateTime->format('n') - 1);
		});
		$this->addFormatOption('l', function (\DateTime $dateTime) use ($dater) {
			return $dater->getLocale()->getWeekDay($dateTime->format('N') - 1);
		});
		$this->addFormatOption('D', function (\DateTime $dateTime) use ($dater) {
			return $dater->getLocale()->getWeekDayShort($dateTime->format('N') - 1);
		});
	}

	public function setServerTimezone($timezone, $setSystemGlobal = true) {
		if($setSystemGlobal) {
			date_default_timezone_set($timezone);
		}
		$this->serverTimezone = $timezone;
	}

	public function getServerTimezone() {
		return $this->serverTimezone;
	}

	public function setClientTimezone($timezone) {
		$this->clientTimezone = $timezone;
	}

	public function getClientTimezone() {
		return $this->clientTimezone;
	}

	public function addFormatOption($option, $callback) {
		if(!is_callable($callback)) {
			throw new \Exception('Argument $callback is not callable');
		}
		if(array_search($option, $this->formatOptionsPlaceholders) !== false) {
			throw new \Exception('Option "' . $option . '" already added');
		}
		$this->formatOptionsNames[] = $option;
		$this->formatOptionsPlaceholders[] = '~' . count($this->formatOptionsPlaceholders) . '~';
		$this->formatOptionsCallbacks[] = $callback;
	}

	/**
	 * Stash custom format options from standard PHP \DateTime format parser
	 * @param $format
	 * @return bool Return true if there was any custom options in $format
	 */
	protected function stashCustomFormatOptions(&$format) {
		$format = str_replace($this->formatOptionsNames, $this->formatOptionsPlaceholders, $format, $count);
		return (bool)$count;
	}

	/**
	 * Stash custom format options from standard PHP \DateTime format parser
	 * @param $format
	 * @param \DateTime $dateTime
	 * @return bool Return true if there was any custom options in $format
	 */
	protected function applyCustomFormatOptions(&$format, \DateTime $dateTime) {
		$formatOptionsCallbacks = $this->formatOptionsCallbacks;
		$format = preg_replace_callback('/~(\d+)~/', function ($matches) use ($dateTime, $formatOptionsCallbacks) {
			return call_user_func($formatOptionsCallbacks[$matches[1]], $dateTime);
		}, $format);
	}

	/**
	 * Format current datetime to specified format with timezone converting
	 * @param string|null $format http://php.net/date format or format name
	 * @param string|null $outputTimezone Default value is Dater::$clientTimezone
	 * @return string
	 */
	public function now($format, $outputTimezone = null) {
		return $this->format(null, $format, $outputTimezone);
	}

	/**
	 * Init standard \DateTime object configured to outputTimezone corresponding to inputTimezone
	 * @param null $dateTimeOrTimestamp
	 * @param null $inputTimezone
	 * @param null $outputTimezone
	 * @return \DateTime
	 */
	public function initDateTime($dateTimeOrTimestamp = null, $inputTimezone = null, $outputTimezone = null) {
		if(!$inputTimezone) {
			$inputTimezone = $this->serverTimezone;
		}
		if(!$outputTimezone) {
			$outputTimezone = $this->clientTimezone;
		}

		if(strlen($dateTimeOrTimestamp) == 10) {
			$isTimeStamp = is_numeric($dateTimeOrTimestamp);
			$isDate = !$isTimeStamp;
		}
		else {
			$isTimeStamp = false;
			$isDate = false;
		}

		if($isTimeStamp) {
			$dateTime = new \DateTime();
			$dateTime->setTimestamp($dateTimeOrTimestamp);
		}
		else {
			$dateTime = new \DateTime($dateTimeOrTimestamp, $inputTimezone ? $this->getTimezoneObject($inputTimezone) : null);
		}

		if(!$isDate && $outputTimezone && $outputTimezone != $inputTimezone) {
			$dateTime->setTimezone($this->getTimezoneObject($outputTimezone));
		}
		return $dateTime;
	}

	/**
	 * Format \DateTime object to http://php.net/date format or format name
	 * @param \DateTime $dateTime
	 * @param $format
	 * @return string
	 */
	public function formatDateTime(\DateTime $dateTime, $format) {
		$format = $this->getFormat($format) ? : $format;
		$isStashed = $this->stashCustomFormatOptions($format);
		$result = $dateTime->format($format);
		if($isStashed) {
			$this->applyCustomFormatOptions($result, $dateTime);
		}
		return $result;
	}

	/**
	 * Format date/datetime/timestamp to specified format with timezone converting
	 * @param string|int|null $dateTimeOrTimestamp Default value current timestamp
	 * @param string|null $format http://php.net/date format or format name. Default value is current
	 * @param string|null $outputTimezone Default value is Dater::$clientTimezone
	 * @param string|null $inputTimezone Default value is Dater::$serverTimezone
	 * @return string
	 */
	public function format($dateTimeOrTimestamp, $format, $outputTimezone = null, $inputTimezone = null) {
		$dateTime = $this->initDateTime($dateTimeOrTimestamp, $inputTimezone, $outputTimezone);
		$result = $this->formatDateTime($dateTime, $format);
		return $result;
	}

	/**
	 * @param $dateTimeOrTimestamp
	 * @param string $modify Modification string as in http://php.net/date_modify
	 * @param string|null $format http://php.net/date format or format name. Default value is Dater::ISO_DATETIME_FORMAT
	 * @param string|null $outputTimezone Default value is Dater::$serverTimezone
	 * @param string|null $inputTimezone Default value is Dater::$serverTimezone
	 * @return string
	 */
	public function modify($dateTimeOrTimestamp, $modify, $format = null, $outputTimezone = null, $inputTimezone = null) {
		$format = $format ? : self::ISO_DATETIME_FORMAT;
		$outputTimezone = $outputTimezone ? : $this->serverTimezone;
		$inputTimezone = $inputTimezone ? : $this->serverTimezone;
		$dateTime = $this->initDateTime($dateTimeOrTimestamp, $inputTimezone, $outputTimezone);
		$dateTime->modify($modify);
		return $this->formatDateTime($dateTime, $format);
	}

	/**
	 * Get date in YYYY-MM-DD format, in server timezone
	 * @param string|int|null $serverDateTimeOrTimestamp
	 * @return string
	 */
	public function serverDate($serverDateTimeOrTimestamp = null) {
		return $this->format($serverDateTimeOrTimestamp, self::ISO_DATE_FORMAT, $this->serverTimezone);
	}

	/**
	 * Get date in HH-II-SS format, in server timezone
	 * @param string|int|null $serverDateTimeOrTimestamp
	 * @return string
	 */
	public function serverTime($serverDateTimeOrTimestamp = null) {
		return $this->format($serverDateTimeOrTimestamp, self::ISO_TIME_FORMAT, $this->serverTimezone);
	}

	/**
	 * Get datetime in YYYY-MM-DD HH:II:SS format, in server timezone
	 * @param null $serverDateTimeOrTimestamp
	 * @return string
	 */
	public function serverDateTime($serverDateTimeOrTimestamp = null) {
		return $this->format($serverDateTimeOrTimestamp, self::ISO_DATETIME_FORMAT, $this->serverTimezone);
	}

	public function setFormat($alias, $format) {
		$this->formats[$alias] = $format;
	}

	protected function getFormat($alias) {
		if(isset($this->formats[$alias])) {
			return $this->formats[$alias];
		}
	}

	/**
	 * Get DateTimezone object by timezone name
	 * @param $timezone
	 * @return \DateTimezone
	 */
	protected function getTimezoneObject($timezone) {
		if(!isset($this->timezonesObjects[$timezone])) {
			$this->timezonesObjects[$timezone] = new \DateTimezone($timezone);
		}
		return $this->timezonesObjects[$timezone];
	}

	/**
	 * Magic call of $dater->format($dateTimeOrTimestamp, $formatAlias).
	 *
	 * Example:
	 *   $dater->addFormat('shortDate', 'd/m')
	 *   echo $dater->shortDate(time());
	 * To annotate available formats-methods just add to Dater class annotations like:
	 *   @method shortDate($dateTimeOrTimestamp = null)
	 *
	 * @param $formatAlias
	 * @param array $dateTimeOrTimestampArg
	 * @return string
	 * @throws \Exception
	 */
	public function __call($formatAlias, array $dateTimeOrTimestampArg) {
		$formatAlias = $this->getFormat($formatAlias);
		if(!$formatAlias) {
			throw new \Exception('There is no method or format with name "' . $formatAlias . '"');
		}
		return $this->format(reset($dateTimeOrTimestampArg), $formatAlias);
	}
}