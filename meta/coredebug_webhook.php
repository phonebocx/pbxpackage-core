<?php

use PhoneBocx\WebUI\DebugTools;

function coredebug_sidebarname()
{
  return "Debug Tools";
}

function coredebug_sidebarclasxs()
{
  return 'd-none debugbutton';
}

function coredebug_divcontent()
{
  $str = "<p><ul>";
  $entries = DebugTools::getToolList();
  foreach ($entries as $name => $row) {
    $str .= "<li>" . DebugTools::getToolHtml($name);
  }
  $str .= "</ul></p>";
  return $str;
}

function coredebug_iconname()
{
  return "bi-pc-display-horizontal";
}

function coredebug_header()
{
  return "System Tools";
}
