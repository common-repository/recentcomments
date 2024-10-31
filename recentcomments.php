<?php
/*
Plugin Name: RecentComments
Plugin URI: https://wordpress.org/plugins/recentcomments/
Description: Displays the latest comments, trackbacks, pingbacks in the sidebar of your blog via widget interface or anywhere else via function call. The plugin brings tons of <a href="options-general.php?page=recentcomments/recentcomments.php">options for listing configuration</a> and supports gravatars.
Version: 0.2
Author: kornelly
Author URI: https://profiles.wordpress.org/kornelly/
*/

/**
 * v0.2 2014-10-13 updated to wordpress 4.0
 * v0.1 2009-07-06 initial release
 */
class RecentComments {
  var $id;
  var $title;
  var $version;
  var $name;
  var $url;
  var $options;
  var $locale;
  var $signature;
  
  function RecentComments() {
    $this->id         = 'recentcomments';
    $this->title      = 'RecentComments';
    $this->version    = '0.2';
    $this->plugin_url = 'https://wordpress.org/plugins/recentcomments/';
    $this->name       = 'RecentComments v'. $this->version;
    $this->url        = get_bloginfo('wpurl'). '/wp-content/plugins/' . $this->id;
	  $this->locale     = get_locale();
    $this->path       = dirname(__FILE__);
    $this->signature  = false;

	  if(empty($this->locale)) {
		  $this->locale = 'en_US';
    }

    load_textdomain($this->id, sprintf('%s/%s.mo', $this->path, $this->locale));

    $this->loadOptions();

    if(!is_admin()) {
      add_filter('wp_head', array(&$this, 'blogHeader'));
      add_filter('wp_footer', array(&$this, 'blogFooter'));
    }
    else {
      add_action('admin_menu', array( &$this, 'optionMenu')); 
    }

    add_action('widgets_init', array( &$this, 'initWidgets')); 
  }
  
  function optionMenu() {
    add_options_page($this->title, $this->title, 8, __FILE__, array(&$this, 'optionMenuPage'));
  }

  function getFormField($name, $prefix = '', $field = array()) {
    /*
      title
      field type
      value type
      value
      default value
      description
    */
    if(!empty($prefix)) {
      $name = sprintf('%s[%s]', $prefix, $name);
    }
    
    $id = $name;

    if(empty($field[3]) && $field[3] != '0') {
      $field[3] = $field[4];
    }
    
    $value = $field[3];
    
    $description = empty($field[5]) ? '' : '<br />'. $field[5];

    switch($field[1]) {
      case 'radiogroup':
        $data = '';
        
        foreach($field[4] as $k => $v) {
          $data .= sprintf('<input type="radio" name="%s"%s value="%s" /> %s&#160;', $name, $value == $k ? ' checked="checked"' : '', $k, $v );
        }
        return $data . $description;
      case 'select':
        $data = sprintf('<select name="%s" id="%s">', $name, $id);

        foreach($field[4] as $k => $v) {
          $data .= sprintf( '<option value="%s"%s>%s</option>', $k, $k == $value ? ' selected="selected"' : '', $v );
        }
        return $data. '</select>'. $description;
      case 'checkbox':
        return sprintf('<input type="checkbox" name="%s"%s id="%s" />%s', $name, $value == 1 ? ' checked="checked"' : '', $id, $description);
      case 'hidden':
        return sprintf('<input type="hidden" name="%s" value="%s" id="%s" />%s', $name, $value, $id, $description);
      case 'textarea':
        return sprintf('<textarea name="%s">%s</textarea>%s', $name, $value, $description);
      case 'text':
        return sprintf('<input type="text" name="%s" value="%s" />%s', $name, $value, $description);
      case 'yesnoradio':
        return sprintf('<input type="radio" name="%s"%s value="1" />%s <input type="radio" name="%s"%s value="0" />%s %s', $name, intval($value) == 1 ? ' checked="checked"' : '', __('yes', $this->id), $name, intval($value) == 0 ? ' checked="checked"' : '', __('no', $this->id), $description);
    }
  }

