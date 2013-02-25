<?php
class Skookum_View_Helper_RelativeTime extends Zend_View_Helper_Abstract
{
    public function relativeTime($date, $levels = 1)
    {
        $date_conversions = array('year' => 31536000,
                                  'month' => 2678400,
                                  'week' => 604800,
                                  'day' => 86400,
                                  'hour' => 60,
                                  'second' => 1);
        
        $curdate = time();
        $diff = $date - $curdate;
        $ending = $diff > 0 ? 'left' : 'ago';
        $diff = abs($diff);
        $current_level = 1;
        $result = array();
        
        foreach($date_conversions as $timeframe => $seconds) {
            if ($current_level > $levels) break;
            if (($diff / $seconds) >= 1) {
                $amount = $display = floor($diff / $seconds);
                if ($timeframe == 'hour')  {
                    $display = ceil($amount / $seconds);
                }
                $plural = ($display > 1) ? 's' : '';
                $result[] = "$display {$timeframe}{$plural}";
                $diff -= $amount * $seconds;
                ++$current_level;
            } else if ($diff == 0) {
                $result[] = '0 seconds';
                break;
            }
        }
        
        return ucwords(strtolower(implode(" ", $result) . " $ending"));
    }
}