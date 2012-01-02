<?php
/*
Plugin Name: Web Ninja Google Analytics
Plugin URI: http://josh-fowler.com/?page_id=70
Description: Enable Google Analytics on all of your pages instantly and add Google Analytics Stats to your Admin Dashboard and Posts. Can track external links, mailto links, and download links on your site. 
Version: 1.0.3
Author: Josh Fowler
Author URI: http://josh-fowler.com
*/

/*  Copyright 2010  Josh Fowler (http://josh-fowler.com/)

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

define('wbgaversion', '1.0.3', true);

if ( !function_exists('sys_get_temp_dir')) 
{
  function sys_get_temp_dir() 
  {
    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
    $tempfile = tempnam(uniqid(rand(),TRUE),'');
    if (file_exists($tempfile)) 
    {
      unlink($tempfile);
      return realpath(dirname($tempfile));
    }
  }
}

function is_assoc($array) 
{
  return (is_array($array) && 0 !== count(array_diff_key($array, array_keys(array_keys($array)))));
}

class SimpleFileCache
{
  function canCache()
  {
    $lh = SimpleFileCache::lock();
    $filename = sys_get_temp_dir() . '/gad_cache_' . md5('cachetest') . '.dat';
    if($f = @fopen($filename, "w"))
    {
      fclose($f);
      SimpleFileCache::unlock($lh);
      return true;
    }
    else
    {
      return false;
    }
  }

  function isExpired($key, $expire)
  {
    $filename = sys_get_temp_dir() . '/gad_cache_' . md5($key) . '.dat';
    if(file_exists($filename)) 
    {
      return time() - filemtime($filename) > $expire;
    }
    else
    {
      return true;
    }
  }

  function clearCache()
  {
    $lh = SimpleFileCache::lock();
    foreach(glob(sys_get_temp_dir() . '/gad_cache_*.dat') as $filename) 
    {
      unlink($filename);
    }
    SimpleFileCache::unlock($lh);
  }

  function cachePut($key, $value)
  {
    $lh = SimpleFileCache::lock();
    $filename = sys_get_temp_dir() . '/gad_cache_' . md5($key) . '.dat';
    if($f = @fopen($filename, "w"))
    {
      fwrite($f, serialize($value));
      fclose($f);
    }
    SimpleFileCache::unlock($lh);
  }

  function cacheGet($key)
  {
    $lh = SimpleFileCache::lock();
    $filename = sys_get_temp_dir() . '/gad_cache_' . md5($key) . '.dat';
    $result = '';
    if($f = @fopen($filename, "r"))
    {
      $data = fread($f, filesize($filename));
      $result = unserialize($data);
      fclose($f);
    }
    SimpleFileCache::unlock($lh);
    return $result;
  }

  function lock()
  {
    $filename = sys_get_temp_dir() . '/gad_lock.dat';
    if(file_exists($filename))
    {
      $file_size = filesize($filename);
      $fp = @fopen($filename, "r+");
    }
    else
    {
      $file_size = 0;
      $fp = @fopen($filename, "w+");
    }
    if (@flock($fp, LOCK_EX)) 
    {
      $last_ts = $file_size == 0 ? time() : fread($fp, $file_size);
      fseek($fp, 0);
      if($last_ts + 360 < time())
      {
        foreach(glob(sys_get_temp_dir() . '/gad_cache_*.dat') as $filename) 
        {
          if( time() - filemtime($filename) > 360)
          {
            unlink($filename);
          }
        }
        fwrite($fp, time());
      }
      else if($file_size == 0)
      {
        fwrite($fp, $last_ts);
      }
    }
    return $fp;
  }

  function unlock($fp)
  {
    @flock($fp, LOCK_UN);
  }
}

class GALib
{
  var $auth;
  var $ids;

  var $base_url = 'https://www.google.com/analytics/feeds/';

  var $http_code;
  var $error_message;
  var $cache_timeout;

  function GALib($auth, $ids = '', $cache_timeout = 60)
  {
    $this->auth = $auth;
    $this->ids = $ids;
    $this->cache_timeout = $cache_timeout;
  }

  function setAuth($auth)
  {
    $this->auth = $auth;
  }

  function isError()
  {
    return $this->http_code != 200;
  }

  function isAuthError()
  {
    return $this->http_code == 401;
  }

  function isProfileAccessError()
  {
    return $this->http_code == 403;
  }

  function isRequestError()
  {
    return $this->http_code == 400;
  }

  function getErrorMessage()
  {
    return $this->error_message;
  }

  function account_query()
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $this->base_url . 'accounts/default');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: GoogleLogin auth=" . $this->auth));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $return = curl_exec($ch);

    $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($this->http_code != 200)
    {
      $this->error_message = $return;
      return false;
    }
    else
    {
      $this->error_message = '';
      $xml = new SimpleXMLElement($return);

      curl_close($ch);

      $vhash = array();
      foreach($xml->entry as $entry)
      {
        $value = (string)$entry->id;
        list($part1, $part2) = split('accounts/', $value);
        $vhash[$part2] = (string)$entry->title;
      }

      return $vhash;
    }
  }

  function simple_report_query($start_date, $end_date, $dimensions = '', $metrics = '', $sort = '', $filters = '')
  {
    $url  = $this->base_url . 'data';
    $url .= '?ids=' . $this->ids;
    $url .= $dimensions != '' ? ('&dimensions=' . $dimensions) : '';
    $url .= $metrics != '' ? ('&metrics=' . $metrics) : '';
    $url .= $sort != '' ? ('&sort=' . $sort) : '';
    $url .= $filters != '' ? ('&filters=' . urlencode($filters)) : '';
    $url .= '&start-date=' . $start_date;
    $url .= '&end-date=' .$end_date;

    if(!SimpleFileCache::isExpired($url, $this->cache_timeout))
    {
      $this->http_code = 200; // We never cache bad requests
      return SimpleFileCache::cacheGet($url);
    }
    else
    {
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: GoogleLogin auth=" . $this->auth));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $return = curl_exec($ch);

      $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if($this->http_code != 200)
      {
        $this->error_message = $return;
        return false;
      }
      else
      {
        $xml = simplexml_load_string($return);

        curl_close($ch);

        $return_values = array();
        foreach($xml->entry as $entry)
        {
          if($dimensions == '')
          {
            $dim_name = 'value';
          }
          else
          {
            $dimension = $entry->xpath('dxp:dimension');
            $dimension_attributes = $dimension[0]->attributes();
            $dim_name = (string)$dimension_attributes['value'];
          }

          $metric = $entry->xpath('dxp:metric');
          if(sizeof($metric) > 1)
          {
            foreach($metric as $single_metric)
            { 
              $metric_attributes = $single_metric->attributes();
              $return_values[$dim_name][(string)$metric_attributes['name']] = (string)$metric_attributes['value'];
            }
          }
          else
          {
            $metric_attributes = $metric[0]->attributes();
            $return_values[$dim_name] = (string)$metric_attributes['value'];
          }
        }

        SimpleFileCache::cachePut($url, $return_values);

        return $return_values;
      }
    }
  }

  function complex_report_query($start_date, $end_date, $dimensions = array(), $metrics = array(), $sort = array(), $filters = array())
  {
    $url  = $this->base_url . 'data';
    $url .= '?ids=' . $this->ids;
    $url .= sizeof($dimensions) > 0 ? ('&dimensions=' . join(array_reverse($dimensions), ',')) : '';
    $url .= sizeof($metrics) > 0 ? ('&metrics=' . join($metrics, ',')) : '';
    $url .= sizeof($sort) > 0 ? '&sort=' . join($sort, ',') : '';
    $url .= sizeof($filters) > 0 ? '&filters=' . urlencode(join($filters, ',')) : '';
    $url .= '&start-date=' . $start_date;
    $url .= '&end-date=' .$end_date;

    if(!SimpleFileCache::isExpired($url, $this->cache_timeout))
    {
      $this->http_code = 200; // We never cache bad requests
      return SimpleFileCache::cacheGet($url);
    }
    else
    {
      $ch = curl_init();

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: GoogleLogin auth=" . $this->auth));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

      $return = curl_exec($ch);

      $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      if($this->http_code != 200)
      {
        $this->error_message = $return;
        return false;
      }
      else
      {
        $xml = simplexml_load_string($return);

        curl_close($ch);

        $return_values = array();
        foreach($xml->entry as $entry)
        {
          $metrics = array();
          foreach($entry->xpath('dxp:metric') as $metric)
          {
            $metric_attributes = $metric->attributes();
            $metrics[(string)$metric_attributes['name']] = (string)$metric_attributes['value']; 
          }

          $last_dimension_var_name = null;
          foreach($entry->xpath('dxp:dimension') as $dimension)
          {
            $dimension_attributes = $dimension->attributes();

            $dimension_var_name = 'dimensions_' . strtr((string)$dimension_attributes['name'], ':', '_');
            $$dimension_var_name = array();

            if($last_dimension_var_name == null)
            {
              $$dimension_var_name = array('name' => (string)$dimension_attributes['name'],
                                           'value' => (string)$dimension_attributes['value'],
                                           'children' => $metrics); 
            }
            else
            {
              $$dimension_var_name = array('name' => (string)$dimension_attributes['name'],
                                           'value' => (string)$dimension_attributes['value'],
                                           'children' => $$last_dimension_var_name); 
            }
            $last_dimension_var_name = $dimension_var_name;
          }
          array_push($return_values, $$last_dimension_var_name);
        }

        SimpleFileCache::cachePut($url, $return_values);

        return $return_values;
      }
    }
  }

  function hour_pageviews_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:hour', 'ga:visits');
  }

  function daily_pageviews_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:date', 'ga:pageviews');
  }

  function weekly_pageviews_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:week', 'ga:pageviews');
  }

  function monthly_pageviews_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:month', 'ga:pageviews');
  }

  function total_visits_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, '', 'ga:visits');
  }

  function daily_uri_pageviews_for_date_period($partial_uri, $start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:date', 'ga:pageviews', '', 'ga:pagePath=~' . $partial_uri . '.*');
  }

  function total_uri_pageviews_for_date_period($partial_uri, $start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, '', 'ga:pageviews', '', 'ga:pagePath=~' . $partial_uri . '.*');
  }

  function total_pageviews_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, '', 'ga:pageviews');
  }

  function keywords_for_date_period($start_date, $end_date, $limit = 20)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:keyword', 'ga:visits', '-ga:visits', 'ga:visits>' . $limit);
  }

  function sources_for_date_period($start_date, $end_date, $limit = 20)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:source', 'ga:visits', '-ga:visits', 'ga:visits>' . $limit);
  }

  function pages_for_date_period($start_date, $end_date, $limit = 20)
  {
    return $this->complex_report_query($start_date, $end_date, array('ga:pagePath', 'ga:pageTitle'), array('ga:pageviews'), array('-ga:pageviews'), array('ga:pageviews>' . $limit));
  }

  function summary_by_partial_uri_for_date_period($partial_uri, $start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, 'ga:date', join(array('ga:pageviews', 'ga:exits', 'ga:uniquePageviews'), ','), 'ga:date', 'ga:pagePath=~' . $partial_uri . '.*');
  }

  function summary_for_date_period($start_date, $end_date)
  {
    return $this->simple_report_query($start_date, $end_date, '', join(array('ga:visits', 'ga:bounces', 'ga:entrances', 'ga:timeOnSite', 'ga:newVisits'), ','));
  }

  function goals_for_date_period($start_date, $end_date, $enabled_goals)
  {
    $goals = array();

    if($enabled_goals[0]) array_push($goals, 'ga:goal1Completions');
    if($enabled_goals[1]) array_push($goals, 'ga:goal2Completions');
    if($enabled_goals[2]) array_push($goals, 'ga:goal3Completions');
    if($enabled_goals[3]) array_push($goals, 'ga:goal4Completions');

    return $this->simple_report_query($start_date, $end_date, 'ga:date', join($goals, ','));
  }
}
class GAuthLib
{
  var $base_url = 'https://www.google.com/accounts/ClientLogin';
  var $client_name;
  var $http_code;
  var $response_hash;

  function GAuthLib($client_name)
  {
    $this->client_name = $client_name;
  }

  function authenticate($email, $password, $service, $login_token = '', $login_captcha = '')
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $this->base_url);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch, CURLOPT_POST, true);

    $post_data = array('accountType'=>'GOOGLE', 'Email'=>$email, 'Passwd'=>$password, 'service'=>$service, 'source'=>$this->client_name);
    if($login_token != '')
    {
      $post_data['logintoken'] = $login_token;
      $post_data['logincaptcha'] = $login_captcha;
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); 

    $return = curl_exec($ch);

    $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 

    $this->response_hash = array();
    foreach( explode("\n", $return) as $line )
    {
      if(trim($line) != "")
      {
        $pos = strpos($line, '=');
        if($pos !== false)
        {
          $this->response_hash[strtolower(substr($line, 0, $pos))] = substr($line, $pos+1);
        }
      }
    }

    curl_close($ch);
  }

  function isError()
  {
    return $this->http_code != 200;
  }

  function requiresCaptcha()
  {
    return $this->isError() && $this->response_hash['error'] == 'CaptchaRequired';
  }

  function getCaptchaImageURL()
  {
    return 'http://www.google.com/accounts/' . $this->response_hash['captchaurl'];
  }

  function getAuthToken()
  {
    return $this->response_hash['auth'];
  }

  function getErrorMessage()
  {
    switch($this->response_hash['error'])
    {
      case 'BadAuthentication': return 'The login request is for a username or password that is not recognized.';
      case 'NotVerified': return 'The account email address has not been verified. You will need to access your Google account directly to resolve this issue.';
      case 'TermsNotAgreed': return 'You have not agreed to Google terms. You will need access your Google account directly to resolve the issue.';
      case 'CaptchaRequired': return 'A CAPTCHA is required.';
      case 'Unknown': return 'Unknown error.';
      case 'AccountDeleted': return 'Your Google account has been deleted.';
      case 'AccountDisabled': return 'Your Google account has been disabled.';
      case 'ServiceDisabled': return 'Your Google access to the specified service has been disabled.';
      case 'ServiceUnavailable': return 'The service is not available; try again later.';
      default: return $this->response_hash['error'];
    }
  }

  function getRawErrorMessage()
  {
    return $this->response_hash['error'];
  }

  function getCaptchaToken()
  {
    return $this->response_hash['captchatoken'];
  }
}

class GADWidgetData
{
  var $auth_token;
  var $account_id;
  function GADWidgetData($auth_token = '', $account_id = '')
  {
    $wbga_options = get_option('webninja_ga_options'); 
    $this->auth_token = ($auth_token != '') ? $auth_token : $wbga_options['gad_auth_token'];
    $this->account_id = ($account_id != '') ? $account_id : $wbga_options['gad_account_id'];
  }

  function gad_pageviews_text($link_uri)
  {
    $ga = new GALib($this->auth_token, $this->account_id);

    $start_date = date('Y-m-d', time() - (60 * 60 * 24 * 30));
    $end_date = date('Y-m-d');

    $data = $ga->total_uri_pageviews_for_date_period($link_uri, $start_date, $end_date);
    $error_type = gad_request_error_type($ga);
    if($error_type == 'perm') return '';
    else if($error_type == 'retry') $data = $ga->total_uri_pageviews_for_date_period($link_uri, $start_date, $end_date);

    return $data['value'];
  }

  function gad_pageviews_sparkline($link_uri)
  {
    $ga = new GALib($this->auth_token, $this->account_id);

    $start_date = date('Y-m-d', time() - (60 * 60 * 24 * 30));
    $end_date = date('Y-m-d');

    $data = $ga->daily_uri_pageviews_for_date_period($link_uri, $start_date, $end_date);
    $error_type = gad_request_error_type($ga);
    if($error_type == 'perm') return '';
    else if($error_type == 'retry') $data = $ga->daily_uri_pageviews_for_date_period($link_uri, $start_date, $end_date);

    $minvalue = 999999999;
    $maxvalue = 0;
    $count = 0;
    foreach($data as $date => $value)
    {
      if($minvalue > $value['ga:pageviews'])
      {
        $minvalue = $value['ga:pageviews'];
      }
      if($maxvalue < $value['ga:pageviews'])
      {
        $maxvalue = $value['ga:pageviews'];
      }
      $cvals .= $value['ga:pageviews'] . ($count < sizeof($data)-1 ? "," : "");
      $count++;
    }

    return '<img width="90" height="30" src="http://chart.apis.google.com/chart?chs=90x30&cht=ls&chf=bg,s,FFFFFF00&chco=0077CC&chd=t:' . $cvals . '&chds=' . $minvalue . ',' . $maxvalue . '"/>';
  }
}

if (version_compare($wp_version, '2.8', '>=')) 
{

  class GADWidget extends WP_Widget 
  {
    function GADWidget() 
    {
      parent::WP_Widget(false, $name = 'Web Ninja GA Widget');
    }

    function widget($args, $instance) 
    {
      extract($args);
      echo $before_widget;

      $link_uri = substr($_SERVER["REQUEST_URI"], -20);

      echo '<div>';
	  $wbga_options = get_option('webninja_ga_options'); 
      switch($instance['data_type'])
      {
        case 'pageviews-sparkline':
            $data = new GADWidgetData($wbga_options['gad_auth_token'], $wbga_options['gad_account_id']);
            echo $data->gad_pageviews_sparkline($link_uri);
          break;
        case 'pageviews-text':
            $data = new GADWidgetData($wbga_options['gad_auth_token'], $wbga_options['gad_account_id']);
            echo $data->gad_pageviews_text($link_uri);
          break;
      }

      echo '</div>';

      echo $after_widget;
    }

    function update($new_instance, $old_instance) 
    {
      $old_instance['data_type'] = strip_tags($new_instance['data_type']);
      return $old_instance;
    }

    function form($instance) 
    {
      $field_id = $this->get_field_id('data_type');
      $field_name = $this->get_field_name('data_type');

      $widget_types = array('pageviews-sparkline' => 'Pageviews - Sparkline',
                            'pageviews-text' => 'Pageviews - Text');

?>
      <p>
        <label for="'. $field_id .'">
          Data Type: 
          <select id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>">
<?php
      foreach($widget_types as $key => $value)
      {
        $selected_value = esc_attr($instance['data_type']) == $key ? 'selected' : '';
        echo "<option value='$key' $selected_value>$value</option>";
      }
?>
          </select>
        </label>
      </p>
<?php
    }
  }

}

$wbga_options = get_option('webninja_ga_options'); 
$wbga_debug_enabled = $wbga_options['debug'];

if (version_compare($wp_version, '2.8', '>=')) 
{
  //add_action('widgets_init', create_function('', 'return register_widget("GADWidget");'));
}
add_action('admin_print_scripts', 'gad_admin_print_scripts');
function gad_admin_print_scripts() 
{
  wp_enqueue_script('gad_script', get_bloginfo('wpurl') . '/wp-content/plugins/web-ninja-google-analytics/js/gadmain.js.php', array('jquery', 'sack'), '1.0');
}    

add_action('wp_ajax_gad_set_preference', 'gad_ajax_set_preference' );
function gad_ajax_set_preference()
{
  global $current_user;
  get_currentuserinfo();

  switch($_POST['pi'])
  {
    case 'base-stats':
      update_usermeta($current_user->ID, 'gad_bs_toggle', $_POST['pv']);
    break;
    case 'goal-stats':
      update_usermeta($current_user->ID, 'gad_gs_toggle', $_POST['pv']);
    break;
    case 'extended-stats':
      update_usermeta($current_user->ID, 'gad_es_toggle', $_POST['pv']);
    break;
    default:
      die("alert('Unknown option.')");
  }

  die("");
}

function wbga_debug($message) {
	if ($wbga_debug_enabled) {
		global $wbga_debug;
		$wbga_debug .= "$message\n";
	}
}

function wbga_set_option($option_name, $option_value) {
	wbga_debug ("Start function wbga_set_option: $option_name, $option_value");
	$wbga_options = get_option('webninja_ga_options');
	$wbga_options[$option_name] = $option_value;
	update_option('webninja_ga_options', $wbga_options);
	wbga_debug ('End function wbga_set_option');
}

function wbga_get_option($option_name) {
	wbga_debug("Start function wbga_get_option: $option_name");
	$wbga_options = get_option('webninja_ga_options'); 
	wbga_debug('wbga_options: '.var_export($wbga_options,true));
	
	if (!$wbga_options || !array_key_exists($option_name, $wbga_options)) {
		wbga_debug('Making default options array');
		$wbga_default_options=array();
		$wbga_default_options['internal_domains']  = $_SERVER['SERVER_NAME'];
		if (preg_match('@www\.(.*)@i', $wbga_default_options['internal_domains'], $parts)>=1) {
		  $wbga_default_options['internal_domains'] .= ','.$parts[1];
		}
		$wbga_default_options['account_id']             = 'UA-XXXXXX-X';  
		$wbga_default_options['enable_tracker']         = true;  
		$wbga_default_options['track_adm_pages']        = false;  
		$wbga_default_options['ignore_users']           = true;  
		$wbga_default_options['max_user_level']         = 8;   
		$wbga_default_options['footer_hooked']          = false; // assume the worst
		$wbga_default_options['filter_content']         = true;  
		$wbga_default_options['filter_comments']        = true;  
		$wbga_default_options['filter_comment_authors'] = true;  
		$wbga_default_options['track_ext_links']        = true;  
		$wbga_default_options['prefix_ext_links']       = '/out/';  
		$wbga_default_options['track_files']            = true;  
		$wbga_default_options['prefix_file_links']      = '/download/';  
		$wbga_default_options['track_extensions']       = 'gif,jpg,jpeg,bmp,png,pdf,mp3,wav,phps,zip,gz,tar,rar,jar,exe,pps,ppt,xls,doc';  
		$wbga_default_options['track_mail_links']       = true;  
		$wbga_default_options['prefix_mail_links']      = '/mailto/';  
		$wbga_default_options['debug']                  = false;  
		$wbga_default_options['check_updates']          = true;  
		$wbga_default_options['version_sent']           = '';  
		$wbga_default_options['advanced_config']        = false;  
		wbga_debug('wbga_default_options: '.var_export($wbga_default_options,true));
		add_option('webninja_ga_options', $wbga_default_options, 
				   'Settings for Web Ninja Google Analytics plugin');
		$result = $wbga_default_options[$option_name];
	} else {
		$result = $wbga_options[$option_name];
	}
  
	wbga_debug("Ending function wbga_get_option: $option_name ($result)");
	return $result;
}

function wbga_check_updates($echo) {
  $crlf = "\r\n";
  $host = 'josh-fowler.com';
  $handle = fsockopen($host, 80, $error, $err_message, 3);
  if (!$handle) {
    if ($echo) {
      echo __('Unable to get latest version', 'wbga')." ($err_message)";
    }
  } else {
    $req = 'GET http://'.$host.'/version/wbga.php?v='.urlencode(wbgaversion)
             . '&site='.urlencode(get_option('siteurl')).'&email='.urlencode(get_option('admin_email')).' HTTP/1.0' . $crlf
             . 'Host: '.$host. $crlf
             . $crlf;
    fwrite($handle, $req);
    while(!feof($handle))
      $response .= fread($handle, 1024);
    fclose($handle);
    $splitter = $crlf.$crlf.'Latest version: ';
    $pos = strpos($response, $splitter);
    if ($pos === false) {
      if ($echo) {
        _e('Invalid response from server', 'wbga');
      }
    } else {
      $body = substr($response, $pos + strlen($splitter));
      if ($body==wbgaversion) {
        if ($echo) {
          echo __('You are running the latest version', 'wbga'). ' ('.wbgaversion.')';
        }
      } else {
        if ($echo) {
          _e ('You are running version', 'wbga');
          echo ' '.wbgaversion.'. ';
          echo '<br><strong><span style="font-size:135%;"><a target="_blank" href="http://josh-fowler.com/?page_id=70">';
          _e ('Version', 'wbga');
          echo " $body ";
          _e ('is available', 'wbga');
          echo '</a></span></strong><br>';
        }
      }
    }      
  }
}

function wbga_admin() {
  wbga_debug('Start function wbga_admin');
  if (function_exists('add_options_page')) {
    wbga_debug('Adding options page');
    add_options_page('Web Ninja Google Analytics', 
                     'Web Ninja GA', 
                     8, 
                     basename(__FILE__), 
                     'wbga_options');
  }
  wbga_debug('End function wbga_admin');
}

function wbga_options() {
  wbga_debug('Start function wbga_options');
  if (isset($_POST['advanced_options'])) {
    wbga_set_option('advanced_config', true);
  }
  if (isset($_POST['simple_options'])) {
    wbga_set_option('advanced_config', false);
  }
  if (isset($_POST['default_settings'])) {
    $wbga_factory_options = array();
    update_option('webninja_ga_options', $wbga_factory_options);
    ?><div class="updated"><p><strong><?php _e('Default settings set, remember to set GA Account ID', 'wbga')?></strong></p></div><?php
  }
  if (isset($_POST['info_update'])) {
    wbga_debug('Saving options: '.var_export($_POST, true));
    ?><div class="updated"><p><strong><?php 
    $wbga_options = get_option('webninja_ga_options');
    $wbga_options['account_id']             = $_POST['account_id'];
    $wbga_options['internal_domains']       = $_POST['internal_domains'];
    $wbga_options['max_user_level']         = $_POST['max_user_level'];
    $wbga_options['prefix_ext_links']       = $_POST['prefix_ext_links'];
    $wbga_options['prefix_mail_links']      = $_POST['prefix_mail_links'];
    $wbga_options['prefix_file_links']      = $_POST['prefix_file_links'];
    $wbga_options['track_extensions']       = $_POST['track_extensions'];
    $wbga_options['enable_tracker']         = ($_POST['enable_tracker']=="true"          ? true : false);
    $wbga_options['filter_content']         = ($_POST['filter_content']=="true"          ? true : false);
    $wbga_options['filter_comments']        = ($_POST['filter_comments']=="true"         ? true : false);
    $wbga_options['filter_comment_authors'] = ($_POST['filter_comment_authors']=="true"  ? true : false);
    $wbga_options['track_adm_pages']        = ($_POST['track_adm_pages']=="true"         ? true : false);
    $wbga_options['track_ext_links']        = ($_POST['track_ext_links']=="true"         ? true : false);
    $wbga_options['track_mail_links']       = ($_POST['track_mail_links']=="true"        ? true : false);
    $wbga_options['track_files']            = ($_POST['track_files']=="true"             ? true : false);
    $wbga_options['ignore_users']           = ($_POST['ignore_users']=="true"            ? true : false);
    $wbga_options['debug']                  = ($_POST['debug']=="true"                   ? true : false);
    $wbga_options['check_updates']          = ($_POST['check_updates']=="true"           ? true : false);
    update_option('webninja_ga_options', $wbga_options);
    
    if (wbga_get_option('track_adm_pages')) {
      add_action('admin_footer', 'wbga_adm_footer_track');
    } else {
      remove_action('admin_footer', 'wbga_adm_footer_track');
    }
    
    _e('Tracking options saved', 'wbga')
    ?></strong></p></div><?php
	} 
	wbga_debug('Showing options page with wbga options');
	?>
<div class=wrap style="width:820px">
    <h2>Web Ninja Google Analytics</h2>
    <?php if (wbga_get_option('check_updates')) { echo "<br /><strong>Update Check</strong>: "; wbga_check_updates(true); } ?>

<div style="float:right; width:290px; border:1px #DEDEDD dashed; background-color:#FEFAE7; padding:10px 10px 10px 10px">
<b>Description:</b> The Web Ninja Google Analytics Plugin is the one stop shop for all your Google Analytic needs. It not only allows you to add Google Analytics JavaScript to each page on your site without making any changes to your template, but it also adds an Admin Dashboard Widget with Analytic Stats. Plus, not only do you see the over all stats on the Admin Dashboard but you can see individual post and page stats in the Post and Pages Admin sections.<br />
<br />
<b>Homepage:</b> <a href="http://josh-fowler.com/?page_id=70" target="_blank">Web Ninja Google Analytics</a><br />
<Br />
<b>Support:</b> <a href="http://josh-fowler.com/forum/" target="_blank">Web Ninja Forums</a><br />
<br />
<b>Developed by:</b> <a href="http://josh-fowler.com/" target="_blank">Josh Fowler</a><br />
<br />
<b>Like the plugin? "Like" The Web Ninja!</b>
<iframe src="http://www.facebook.com/plugins/like.php?href=http%3A%2F%2Fwww.facebook.com%2Fpages%2FThe-Web-Ninja%2F160118787364131&amp;layout=standard&amp;show_faces=false&amp;width=375&amp;action=like&amp;colorscheme=light&amp;height=35" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:375px; height:35px;" allowTransparency="true"></iframe>
<br />
<b>Donate:</b> I spend a lot of time on the plugins I've written for WordPress. Donations are not required but any donation would be highly appreciated. Just enter the donation amount and click the "Donate Now" button below.<br />
<br />
<center><form class="gcheckout" method="POST" action="https://checkout.google.com/cws/v2/Merchant/462781349183533/checkoutForm" accept-charset="utf-8"> <input name="item_name_1" value="Web Ninja Google Analytics Donation" type="hidden"> <input name="item_description_1" value="Thanks for your donation. Every little bit helps!" type="hidden"> <input name="item_quantity_1" value="1" id="qty" type="hidden"> <label><b>Amount:</b> $</label><input name="item_price_1" value="" id="amt" type="text" size="10"> <input name="charset" type="hidden"> <br /><input id="submit" name="Google Checkout" alt="Fast checkout through Google" src="http://josh-fowler.com/images/donateNow.png" type="image"> </form></center><br />
<br />
<b>Thanks:</b> I wanted to say thanks for using my plugin and if you have any suggestions for new features head over to the Support Forum and just drop me a little note. You never know, it could be on the next version.
</div>
<div style="float:left; width:500px">
  <form method="post">
  <script language="javascript" type="text/javascript">
  var tooltip=function(){
 var id = 'tt';
 var top = 3;
 var left = 3;
 var maxw = 500;
 var speed = 10;
 var timer = 20;
 var endalpha = 95;
 var alpha = 0;
 var tt,t,c,b,h;
 var ie = document.all ? true : false;
 return{
  show:function(v,w){
   if(tt == null){
    tt = document.createElement('div');
    tt.setAttribute('id',id);
    t = document.createElement('div');
    t.setAttribute('id',id + 'top');
    c = document.createElement('div');
    c.setAttribute('id',id + 'cont');
    b = document.createElement('div');
    b.setAttribute('id',id + 'bot');
    tt.appendChild(t);
    tt.appendChild(c);
    tt.appendChild(b);
    document.body.appendChild(tt);
    tt.style.opacity = 0;
    tt.style.filter = 'alpha(opacity=0)';
    document.onmousemove = this.pos;
   }
   tt.style.display = 'block';
   c.innerHTML = v;
   tt.style.width = w ? w + 'px' : 'auto';
   if(!w && ie){
    t.style.display = 'none';
    b.style.display = 'none';
    tt.style.width = tt.offsetWidth;
    t.style.display = 'block';
    b.style.display = 'block';
   }
  if(tt.offsetWidth > maxw){tt.style.width = maxw + 'px'}
  h = parseInt(tt.offsetHeight) + top;
  clearInterval(tt.timer);
  tt.timer = setInterval(function(){tooltip.fade(1)},timer);
  },
  pos:function(e){
   var u = ie ? event.clientY + document.documentElement.scrollTop : e.pageY;
   var l = ie ? event.clientX + document.documentElement.scrollLeft : e.pageX;
   tt.style.top = (u - h) + 'px';
   tt.style.left = (l + left) + 'px';
  },
  fade:function(d){
   var a = alpha;
   if((a != endalpha && d == 1) || (a != 0 && d == -1)){
    var i = speed;
   if(endalpha - a < speed && d == 1){
    i = endalpha - a;
   }else if(alpha < speed && d == -1){
     i = a;
   }
   alpha = a + (i * d);
   tt.style.opacity = alpha * .01;
   tt.style.filter = 'alpha(opacity=' + alpha + ')';
  }else{
    clearInterval(tt.timer);
     if(d == -1){tt.style.display = 'none'}
  }
 },
 hide:function(){
  clearInterval(tt.timer);
   tt.timer = setInterval(function(){tooltip.fade(-1)},timer);
  }
 };
}();
</script>
<style type="text/css">
#tt {
 position:absolute;
 display:block;
 }
 #tttop {
 display:block;
 height:5px;
 margin-left:5px;
 overflow:hidden;
 }
 #ttcont {
 display:block;
 padding:2px 12px 3px 7px;
 margin-left:5px;
 background:#666;
 color:#fff;
 }
#ttbot {
display:block;
height:5px;
margin-left:5px;
overflow:hidden;
}
th {
	text-align:right;
}
</style>
    <fieldset class="options" name="tracking">
      <h3>Stat Tracking Settings</h3>
      <table width="500px" cellspacing="5" cellpadding="5" class="editform">
        <tr>
          <th nowrap valign="top" width="200px">Tracking Enabled: <span onmouseover="tooltip.show('Unchecking this will disable the stat tracking.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="enable_tracker" id="enable_tracker" value="true" <?php if (wbga_get_option('enable_tracker')) echo "checked"; ?> /></td>
		</tr>
        <tr>
          <th nowrap valign="top">GA Account ID: <span onmouseover="tooltip.show('Enter your Google Analytics account ID. Google Analytics supplies you with a snippet of JavaScript to put on your webpage. In this JavaScript you can see your account ID in a format like UA-999999-9. There is no need to actually include this JavaScript yourself on any page. That is all handled by Ultimate Google Analytics.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span> </th>
          <td ><input name="account_id" type="text" id="account_id" value="<?php echo wbga_get_option('account_id'); ?>" size="30" />
          </td>
        </tr>
        <tr>
          <th nowrap valign="top">Track admin pages: <span onmouseover="tooltip.show('Run Google Analytics tracking on admin pages.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="track_adm_pages" id="track_adm_pages" value="true" <?php if (wbga_get_option('track_adm_pages')) echo "checked"; ?> /></td>
		</tr>
        <tr>
          <th nowrap valign="top">Exclude logged on users: <span onmouseover="tooltip.show('Check this box and specify a user level to exclude those users of that level and above from Google Analytics tracking.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="ignore_users" id="ignore_users" value="true" <?php if (wbga_get_option('ignore_users')) echo "checked"; ?> />
            of level <input name="max_user_level" type="text" id="max_user_level" value="<?php echo wbga_get_option('max_user_level'); ?>" size="2" /> and above</td>
        </tr>
        <tr>
          <th nowrap valign="top">Enable debugging: <span onmouseover="tooltip.show('When enabled it write debug code to your page. This is help in any troubleshooting.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="debug" id="debug" value="true" <?php if (wbga_get_option('debug')) echo "checked"; ?> /></td>
		</tr>
        <tr>
          <th nowrap valign="top">Check for updates: <span onmouseover="tooltip.show('This will have Web Ninja Google Analytics check for updates.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="check_updates" id="check_updates" value="true" <?php if (wbga_get_option('check_updates')) echo "checked"; ?> /></td>
        </tr>
        <tr>
          <th nowrap valign="top">Filter content: <span onmouseover="tooltip.show('Enable tracking of links in your content. Disabling this will save performance if things are going slow.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="filter_content" id="filter_content" value="true" <?php if (wbga_get_option('filter_content')) echo "checked"; ?> /></td>
		</tr>
        <tr>
          <th nowrap valign="top">Filter comments: <span onmouseover="tooltip.show('Enable tracking of links in the comments.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="filter_comments" id="filter_comments" value="true" <?php if (wbga_get_option('filter_comments')) echo "checked"; ?> /></td>
        </tr>
        <tr>
          <th nowrap valign="top">Filter author links: <span onmouseover="tooltip.show('Enable tracking of links in the comments footer showing the author.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td colspan="3"><input type="checkbox" name="filter_comment_authors" id="filter_comment_authors" value="true" <?php if (wbga_get_option('filter_comment_authors')) echo "checked"; ?> /></td>
        </tr>
        <tr>
          <th nowrap valign="top">Track external links: <span onmouseover="tooltip.show('Include code to track links to external sites and specify what prefix should be used in the tracking URL.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="track_ext_links" id="track_ext_links" value="true" <?php if (wbga_get_option('track_ext_links')) echo "checked"; ?> /> With prefix: <input name="prefix_ext_links" type="text" id="prefix_ext_links" value="<?php echo wbga_get_option('prefix_ext_links'); ?>" size="20" /></td>
		</tr>
        <tr>
          <th nowrap valign="top">Internal host(s): <span onmouseover="tooltip.show('Hostname(s) that are considered internal links and they will not be tagged with the prefix for external links. Specify multiple hostnames with commas.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input name="internal_domains" type="text" id="internal_domains" value="<?php echo wbga_get_option('internal_domains'); ?>" size="30" /></td>
        </tr>
        <tr>
          <th nowrap valign="top">Track download links: <span onmouseover="tooltip.show('Include code to track internal links to certain file types.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input type="checkbox" name="track_files" id="track_files" value="true" <?php if (wbga_get_option('track_files')) echo "checked"; ?> />
            With prefix: <input name="prefix_file_links" type="text" id="prefix_file_links" value="<?php echo wbga_get_option('prefix_file_links'); ?>" size="20" /></td>
		</tr>
        <tr>
          <th nowrap valign="top">File extensions to track: <span onmouseover="tooltip.show('Specify which file extensions you want to check when download link tracking is enabled.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input name="track_extensions" type="text" id="track_extensions" value="<?php echo wbga_get_option('track_extensions'); ?>" size="30" /></td>
        </tr>
        <tr>
          <th nowrap valign="top">Track mailto links: <span onmouseover="tooltip.show('Include code to track mailto: links to email addresses.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td ><input type="checkbox" name="track_mail_links" id="track_mail_links" value="true" <?php if (wbga_get_option('track_mail_links')) echo "checked"; ?> />
            With prefix: <input name="prefix_mail_links" type="text" id="prefix_mail_links" value="<?php echo wbga_get_option('prefix_mail_links'); ?>" size="20" /></td>
        </tr>
      </table>
    </fieldset>
    <div class="submit">
      <input type="submit" name="info_update" class="button-primary" value="<?php _e('Save Tracking Options', 'wbga') ?>" />
      <input type="submit" name="default_settings" class="button-primary" value="<?php _e('Default Settings', 'wbga') ?>" />
	  </div>
  </form>
  <form method="post">
  <fieldset class="options" name="dashboard">
  <h3>Stat Dashboard Settings</h3>
  <?php if(!class_exists('SimpleXMLElement'))
  {
    echo '<br/><br/><div id="message" class="updated fade"><p><strong>It appears that <a href="http://us3.php.net/manual/en/book.simplexml.php">SimpleXML</a> is not compiled into your version of PHP. It is required for this part of the plugin to function correctly.</strong></p></div>';
  }
  else if(!function_exists('curl_init'))
  {
    echo '<br/><br/><div id="message" class="updated fade"><p><strong>It appears that <a href="http://www.php.net/manual/en/book.curl.php">CURL</a> is not compiled into your version of PHP. It is required for for this part of the plugin to function correctly.</strong></p></div>';
  }
  else
  {
    $gad_auth_token = wbga_get_option('gad_auth_token');

    if(isset($gad_auth_token) && $gad_auth_token != '')
    {
      gad_admin_handle_other_options($info_message);
    }
    else
    {
      gad_admin_handle_login_options($info_message);
    }
  }
  ?>
  </fieldset>
  </form>
</div>
</div><?php
  wbga_debug('End functions wbga_options');
}

function gad_admin_handle_login_options($info_message = '')
{
  if( isset($_POST['SubmitLogin']) ) 
  {
    if( function_exists('current_user_can') && !current_user_can('manage_options') )
    {
      die(__('Cheatin&#8217; uh?'));
    }

    if( !isset($_POST['ga_email']) || trim($_POST['ga_email']) == '' )
    {
      $error_message = "Email is required";
    }
    else if( !isset($_POST['ga_pass']) || $_POST['ga_pass'] == '' )
    {
      $error_message = "Password is required";
    }
    else
    {
	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_login_email']       = $_POST['ga_email'];
      update_option('webninja_ga_options', $wbga_options);

      if(isset($_POST['ga_save_pass']))
      {
  	    $wbga_options = get_option('webninja_ga_options');
	    $wbga_options['gad_login_pass']     = $_POST['ga_pass'];
        update_option('webninja_ga_options', $wbga_options);
      }
      else
      {
  	    $wbga_options = get_option('webninja_ga_options');
	    $wbga_options['gad_login_pass']     = "";
        update_option('webninja_ga_options', $wbga_options);
      }

      $gauth = new GAuthLib('wpga-display-1.0');
      if(isset($_POST['ga_captcha_token']) && isset($_POST['ga_captcha']))
      {
        $gauth->authenticate($_POST['ga_email'], $_POST['ga_pass'], 'analytics', $_POST['ga_captcha_token'], $_POST['ga_captcha']);
      }
      else
      {
        $gauth->authenticate($_POST['ga_email'], $_POST['ga_pass'], 'analytics');
      }

      if($gauth->isError())
      {
        $error_message = $gauth->getErrorMessage();
      }
      else
      {
  	    $wbga_options = get_option('webninja_ga_options');
	    $wbga_options['gad_auth_token']     = $gauth->getAuthToken();
        update_option('webninja_ga_options', $wbga_options);
        gad_admin_handle_other_options('Login successful. Please select an Account.');
        return;
      }
    }
  }

?>

  <div class="wrap" style="padding-top: 5px;">

<?php if( isset($info_message) && trim($info_message) != '' ) : ?>
    <div id="message" class="updated fade"><p><strong><?php echo $info_message ?></strong></p></div>
<?php endif; ?>

<?php if( isset($error_message) ) : ?>
    <div id="message" class="error fade"><p><strong><?php echo $error_message ?></strong></p></div>
<?php endif; ?>

    <form action="" method="post">
      <table width="500px" cellspacing="5" cellpadding="5" class="editform">
        <tr>
          <th nowrap valign="top" width="200px">Google Analytics Email: <span onmouseover="tooltip.show('Email you use to log into Google Analytics', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input name="ga_email" type="text" size="30" id="ga_email" class="regular-text" value="<?php echo isset($_POST['ga_email']) ? $_POST['ga_email'] : wbga_get_option('gad_login_email'); ?>" /></td>
		</tr>
        <tr>
          <th nowrap valign="top">Google Analytics Password: <span onmouseover="tooltip.show('Password you use to log into Google Analytics', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input name="ga_pass" type="password" size="30" id="ga_pass" class="regular-text" value="" /></td>
		</tr>
<?php if( isset($gauth) && $gauth->requiresCaptcha() ) : ?>
        <tr>
          <th nowrap valign="top">Google CAPTCHA: <span onmouseover="tooltip.show('Fill out the CAPTCHA', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><img src="<?php echo $gauth->getCaptchaImageURL(); ?>"/><br/><br/>
            <input name="ga_captcha" type="text" size="10" id="ga_captcha" class="regular-text" value="" />
            <input type="hidden" name="ga_captcha_token" value="<?php echo $gauth->getCaptchaToken(); ?>"/></td>
		</tr>
<?php endif; ?>
        <tr>
          <th nowrap valign="top">Save Password: <span onmouseover="tooltip.show('Save password in Wordpress Database for quicker access to stats', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input name="ga_save_pass" type="checkbox" id="ga_save_pass" value="ga_save_pass" <?php if(isset($_POST['ga_save_pass']) || wbga_get_option('gad_login_pass') !== false) echo 'checked'; ?> /></td>
		</tr>
      </table>

      <div class="submit">
        <input type="submit" name="SubmitLogin" class="button-primary" value="<?php _e('Save Stat Options &raquo;'); ?>" />
      </div>
    </form>

  </div>

<?php
}
function gad_admin_handle_other_options($info_message = '')
{
	if( isset($_POST['ForgetAll']) )
	{
	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_goal_one']     = "";
	  $wbga_options['gad_goal_two']     = "";
	  $wbga_options['gad_goal_three']     = "";
	  $wbga_options['gad_goal_four']     = "";
	  $wbga_options['gad_login_email']     = "";
	  $wbga_options['gad_login_pass']     = "";
	  $wbga_options['gad_auth_token']     = "";
	  update_option('webninja_ga_options', $wbga_options);
	  gad_admin_handle_login_options('Everything Reset');
	  return;
	}
  if( isset($_POST['SubmitOptions']) ) 
  {
    if( function_exists('current_user_can') && !current_user_can('manage_options') )
    {
      die(__('Cheatin&#8217; uh?'));
    }

    @SimpleFileCache::clearCache(); 

  	$wbga_options = get_option('webninja_ga_options');
	$wbga_options['gad_account_id']     = $_POST['ga_account_id'];
	update_option('webninja_ga_options', $wbga_options);

    if( isset($_POST['ga_display_level']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_display_level']     = $_POST['ga_display_level'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    if( isset($_POST['ga_post_days']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_post_days']     = $_POST['ga_post_days'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    if( isset($_POST['ga_cache_timeout']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_cache_timeout']     = $_POST['ga_cache_timeout'];
	  update_option('webninja_ga_options', $wbga_options);
    }


    if( isset($_POST['ga_goal_one']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_goal_one']     = $_POST['ga_goal_one'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    if( isset($_POST['ga_goal_two']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_goal_two']     = $_POST['ga_goal_two'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    if( isset($_POST['ga_goal_three']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_goal_three']     = $_POST['ga_goal_three'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    if( isset($_POST['ga_goal_four']) )
    {
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_goal_four']     = $_POST['ga_goal_four'];
	  update_option('webninja_ga_options', $wbga_options);
    }

    $info_message = 'Dashboard Options Saved';
  }
  $ga = new GALib(wbga_get_option('gad_auth_token'), '', wbga_get_option('gad_cache_timeout') !== false ? wbga_get_option('gad_cache_timeout') : 60);
  $account_hash = $ga->account_query();

  if($ga->isError())
  {
    if($ga->isAuthError())
    {	
  	  $wbga_options = get_option('webninja_ga_options');
	  $wbga_options['gad_auth_token']       = "";
      update_option('webninja_ga_options', $wbga_options);
      gad_admin_handle_login_options('Error communicating with Google, please log in again.');
      return;
    }
    else
    {
      echo 'Error gathering analytics data from Google: ' . strip_tags($ga->getErrorMessage());
      return;
    }
  }

?>

  <div class="wrap" style="padding-top: 5px;">

<?php if( isset($info_message) && trim($info_message) != '' ) : ?>
    <div id="message" class="updated fade"><p><strong><?php echo $info_message ?></strong></p></div>
<?php endif; ?>

<?php if( isset($error_message) ) : ?>
    <div id="message" class="error fade"><p><strong><?php echo $error_message ?></strong></p></div>
<?php endif; ?>

    <form action="" method="post">
      <table width="500px" cellspacing="5" cellpadding="5" class="editform">
        <tr>
          <th nowrap valign="top" width="200px">Available Accounts: <span onmouseover="tooltip.show('Select the correct Account for this site. You will need to select an account and save before the analytics dashboard will work.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><?php
    if(sizeof($account_hash) == 0)
    {
      echo '<span id="ga_account_id">No accounts available.</span>';
    }
    else
    {
      $current_account_id = isset($_POST['ga_account_id']) ? $_POST['ga_account_id'] : wbga_get_option('gad_account_id') !== false ? wbga_get_option('gad_account_id') : '';

      echo '<select id="ga_account_id" name="ga_account_id">';
      foreach($account_hash as $account_id => $account_name)
      {
        echo '<option value="' . $account_id . '" ' . ($current_account_id == $account_id ? 'selected' : '') . '>' . $account_name . '</option>';
      }
      echo '</select>';
    }
?></td>
		</tr>
        <tr>
          <th nowrap valign="top">Dashboard Level: <span onmouseover="tooltip.show('Select the correct Account for this site. You will need to select an account and save before the analytics dashboard will work.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><?php $ga_display_level = wbga_get_option('gad_display_level'); ?>
          <select name="ga_display_level" id="ga_display_level">
              <option value="level_8" <?php echo ($ga_display_level == 'level_8') ? 'selected' : ''; ?>>Admin</option>
              <option value="level_7" <?php echo ($ga_display_level == 'level_7') ? 'selected' : ''; ?>>Editor</option>
              <option value="level_2" <?php echo ($ga_display_level == 'level_2') ? 'selected' : ''; ?>>Author</option>
              <option value="level_1" <?php echo ($ga_display_level == 'level_1') ? 'selected' : ''; ?>>Contributor</option>
              <option value="level_0" <?php echo ($ga_display_level == 'level_0') ? 'selected' : ''; ?>>Subscriber</option>
            </select></td>
		</tr>
        <tr>
          <th nowrap valign="top">Post Admin Stats: <span onmouseover="tooltip.show('This is the number of days you would like the Post and Page Admin Section to show stats for.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><?php $ga_post_days = wbga_get_option('gad_post_days'); ?>
          <select name="ga_post_days" id="ga_display_level">
              <option value="7" <?php echo ($ga_post_days == '7') ? 'selected' : ''; ?>>Last 7 Days</option>
              <option value="30" <?php echo ($ga_post_days == '30') ? 'selected' : ''; echo ($ga_post_days == '') ? 'selected' : ''; ?>>Last 30 Days</option>
              <option value="60" <?php echo ($ga_post_days == '60') ? 'selected' : ''; ?>>Last 60 Days</option>
              <option value="90" <?php echo ($ga_post_days == '90') ? 'selected' : ''; ?>>Last 90 Days</option>
            </select></td>
		</tr>
        <tr>
          <th nowrap valign="top">Cache Timeout (secs): <span onmouseover="tooltip.show('Time before Web Ninja GA Stats service checks Google Analytics for your site stats.', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><?php
if( SimpleFileCache::canCache() )
{
?><input value="<?php echo (wbga_get_option('gad_cache_timeout') != "" ? wbga_get_option('gad_cache_timeout') : '60'); ?>" name="ga_cache_timeout" id="ga_cache_timeout"/><?php
}
else
{
?><span style="padding: 10px;" class="error">The configuration of your server will prevent response caching.</span><?php
}
?></td>
		</tr>
        <tr>
          <th nowrap valign="top">Goal #1 Name: <span onmouseover="tooltip.show('Name for Goal #1 if applicable', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input value="<?php echo wbga_get_option('gad_goal_one'); ?>" name="ga_goal_one" id="ga_goal_one"/></td>
		</tr>
        <tr>
          <th nowrap valign="top">Goal #2 Name: <span onmouseover="tooltip.show('Name for Goal #2 if applicable', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input value="<?php echo wbga_get_option('gad_goal_one'); ?>" name="ga_goal_two" id="ga_goal_two"/></td>
		</tr>
        <tr>
          <th nowrap valign="top">Goal #3 Name: <span onmouseover="tooltip.show('Name for Goal #3 if applicable', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input value="<?php echo wbga_get_option('gad_goal_one'); ?>" name="ga_goal_three" id="ga_goal_three"/></td>
		</tr>
        <tr>
          <th nowrap valign="top">Goal #4 Name: <span onmouseover="tooltip.show('Name for Goal #4 if applicable', 400);" onmouseout="tooltip.hide();" style="color:#00F; cursor:pointer">[?]</span></th>
          <td><input value="<?php echo wbga_get_option('gad_goal_one'); ?>" name="ga_goal_four" id="ga_goal_four"/></td>
		</tr>
      </table>
      <div class="submit">
        <input type="submit" name="SubmitOptions" class="button-primary" value="<?php _e('Save Dashboard Options'); ?>" />
        <input type="submit" name="ForgetAll" class="button-primary" value="<?php _e('Remove Account Info'); ?>" />
      </div>
    </form>

  </div>

<?php
}


function wbga_track_user() {
  global $user_level;
  wbga_debug('Start function wbga_track_user');
  if (!user_level) {
    wbga_debug('User not logged on');
    $result = true;
  } else {
    if (wbga_get_option('ignore_users') && 
        $user_level>=wbga_get_option('max_user_level')) {
      wbga_debug("Not tracking user with level $user_level");
      $result = false;
    } else {
      wbga_debug("Tracking user with level $user_level");
      $result = true;
    }
  }
  wbga_debug("Ending function wbga_track_user: $result");
  return $result;
}

function wbga_is_url_internal($url) {
  wbga_debug("Start function wbga_is_url_internal: $url");
  $url=strtolower($url);
  $internal=false;
  $internals=explode(',', wbga_get_option('internal_domains'));
  foreach ($internals as $hostname) {
    wbga_debug("Checking hostname $hostname");
    $hostname=strtolower($hostname);
    if (substr($url, 0, strlen($hostname))==$hostname) {
      wbga_debug('Match found, url is internal');
      $internal=true;
    }
  }
  wbga_debug("Ending function wbga_is_url_internal: $internal");
  return $internal;
}

function wbga_remove_hostname($url) {
  wbga_debug("Start function wbga_remove_hostname: $url");
  $pos=strpos($url, '/');
  $result='';
  if ($pos===false) {
    wbga_debug('URL just hostname, return empty string');
    $result='';
  } else {
    wbga_debug('Stripping everything up until and including first /');
    $result=substr($url, $pos+1);
  }
  wbga_debug("Ending function wbga_remove_hostname: $result");
  return $result;
}

function wbga_track_mailto($mailto) {
  wbga_debug("Start function wbga_track_mailto: $mailto");
  $tracker='';
  if (wbga_get_option('track_mail_links')) {
    $tracker=wbga_get_option('prefix_mail_links').$mailto;
  }        
  wbga_debug("Ending function wbga_track_mailto: $tracker");
  return $tracker;
}

function wbga_track_internal_url($url, $relative) {
  wbga_debug("Start function wbga_track_internal_url: $url, $relative");
  $tracker='';
  if (wbga_get_option('track_files')) {
    wbga_debug('Tracking files enabled');
    if (strpos($url,'?') !== false) {
      $url=substr($url, 0, strpos($url, '?'));
      wbga_debug("Removed query params from url: $url");
    }
    $exts=explode(',', wbga_get_option('track_extensions'));
    foreach ($exts as $ext) {
      wbga_debug("Checking file extension $ext");
      if (substr($url, -strlen($ext)-1) == ".$ext") {
        wbga_debug('File extension found');
        if ($relative) {
          wbga_debug('Relative URL');
          if (substr($url, 0, 1)=='/') {
            $url=substr($url, 1);
            wbga_debug("Removed starting slash from url: $url");
          } else {
            wbga_debug("Rewriting relative url: $url");
            $base_dir=$_SERVER['REQUEST_URI'];  
            wbga_debug("Request URI: $base_dir");
            if (strpos($base_dir,'?')) {
              $base_dir=substr($base_dir, 0, strpos($base_dir,'?'));
            }
            if ('/'!=substr($base_dir, -1, 1)) {
              $base_dir=substr($base_dir, 0, strrpos($base_dir,'/')+1);
            }
            $url=substr($base_dir.$url, 1);
            wbga_debug("Rewrote url to absolute: $url");
          }
          $tracker=wbga_get_option('prefix_file_links').$url;
        } else {
          wbga_debug('Absolute URL, remove hostname from URL');
          $tracker=wbga_get_option('prefix_file_links').wbga_remove_hostname($url);
        }
      }
    }
  }
  
  wbga_debug("Ending function wbga_track_internal_url: $tracker");
  return $tracker;

}

function wbga_track_external_url($url) {
  wbga_debug("Start function wbga_track_external_url: $url");
  $tracker='';
  if (wbga_get_option('track_ext_links')) {
    wbga_debug('Tracking external links enabled');
    $tracker=wbga_get_option('prefix_ext_links').$url;
  }
  wbga_debug("Ending function wbga_track_external_url: $url");
  return $tracker;
}

function wbga_track_full_url($url) {
  wbga_debug("Start function wbga_track_full_url: $url");
  $tracker = '';
  if (wbga_is_url_internal($url)) {
    wbga_debug('Get tracker for internal URL');
    $tracker = wbga_track_internal_url($url, false);
  } else {
    wbga_debug('Get tracker for external URL');
    $tracker = wbga_track_external_url($url);
  }
  wbga_debug("Ending function wbga_track_full_url: $tracker");
  return $tracker;
}

function wbga_preg_callback($match) {
  wbga_debug("Start function wbga_preg_callback: $match");
  $before_href=1; 
  $after_href=3;  
  $href_value=2;  
  $a_content=4; 
  $result = $match[0];
  $tracker='';
  if (preg_match('@^([a-z]+)://(.*)@i', trim($match[$href_value]), $target) > 0) {
    wbga_debug('Get tracker for full url');
    $tracker = wbga_track_full_url($target[2]);
  } else if (preg_match('@^(mailto):(.*)@i', trim($match[$href_value]), $target) > 0) {
    wbga_debug('Get tracker for mailto: link');
    $tracker = wbga_track_mailto($target[2]);
  } else {
    wbga_debug('Get tracker for relative (and thus internal) url');
    $tracker = wbga_track_internal_url(trim($match[$href_value]), true);
  }

  if ($tracker) {
    wbga_debug("Adding onclick attribute for $tracker");
    $onClick="javascript:pageTracker._trackPageview('$tracker');";
    $result=preg_replace('@<a\s([^>]*?)href@i','<a onclick="'.$onClick.'" $1 href', $result);
  }

  wbga_debug("Ending function wbga_preg_callback: $result");
  return $result;

}

function wbga_in_feed() {
  global $doing_rss;
  wbga_debug('Start function wbga_in_feed');
  if (is_feed() || $doing_rss) {
    $result = true;
  } else {
    $result = false;
  }
  wbga_debug("Ending function wbga_in_feed: $result");
  return $result;
}

function wbga_filter($content) {
  wbga_debug("Start function wbga_filter: $content");
  if (!wbga_in_feed() && wbga_track_user()) {
    $pattern = '<a\s([^>]*?)href\s*=\s*[\'"](.*?)[\'"]([^>]*)>(.*?)</a\s*>';
    wbga_debug("Calling preg_replace_callback: $pattern");
    $content = preg_replace_callback('@'.$pattern.'@i', 'wbga_preg_callback', $content);
  }
  wbga_debug("Ending function wbga_filter: $content");
  return $content;
}

function wbga_insert_html_once($location, $html) {
  wbga_debug("Start function wbga_insert_html_once: $location, $html");
  global $wbga_header_hooked;
  global $wbga_footer_hooked;
  global $wbga_html_inserted;
  wbga_debug("Footer hooked: $wbga_footer_hooked");
  wbga_debug("HTML inserted: $wbga_html_inserted");
  
  if ('head'==$location) {
    wbga_debug('Location is HEAD');
    $wbga_header_hooked = true;
    if (!wbga_get_option('footer_hooked')) {
      wbga_debug('Inserting HTML since footer is not hooked');
      echo $html;
      $wbga_html_inserted=true;
    }
  } else if ('footer'==$location) {
    wbga_debug('Location is FOOTER');
    $wbga_footer_hooked = true;
    if (!$wbga_html_inserted) {
      wbga_debug('Inserting HTML');
      echo $html;
    }
  } else if ('adm_footer'==$location) {
    wbga_debug('Location is ADM_FOOTER');
    if (!$wbga_html_inserted) {
      wbga_debug('Inserting HTML');
      echo $html;
    }
  }
  wbga_debug('End function wbga_insert_html');
}

function wbga_get_tracker() {
  wbga_debug('Start function wbga_get_tracker');
  $result='';
  if (!wbga_in_feed()) {
    if (wbga_track_user()) {
      $result='
<!-- Google Analytics Code added by Web Ninja Google Analytics plugin v'.wbgaversion.': http://josh-fowler.com/?page_id=70 -->
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
var pageTracker = _gat._getTracker("'.wbga_get_option('account_id').'");
pageTracker._initData();
pageTracker._trackPageview();
</script>
<!-- Web Ninja Google Analytics Done -->
';
    } else {
      // logged on user not tracked
      $result='
<!-- Google Analytics Code not added by Web Ninja Google Analytics plugin v'.wbgaversion.': http://josh-fowler.com/?page_id=70 -->
<!-- Google Analytics Code is not added for a logged on user of this level -->
';
    }
  }
  wbga_debug("Ending function wbga_get_tracker: $result");
  return $result;
}

function wbga_wp_head_track($dummy) {
  wbga_debug("Start function wbga_wp_head_track: $dummy");
  wbga_insert_html_once('head', wbga_get_tracker());
  wbga_debug("Ending function wbga_wp_head_track: $dummy");
  return $dummy;
}

function wbga_wp_footer_track($dummy) {
  wbga_debug("Start function wbga_wp_footer_track: $dummy");
  wbga_insert_html_once('footer', wbga_get_tracker());
  wbga_debug("Ending function wbga_wp_footer_track: $dummy");
  return $dummy;
}

function wbga_adm_footer_track($dummy) {
  wbga_debug("Start function wbga_adm_footer_track: $dummy");
  wbga_insert_html_once('adm_footer', wbga_get_tracker());
  wbga_debug("Ending function wbga_adm_footer_track: $dummy");
  return $dummy;
}

function wbga_init() {
  wbga_debug("Start function wbga_init");
  load_plugin_textdomain('wbga');
  wbga_debug("Ending function wbga_init");
}

function wbga_shutdown() {
  wbga_debug('Start function wbga_shutdown');
  global $wbga_header_hooked;
  global $wbga_footer_hooked;

  if (is_404()) {
    wbga_debug('Building 404 page, not setting footer_hooked flag');
  } else if (wbga_in_feed()) {
    wbga_debug('Building feed, not setting footer_hooked flag');
  } else if (!wbga_track_user()) {
    wbga_debug('Not tracking this user, not setting footer_hooked flag');
  } else {
    if (!$wbga_footer_hooked && !$wbga_header_hooked) {
      wbga_debug('Header and footer hook were not executed');
    } else if ($wbga_footer_hooked) {
      wbga_debug('Footer hook was executed');
      if (!wbga_get_option('footer_hooked')) {
        wbga_debug('Changing footer_hooked option to true');
        wbga_set_option('footer_hooked', true);
      }
    } else {
      wbga_debug('Footer hook was not executed, but header hook did');
      if (wbga_get_option('footer_hooked')) {
        wbga_debug('Changing footer_hooked option to false');
        wbga_set_option('footer_hooked', false);
      }
    }
  }
  if (wbga_get_option('debug')) {
    global $wbga_debug;
    echo "\n<!-- \n$wbga_debug -->";  
  }
  wbga_debug('End function wbga_shutdown');
}
add_filter('the_content', 'gad_content_tag_filter', 7);
function gad_content_tag_filter( $content ) 
{
  return preg_replace_callback('/\[\s*(wnga)(:(.*))?\s*\]/iU', gad_content_tag_filter_replace, $content);
}

function gad_content_tag_filter_replace($matches)
{
  $link_uri = substr($_SERVER["REQUEST_URI"], -20);

  switch(strtolower($matches[1]))
  {
    case 'wnga':
      $data = new GADWidgetData(wbga_get_option('gad_auth_token'), wbga_get_option('gad_account_id'));

      if(isset($matches[3]) && trim($matches[3]) != '')
      {
        return $data->gad_pageviews_sparkline($link_uri);
      }
      else
      {
        return $data->gad_pageviews_text($link_uri);
      }
      break;
    default:
      return '';
  }
}



add_filter('manage_posts_columns', 'gad_posts_pages_columns');
add_filter('manage_pages_columns', 'gad_posts_pages_columns');
function gad_posts_pages_columns($defaults) 
{
  $defaults['analytics'] = __('Analytics');
  return $defaults;
}

add_action('manage_pages_custom_column', 'gad_pages_custom_column', 2, 2);
function gad_pages_custom_column($column_name, $page_id) 
{
  gad_posts_pages_custom_column($column_name, $page_id, 'page');
}

add_action('manage_posts_custom_column', 'gad_posts_custom_column', 2, 2);
function gad_posts_custom_column($column_name, $post_id) 
{
  gad_posts_pages_custom_column($column_name, $post_id, 'post');
}

function gad_posts_pages_custom_column($column_name, $post_id, $post_or_page)
{
  global $wpdb;

  if(wbga_get_option('gad_auth_token') === false || wbga_get_option('gad_account_id') === false)
  {
    if (current_user_can( 'manage_options' ) )
    {
      echo 'You need to log in and select an account in the <a href="options-general.php?page=web-ninja-google-analytics/webninja_ga.php">options panel</a>.';
    }
    else
    {
      echo 'The administrator needs to log in and select a Google Analytics account.';
    }
    return;
  }

  if( $column_name == 'analytics' ) 
  {
    $ga = new GALib(wbga_get_option('gad_auth_token'), wbga_get_option('gad_account_id'), wbga_get_option('gad_cache_timeout') !== false ? wbga_get_option('gad_cache_timeout') : 60);

    $link_value = get_permalink($post_id);
    $url_data = parse_url($link_value);
    $link_uri = substr($url_data['path'] . (isset($url_data['query']) ? ('?' . $url_data['query']) : ''), -20);

    $is_draft = $wpdb->get_var("SELECT count(1) FROM $wpdb->posts WHERE post_status = 'draft' AND ID = $post_id AND post_type = '$post_or_page'");
    if($link_uri == '' || (isset($is_draft) && $is_draft > 0))
    {
      echo "";
    }
    else
    {
		
  	  $postdays = wbga_get_option('gad_post_days');
	  if ($postdays == '') { $postdays = 30; }
      $start_date = date('Y-m-d', time() - (60 * 60 * 24 * $postdays));
      $end_date = date('Y-m-d'); 
      $data = $ga->summary_by_partial_uri_for_date_period($link_uri, $start_date, $end_date);
      $error_type = gad_request_error_type($ga);
      if($error_type == 'perm') return;
      else if($error_type == 'retry') $data = $ga->summary_by_partial_uri_for_date_period($link_uri, $start_date, $end_date);

      $minvalue = 999999999;
      $maxvalue = 0;
      $pageviews = 0;
      $exits = 0;
      $uniques = 0;
      $count = 0;
      foreach($data as $date => $value)
      {
        if($minvalue > $value['ga:pageviews'])
        {
          $minvalue = $value['ga:pageviews'];
        }
        if($maxvalue < $value['ga:pageviews'])
        {
          $maxvalue = $value['ga:pageviews'];
        }
        $cvals .= $value['ga:pageviews'] . ($count < sizeof($data)-1 ? "," : "");
        $count++;

        $pageviews += $value['ga:pageviews'];
        $exits += $value['ga:exits'];
        $uniques += $value['ga:uniquePageviews'];
      }

?>
    <div style="width:150px; display:block">
    <table style="padding:0">
      <tr>
        <td style="border:0">
          <img width="90" height="30" src="http://chart.apis.google.com/chart?chs=90x30&cht=ls&chf=bg,s,FFFFFF00&chco=0077CC&chd=t:<?php echo $cvals; ?>&chds=<?php echo $minvalue; ?>,<?php echo $maxvalue; ?>"/><br />
          <?php echo number_format($pageviews); ?> pageviews<br/>
          <?php echo number_format($exits); ?> exits<br/>
          <?php echo number_format($uniques); ?> uniques<br/>
        </td>
      </tr>
    </table>
    </div>
<?php
    }
  }
}

function gad_request_error_type($ga)
{
  if($ga->isError())
  {
    if($ga->isAuthError())
    {
      if(wbga_get_option('gad_login_pass') === false || wbga_get_option('gad_login_email') === false)
      {
        if (current_user_can( 'manage_options' ) )
        {
          echo 'You need to log in and select an account in the <a href="options-general.php?page=web-ninja-google-analytics/webninja_ga.php">options panel</a>.';
        }
        else
        {
          echo 'The administrator needs to log in and select a Google Analytics account.';
        }
        return 'perm';
      }
      else
      {
        $gauth = new GAuthLib('wpga-display-1.0');
        $gauth->authenticate(wbga_get_option('gad_login_email'), wbga_get_option('gad_login_pass'), 'analytics');

        if($gauth->isError())
        {
          $error_message = $gauth->getErrorMessage();
          if (current_user_can( 'manage_options' ) )
          {
            echo 'You need to log in and select an account in the <a href="options-general.php?page=web-ninja-google-analytics/webninja_ga.php">options panel</a>.';
          }
          else
          {
            echo 'The administrator needs to log in and select a Google Analytics account.';
          }
          return 'perm';
        }
        else
        {
          delete_option('gad_auth_token');
          add_option('gad_auth_token', $gauth->getAuthToken());
          $ga->setAuth($gauth->getAuthToken());
          return 'retry';
        }
      }
    }
    else
    {
      echo 'Error gathering analytics data from Google: ' . strip_tags($ga->getErrorMessage());
      return 'perm';
    }
  }
  else
  {
    return 'none';
  }
}


add_action('wp_dashboard_setup', 'gad_register_dashboard_widget');
function gad_register_dashboard_widget() 
{
  wp_register_sidebar_widget( 'dashboard_gad', __('Web Ninja GA Dashboard Widget', 'gad'), 'dashboard_gad', array( 'width' => 'full', 'height' => 'single'));
}
 
add_filter('wp_dashboard_widgets', 'gad_add_dashboard_widget');
function gad_add_dashboard_widget($widgets) 
{
  global $wp_registered_widgets;
  $dashboard_display_level = wbga_get_option('gad_display_level');
  if (!isset($wp_registered_widgets['dashboard_gad']) || !current_user_can( $dashboard_display_level !== false ? $dashboard_display_level : 'manage_options' ) )
  {
    return $widgets;
  }
  array_splice($widgets, sizeof($widgets)-1, 0, 'dashboard_gad');
  return $widgets;
}
 
function dashboard_gad()
{
  global $current_user;
  get_currentuserinfo();

  if(wbga_get_option('gad_auth_token') === false || wbga_get_option('gad_account_id') === false)
  {
    if (current_user_can( 'manage_options' ) )
    {
      echo 'You need to log in and select an account in the <a href="options-general.php?page=google-analytics-dashboard/google-analytics-dashboard.php">options panel</a>.';
    }
    else
    {
      echo 'The administrator needs to log in and select a Google Analytics account.';
    }
    return;
  }
  		$dashdays = wbga_get_option('gad_dash_days');
		if ($dashdays == '') { 
		  $wbga_options = get_option('webninja_ga_options');
		  $wbga_options['gad_dash_days']     = "7";
		  update_option('webninja_ga_options', $wbga_options);
		  $dashdays = 7;
		}
		if(isset($_POST['WNGA7Day']) )
		{
		  $wbga_options = get_option('webninja_ga_options');
		  $wbga_options['gad_dash_days']     = "7";
		  update_option('webninja_ga_options', $wbga_options);
		  $dashdays = 7;
		}
		if(isset($_POST['WNGA30Day']) )
		{
		  $wbga_options = get_option('webninja_ga_options');
		  $wbga_options['gad_dash_days']     = "30";
		  update_option('webninja_ga_options', $wbga_options);
		  $dashdays = 30;
		}
		if(isset($_POST['WNGA60Day']) )
		{
		  $wbga_options = get_option('webninja_ga_options');
		  $wbga_options['gad_dash_days']     = "60";
		  update_option('webninja_ga_options', $wbga_options);
		  $dashdays = 60;
		}
		if(isset($_POST['WNGA90Day']) )
		{
		  $wbga_options = get_option('webninja_ga_options');
		  $wbga_options['gad_dash_days']     = "90";
		  update_option('webninja_ga_options', $wbga_options);
		  $dashdays = 90;
		}

		
		
      $start_date = date('Y-m-d', time() - (60 * 60 * 24 * $dashdays));
	  if ($dashdays == 1) {
      	$end_date = date('Y-m-d', time() - (60 * 60 * 24 * $dashdays));
	  } else {
      	$end_date = date('Y-m-d');
	  }

  
  $start_date_ts = time() - (60 * 60 * 24 * $dashdays); // 30 days in the past
  //$start_date = date('Y-m-d', $start_date_ts);
  //$end_date = date('Y-m-d');

  $ga = new GALib(wbga_get_option('gad_auth_token'), wbga_get_option('gad_account_id'), wbga_get_option('gad_cache_timeout') !== false ? wbga_get_option('gad_cache_timeout') : 60);

  $summary_data = $ga->summary_for_date_period($start_date, $end_date);
  $error_type = gad_request_error_type($ga);
  if($error_type == 'perm') return;
  else if($error_type == 'retry') $summary_data = $ga->summary_for_date_period($start_date, $end_date);
  $daily_pageviews = $ga->daily_pageviews_for_date_period($start_date, $end_date);
  $error_type = gad_request_error_type($ga);
  if($error_type == 'perm') return;
  else if($error_type == 'retry') $daily_pageviews = $ga->daily_pageviews_for_date_period($start_date, $end_date);
  $pages = $ga->pages_for_date_period($start_date, $end_date);
  $error_type = gad_request_error_type($ga);
  if($error_type == 'perm') return;
  else if($error_type == 'retry') $pages = $ga->pages_for_date_period($start_date, $end_date);
  $keywords = $ga->keywords_for_date_period($start_date, $end_date);
  $error_type = gad_request_error_type($ga);
  if($error_type == 'perm') return;
  else if($error_type == 'retry') $keywords = $ga->keywords_for_date_period($start_date, $end_date);
  $sources = $ga->sources_for_date_period($start_date, $end_date);
  $error_type = gad_request_error_type($ga);
  if($error_type == 'perm') return;
  else if($error_type == 'retry') $sources = $ga->sources_for_date_period($start_date, $end_date);

  if( wbga_get_option('gad_goal_one') !== false || wbga_get_option('gad_goal_two') !== false ||
      wbga_get_option('gad_goal_three') !== false || wbga_get_option('gad_goal_four') !== false ) 
  {
    $goal_data_tmp = $ga->goals_for_date_period($start_date, $end_date, array(wbga_get_option('gad_goal_one') !== false, wbga_get_option('gad_goal_two') !== false, wbga_get_option('gad_goal_three') !== false, wbga_get_option('gad_goal_four') !== false));
    $error_type = gad_request_error_type($ga);
    if($error_type == 'perm') return;
    else if($error_type == 'retry') $goal_data_tmp = $ga->goals_for_date_period($start_date, $end_date, array(wbga_get_option('gad_goal_one') !== false, wbga_get_option('gad_goal_two') !== false, wbga_get_option('gad_goal_three') !== false, wbga_get_option('gad_goal_four') !== false));

    $goal_data = array();
    foreach($goal_data_tmp as $gd)
    {
      if(is_assoc($gd))
      {
        foreach($gd as $gk => $gv)
        {
          $goal_data[$gk] += $gv;
        }
      }
    }
  }

  $labelv = '';
  $labelp = '';
  $minvalue = 999999999;
  $maxvalue = 0;
  $count = 0;
  $total_count = sizeof($daily_pageviews);
  $total_pageviews = 0;
  $first_monday_index = -1;
  foreach($daily_pageviews as $pageview)
  {
    $current_date = $start_date_ts + (60 * 60 * 24 * $count);
    $day = date('w', $current_date); // 0 = sun 6 = sat

    if( $day == 1 ) // monday
    {
      if( $first_monday_index == -1 )
      {
        $first_monday_index = $count;
      }
      $labelv .= '|' . urlencode(date('D m/d', $current_date));
      $labelp .= round($count/($total_count-1)*100, 2) . ',';
    }

    if($minvalue > $pageview) $minvalue = $pageview;
    if($maxvalue < $pageview) $maxvalue = $pageview;

    $cvals .= $pageview . ($count < $total_count-1 ? "," : "");
    $count++;
    $total_pageviews += $pageview;
  }

  $labelp = substr($labelp, 0, strlen($labelp)-1); // strip off the last ,

  $bs_toggle_usermeta = get_usermeta($current_user->ID, 'gad_bs_toggle');
  $bs_toggle_option = !isset($bs_toggle_usermeta) || $bs_toggle_usermeta == '' ? wbga_get_option('gad_bs_toggle') : $bs_toggle_usermeta;
  $bs_toggle_option = !isset($bs_toggle_option) || $bs_toggle_option == '' ? 'hide' : $bs_toggle_option;

  $gs_toggle_usermeta = get_usermeta($current_user->ID, 'gad_gs_toggle');
  $gs_toggle_option = !isset($gs_toggle_usermeta) || $gs_toggle_usermeta == '' ? wbga_get_option('gad_gs_toggle') : $gs_toggle_usermeta;
  $gs_toggle_option = !isset($gs_toggle_option) || $gs_toggle_option == '' ? 'show' : $gs_toggle_option;

  $es_toggle_usermeta = get_usermeta($current_user->ID, 'gad_es_toggle');
  $es_toggle_option = !isset($es_toggle_usermeta) || $es_toggle_usermeta == '' ? wbga_get_option('gad_es_toggle') : $es_toggle_usermeta;
  $es_toggle_option = !isset($es_toggle_option) || $es_toggle_option == '' ? 'hide' : $es_toggle_option;
?>

<!--[if IE]><style>
.ie_layout {
  height: 0;
  he\ight: auto;
  zoom: 1;
}
</style><![endif]-->

  <div style="text-align: center;">

  <div style="padding-bottom: 5px;">
    <?php echo $start_date ?> to <?php echo $end_date ?> Pageviews<br/>
      <img width="450" height="200" src="http://chart.apis.google.com/chart?chs=500x200&chf=bg,s,FFFFFF00&cht=lc&chco=0077CC&chd=t:<?php echo $cvals; ?>&chds=<?php echo ($minvalue - 20); ?>,<?php echo ($maxvalue + 20); ?>&chxt=x,y&chxl=0:<?php echo $labelv; ?>&chxr=1,<?php echo $minvalue; ?>,<?php echo $maxvalue; ?>&chxp=0,<?php echo $labelp; ?>&chm=V,707070,0,<?php echo $first_monday_index; ?>:<?php echo $total_count; ?>:7,1|o,0077CC,0,-1.0,6<?php if ($dashdays <= 31) { if ($dashdays <= 10) { ?>|N,FF0000,0,-1,9<?php } else { ?>|N,FF0000,0,-2,9<?php } } ?>"/>
  </div>
  <form method="post">
      <div class="submit">
        <input type="submit" name="WNGA7Day" class="button-primary" value="Last 7 days" />
        <input type="submit" name="WNGA30Day" class="button-primary" value="Last 30 days" />
        <input type="submit" name="WNGA60Day" class="button-primary" value="Last 60 days" />
        <input type="submit" name="WNGA90Day" class="button-primary" value="Last 90 days" />
      </div>
    </form>

  <div style="position: relative; padding-top: 5px;" class="ie_layout">
    <h4 style="position: absolute; top: 6px; left: 10px; background-color: #fff; padding-left: 5px; padding-right: 5px;">Basic Stats <a id="toggle-base-stats" href="#">(<?php echo $bs_toggle_option; ?>)</a></h4>
    <hr style="border: solid #eee 1px"/><br/>
  </div>

  <div>
    <div id="base-stats" <?php if($bs_toggle_option == 'show') echo 'style="display: none"'; ?>>
    <div style="text-align: left;">
      <div style="width: 50%; float: left;">
        <table>
          <tr><td align="right"><?php echo number_format($summary_data['value']['ga:visits']); ?></td><td></td><td>Visits</td></tr>
          <tr><td align="right"><?php echo number_format($total_pageviews); ?></td><td></td><td>Pageviews</td></tr>
          <tr><td align="right"><?php echo (isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits'] > 0) ? round($total_pageviews / $summary_data['value']['ga:visits'], 2) : '0'; ?></td><td></td><td>Pages/Visit</td></tr>
        </table>
      </div>
      <div style="width: 50%; float: right;">
        <table>
          <tr><td align="right"><?php echo (isset($summary_data['value']['ga:entrances']) && $summary_data['value']['ga:entrances'] > 0) ? round($summary_data['value']['ga:bounces'] / $summary_data['value']['ga:entrances'] * 100, 2) : '0'; ?>%</td><td></td><td>Bounce Rate</td></tr>
          <tr><td align="right"><?php echo (isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits']) ? gad_convert_seconds_to_time($summary_data['value']['ga:timeOnSite'] / $summary_data['value']['ga:visits']) : '00:00:00'; ?></td><td></td><td>Avg. Time on Site</td></tr>
          <tr><td align="right"><?php echo (isset($summary_data['value']['ga:visits']) && $summary_data['value']['ga:visits'] > 0) ? round($summary_data['value']['ga:newVisits'] / $summary_data['value']['ga:visits'] * 100, 2) : '0'; ?>%</td><td></td><td>New Visits</td></tr>
        </table>
      </div>
      <br style="clear: both"/>
    </div>
    </div>

  </div>

<?php
if( wbga_get_option('gad_goal_one')  || wbga_get_option('gad_goal_two')  ||
    wbga_get_option('gad_goal_three')  || wbga_get_option('gad_goal_four')  ) 
{
?>
  <div style="position: relative; padding-top: 5px;" class="ie_layout">
    <h4 style="position: absolute; top: 6px; left: 10px; background-color: #fff; padding-left: 5px; padding-right: 5px;">Goals <a id="toggle-goal-stats" href="#">(<?php echo $gs_toggle_option; ?>)</a></h4>
    <hr style="border: solid #eee 1px"/><br/>
  </div>

  <div>
    <div id="goal-stats" <?php if($gs_toggle_option == 'show') echo 'style="display: none"'; ?>>
    <div style="text-align: left;">
      <div style="width: 50%; float: left;">
        <table>
<?php
if( wbga_get_option('gad_goal_one') )
{
  echo '<tr><td>' . wbga_get_option('gad_goal_one') . '</td><td width="20px">&nbsp;</td><td>' . $goal_data['ga:goal1Completions'] . ' (' . round($goal_data['ga:goal1Completions'] / $summary_data['value']['ga:visits'] * 100, 2) . '%)</td></tr>';
}
if( wbga_get_option('gad_goal_two')  )
{
  echo '<tr><td>' . wbga_get_option('gad_goal_two') . '</td><td width="20px">&nbsp;</td><td>' . $goal_data['ga:goal2Completions'] . ' (' . round($goal_data['ga:goal2Completions'] / $summary_data['value']['ga:visits'] * 100, 2) . '%)</td></tr>';
}
?>
        </table>
      </div>
      <div style="width: 50%; float: right;">
        <table>
<?php
if( wbga_get_option('gad_goal_three') )
{
  echo '<tr><td>' . wbga_get_option('gad_goal_three') . '</td><td width="20px">&nbsp;</td><td>' . $goal_data['ga:goal3Completions'] . ' (' .  round($goal_data['ga:goal3Completions'] / $summary_data['value']['ga:visits'] * 100, 2) . '%)</td></tr>';
}
if( wbga_get_option('gad_goal_four') ) 
{
  echo '<tr><td>' . wbga_get_option('gad_goal_four') . '</td><td width="20px">&nbsp;</td><td>' . $goal_data['ga:goal4Completions'] . ' (' .  round($goal_data['ga:goal4Completions'] / $summary_data['value']['ga:visits'] * 100, 2) . '%)</td></tr>';
}
?>
        </table>
      </div>
      <br style="clear: both"/>
    </div>
    </div>
  </div>
<?php
}
?>

  <div style="position: relative; padding-top: 5px;" class="ie_layout">
    <h4 style="position: absolute; top: 6px; left: 10px; background-color: #fff; padding-left: 5px; padding-right: 5px;">Detailed Stats <a id="toggle-extended-stats" href="#">(<?php echo $es_toggle_option; ?>)</a></h4>
    <hr style="border: solid #eee 1px"/><br/>
  </div>

  <div>
    <div id="extended-stats" <?php if($es_toggle_option == 'show') echo 'style="display: none"'; ?>>
      <div style="text-align: left; font-size: 90%;">
        <div style="width: 50%; float: left;">

          <h4 class="heading"><?php echo __( 'Top Posts' ); ?></h4>

          <div style="padding-top: 5px;">
<?php
  $z = 0;
  foreach($pages as $page)
  {
    $url = $page['value'];
    $title = $page['children']['value'];
    $page_views = $page['children']['children']['ga:pageviews'];
    echo '<a href="' . $url . '" target="_blank">' . $title . '</a><br/> <div style="color: #666; padding-left: 5px; padding-bottom: 5px; padding-top: 2px;">' . $page_views . ' views</div>';
    $z++;
    if($z > 10) break;
  }
?>
          </div>
        </div>

        <div style="width: 50%; float: right;">
          <h4 class="heading"><?php echo __( 'Top Searches' ); ?></h4>

          <div style="padding-top: 5px; padding-bottom: 15px;">
            <table width="100%">
<?php
  $z = 0;
  foreach($keywords as $keyword => $count)
  {
    if($keyword != "(not set)")
    {
      echo '<tr>';
      echo '<td>' . $count . '</td><td>&nbsp;</td><td> ' . $keyword . '</td>';
      echo '</tr>';
      $z++;
    }
    if($z > 10) break;
  }
?>
            </table>
          </div>

          <h4 class="heading"><?php echo __( 'Top Referers' ); ?></h4>

          <div style="padding-top: 5px;">
            <table width="100%">
<?php
  $z = 0;
  foreach($sources as $source => $count)
  {
    echo '<tr>';
    echo '<td>' . $count . '</td><td>&nbsp;</td><td> ' . $source . '</td>';
    echo '</tr>';
    $z++;
    if($z > 10) break;
  }
?>
            </table>
          </div>
        </div>
        <br style="clear: both"/>
      </div>
    </div>

  </div>
	<center><b><a href="http://josh-fowler.com/?page_id=70" target="_blank">Web Ninja Google Analytics</a></b></center>
  </div>

<?php
}

function gad_convert_seconds_to_time($time_in_seconds)
{
  $hours = floor($time_in_seconds / (60 * 60));
  $minutes = floor(($time_in_seconds - ($hours * 60 * 60)) / 60);
  $seconds = $time_in_seconds - ($minutes * 60) - ($hours * 60 * 60);

  return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

wbga_debug('Web Ninja Google Analytics initialization');

if (wbga_get_option('check_updates') && wbga_get_option('version_sent')!=wbga_version) {
  wbga_debug('Phone home with version number');
  wbga_set_option('version_sent', wbga_version);
  wbga_check_updates(false);
}

global $wbga_header_hooked;
global $wbga_footer_hooked;
$wbga_header_hooked=false;
$wbga_footer_hooked=false;

add_action('admin_menu', 'wbga_admin');

if (wbga_get_option('enable_tracker') && wbga_get_option('filter_content')) {
  wbga_debug('Adding the_content and the_excerpt filters');
  add_filter('the_content', 'wbga_filter', 50);
  add_filter('the_excerpt', 'wbga_filter', 50);
}
if (wbga_get_option('enable_tracker') && wbga_get_option('filter_comments')) {
  wbga_debug('Adding comment_text filter');
  add_filter('comment_text', 'wbga_filter', 50);
}
if (wbga_get_option('enable_tracker') && wbga_get_option('filter_comment_authors')) {
  wbga_debug('Adding get_comment_author_link filter');
  add_filter('get_comment_author_link', 'wbga_filter', 50);
}

if (wbga_get_option('enable_tracker')) {
  wbga_debug('Adding wp_head and wp_footer action hooks for tracker');
  add_action('wp_head',   'wbga_wp_head_track');
  add_action('wp_footer', 'wbga_wp_footer_track');
}
if (wbga_get_option('track_adm_pages')) {
  wbga_debug('Adding admin_footer action hook for tracker');
  add_action('admin_footer', 'wbga_adm_footer_track');
}
wbga_debug('Adding init action hook');
add_action('init', 'wbga_init');
wbga_debug('Adding shutdown action hook for debugging and notice if wp_footer is hooked');
add_action('shutdown', 'wbga_shutdown');

?>