  function optionMenuPage() {
    /*
      title
      field type
      value type
      value
      default value
      description
    */
    
    $fields = array(
      'time_show' => array(__('Show date/time', $this->id), 'yesnoradio', 'integer', 1, 1, ''),
      'time_format' => array( __('Date/time format', $this->id), 'text', 'string', __('m.d.Y', $this->id), __('m.d.Y', $this->id), __('According to the PHP <a href="http://www.php.net/date" target="_blank">date()</a> function.', $this->id)),
      'label1' => 'Gravatars',
      'gravatar_show' => array( __('Show gravatars', $this->id), 'yesnoradio', 'integer', 1, ''),
      'gravatar_default' => array( __('Gravatar default', $this->id), 'select', 'string', 'identicon', array('identicon' => 'identicon', 'monsterid' => 'monsterid', 'wavatar' => 'wavatar'), ''),
      'gravatar_default_url' => array( __('Gravatar default url', $this->id), 'text', 'string', '', '', __('if set overrules the default gravatar selected above', $this->id)),
      'gravatar_rating' => array(__('Gravatar rating', $this->id), 'select', 'string', 'R', array('G' => 'G', 'PG' => 'PG', 'R' => 'R', 'X' => 'X'), '<a href="http://en.wikipedia.org/wiki/Motion_picture_rating_system#Ratings" target="_blank">Motion picture rating system</a> '. __('to ensure the images are SFW', $this->id)),
      'gravatar_size' => array( __('Gravatar size', $this->id), 'text', 'integer', 16, ''),
      
      'label2' => __('Misc. listing - comments, trackbacks, pingbacks mixed', $this->id),
      'misc_title' => array(__('Misc title', $this->id), 'text', 'string', __('Recent activity', $this->id), '', __('The title is shown above the listing in widget mode.', $this->id)),
      'misc_limit' => array(__('Misc limit', $this->id), 'text', 'integer', 5, 5, __('Max. # of items to show', $this->id)),
      'misc_length' => array(__('Misc length', $this->id), 'text', 'integer', 5, 5, __('Max. length of text', $this->id)), 
      'misc_sorting' => array(__('Misc sorting', $this->id), 'select', 'string', 'flat', array('flat' => __('flat', $this->id), 'grouped' => __('grouped', $this->id)), ''),
      'misc_expandable' => array( __('Expandable?', $this->id), 'yesnoradio', 'integer', 1, __('listing expands on mouse over', $this->id)),

      'label3' => __('Comments', $this->id),
      'comment_title' => array(__('Comments title', $this->id), 'text', 'string', __('Recent comments', $this->id), '', __('The title is shown above the listing in widget mode.', $this->id)),
      'comment_limit' => array(__('Comments limit', $this->id), 'text', 'integer', 5, 5, __('Max. # of comments to show', $this->id)),
      'comment_length' => array(__('Comments length', $this->id), 'text', 'integer', 5, 5, __('Max. length of text', $this->id)), 
      'comment_sorting' => array(__('Comments sorting', $this->id), 'select', 'string', 'flat', array('flat' => __('flat', $this->id), 'grouped' => __('grouped', $this->id)), ''),
      'comment_expandable' => array( __('Expandable comments?', $this->id), 'yesnoradio', 'integer', 1, __('listing expands on mouse over', $this->id)),
      'exclude_authors' => array(__('Exclude', $this->id), 'textarea', 'string', '', '', __('Exclude comment authors by email', $this->id)),
      'label4' => __('Trackbacks', $this->id),
      'trackback_title' => array(__('Trackbacks title', $this->id), 'text', 'string', __('Recent trackbacks ', $this->id), '', __('The title is shown above the listing in widget mode.', $this->id)),
      'trackback_limit' => array(__('Trackbacks limit', $this->id), 'text', 'integer', 5, 5, __('Max. # of trackbacks to show', $this->id)),
      'trackback_length' => array(__('Trackbacks length', $this->id), 'text', 'integer', 5, 5, __('Max. length of text', $this->id)),
      'trackback_sorting' => array(__('Trackbacks sorting', $this->id), 'select', 'string', 'flat', array('flat' => __('flat', $this->id), 'grouped' => __('grouped', $this->id)), ''),
      'trackback_expandable' => array( __('Expandable trackbacks?', $this->id), 'yesnoradio', 'integer', 1, __('listing expands on mouse over', $this->id)),
      'label5' => __('Pingbacks', $this->id),
      'pingback_title' => array(__('Pingbacks title', $this->id), 'text', 'string', __('Recent pingbacks ', $this->id), '', __('The title is shown above the listing in widget mode.', $this->id)),
      'pingback_limit' => array(__('Pingbacks limit', $this->id), 'text', 'integer', 5, 5, __('Max. # of pingbacks to show', $this->id)),
      'pingback_length' => array(__('Pingbacks length', $this->id), 'text', 'integer', 5, 5, __('Max. length of text', $this->id)), 
      'pingback_sorting' => array(__('Pingbacks sorting', $this->id), 'select', 'string', 'flat', array('flat' => __('flat', $this->id), 'grouped' => __('grouped', $this->id)), ''),
      'pingback_expandable' => array( __('Expandable pingbacks?', $this->id), 'yesnoradio', 'integer', 1, '', __('listing expands on mouse over', $this->id))
    );
?>
<div class="wrap">
<h2><?=$this->title?></h2>
<div align="center">
  <p><?=$this->name?> <a href="<?=$this->plugin_url?>" target="_blank">Plugin Homepage</a></p>
</div> 
<?php
  if(isset($_POST[$this->id])) {
    $this->updateOptions($_POST[$this->id]);
    
    echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved!', $this->id) . '</strong></p></div>'; 
  }
?>      
<form method="post" action="options-general.php?page=<?=$this->id?>/<?=$this->id?>.php">

<table class="form-table">
<tr valign="top"><td bgcolor="#ffffff" colspan="4"><a href="#integration"><?php _e('Click here for integration instructions.', $this->id); ?></a></td></tr>
<?php

foreach($fields as $k => $v) {
  
  if(ereg("^label", $k)) {
    printf('<tr valign="top"><td bgcolor="#ffffff" colspan="4" style="height:20px;"><h1>%s</h1></td></tr>', $v);
  }
  else {
    $v[3] = $this->options[$k];
  
    if($v[1] == 'checkbox') {
      printf('<tr><th scope="row" colspan="4" class="th-full"><label for="">%s</label></th></tr>', $this->getFormField($k, $this->id, $v));
    }
    else {
      printf('<tr valign="top"><th scope="row">%s</th><td colspan="3">%s</td></tr>', $v[0], $this->getFormField($k, $this->id, $v));
    }
  }
}
?>
</table>
<a name="integration"></a>
<h1><?php _e('Integration', $this->id); ?></h1>
<p><?php _e('Use the sidebar widgets of paste on of the codesnippets below into your template file.', $this->id); ?></p>
<table>
<tr>
  <th align="left"><?php _e('Misc. listing', $this->id); ?></th><td colspan="3">  <code>&lt;?php if(function_exists('recentcomments_display')) recentcomments_display(); ?&gt;</code></td>
</tr>
<tr>
  <th align="left"><?php _e('Comments only', $this->id); ?></th><td colspan="3">  <code>&lt;?php if(function_exists('recentcomments_display')) recentcomments_display('comment'); ?&gt;</code></td>
</tr>
<tr>
  <th align="left"><?php _e('Trackbacks only', $this->id); ?></th><td colspan="3">  <code>&lt;?php if(function_exists('recentcomments_display')) recentcomments_display('trackback'); ?&gt;</code></td>
</tr>
<tr>
  <th align="left"><?php _e('Pingbacks only', $this->id); ?></th><td colspan="3">  <code>&lt;?php if(function_exists('recentcomments_display')) recentcomments_display('pingback'); ?&gt;</code></td>
</tr>
</table>

<p class="submit">
  <input type="submit" name="Submit" value="<?php _e('save', $this->id); ?>" class="button" />
</p>
</form>

</div>
<?php
  }
  
