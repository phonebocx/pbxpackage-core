<?php


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
  $str .= "<li>Debug tools go here</li>";
  $str .= "</ul></p>";
  return $str;
}

function coredebug_iconname()
{
  return "bi-pc-display-horizontal";
}

function coredebug_header()
{
  return "System Tools and Information";
}
