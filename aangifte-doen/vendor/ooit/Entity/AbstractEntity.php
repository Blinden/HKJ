<?php

abstract class AbstractEntity implements EntityInterface
{

    public function __construct($data = null)
    {
        if (is_array($data)) {
            $this->hydrate($data);
        }
    }

    public function formatDate($date, $format = 'Y-m-d H:i:s')
    {
        if ($date instanceof \DateTime) {
            return $date->format($format);
        }
        return $date;
    }

    public function datetime($date, $format = 'Y-m-d H:i:s')
    {
        if ($date instanceof \DateTime) {
            return $date;
        }
        
        if (is_array($date)) {
            if (isset($date['year']) && isset($date['month']) && isset($date['day'])) {
                $date = "{$date['year']}-{$date['month']}-{$date['day']}";
            }
        }
        
        $parsedDate = date_parse($date);
        if ($parsedDate['error_count']) {
            return null;
        }
        $date = DateTime::createFromFormat($format, date($format, strtotime($date)));
        // Invalid dates can show up as warnings (ie. "2007-02-99")
        // and still return a DateTime object
        $errors = DateTime::getLastErrors();
        if ($errors['warning_count'] > 0) {
            return false;
        }
        return $date;
    }

}
