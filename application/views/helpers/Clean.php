<?php
// +----------------------------------------------------------------------+
// | Copyright (c) 2001-2008 Liip AG                                      |
// +----------------------------------------------------------------------+
// | Licensed under the Apache License, Version 2.0 (the "License");      |
// | you may not use this file except in compliance with the License.     |
// | You may obtain a copy of the License at                              |
// | http://www.apache.org/licenses/LICENSE-2.0                           |
// | Unless required by applicable law or agreed to in writing, software  |
// | distributed under the License is distributed on an "AS IS" BASIS,    |
// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
// | implied. See the License for the specific language governing         |
// | permissions and limitations under the License.                       |
// +----------------------------------------------------------------------+
// | Author: Christian Stocker <christian.stocker@liip.ch>                |
// +----------------------------------------------------------------------+
class Clean {

    /**
     * Cleans HTML code from external input. You can specify $filterOut
     * to something like array('Tidy', 'Dom') to filter the output as well.
     *
     * @access  public  static
     * @param   mixed   $string
     * @param   bool    $htmlentities
     * @param   mixed   $filterIn
     * @param   mixed   $filterOut
     */
    public static function xss($string, $htmlentities = FALSE, $filterIn = array('Tidy', 'Dom', 'Striptags'), $filterOut = 'none') {
        // begin by tidying up the input
        $string = self::tidyUp($string, $filterIn);

        // Remove NULL characters (ignored by some browsers)
        $string = str_replace(chr(0), '', $string);

        // Remove Netscape 4 JS entities
        $string = preg_replace('%&\s*\{[^}]*(\}\s*;?|$)%', '', $string);

        // Defuse certain HTML entities
        $string = str_replace(
            array("&amp;", "&lt;", "&gt;"),
            array("&amp;amp;", "&amp;lt;", "&amp;gt;"),
            $string
        );

        // fix &entity
        $string = preg_replace('#(&\#*\w+)[\x00-\x20]+;#u', "$1;", $string);
        $string = preg_replace('#(&\#x*)([0-9A-F]+);*#iu', "$1$2;", $string);

        // revert fixed entities back to their character representation
        $string = html_entity_decode($string, ENT_COMPAT, "UTF-8");


        // if we want to return htmlentities
        if ($htmlentities) {
            $string = htmlentities($string);
        }

        // remove any attribute starting with "on" or xmlns
        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])(on|xmlns)[^>]*>#iUu', "$1>", $string);

        // remove javascript: and vbscript: protocol
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*-moz-binding[\x00-\x20]*:#Uu', '$1=$2nomozbinding...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20\/]*=[\x00-\x20\/]*([\`\'\"]*)[\x00-\x20\/]*data[\x00-\x20]*:#Uu', '$1=$2nodata...', $string);

        // remove any style attributes, IE allows too much stupid things in them, eg.
        // <span style="width: expression(alert('Ping!'));"></span>
        // and in general you really don't want style declarations in your UGC
        $string = preg_replace('#(<[^>]+[\x00-\x20\"\'\/])style[^>]*>#iUu', "$1>", $string);

        // remove namespaced elements (we do not need them...)
        $string = preg_replace('#</*\w+:\w[^>]*>#i', "", $string);

        // remove really unwanted tags
        do {
            $oldstring = $string;
            $string = preg_replace('#</*(applet|meta|xml|blink|link|style|script|embed|object|iframe|frame|frameset|ilayer|layer|bgsound|title|base)[^>]*>#i', "", $string);
        } while ($oldstring != $string);

        // potentially tidy output
        return ($filterOut != 'none') ? self::tidyUp($string, $filterOut) : $string;
    }



    /**
     * Clean up a uristub by removing crappy data.
     *
     * @access  static public
     * @param   string  $str
     * @return  string
     */
    public static function uristub($str)
    {
        $str = strip_tags(trim($str));
        $str = str_replace('&', 'and', $str);
        $str = preg_replace('/[^-a-zA-Z0-9\s]/', '', $str);
        return preg_replace('/\s+/', '-', $str);
    }

    /**
     * Attempt to reverse a uristub by doing a few replaces.
     *
     * @access  public
     */
    public static function deuristub($str)
    {
        $str = str_replace(
            array('-and-', '-'),
            array('-&-', ' '),
            $str
        );
        return self::xss($str, true);
    }

    /**
     * Tidy up a string with a given set of filters, falling back on strip_tags
     * if all other filters failed.
     *
     * @access  public  static
     * @param   string  $string
     * @param   mixed   $filters
     * @return  mixed
     */
    public static function tidyUp($string, $filters) {
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                $return = self::tidyUpWithFilter($string, $filter);
                if ($return !== false) {
                    return $return;
                }
            }
        } else {
            $return = self::tidyUpWithFilter($string, $filters);
        }

        // if no filter matched, use the Striptags filter to be sure.
        if ($return === false) {
            return self::tidyUpModuleStriptags($string);
        } else {
            return $return;
        }
    }

    /**
     * Check if the given filter is callable and perform the call.
     *
     * @access  private static
     * @param   string  $string
     * @param   string  $filter
     * @return  mixed
     */
    private static function tidyUpWithFilter($string, $filter) {
        $class = '';
        if (is_callable(array("self", "tidyUpModule" . $filter, $class))) {
            return $class($string);
            //return call_user_func(array("self", "tidyUpModule" . $filter), $string);
        }
        return false;
    }

    /**
     * Basic functionality to strip tags (buggy). Doesn't properly prevent
     * XSS on attributes or parameters.
     *
     * @access  private static
     * @param   string  $string
     * @return  string
     */
    private static function tidyUpModuleStriptags($string) {
        return strip_tags($string);
    }

    /**
     * Prevent tidying of the string whatsoever.
     *
     * @access  private static
     * @param   string  $string
     * @return  string
     */
    private static function tidyUpModuleNone($string) {
        return $string;
    }

    /**
     * Attempts to tidy up the HTML with DomDocument. Not particularly
     * failsafe.
     *
     * @access  private static
     * @param   string  $string
     * @return  string
     */
    private static function tidyUpModuleDom($string) {
        $dom = new domdocument();
        @$dom->loadHTML("<html><body>" . $string . "</body></html>");
        $string = '';
        foreach ($dom->documentElement->firstChild->childNodes as $child) {
            $string .= $dom->saveXML($child);
        }
        return $string;
    }

    /**
     * Attempts to tidy up the HTML with Tidy class using UTF-8 encoding.
     *
     * @access  private static
     * @param   string  $string
     * @return  string
     */
    public static function tidyUpModuleTidy($string) {
        if (class_exists("tidy")) {

            $tidyOptions = array(
                "output-xhtml" => true,
                "show-body-only" => true,
                "clean" => true,
                "wrap" => 0,
                "indent" => true,
                "indent-spaces" => 1,
                "ascii-chars" => false,
                "wrap-attributes" => false,
                "alt-text" => "",
                "doctype" => "loose",
                "numeric-entities" => true,
                "drop-proprietary-attributes" => true,
                "enclose-text" => false,
                "enclose-block-text" => false
            );

            try {
                $tidy = new tidy();
                $tidy->parseString($string, $tidyOptions, "utf8");
                $tidy->cleanRepair();
                return (string) $tidy;
            } catch (Exception $e) {
                return $string;
            }

        } else {
            return false;
        }
    }
}