  function updateOptions($options) {
    foreach($this->options as $k => $v) {
      if(array_key_exists( $k, $options)) {
        $this->options[$k] = trim($options[ $k ]);
      }
    }
		update_option($this->id, $this->options);
	}
  
  function loadOptions() {
#  delete_option($this->id);
    $this->options = get_option($this->id);
    if(!$this->options) {

      $this->options = array(
        'installed' => time(),

        'time_show' => 1,
        'time_format' => __('m.d.y', $this->id), # http://www.php.net/date

        'gravatar_default' => 'identicon', # monsterid | wavatar
        'gravatar_default_url' => '',
        'gravatar_rating' => 'R', # G | PG | R | X
        'gravatar_size' => 16,
        'gravatar_show' => 1,
        
        'misc_expandable' => 1,
        'misc_title' => __('Recent activity', $this->id),
        'misc_limit' => 10,
        'misc_length' => 20, 
        'misc_sorting' => 'flat',
        
        'comment_expandable' => 1,
        'comment_title' => __('Recent comments', $this->id),
        'comment_limit' => 10,
        'comment_length' => 20, 
        'comment_sorting' => 'flat',
        
        'trackback_expandable' => 1,
        'trackback_title' => __('Recent trackbacks', $this->id),
        'trackback_limit' => 10,
        'trackback_length' => 20, 
        'trackback_sorting' => 'flat',
        
        'pingback_expandable' => 1,
        'pingback_title' => __('Recent pingbacks', $this->id),
        'pingback_count' => 10,
        'pingback_length' => 20, 
        'pingback_sorting' => 'flat',
        
        'exclude_authors' => ''
			);

      add_option($this->id, $this->options, $this->name, 'yes');
    }
  }

