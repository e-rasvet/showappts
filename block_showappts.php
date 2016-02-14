<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Newblock block caps.
 *
 * @package    block_newblock
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_showappts extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_showappts');
    }

    function get_content() {
        global $CFG, $OUTPUT, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        // user/index.php expect course context, so get one if page has module context.
        $currentcontext = $this->page->context->get_course_context(false);

        if (! empty($this->config->text)) {
            $this->content->text = $this->config->text;
        }

        $this->content = '';
        if (empty($currentcontext)) {
            return $this->content;
        }
        if ($this->page->course->id == SITEID) {
            $this->context->text .= "site context";
        }

        if (! empty($this->config->text)) {
            $this->content->text .= $this->config->text;
        }
        
        $groupshtml    = "";
        $teachershtml  = "";
        $shedulershtml = "";
        if ($groupsarr = $DB->get_records("groups", array("courseid"=>$this->page->course->id))){
          foreach($groupsarr as $groupsarr_)
            $groupshtml .= '<option value="'.$groupsarr_->id.'">'.$groupsarr_->name.'</option>';
        }
        
        if ($teachers = $DB->get_records_sql("SELECT
u.id AS userid, c.id AS courseid, c.fullname, u.username, u.firstname, u.lastname, u.email

FROM 
mdl_role_assignments ra 
JOIN mdl_user u ON u.id = ra.userid
JOIN mdl_role r ON r.id = ra.roleid
JOIN mdl_context cxt ON cxt.id = ra.contextid
JOIN mdl_course c ON c.id = cxt.instanceid

WHERE ra.userid = u.id

AND ra.contextid = cxt.id
AND cxt.contextlevel =50
AND cxt.instanceid = ".$this->page->course->id."
AND r.id = 4

ORDER BY c.fullname")){
          foreach($teachers as $teacher)
            $teachershtml .= '<option value="'.$teacher->userid.'">'.$teacher->firstname.'</option>';
        }
        
        
        
        
        $accessarray = array();
        
        if ($access = $DB->get_records_sql("SELECT ra.userid FROM 
mdl_role_assignments ra , mdl_role r
WHERE (r.archetype = 'manager' OR r.archetype = 'coursecreator' OR r.archetype = 'editingteacher' OR r.archetype = 'teacher') AND ra.roleid = r.id")){
          foreach($access as $acces) {
            $accessarray[] = $acces->userid;
          }
        }
        
        
        if(!in_array($USER->id, $accessarray)) 
          return false;
          
        
        if ($shedulers = $DB->get_records("scheduler", array("course"=>$this->page->course->id))){
          foreach($shedulers as $sheduler)
            $shedulershtml .= '<option value="'.$sheduler->id.'">'.$sheduler->name.'</option>';
        }
        
        
        
        $this->content->text = '<div>
        <form action="'.$CFG->wwwroot.'/blocks/showappts/export.php?cid='.$this->page->course->id.'" method="post" target="_blank">
        <div style="white-space: nowrap;">Class: <select name="gid"><option value="0">All groups</option>'.$groupshtml.'</select></div>
        <div style="white-space: nowrap;">Partner: <select name="tid"><option value="0">All partners</option>'.$teachershtml.'</select></div>
        <div style="white-space: nowrap;">Scheduler: <select name="shid"><option value="0">All schedulers</option>'.$shedulershtml.'</select></div>
        <div>
          <div style="float:left;width:80px;"><b>Format</b></div>
          <div style="float:left;">
            <input type="radio" name="format" value="excel" id="showappts_format_1" checked="checked"> <label for="showappts_format_1">excel</label><br />
            <input type="radio" name="format" value="txt" id="showappts_format_2"> <label for="showappts_format_2">txt</label><br />
          </div>
        </div>
        <div><input type="submit" name="" value="Report" style="margin: 10px;padding: 4px;" /></div>
        </form>
        </div>';

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('all' => false,
                     'site' => true,
                     'site-index' => true,
                     'course-view' => true, 
                     'course-view-social' => false,
                     'mod' => true, 
                     'mod-quiz' => false);
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}

    public function cron() {
            mtrace( "Hey, my cron script is running" );
             
                 // do something
                  
                      return true;
    }
}
