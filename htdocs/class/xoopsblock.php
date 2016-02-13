<?php
/**
 * XOOPS Block management
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       (c) 2000-2015 XOOPS Project (www.xoops.org)
 * @license             GNU GPL 2 (http://www.gnu.org/licenses/gpl-2.0.html)
 * @package             kernel
 * @since               2.0.0
 * @author              Kazumi Ono (AKA onokazu) http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/
 * @author              Skalpa Keo <skalpa@xoops.org>
 * @author              Taiwen Jiang <phppp@users.sourceforge.net>
 * @version             $Id: xoopsblock.php 13090 2015-06-16 20:44:29Z beckmi $
 */

defined('XOOPS_ROOT_PATH') || exit('Restricted access');

include_once $GLOBALS['xoops']->path('kernel/object.php');

/**
 * Class XoopsBlock
 */
class XoopsBlock extends XoopsObject
{
    public $db;

    /**
     * @param null $id
     */
    public function XoopsBlock($id = null)
    {
        $this->db = XoopsDatabaseFactory::getDatabaseConnection();
        $this->initVar('bid', XOBJ_DTYPE_INT, null, false);
        $this->initVar('mid', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('func_num', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('options', XOBJ_DTYPE_TXTBOX, null, false, 255);
        $this->initVar('name', XOBJ_DTYPE_TXTBOX, null, true, 150);
        //$this->initVar('position', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('title', XOBJ_DTYPE_TXTBOX, null, false, 150);
        $this->initVar('content', XOBJ_DTYPE_TXTAREA, null, false);
        $this->initVar('side', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('weight', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('visible', XOBJ_DTYPE_INT, 0, false);
        // The block_type is in a mess, let's say:
        // S - generated by system module
        // M - generated by a non-system module
        // C - Custom block
        // D - cloned system/module block
        // E - cloned custom block, DON'T use it
        $this->initVar('block_type', XOBJ_DTYPE_OTHER, null, false);
        $this->initVar('c_type', XOBJ_DTYPE_OTHER, null, false);
        $this->initVar('isactive', XOBJ_DTYPE_INT, null, false);

        $this->initVar('dirname', XOBJ_DTYPE_TXTBOX, null, false, 50);
        $this->initVar('func_file', XOBJ_DTYPE_TXTBOX, null, false, 50);
        $this->initVar('show_func', XOBJ_DTYPE_TXTBOX, null, false, 50);
        $this->initVar('edit_func', XOBJ_DTYPE_TXTBOX, null, false, 50);

        $this->initVar('template', XOBJ_DTYPE_OTHER, null, false);
        $this->initVar('bcachetime', XOBJ_DTYPE_INT, 0, false);
        $this->initVar('last_modified', XOBJ_DTYPE_INT, 0, false);

        if (!empty($id)) {
            if (is_array($id)) {
                $this->assignVars($id);
            } else {
                $this->load((int)($id));
            }
        }
    }

    /**
     * Load $id
     *
     * @param int $id
     */
    public function load($id)
    {
        $id  = (int)($id);
        $sql = 'SELECT * FROM ' . $this->db->prefix('newblocks') . ' WHERE bid = ' . $id;
        $arr = $this->db->fetchArray($this->db->query($sql));
        $this->assignVars($arr);
    }

    /**
     * Store Block Data to Database
     *
     * @return int $id
     */
    public function store()
    {
        if (!$this->cleanVars()) {
            return false;
        }
        foreach ($this->cleanVars as $k => $v) {
            ${$k} = $v;
        }
        if (empty($bid)) {
            $bid = $this->db->genId($this->db->prefix("newblocks") . "_bid_seq");
            $sql = sprintf("INSERT INTO %s (bid, mid, func_num, options, name, title, content, side, weight, visible, block_type, c_type, isactive, dirname, func_file, show_func, edit_func, template, bcachetime, last_modified) VALUES (%u, %u, %u, %s, %s, %s, %s, %u, %u, %u, %s, %s, %u, %s, %s, %s, %s, %s, %u, %u)", $this->db->prefix('newblocks'), $bid, $mid, $func_num, $this->db->quoteString($options), $this->db->quoteString($name), $this->db->quoteString($title), $this->db->quoteString($content), $side, $weight, $visible, $this->db->quoteString($block_type), $this->db->quoteString($c_type), 1, $this->db->quoteString($dirname), $this->db->quoteString($func_file), $this->db->quoteString($show_func), $this->db->quoteString($edit_func), $this->db->quoteString($template), $bcachetime, time());
        } else {
            $sql = "UPDATE " . $this->db->prefix("newblocks") . " SET options=" . $this->db->quoteString($options);
            // a custom block needs its own name
            if ($this->isCustom() /* in_array( $block_type , array( 'C' , 'E' ) ) */) {
                $sql .= ", name=" . $this->db->quoteString($name);
            }
            $sql .= ", isactive=" . $isactive . ", title=" . $this->db->quoteString($title) . ", content=" . $this->db->quoteString($content) . ", side=" . $side . ", weight=" . $weight . ", visible=" . $visible . ", c_type=" . $this->db->quoteString($c_type) . ", template=" . $this->db->quoteString($template) . ", bcachetime=" . $bcachetime . ", last_modified=" . time() . " WHERE bid=" . $bid;
        }
        if (!$this->db->query($sql)) {
            $this->setErrors("Could not save block data into database");

            return false;
        }
        if (empty($bid)) {
            $bid = $this->db->getInsertId();
        }

        return $bid;
    }

    /**
     * Delete a ID from the database
     *
     * @return bool
     */
    public function delete()
    {
        $sql = sprintf("DELETE FROM %s WHERE bid = %u", $this->db->prefix('newblocks'), $this->getVar('bid'));
        if (!$this->db->query($sql)) {
            return false;
        }
        $sql = sprintf("DELETE FROM %s WHERE gperm_name = 'block_read' AND gperm_itemid = %u AND gperm_modid = 1", $this->db->prefix('group_permission'), $this->getVar('bid'));
        $this->db->query($sql);
        $sql = sprintf("DELETE FROM %s WHERE block_id = %u", $this->db->prefix('block_module_link'), $this->getVar('bid'));
        $this->db->query($sql);

        return true;
    }

    /**
     * do stripslashes/htmlspecialchars according to the needed output
     *
     * @param string $format output use: 's' for Show and 'e' for Edit
     * @param string $c_type type of block content
     *
     * @returns string
     */
    public function getContent($format = 's', $c_type = 't')
    {
        switch ($format) {
            case 's':
                // check the type of content
                // H : custom HTML block
                // P : custom PHP block
                // S : use text sanitizater (smilies enabled)
                // T : use text sanitizater (smilies disabled)
                if ($c_type === 'H') {
                    return str_replace('{X_SITEURL}', XOOPS_URL . '/', $this->getVar('content', 'n'));
                } elseif ($c_type === 'P') {
                    ob_start();
                    echo eval($this->getVar('content', 'n'));
                    $content = ob_get_contents();
                    ob_end_clean();

                    return str_replace('{X_SITEURL}', XOOPS_URL . '/', $content);
                } elseif ($c_type === 'S') {
                    $myts    = MyTextSanitizer::getInstance();
                    $content = str_replace('{X_SITEURL}', XOOPS_URL . '/', $this->getVar('content', 'n'));

                    return $myts->displayTarea($content, 1, 1);
                } else {
                    $myts    = MyTextSanitizer::getInstance();
                    $content = str_replace('{X_SITEURL}', XOOPS_URL . '/', $this->getVar('content', 'n'));

                    return $myts->displayTarea($content, 1, 0);
                }
                break;
            case 'e':
                return $this->getVar('content', 'e');
                break;
            default:
                return $this->getVar('content', 'n');
                break;
        }
    }

    /**
     * Build Block
     *
     * @return unknown
     */
    public function buildBlock()
    {
        global $xoopsConfig, $xoopsOption, $xoTheme;
        $block = array();
        if (!$this->isCustom()) {
            // get block display function
            $show_func = $this->getVar('show_func');
            if (!$show_func) {
                return false;
            }
            if (!file_exists($func_file = $GLOBALS['xoops']->path('modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file')))) {
                return false;
            }
            // must get lang files b4 including the file
            // some modules require it for code that is outside the function
            xoops_loadLanguage('blocks', $this->getVar('dirname'));
            include_once $func_file;

            if (function_exists($show_func)) {
                // execute the function
                $options = explode('|', $this->getVar('options'));
                $block   = $show_func($options);
                if (!$block) {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            // it is a custom block, so just return the contents
            $block['content'] = $this->getContent('s', $this->getVar('c_type'));
            if (empty($block['content'])) {
                return false;
            }
        }

        return $block;
    }

    /*
    * Aligns the content of a block
    * If position is 0, content in DB is positioned
    * before the original content
    * If position is 1, content in DB is positioned
    * after the original content
    */
    /**
     * @param        $position
     * @param string $content
     * @param string $contentdb
     *
     * @return string
     */
    public function buildContent($position, $content = "", $contentdb = "")
    {
        if ($position == 0) {
            $ret = $contentdb . $content;
        } elseif ($position == 1) {
            $ret = $content . $contentdb;
        }

        return $ret;
    }

    /**
     * Enter description here...
     *
     * @param  string $originaltitle
     * @param  string $newtitle
     * @return string title
     */
    public function buildTitle($originaltitle, $newtitle = '')
    {
        $ret = $originaltitle;
        if ($newtitle != '') {
            $ret = $newtitle;
        }

        return $ret;
    }

    /**
     * XoopsBlock::isCustom()
     *
     * @return bool
     */
    public function isCustom()
    {
        return in_array($this->getVar('block_type'), array(
            'C',
            'E'));
    }

    /**
     * XoopsBlock::getOptions()
     *
     * @return bool
     */
    public function getOptions()
    {
        global $xoopsConfig;
        if (!$this->isCustom()) {
            $edit_func = $this->getVar('edit_func');
            if (!$edit_func) {
                return false;
            }
            if (file_exists($GLOBALS['xoops']->path('modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file')))) {
                if (file_exists($file = $GLOBALS['xoops']->path('modules/' . $this->getVar('dirname') . '/language/' . $xoopsConfig['language'] . '/blocks.php'))) {
                    include_once $file;
                } elseif (file_exists($file = $GLOBALS['xoops']->path('modules/' . $this->getVar('dirname') . '/language/english/blocks.php'))) {
                    include_once $file;
                }
                include_once $GLOBALS['xoops']->path('modules/' . $this->getVar('dirname') . '/blocks/' . $this->getVar('func_file'));
                $options   = explode("|", $this->getVar("options"));
                $edit_form = $edit_func($options);
                if (!$edit_form) {
                    return false;
                }

                return $edit_form;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * get all the blocks that match the supplied parameters
     * @param        $groupid  groupid (can be an array)
     * @param bool   $asobject
     * @param        $side     0: sideblock - left
     *                         1: sideblock - right
     *                         2: sideblock - left and right
     *                         3: centerblock - left
     *                         4: centerblock - right
     *                         5: centerblock - center
     *                         6: centerblock - left, right, center
     * @param        $visible  0: not visible 1: visible
     * @param string $orderby  order of the blocks
     * @param int    $isactive
     * @returns array of block objects
     */
    public static function getAllBlocksByGroup($groupid, $asobject = true, $side = null, $visible = null, $orderby = "b.weight,b.bid", $isactive = 1)
    {
        $db  = XoopsDatabaseFactory::getDatabaseConnection();
        $ret = array();
        $sql = 'SELECT b.* ';
        if (!$asobject) {
            $sql = 'SELECT b.bid ';
        }
        $sql .= "FROM " . $db->prefix("newblocks") . " b LEFT JOIN " . $db->prefix("group_permission") . " l ON l.gperm_itemid=b.bid WHERE gperm_name = 'block_read' AND gperm_modid = 1";
        if (is_array($groupid)) {
            $sql .= " AND (l.gperm_groupid=" . $groupid[0] . "";
            $size = count($groupid);
            if ($size > 1) {
                for ($i = 1; $i < $size; ++$i) {
                    $sql .= " OR l.gperm_groupid=" . $groupid[$i] . "";
                }
            }
            $sql .= ")";
        } else {
            $sql .= " AND l.gperm_groupid=" . $groupid . "";
        }
        $sql .= " AND b.isactive=" . $isactive;
        if (isset($side)) {
            // get both sides in sidebox? (some themes need this)
            if ($side == XOOPS_SIDEBLOCK_BOTH) {
                $side = "(b.side=0 OR b.side=1)";
            } elseif ($side == XOOPS_CENTERBLOCK_ALL) {
                $side = "(b.side=3 OR b.side=4 OR b.side=5 OR b.side=7 OR b.side=8 OR b.side=9 )";
            } elseif ($side == XOOPS_FOOTERBLOCK_ALL) {
                $side = "(b.side=10 OR b.side=11 OR b.side=12 )";
            } else {
                $side = "b.side=" . $side;
            }
            $sql .= " AND " . $side;
        }
        if (isset($visible)) {
            $sql .= " AND b.visible=$visible";
        }
        $sql .= " ORDER BY $orderby";
        $result = $db->query($sql);
        $added  = array();
        while ($myrow = $db->fetchArray($result)) {
            if (!in_array($myrow['bid'], $added)) {
                if (!$asobject) {
                    $ret[] = $myrow['bid'];
                } else {
                    $ret[] = new XoopsBlock($myrow);
                }
                $added[] = $myrow['bid'];
            }
        }

        return $ret;
    }

    /**
     * XoopsBlock::getAllBlocks()
     *
     * @param  string  $rettype
     * @param  mixed   $side
     * @param  mixed   $visible
     * @param  string  $orderby
     * @param  integer $isactive
     * @return array
     */
    public function getAllBlocks($rettype = "object", $side = null, $visible = null, $orderby = "side,weight,bid", $isactive = 1)
    {
        $db          = XoopsDatabaseFactory::getDatabaseConnection();
        $ret         = array();
        $where_query = " WHERE isactive=" . $isactive;
        if (isset($side)) {
            // get both sides in sidebox? (some themes need this)
            if ($side == XOOPS_SIDEBLOCK_BOTH) {
                $side = "(side=0 OR side=1)";
            } elseif ($side == XOOPS_CENTERBLOCK_ALL) {
                $side = "(side=3 OR side=4 OR side=5 OR side=7 OR side=8 OR side=9)";
            } elseif ($side == XOOPS_FOOTERBLOCK_ALL) {
                $side = "(side=10 OR side=11 OR side=12)";
            } else {
                $side = "side=" . $side;
            }
            $where_query .= " AND " . $side;
        }
        if (isset($visible)) {
            $where_query .= " AND visible=." . $visible;
        }
        $where_query .= " ORDER BY " . $orderby;
        switch ($rettype) {
            case "object":
                $sql    = "SELECT * FROM " . $db->prefix("newblocks") . "" . $where_query;
                $result = $db->query($sql);
                while ($myrow = $db->fetchArray($result)) {
                    $ret[] = new XoopsBlock($myrow);
                }
                break;
            case "list":
                $sql    = "SELECT * FROM " . $db->prefix("newblocks") . "" . $where_query;
                $result = $db->query($sql);
                while ($myrow = $db->fetchArray($result)) {
                    $block                      = new XoopsBlock($myrow);
                    $title                      = $block->getVar("title");
                    $title                      = empty($title) ? $block->getVar("name") : $title;
                    $ret[$block->getVar("bid")] = $title;
                }
                break;
            case "id":
                $sql    = "SELECT bid FROM " . $db->prefix("newblocks") . "" . $where_query;
                $result = $db->query($sql);
                while ($myrow = $db->fetchArray($result)) {
                    $ret[] = $myrow['bid'];
                }
                break;
        }

        //echo $sql;
        return $ret;
    }

    /**
     * XoopsBlock::getByModule()
     *
     * @param  mixed $moduleid
     * @param  mixed $asobject
     * @return array
     */
    public static function getByModule($moduleid, $asobject = true)
    {
        $moduleid = (int)($moduleid);
        $db       = XoopsDatabaseFactory::getDatabaseConnection();
        if ($asobject == true) {
            $sql = $sql = "SELECT * FROM " . $db->prefix("newblocks") . " WHERE mid=" . $moduleid;
        } else {
            $sql = "SELECT bid FROM " . $db->prefix("newblocks") . " WHERE mid=" . $moduleid;
        }
        $result = $db->query($sql);
        $ret    = array();
        while ($myrow = $db->fetchArray($result)) {
            if ($asobject) {
                $ret[] = new XoopsBlock($myrow);
            } else {
                $ret[] = $myrow['bid'];
            }
        }

        return $ret;
    }

    /**
     * XoopsBlock::getAllByGroupModule()
     *
     * @param  mixed   $groupid
     * @param  integer $module_id
     * @param  mixed   $toponlyblock
     * @param  mixed   $visible
     * @param  string  $orderby
     * @param  integer $isactive
     * @return array
     */
    public function getAllByGroupModule($groupid, $module_id = 0, $toponlyblock = false, $visible = null, $orderby = 'b.weight, m.block_id', $isactive = 1)
    {
        $isactive = (int)($isactive);
        $db       = XoopsDatabaseFactory::getDatabaseConnection();
        $ret      = array();
        if (isset($groupid)) {
            $sql = "SELECT DISTINCT gperm_itemid FROM " . $db->prefix('group_permission') . " WHERE gperm_name = 'block_read' AND gperm_modid = 1";
            if (is_array($groupid)) {
                $sql .= ' AND gperm_groupid IN (' . implode(',', $groupid) . ')';
            } else {
                if ((int)($groupid) > 0) {
                    $sql .= ' AND gperm_groupid=' . (int)($groupid);
                }
            }
            $result   = $db->query($sql);
            $blockids = array();
            while ($myrow = $db->fetchArray($result)) {
                $blockids[] = $myrow['gperm_itemid'];
            }
            if (empty($blockids)) {
                return $blockids;
            }
        }
        $sql = 'SELECT b.* FROM ' . $db->prefix('newblocks') . ' b, ' . $db->prefix('block_module_link') . ' m WHERE m.block_id=b.bid';
        $sql .= ' AND b.isactive=' . $isactive;
        if (isset($visible)) {
            $sql .= ' AND b.visible=' . (int)($visible);
        }
        if (!isset($module_id)) {
        } elseif (!empty($module_id)) {
            $sql .= ' AND m.module_id IN (0,' . (int)($module_id);
            if ($toponlyblock) {
                $sql .= ',-1';
            }
            $sql .= ')';
        } else {
            if ($toponlyblock) {
                $sql .= ' AND m.module_id IN (0,-1)';
            } else {
                $sql .= ' AND m.module_id=0';
            }
        }
        if (!empty($blockids)) {
            $sql .= ' AND b.bid IN (' . implode(',', $blockids) . ')';
        }
        $sql .= ' ORDER BY ' . $orderby;
        $result = $db->query($sql);
        while ($myrow = $db->fetchArray($result)) {
            $block              = new XoopsBlock($myrow);
            $ret[$myrow['bid']] = &$block;
            unset($block);
        }

        return $ret;
    }

    /**
     * XoopsBlock::getNonGroupedBlocks()
     *
     * @param  integer $module_id
     * @param  mixed   $toponlyblock
     * @param  mixed   $visible
     * @param  string  $orderby
     * @param  integer $isactive
     * @return array
     */
    public function getNonGroupedBlocks($module_id = 0, $toponlyblock = false, $visible = null, $orderby = 'b.weight, m.block_id', $isactive = 1)
    {
        $db   = XoopsDatabaseFactory::getDatabaseConnection();
        $ret  = array();
        $bids = array();
        $sql  = "SELECT DISTINCT(bid) from " . $db->prefix('newblocks');
        if ($result = $db->query($sql)) {
            while ($myrow = $db->fetchArray($result)) {
                $bids[] = $myrow['bid'];
            }
        }
        $sql     = "SELECT DISTINCT(p.gperm_itemid) from " . $db->prefix('group_permission') . " p, " . $db->prefix('groups') . " g WHERE g.groupid=p.gperm_groupid AND p.gperm_name='block_read'";
        $grouped = array();
        if ($result = $db->query($sql)) {
            while ($myrow = $db->fetchArray($result)) {
                $grouped[] = $myrow['gperm_itemid'];
            }
        }
        $non_grouped = array_diff($bids, $grouped);
        if (!empty($non_grouped)) {
            $sql = 'SELECT b.* FROM ' . $db->prefix('newblocks') . ' b, ' . $db->prefix('block_module_link') . ' m WHERE m.block_id=b.bid';
            $sql .= ' AND b.isactive=' . (int)($isactive);
            if (isset($visible)) {
                $sql .= ' AND b.visible=' . (int)($visible);
            }
            if (!isset($module_id)) {
            } elseif (!empty($module_id)) {
                $sql .= ' AND m.module_id IN (0,' . (int)($module_id);
                if ($toponlyblock) {
                    $sql .= ',-1';
                }
                $sql .= ')';
            } else {
                if ($toponlyblock) {
                    $sql .= ' AND m.module_id IN (0,-1)';
                } else {
                    $sql .= ' AND m.module_id=0';
                }
            }
            $sql .= ' AND b.bid IN (' . implode(',', $non_grouped) . ')';
            $sql .= ' ORDER BY ' . $orderby;
            $result = $db->query($sql);
            while ($myrow = $db->fetchArray($result)) {
                $block              = new XoopsBlock($myrow);
                $ret[$myrow['bid']] =& $block;
                unset($block);
            }
        }

        return $ret;
    }

    /**
     * XoopsBlock::countSimilarBlocks()
     *
     * @param  mixed $moduleId
     * @param  mixed $funcNum
     * @param  mixed $showFunc
     * @return int
     */
    public function countSimilarBlocks($moduleId, $funcNum, $showFunc = null)
    {
        $funcNum  = (int)($funcNum);
        $moduleId = (int)($moduleId);
        if ($funcNum < 1 || $moduleId < 1) {
            // invalid query
            return 0;
        }
        $db = XoopsDatabaseFactory::getDatabaseConnection();
        if (isset($showFunc)) {
            // showFunc is set for more strict comparison
            $sql = sprintf("SELECT COUNT(*) FROM %s WHERE mid = %d AND func_num = %d AND show_func = %s", $db->prefix('newblocks'), $moduleId, $funcNum, $db->quoteString(trim($showFunc)));
        } else {
            $sql = sprintf("SELECT COUNT(*) FROM %s WHERE mid = %d AND func_num = %d", $db->prefix('newblocks'), $moduleId, $funcNum);
        }
        if (!$result = $db->query($sql)) {
            return 0;
        }
        list($count) = $db->fetchRow($result);

        return $count;
    }
}
