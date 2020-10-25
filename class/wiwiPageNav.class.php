<?php

/*
 * derived from standard XoopsPageNav, to use a custom function to change current page.
 */
require_once XOOPS_ROOT_PATH . '/class/pagenav.php';

class wiwiPageNav extends XoopsPageNav
{
    public $navcall;

    public $extra_arg;

    public $start_name;

    /*
     * Added $navcall parameter, which should be a javascript function with the prototype below :
     *		function mynav (extra_arg);
     */

    public function __construct($total_items, $items_perpage, $current_start, $start_name = 'start', $extra_arg = '', $navcall = '')
    {
        $this->navcall = $navcall;

        $this->extra_arg = ('' == $extra_arg ? '' : '&' . $extra_arg);

        $this->start_name = $start_name;

        return parent::__construct($total_items, $items_perpage, $current_start, $start_name, $extra_arg);
    }

    /**
     * Create text navigation
     *
     * @param int $offset
     * @return  string
     **/

    public function renderNav($offset = 4)
    {
        $ret = '';

        if ($this->total <= $this->perpage) {
            return $ret;
        }

        $total_pages = ceil($this->total / $this->perpage);

        if ($total_pages > 1) {
            $prev = $this->current - $this->perpage;

            if ($prev >= 0) {
                if ('' == $this->navcall) {
                    $ret .= '<a href="' . $this->url . $prev . '"><u>&laquo;</u></a> ';
                } else {
                    $ret .= '<a href="#" onclick="javascript:' . $this->navcall . '(\'' . $this->start_name . '=' . $prev . $this->extra_arg . '\');"><u>&laquo;</u></a> ';
                }
            }

            $counter = 1;

            $current_page = (int)floor(($this->current + $this->perpage) / $this->perpage);

            while ($counter <= $total_pages) {
                if ($counter == $current_page) {
                    $ret .= '<b>(' . $counter . ')</b> ';
                } elseif (($counter > $current_page - $offset && $counter < $current_page + $offset) || 1 == $counter || $counter == $total_pages) {
                    if ($counter == $total_pages && $current_page < $total_pages - $offset) {
                        $ret .= '... ';
                    }

                    if ('' == $this->navcall) {
                        $ret .= '<a href="' . $this->url . (($counter - 1) * $this->perpage) . '">' . $counter . '</a> ';
                    } else {
                        $ret .= '<a href="#" onclick="javascript:' . $this->navcall . '(\'' . $this->start_name . '=' . (($counter - 1) * $this->perpage) . $this->extra_arg . '\');">' . $counter . '</a> ';
                    }

                    if (1 == $counter && $current_page > 1 + $offset) {
                        $ret .= '... ';
                    }
                }

                $counter++;
            }

            $next = $this->current + $this->perpage;

            if ($this->total > $next) {
                if ('' == $this->navcall) {
                    $ret .= '<a href="' . $this->url . $next . '"><u>&raquo;</u></a> ';
                } else {
                    $ret .= '<a href="#" onclick="javascript:' . $this->navcall . '(\'' . $this->start_name . '=' . $next . $this->extra_arg . '\');"><u>&raquo;</u></a> ';
                }
            }
        }

        return $ret;
    }
}



