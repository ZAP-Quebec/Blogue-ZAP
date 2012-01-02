<?php
/*  Copyright 2010  Josh Fowler (http://josh-fowler.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$current_error_reporting = error_reporting();
error_reporting ($current_error_reporting & ~E_STRICT); 

if (!function_exists('add_action'))
{
  require_once("../../../../wp-config.php");
}
?>

jQuery(document).ready(function() 
{
  jQuery("#toggle-base-stats").click(function(event) 
  {
    toggle_stat_display("base-stats");
    return false; 
  });

  jQuery("#toggle-goal-stats").click(function(event) 
  {
    toggle_stat_display("goal-stats");
    return false; 
  });

  jQuery("#toggle-extended-stats").click(function(event) 
  {
    toggle_stat_display("extended-stats");
    return false; 
  });
});

function toggle_stat_display(name)
{
  var link = jQuery("#" + name);

  var gadsack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" );    
  gadsack.execute = 1;
  gadsack.method = 'POST';
  gadsack.setVar( "action", "gad_set_preference" );
  gadsack.setVar( "pi", name );
  gadsack.setVar( "pv", link.css('display') == 'none' ? "hide" : "show" );
  gadsack.encVar( "cookie", document.cookie, false );
  gadsack.onError = function() { alert('Could not save preference.' )};
  gadsack.runAJAX();

  if(link.css('display') == 'none')
  {
    link.show();
    jQuery("#toggle-" + name).html("(hide)");
  }
  else
  {
    link.hide();
    jQuery("#toggle-" + name).html("(show)");
  }
}

<?php
error_reporting($current_error_reporting); 
?>