  function initWidgets() {
    if(function_exists('register_sidebar_widget')) {
      register_sidebar_widget($this->options['comment_title'], array($this, 'widgetComments'), null, 'widget_recentcomments');
      register_sidebar_widget($this->options['trackback_title'], array($this, 'widgetTrackbacks'), null, 'widget_recenttrackbacks');
      register_sidebar_widget($this->options['pingback_title'], array($this, 'widgetPingbacks'), null, 'widget_recentpingbacks');
      register_sidebar_widget($this->options['misc_title'], array($this, 'widgetMisc'), null, 'widget_recentmisc');
    }
  }
  
  function widgetMisc($args) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['misc_title'], $after_title, $this->getCode('misc'), $after_widget );
  }
  
  function widgetComments($args) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['comment_title'], $after_title, $this->getCode('comment'), $after_widget );
  }
  
  function widgetPingbacks($args) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['pingback_title'], $after_title, $this->getCode('pingback'), $after_widget );
  }

  function widgetTrackbacks($args) {
    extract($args);
    printf( '%s%s%s%s%s%s', $before_widget, $before_title, $this->options['trackback_title'], $after_title, $this->getCode('trackback'), $after_widget );
  }
  
  function blogFooter() {
    if(in_array('1', array($this->options['comment_expandable'], $this->options['trackback_expandable'], $this->options['pingback_expandable'], $this->options['misc_expandable']))) {
      printf('<script src="%s/js/%s.js" type="text/javascript"></script>', $this->url, $this->id);
    }
  }
  function blogHeader() {
    printf('<meta name="%s" content="%s/%s" />' . "\n", $this->id, $this->id, $this->version);
    printf('<link rel="stylesheet" href="%s/styles/%s.css" type="text/css" media="screen" />'. "\n", $this->url, $this->id);
  }

  function getComments($type, $sorting = 'flat') {
    global $wpdb;

    $limit = intval($this->options[$type. '_limit']);
    
    if($type == 'comment') {
        $sql_type = " AND comment_type NOT IN('trackback', 'pingback') ";
    }
    elseif($type != 'misc') {
      $sql_type = " AND comment_type = '". $type. "' ";
    }
    else {
      $sql_type = '';
    }

    $sql = "
      SELECT
        comment_id,
        comment_date AS date,
        IF(comment_type IN('trackback', 'pingback'), comment_author_url, comment_author) AS author,
        comment_post_id AS post_id,
        IF(comment_type <> '', comment_type, 'comment') AS type,
        MD5(LCASE(comment_author_email)) AS hash
      FROM
        {$wpdb->comments}
      WHERE
        comment_approved = '1'
      {$sql_type}
    ";

    if(in_array($type, array('misc', 'comment'))) {
  
      if(!empty($this->options['exclude_authors'])) {
        $exclude_authors = array();
        foreach(explode( "\n", strtolower($this->options['exclude_authors'])) as $item) {
          $exclude_authors[] = "'". trim($item). "'";
        }
        $sql .= " AND LCASE(comment_author_email) NOT IN(". implode(',', $exclude_authors). ") ";
      }
    }

    $sql .= " ORDER BY comment_date DESC";

    if($limit == 0) {
      $limit = 5;
    }

    if($sorting == 'grouped') {
      $limit *= 3;
    }

    $sql .= " LIMIT {$limit}";

    if($sorting == 'grouped') {
      $limit /= 3;
    }

    $comments = array();
    
    foreach($wpdb->get_results($sql) as $comment) {
      if($sorting == 'grouped') {
        if(count($comments) > $limit-1) {
          break;
        }
        if(count($comments[$comment->post_id]) < $limit) {
          $comments[$comment->post_id][] = $comment;
        }
      }
      else {
        $comments[] = $comment;
      }
    }

    return $comments;
  }
  
  function getGravatar($hash, $author) {
    
    $gravatar = '';
    
    if(intval($this->options['gravatar_show']) == 1) {
      $gravatar = sprintf(
        '<img src="http://www.gravatar.com/avatar/%s.jpg?s=%d&d=%s&r=%s" style="vertical-align: middle;margin: 0 5px 0 0;" alt="%s" width="%d" height="%d" />',
        $hash,
        $this->options['gravatar_size'],
        empty($this->options['gravatar_default_url']) ? $this->options['gravatar_default'] : urlencode($this->options['gravatar_default_url']),
        $this->options['gravatar_rating'],
        $author,
        $this->options['gravatar_size'],
        $this->options['gravatar_size']
      );
    }
    
    return $gravatar;
  }
  
  function formatAuthor($s, $type) {
    if($type == 'comment') {
      if(strlen($s) > $this->options['comment_length']) {
        $s = substr($s, 0, $this->options['comment_length']). '&hellip;';
      }
    }
    else {
      $s = strtolower($s);
      if(substr($s, 0, 7) == 'http://') {
        $s = substr($s, 7);
      }

      if(substr($s, 0, 4) == 'www.') {
        $s = substr($s, 4);
      }
      
      $tokens = explode('/', $s);
      
      $s = $tokens[0];

      if(strlen($s) > $this->options[$type. '_length']) {
        $s = substr($s, 0, $this->options[$type. '_length']). '&hellip;';
      }
    }
    return $s;
  }

  function getCode($type = 'misc') {
    /**
     * sorting can be 'flat' or 'grouped'
     */
    $sorting = $this->options[$type. '_sorting'];

    $comments = $this->getComments($type, $sorting);

    if(is_array($comments) && count($comments) > 0) {

      $expandable = '';

      if(intval($this->options[$type. '_expandable']) == 1) {
        $expandable = '_expandable';
      }
      
      $data = sprintf('<ul class="recentcomment_%s%s">'. "\n", $sorting, $expandable);
      
      if($sorting == 'flat') {
        foreach($comments as $comment) {

          $link = get_permalink($comment->post_id);

          if(!$link) {
            continue;
          }

          $link .= '#comment-'. $comment->comment_id;

          $data .= sprintf(
            '<li>%s<a href="%s" class="author">%s</a>%s<ul><li><a href="%s">%s</a></li></ul></li>',
            in_array($comment->type, array('trackback', 'pingback')) ? '' : $this->getGravatar($comment->hash, $comment->author),
            $link,
            $this->formatAuthor($comment->author, $comment->type),
            intval($this->options['time_show']) == 1 ? ' <small>'. mysql2date($this->options['time_format'], $comment->date). '</small>' : '',
            $link,
            get_the_title($comment->post_id)
          );
        }
      }
      else {
        foreach($comments as $v) {

          $data .= sprintf('<li><a href="%s">%s</a><ul>', get_permalink($v[0]->post_id), get_the_title($v[0]->post_id));
          
          foreach($v as $comment) {

            $data .= sprintf(
              '<li>%s<a href="%s#comment-%d" class="author">%s</a>%s</li>',
              in_array($comment->type, array('trackback', 'pingback')) ? '' : $this->getGravatar($comment->hash, $comment->author),
              get_permalink($comment->post_id),
              $comment->comment_id,
              $this->formatAuthor($comment->author, $comment->type),
              intval($this->options['time_show']) == 1 ? ' <small>'. mysql2date($this->options['time_format'], $comment->date). '</small>' : ''
            );
          }
          
          $data .= '</ul></li>';
        }
      }
      
      $data .= '</ul>';
      
      if(!$this->signature) {
        $data .= '<div><a href="https://wordpress.org/plugins/recentcomments/" target="_blank" class="snap_noshots">Recent comments plugin</a></div>';
        $this->signature = true;
      }

      return '<div id="recent'. $type. '">'. $data . '</div>';
    }

    return '<p>'. __(sprintf('Not found!', $type), $this->id). '</p>';
  }
}

/**
 * @params $what string comment|trackback|pingback|misc
 */
function recentcomments_display($what='misc') {

  global $RecentComments;

  if($RecentComments) {
    echo $RecentComments->getcode($what);
  }
}

add_action( 'plugins_loaded', create_function( '$RecentComments_2qql', 'global $RecentComments; $RecentComments = new RecentComments();' ) );

?>