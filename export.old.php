<?php

/**
 * Export attendance sessions
 *
 * @package    mod
 * @subpackage attforblock
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 

require_once(dirname(__FILE__).'/../../config.php');
require_once("$CFG->libdir/excellib.class.php");



$cid            = required_param('cid', PARAM_INT);
$gid            = optional_param('gid', 0, PARAM_INT);
$tid            = optional_param('tid', 0, PARAM_INT);
$shid           = optional_param('shid', 0, PARAM_INT);
$format         = optional_param('format', 'excel', PARAM_TEXT);

$course         = $DB->get_record('course', array('id' => $cid), '*', MUST_EXIST);


//$groups = $DB->get_records_sql("SELECT u.id, g.name, u.username, u.firstname, u.lastname FROM {groups} g, {groups_members} gm, {user} u WHERE g.courseid =12 AND gm.groupid = g.id AND gm.userid = u.id");


$filename  = '';

if ($tid != 0) {
  $user = $DB->get_record("user", array("id"=>$tid));
  $filename .= $user->firstname."_";
} else {
  $filename .= "AllPartners_";
}

if ($shid != 0) {
  $scheduler = $DB->get_record("scheduler", array("id"=>$shid));
  $filename .= $scheduler->name."_";
}

$filename .= date("Ymd",time());
$filename .= ".xls";

$workbook = new MoodleExcelWorkbook("-");

/// Sending HTTP headers
$workbook->send($filename);
/// Creating the first worksheet
$myxls =& $workbook->add_worksheet('Show appointments');
/// format types
$formatbc =& $workbook->add_format();
$formatbc->set_bold(1);


$myxls->write(0, 0, 'Group', $formatbc);
$myxls->write(0, 1, 'Partner', $formatbc);
$myxls->write(0, 2, 'Given name', $formatbc);
$myxls->write(0, 3, 'Family name', $formatbc);
$myxls->write(0, 4, 'Username', $formatbc);
$myxls->write(0, 5, 'Date/Time (partner)', $formatbc);
$myxls->write(0, 6, 'Date/Time (student)', $formatbc);
$myxls->write(0, 7, 'Scheduler', $formatbc);
$myxls->write(0, 8, 'Comment', $formatbc);

$data        = array();
$datareport  = array();
$datareport2 = array();
$users       = array();

$appointmentarray  = array();
$shedulerdataarray = array();


if ($shid == 0)
  $shedulerarray = array();
else
  $shedulerarray = array("id"=>$shid);
  

if($shedulers = $DB->get_records("scheduler_slots", $shedulerarray)){
  foreach($shedulers as $sheduler){
    if (empty($appointmentarray[$sheduler->id]))
      $appointmentarray[$sheduler->id]  = $DB->get_record("scheduler_appointment", array("slotid"=>$sheduler->id));
    
    if (!empty($appointmentarray[$sheduler->id]->id)) {
    
      if (empty($shedulerdataarray[$sheduler->schedulerid]))
        $shedulerdataarray[$sheduler->schedulerid] = $DB->get_record("scheduler", array("id"=>$sheduler->schedulerid));
      
      if (empty($users[$sheduler->teacherid]))
        $users[$sheduler->teacherid] = $DB->get_record("user", array("id"=>$sheduler->teacherid));

      if (empty($users[$appointment->studentid])) {
        $users[$appointmentarray[$sheduler->id]->studentid] = $DB->get_record("user", array("id"=>$appointmentarray[$sheduler->id]->studentid));
        if ($group = $DB->get_record_sql("SELECT g.name, u.username, u.firstname, u.lastname FROM {groups} g, {groups_members} gm, {user} u WHERE g.courseid =12 AND gm.groupid = g.id AND g.name NOT LIKE 'Make%' AND gm.userid = ?", array($appointmentarray[$sheduler->id]->studentid)))
          $users[$appointmentarray[$sheduler->id]->studentid]->group = $group->name;
        else
          $users[$appointmentarray[$sheduler->id]->studentid]->group = "--";
      }

      $active = true;
      
      if ($gid != 0) 
        if (!$DB->get_record("groups_members", array("userid"=>$sheduler->teacherid, "groupid"=>$gid)))
          $active = false;
          
      if ($tid != 0) 
        if ($sheduler->teacherid != $tid)
          $active = false;
          
      if ($shid != 0) 
        if ($shedulerdataarray[$sheduler->schedulerid]->id != $shid)
          $active = false;
          
      
      if ($active && $shedulerdataarray[$sheduler->schedulerid]->course == $cid) {
      
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Group"]               = $users[$appointmentarray[$sheduler->id]->studentid]->group;
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Partner"]             = $users[$sheduler->teacherid]->firstname;
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Given name"]          = $users[$appointmentarray[$sheduler->id]->studentid]->firstname;
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Family name"]         = $users[$appointmentarray[$sheduler->id]->studentid]->lastname;
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Username"]            = $users[$appointmentarray[$sheduler->id]->studentid]->username;
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Date/Time (partner)"] = userdate($sheduler->starttime, '%d %B %Y, %I:%M %p', $users[$sheduler->teacherid]->timezone); 
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Date/Time (student)"] = userdate($sheduler->starttime, '%d %B %Y, %I:%M %p', $users[$appointmentarray[$sheduler->id]->studentid]->timezone); 
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Scheduler"]           = strip_tags($shedulerdataarray[$sheduler->schedulerid]->name);
          $data[strip_tags($shedulerdataarray[$sheduler->schedulerid]->name)][$users[$sheduler->teacherid]->firstname][$users[$appointmentarray[$sheduler->id]->studentid]->username][$sheduler->id."_".$appointmentarray[$sheduler->id]->id]["Comment"]             = strip_tags($appointmentarray[$sheduler->id]->appointmentnote);

          if ($tid == 0) {
            if (!empty($appointmentarray[$sheduler->id]->appointmentnote)) {
              //$datareport[$sheduler->teacherid][$appointmentarray[$sheduler->id]->studentid]['total']        += 1;
              $datareport[$sheduler->teacherid]['total']        += 1;
              //$datareport2[$appointmentarray[$sheduler->id]->studentid][$sheduler->teacherid]['total']       += 1;
              $datareport2[$users[$appointmentarray[$sheduler->id]->studentid]->group][$appointmentarray[$sheduler->id]->studentid]['total']       += 1;
              
              if (stristr(strtolower($appointmentarray[$sheduler->id]->appointmentnote), "no shows") || stristr(strtolower($appointmentarray[$sheduler->id]->appointmentnote), "no show") || stristr(strtolower($appointmentarray[$sheduler->id]->appointmentnote), "noshow")) {
                //$datareport[$sheduler->teacherid][$appointmentarray[$sheduler->id]->studentid]['incorrect']  += 1;
                $datareport[$sheduler->teacherid]['incorrect']  += 1;
                //$datareport2[$appointmentarray[$sheduler->id]->studentid][$sheduler->teacherid]['incorrect'] += 1;
                $datareport2[$users[$appointmentarray[$sheduler->id]->studentid]->group][$appointmentarray[$sheduler->id]->studentid]['incorrect'] += 1;
              }
            }
          }

      }
      
    }
    
  }
}


//$myxls->setAutoFilter("A1:C9"); 


//-------------SORTING-----------------//
ksort($data);

foreach($data as $k => $v){
  ksort($v);
  $data[$k] = $v;
  foreach($v as $k2 => $v2){
    ksort($v2);
    $data[$k][$k2] = $v2;
  }
}

$data_tmp = array();

foreach($data as $k => $v){
  foreach($v as $k2 => $v2){
    foreach($v2 as $k3 => $v3){
      foreach($v3 as $k4 => $v4){
        $data_tmp[$k4] = $v4;
      }
    }
  }
}

$data = $data_tmp;


$datareport2_tmp = array();
ksort($datareport2);

foreach($datareport2 as $k => $v){
  foreach($v as $k2 => $v2){
    $datareport2_tmp[$k2] = $v2;
  }
}

$datareport2 = $datareport2_tmp;


//-------------SORTING----------END----//



if ($format == "excel") {

  $i = 0;
  $i++;
  $j = 0;

  foreach ($data as $row) {
    foreach ($row as $cell) {
      $myxls->write_string($i, $j++, $cell);
    }
    $i++;
    $j = 0;
  }
  
  
  if (count($datareport2) > 0) {
    $i+=2;
    $myxls->write($i, 0, '**Student summary**', $formatbc);
    $i++;
    $myxls->write($i, 0, 'Group', $formatbc);
    $myxls->write($i, 1, 'Student', $formatbc);
    $myxls->write($i, 2, 'Given name', $formatbc);
    $myxls->write($i, 3, 'Family name', $formatbc);
    $myxls->write($i, 4, 'Ok', $formatbc);
    $myxls->write($i, 5, 'No Show', $formatbc);
    $myxls->write($i, 6, 'Total', $formatbc);
    $i++;
    
    foreach($datareport2 as $stid => $v){
      //foreach($v as $tid => $d){
        $myxls->write_string($i, 0, $users[$stid]->group);
        $myxls->write_string($i, 1, $users[$stid]->username);
        $myxls->write_string($i, 2, $users[$stid]->firstname);
        $myxls->write_string($i, 3, $users[$stid]->lastname);
        
        if (is_numeric($v['total']) && is_numeric($v['incorrect']))
          $myxls->write_string($i, 4, ($v['total']-$v['incorrect']));
        else if (is_numeric($v['total']))
          $myxls->write_string($i, 4, $v['total']);
        else
          $myxls->write_string($i, 4, 0);
        
        $myxls->write_string($i, 5, $v['incorrect']);
        
        $myxls->write_string($i, 6, $v['total']);
      //}
      $i++;
    }
  }
  
  
  if (count($datareport) > 0) {

    $i+=2;
    $myxls->write($i, 0, '**Partner summary**', $formatbc);
    $i++;
    $myxls->write($i, 0, 'Partner', $formatbc);
    //$myxls->write($i, 1, 'Student', $formatbc);
    $myxls->write($i, 1, 'Ok', $formatbc);
    $myxls->write($i, 2, 'No Show', $formatbc);
    $myxls->write($i, 3, 'Total', $formatbc);
    $i++;
    
    
    foreach($datareport as $tid => $v){
      //foreach($v as $stid => $d){
        $myxls->write_string($i, 0, $users[$tid]->firstname);
        //$myxls->write_string($i, 1, $users[$stid]->username);
        //$myxls->write_string($i, 3, ($d['total']-$d['incorrect']));
        
        if (is_numeric($v['total']) && is_numeric($v['incorrect']))
          $myxls->write_string($i, 1, ($v['total']-$v['incorrect']));
        else if (is_numeric($v['total']))
          $myxls->write_string($i, 1, $v['total']);
        else
          $myxls->write_string($i, 1, 0);
        
        $myxls->write_string($i, 2, $v['incorrect']);
        
        $myxls->write_string($i, 3, $v['total']);
      //}
      $i++;
    }
  }
  
  
  $workbook->close();
  
  

} else {
  
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Demo</title>
<link rel="stylesheet" href="css/ex.css" type="text/css" />
<script type='text/javascript' src='http://yandex.st/jquery/1.7.1/jquery.min.js'></script>
<script type='text/javascript' src='js/stupidtable.min.js<?php echo "?".time(); ?>'></script>
<style type="text/css">
table#table-attempts {
  font-family: verdana,arial,sans-serif;
  font-size:11px;
  color:#333333;
  border-width: 1px;
  border-color: #999999;
  border-collapse: collapse;
}
table#table-attempts th {
  background:#fff url('img/cell-grey.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
  cursor: pointer;
}
table#table-attempts td {
  background:#fff url('img/cell-blue.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
}


table#table-attempts-1 {
  font-family: verdana,arial,sans-serif;
  font-size:11px;
  color:#333333;
  border-width: 1px;
  border-color: #999999;
  border-collapse: collapse;
}
table#table-attempts-1 th {
  background:#fff url('img/cell-grey.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
  cursor: pointer;
}
table#table-attempts-1 td {
  background:#fff url('img/cell-blue.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
}


table#table-attempts-2 {
  font-family: verdana,arial,sans-serif;
  font-size:11px;
  color:#333333;
  border-width: 1px;
  border-color: #999999;
  border-collapse: collapse;
}
table#table-attempts-2 th {
  background:#fff url('img/cell-grey.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
  cursor: pointer;
}
table#table-attempts-2 td {
  background:#fff url('img/cell-blue.jpg') 0 bottom repeat-x;
  border-width: 1px;
  padding: 8px;
  border-style: solid;
  border-color: #999999;
}

</style>
</head>
<body>

    <div class="student-table-box">
      <table border="1" id="table-attempts">
        <thead>
          <tr>
            <th  data-sort="string">Group</th>
            <th  data-sort="string">Partner</th>
            <th  data-sort="string">Given name</th>
            <th  data-sort="string">Family name</th>
            <th  data-sort="string">Username</th>
            <th  data-sort="string">Date/Time (partner)</th>
            <th  data-sort="string">Date/Time (student)</th>
            <th  data-sort="string">Scheduler</th>
            <th>Comment</th>
          </tr>
        </thead>
        <tbody>
<?php
  
  foreach ($data as $row) {
    echo '<tr>';
    foreach ($row as $cell) {
      echo '<td>'.$cell.'</td>';
    }
    echo '</tr>';
  }
  
  echo '</tbody></table></div>';

  
  if (count($datareport2) > 0) {
    
    echo '    <div class="student-table-box">
    <br />
    <br />
    <div><h2>Student summary</h2></div>
      <table border="1" id="table-attempts-1">
        <thead>
          <tr>
            <th  data-sort="string">Group</th>
            <th  data-sort="string">Student</th>
            <th  data-sort="string">Given name</th>
            <th  data-sort="string">Family name</th>
            <th  data-sort="int">Ok</th>
            <th  data-sort="int">No Show</th>
            <th  data-sort="int">Total</th>
          </tr>
        </thead>
        <tbody>';

    foreach($datareport2 as $stid => $v){
      //foreach($v as $tid => $d){

        echo '<tr>';
        echo '<td>'.$users[$stid]->group.'</td>';
        echo '<td>'.$users[$stid]->username.'</td>';
        echo '<td>'.$users[$stid]->firstname.'</td>';
        echo '<td>'.$users[$stid]->lastname.'</td>';
        
        if (is_numeric($v['total']) && is_numeric($v['incorrect']))
          echo '<td>'.($v['total']-$v['incorrect']).'</td>';
        else if (is_numeric($v['total']))
          echo '<td>'.$v['total'].'</td>';
        else
          echo '<td>0</td>';
        
        echo '<td>'.$v['incorrect'].'</td>';
        
        echo '<td>'.$v['total'].'</td>';
        echo '</tr>';
      //}
    }
    
    echo '</tbody></table></div>';
    
  }
  


  if (count($datareport) > 0) {
  
    echo '    <div class="student-table-box">
    <br />
    <br />
      <div><h2>Partner summary</h2></div>
      <table border="1" id="table-attempts-2">
        <thead>
          <tr>
            <th  data-sort="string">Partner</th>
            <th  data-sort="int">Ok</th>
            <th  data-sort="int">No Show</th>
            <th  data-sort="int">Total</th>
          </tr>
        </thead>
        <tbody>';
    
    foreach($datareport as $tid => $v){
      //foreach($v as $stid => $d){
        echo '<tr>';
        echo '<td>'.$users[$tid]->firstname.'</td>';
        
        if (is_numeric($v['total']) && is_numeric($v['incorrect']))
          echo '<td>'.($v['total']-$v['incorrect']).'</td>';
        else if (is_numeric($v['total']))
          echo '<td>'.$v['total'].'</td>';
        else
          echo '<td>0</td>';
        
        echo '<td>'.$v['incorrect'].'</td>';
        
        echo '<td>'.$v['total'].'</td>';
        echo '</tr>';
      //}
    }
    
    echo '</tbody></table></div>';
    
    
  }
  


?>
    <script type="text/javascript">
    jQuery("#table-attempts").stupidtable();
    jQuery("#table-attempts-1").stupidtable();
    jQuery("#table-attempts-2").stupidtable();
    </script>
</body>
</html>
<?php
  
}


