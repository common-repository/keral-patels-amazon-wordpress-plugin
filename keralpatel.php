<?php
/*
Plugin Name: Keral Patel Amazon Wordpress Plugin
Plugin URI: http://www.keralpatel.com/amazon-plugin-by-keral-patel/
Description: Lets you put amazon products right into your blog posts.
Version: 1.1
Author: Keral. Patel.
Author URI: http://www.keralpatel.com
*/ 

/*  Copyright 2008  Keral Patel  (email : specialseo@hotmail.com)

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
add_filter('the_content', 'amz_parse_content', 1);
add_action('admin_menu', 'add_admin_pages');
// posts and content get sent to this function which will look for our bbcode
function amz_parse_content($content) {
if(strpos($content, "<!--amz") !== FALSE)
{
//<!--amz Beauty:lipstick:5-->
$content = preg_replace("/(<!--)(amz)([^}}].*?|[^-->].*?)(-->)/ei", "amz_parse_page('\\2', '\\3')", $content);
}//if strpos
return $content;
}
function amz_parse_page($first, $second)
{
if($first == "amz")
{
//extract variables and call amazon
$second = trim($second);
$arr = explode (":", $second);
}
else
{
$con = "";
return $con;
}
//Build URL
$options = get_option('kp_amazon');
$url = "http://ecs.amazonaws.com/onca/xml?Service=AWSECommerceService&SearchIndex=" . $arr[0] . "&AWSAccessKeyId=" . $options['accesskey'] . "&Operation=ItemSearch&AssociateTag=" . $options['associd'] . "&ResponseGroup=Medium&Number=" . $arr[2] . "&Keywords=" . $arr[1];
$amzconts = file_get_contents($url);
$amzresults = xml2array($amzconts);
$con = "<table cellpadding='2' cellspacing='2' border='0' width='100%'>";
$i = 0;
$cntr = $arr[2] - 1;
for($i == 0; $i <= $cntr; $i++)
{
$link = $amzresults['ItemSearchResponse']['Items']['Item'][$i]['DetailPageURL']['value'];
$image =  $amzresults['ItemSearchResponse']['Items']['Item'][$i]['SmallImage']['URL']['value'];
$title = $amzresults['ItemSearchResponse']['Items']['Item'][$i]['ItemAttributes']['Title']['value'];
$price = $amzresults['ItemSearchResponse']['Items']['Item'][$i]['OfferSummary']['LowestNewPrice']['value'];
$desc = $amzresults['ItemSearchResponse']['Items']['Item'][$i]['EditorialReviews']['EditorialReview']['Content']['value'];
$con .= "<tr><td class='para'><b><a href='$link'>$title</a></b></td></tr>
<tr><td valign='top' class='para'><a href='$link'><img src='$image' border='0' align='left'></a>$desc<br />$price</td></tr>";
}//for
$con .= "</table>";
return $con;
}
function xml2array($contents, $get_attributes=1)
{ // Taken from http://www.bin-co.com/php/scripts/xml2array/
if(!$contents) return array(); if(!function_exists('xml_parser_create'))
{
return array(); 
} 
$parser = xml_parser_create();
xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 ); 
xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 ); 
xml_parse_into_struct( $parser, $contents, $xml_values ); 
xml_parser_free( $parser );
if(!$xml_values) return; 
$xml_array = array(); 
$parents = array(); 
$opened_tags = array(); 
$arr = array(); 
$current = &$xml_array; 
foreach($xml_values as $data)
{
unset($attributes,$value);
extract($data);
$result = '';
if($get_attributes)
{
$result = array();
if(isset($value)) $result['value'] = $value;if(isset($attributes))
{
foreach($attributes as $attr => $val)
{
if($get_attributes == 1) $result['attr'][$attr] = $val;
}
}
}
elseif(isset($value))
{
$result = $value;
}
if($type == "open")
{
$parent[$level-1] = &$current;if(!is_array($current) or (!in_array($tag, array_keys($current))))
{
$current[$tag] = $result; $current = &$current[$tag];
}
else
{
if(isset($current[$tag][0]))
{
array_push($current[$tag], $result);
}
else
{
$current[$tag] = array($current[$tag],$result);
}
$last = count($current[$tag]) - 1;
$current = &$current[$tag][$last];
}
}
elseif($type == "complete")
{
if(!isset($current[$tag]))
{
$current[$tag] = $result;
}
else
{
if((is_array($current[$tag]) and $get_attributes == 0) or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1))
{
array_push($current[$tag],$result);
}
else
{
$current[$tag] = array($current[$tag],$result);
}
}
}
elseif($type == 'close')
{
$current = &$parent[$level-1];
}
}
return($xml_array);
} 
function add_admin_pages() {
add_options_page('KP Amazon', 'Keral Patel Amazon Plugin', 10, __FILE__, 'amazon_options_page');
add_option('kp_amazon', amazon_default_options(), 'Options for the Keral Patel Amazon plugin');
}
function amazon_default_options() {
$options = array();
$options['accesskey'] = "0YVMVC19F3M2C5BMCP82";
$options['associd'] = "juhycom-20";
return $options;
}
function amazon_options_page() {
if ( isset($_POST['submitted']) ) {
$options = array();
$options['accesskey'] = $_POST['accesskey'];
$options['associd'] = $_POST['associd'];
update_option('kp_amazon', $options);
echo '<div id="message" class="updated fade"><p><strong>Plugin settings saved.</strong></p></div>';
}
$options = get_option('kp_amazon');
$action_url = $_SERVER[PHP_SELF] . '?page=' . basename(__FILE__);
?>
<div class='wrap'>
<h2>Amazon Shop</h2>
<p><cite>Keral Patel Amazon Plugin</cite> automatically fetches relevant amazon products with your associate ID and displays them under your post.</p>
<form name="kpamazon" action="<?php echo $action_url; ?>" method="post">
<input type="hidden" name="submitted" value="1" />
<fieldset class="options">
<legend>Settings</legend>
<ul>
<li>
<label for="amz">
Your Amazon Access Key Here : 
<input type="text" id="accesskey" name="accesskey" size="20" maxlength="20" value="<?php echo $options['accesskey']; ?>" />
</label>
</li>
<li>
<label for="associd">
Your Amazon Assoc ID here : 
<input type="text" id="associd" name="associd" size="10" maxlength="50"value="<?php echo $options['associd']; ?>" />
</label>
</li>
</ul>
<script type="text/javascript">
<!--
function amz_set_defaults() {
document.getElementById("accesskey").value = "0YVMVC19F3M2C5BMCP82";
document.getElementById("associd").value = "juhycom-20";
}
document.write('<p><input type="button" name="Defaults" value="Use Defaults" onclick="amz_set_defaults(); return false;" /></p>');
//-->
</script>
<noscript>
<p><strong>Defaults:</strong> Default Set.</p>
</noscript>
</fieldset>
<p class="submit"><input type="submit" name="Submit" value="Save changes &raquo;" /></p>
</form>
</div>
<?php
}
?>