<?php
/*
 * This file is part of the pws-client package.
 *
 * (c) meta-tech.academy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace MetaTech\Output;

use MetaTech\Util\Tool;
use MetaTech\PwsAuth\Authenticator;

/*!
 * PwsAuth token
 * 
 * @package         MetaTech\Output
 * @class           class Formatter
 * @author          a-Sansara
 * @date            2017-03-11 20:09:24 CET
 */
class Formatter
{
    /*! @constant TYPE_CLI */
    const TYPE_CLI  = 0;
    /*! @constant TYPE_HTML */
    const TYPE_HTML = 1;
    /*! @constant TYPE_JSON */
    const TYPE_JSON = 2;
    /*! @constant CSS_PREFIX_TAG */
    const CSS_PREFIX_TAG = 'meta-tech-of-tag';
    /*! @constant CSS_NAME_LINE */
    const CSS_NAME_LINE  = 'meta-tech-of-line';
    /*! @constant LF */
    const LF            = "
";
    /*! @protected @var int $type */
    protected $type;
    /*! @protected @var int $type */
    protected $embedStyle;

    /*!
     * desc
     * 
     * @constructor
     * @public
     * @param       int     $type
     */
    public function __construct($type = Formatter::TYPE_CLI)
    {
        $this->type       = $type;
        $this->embedStyle = false;
    }

    /*!
     * desc
     * 
     * @method      _initCall
     * @private
     * @param       str     $uri    the web service uri
     * @return      $curl instance 
     */
    private function format($value, $color=null)
    {
        if ($this->type == self::TYPE_HTML) {
            $tag     = $color === false ? '' : (is_null($color) ? '</span>' : '<span class="' . self::CSS_PREFIX_TAG . $color . '">');
            $content = $tag . $value;
        }
        else {
            $tag     = $color === false ? '' : (is_null($color) ? "\033[0m" : "\033[1;3".$color."m" );
            $content = $tag . $value;
        }
        return $content;
    }

    /*!
     * @method      getHtmlClass
     * @private
     * @param       int     $idColor
     * @return      str
     */
    public function embedStyleIfNeeded()
    {
        $style = '';
        if (!$this->embedStyle) {
            $this->embedStyle = true;
            $style .= '<style type="text/css">'
                    . '.meta-tech-of-line { font-size:13px !important; background-color:black !important; color:white !important; white-space:pre !important; font-weight:bold !important; font-family:\'monospace\' !important; padding:10px !important; margin-top:0 !important }'
                    . '.meta-tech-of-tag1 { color:#FB4E4E !important; }'
                    . '.meta-tech-of-tag2 { color:#20FF93 !important; }'
                    . '.meta-tech-of-tag3 { color:#FFDC58 !important; }'
                    . '.meta-tech-of-tag4 { color:#44A2D6 !important; }'
                    . '</style>';
        }
        return $style;
    }

    /*!
     * @method      writeTags
     * @private
     * @param       []      $tags
     */
    public function writeTags($tags)
    {
        $content = '';
        foreach($tags as $tag) {
            if (!empty($tag)) {
                if (is_array($tag)) {
                    $content .= $this->format($tag[0], count($tag) > 1 ? $tag[1] : null);
                }
                elseif (is_string($tag)) {
                    $content .= $this->format($tag);
                }
            }
        }
        $this->write($content);
    }

    /*!
     * @method      write
     * @private
     * @param       str     $value
     * @param       bool    $newline
     * @return      str
     */
    private function write($value, $newline=true)
    {
        $content = $value . ($newline ? self::LF : '');
        if ($this->type == self::TYPE_HTML) {
            $content = $this->embedStyleIfNeeded() . '<div class="meta-tech-of-line">'.$content.'</div>';
        }
        echo $content;
    }

}
