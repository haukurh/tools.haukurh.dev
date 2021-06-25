<?php

require_once '../autoload.php';

use Migrate\Dom\Dom;

function noticeMe($errno, $errstr, $errfile, $errline)
{
    if ($errno == 8) {
        throw new Exception("Notice: $errstr");
        return true;
    }
    return false;
}

set_error_handler("noticeMe");

error_reporting(-1);

$input = "";
$output = "";

function renameMonths($string) {
  $isMonths = array('/(jan)(.*) /i', '/(feb)(.*) /i', '/(mar)(.*) /i', '/(apr)(.*) /i', '/(ma)(.*) /i', '/(jún)(.*) /i', '/(júl)(.*) /i', '/(ág)(.*) /i', '/(Ág)(.*) /i', '/(sep)(.*) /i', '/(okt)(.*) /i', '/(nóv)(.*) /i', '/(des)(.*) /i');
  $months = array("January ", "February ", "March ", "April ", "May ", "June ", " July ", "August ", "August ", "September ", "October ", "November ", "December ");
  if(preg_replace($isMonths, $months, $string) != false) {
    return preg_replace($isMonths, $months, $string);
  }
  return $string;
}

if(isset($_POST['input'])) {
    $input = $_POST['input'];
    switch ($_POST['task']) {
        case "HTML Entity encode":
            $output = preg_replace('/&(\w+;)/', '&amp;$1', htmlentities($input, ENT_QUOTES, "UTF-8"));
            break;
        case "HTML Entity decode":
            $input = preg_replace('/&(\w+;)/', '&amp;$1', $input);
            $output = html_entity_decode($input);
            break;
	case "quoted-printable encode":
	    $output = quoted_printable_encode($input);
	    break;
	case "quoted-printable decode":
	    $output = quoted_printable_decode($input);
	    break;
        case "URL encode":
            $output = urlencode($input);
            break;
        case "URL decode":
            $output = urldecode($input);
            break;
        case "Hash":
            $output = "SHA1: " . sha1($input) . "\n";
            $output .= "MD5: " . md5($input) . "\n";
            break;
        case "base64 encode":
            $output = base64_encode($input);
            break;
        case "base64 decode":
            $output = base64_decode($input);
            break;
        case "UTF-8 encode":
            $output = utf8_encode($input);
            break;
        case "UTF-8 decode":
            $output = utf8_decode($input);
            break;
        case "Time to date":
            if($input == "") { 
                $input = time();
            }
            if(is_numeric($input)) {
                $output = date("H:i:s d.m.Y", $input);
            }
            break;
        case "String to time":
            $lines = explode("\n", $input);
            foreach ($lines as $key => $value) {
              if(strtotime(renameMonths($value)) != false) {
                $lines[$key] = strtotime(renameMonths($value));
              }
            }
            $output = implode("\n", $lines);
            break;
        case "JSON encode":
            $output = json_encode($input);
            break;
        case "JSON decode":
            $output = json_decode($input, true);
            break;
        case "Serialize":
            $output = serialize($input);
            break;
        case "Unserialize":
            try {
                $output = unserialize($input);
            } catch (Exception $e) {
                $output = $e->getMessage();
            }
            break;
        case "Lowercase":
            $tmp = mb_strtolower($input, "UTF-8");
            $tmp_first = mb_strtoupper(mb_substr($tmp, 0, 1, "UTF-8"), "UTF-8");
            $tmp_rest = mb_substr($tmp, 1, mb_strlen($tmp, "UTF-8")-1, "UTF-8");
            $output = $tmp_first . $tmp_rest;
            break;
        case "Uppercase":
            $output = mb_strtoupper($input, "UTF-8");
            break;
        case "User agent":
            $output = $_SERVER['HTTP_USER_AGENT'];
            break;
        case "Strip tags":
            $output = strip_tags($input);
            $input = "";
            break;
        case "Sort ASC":
            $tmp = array_filter(explode("\n", $input), function($v) {
              if(trim($v) != "") {
                return $v;
              }
            });
            $collator = new Collator('is_IS');
            $collator->sort($tmp);
            $output = implode("\n", $tmp);
            break;
        case "Sort DESC":
            $tmp = array_filter(explode("\n", $input), function($v) {
              if(trim($v) != "") {
                return $v;
              }
            });
            $collator = new Collator('is_IS');
            $collator->sort($tmp);
            $tmp = array_reverse($tmp);
            $output = implode("\n", $tmp);
            break;
        case "Remove duplicates":
            $tmp = explode("\n", $input);
            foreach ($tmp as $key => $value) {
              if(trim($value) == "") {
                unset($tmp[$key]);
              } else {
                $tmp[$key] = trim($tmp[$key]);
              }
            }
            $unique = array_unique($tmp);
            $output = implode("\n", $unique);
            break;
        case "Grab emails":
            preg_match_all('/[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}/', $input, $emails);
            foreach ($emails[0] as $key => $value) {
                $emails[0][$key] = strtolower($value);
            }
            $emails = array_unique($emails[0]);
            sort($emails);
            $numItems = count($emails);
            $i = 0;
            $output = "";
            foreach ($emails as $value) {
                $output .= $value;
                if(++$i !== $numItems) {
                    $output .= "\n";
                }
            }
            break;
        case "Clean HTML":
            if (!empty($input)) {
                $dom = new Dom($input);
                $output = html_entity_decode($dom->getHTML());
            }
            //$output = strip_tags($input, "<hr><br><p><img><a><h1><h2><h3><h4><h5><h6><strong><table><th><tr><td><caption><colgroup><col><thead><tbody><tfoot>");
            //$output = preg_replace("/ style=\"(.*?)\"/s", "", $output);
            //$output = preg_replace("/ align=\"(.*?)\"/s", "", $output);
            //$output = preg_replace("/(^\s+|\s+$)/m", "", $output);
            break;
        case "Lorem Ipsum":
            $input = "";
            $output = <<<EOL
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque rhoncus dui vitae tincidunt pulvinar. Duis sem dui, aliquam at ipsum at, porttitor molestie ex. Curabitur dapibus justo sed purus vestibulum sollicitudin. In sed nisl non eros sodales consequat in at augue. Cras rutrum leo nunc, quis varius mi scelerisque non. Maecenas eleifend facilisis elit non luctus. Donec vel mauris sit amet diam ultricies imperdiet ac ut libero. Quisque quis quam mauris. Phasellus egestas, quam eget pulvinar tincidunt, libero elit malesuada magna, ac condimentum libero ipsum in erat. Cras at risus congue, vestibulum velit et, eleifend sem. Curabitur condimentum turpis tellus, vitae lobortis purus pharetra a. Mauris efficitur tortor ut mauris vestibulum hendrerit. Suspendisse purus erat, placerat quis faucibus a, porta et diam.

Praesent vel tortor arcu. Donec molestie lectus eget diam finibus, id ornare orci maximus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Fusce at libero vel sapien congue dignissim. Vestibulum vitae ullamcorper odio, sit amet condimentum erat. Nulla non placerat tortor. Vestibulum enim nisi, dictum ullamcorper tellus in, fringilla egestas leo. Integer et augue in leo vehicula maximus. In facilisis metus quam, id pretium neque ultrices tincidunt. Curabitur eleifend nisi vitae elit imperdiet egestas. Maecenas vehicula ipsum id magna ornare, vitae viverra justo bibendum. Aliquam a dapibus nibh, id rhoncus nulla. Maecenas lacinia, nunc sit amet luctus commodo, mi ante egestas turpis, ut congue mauris elit ut lectus. Ut id pulvinar quam, in aliquam justo. Nullam non purus vestibulum, cursus erat nec, tempor purus.

Nam sit amet erat at lorem pretium pellentesque quis vitae ipsum. Mauris facilisis nulla sit amet maximus porta. Maecenas eget magna quis odio iaculis interdum nec eu neque. Aenean vel porttitor metus, ac posuere quam. Nullam iaculis, enim in semper feugiat, leo turpis aliquet nisl, aliquam interdum nisi ex at lectus. Morbi non dui odio. Curabitur ut aliquam erat. Donec eu luctus purus. Duis ex ex, tempus vel justo quis, tempus gravida quam. Etiam diam dui, molestie at ornare et, tempus vitae tortor. Suspendisse quis turpis a neque sollicitudin varius. Etiam quis leo at urna lacinia placerat. Cras pulvinar purus vel erat condimentum, facilisis pulvinar turpis hendrerit.

In rutrum est non dolor aliquam, a feugiat massa lacinia. Mauris vel nisi feugiat, volutpat nibh tincidunt, vestibulum mi. Nullam in metus in mauris hendrerit sollicitudin. Etiam eget elit at dui vestibulum euismod euismod porta nisi. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Nam scelerisque semper commodo. Aliquam convallis nulla in fringilla aliquet. Praesent porttitor, nibh eu sagittis dapibus, enim ex rhoncus ligula, eu volutpat felis ligula et libero. Vestibulum at justo a libero interdum tempus vel eu magna. Donec sed pulvinar ante. Sed feugiat tincidunt eros. Mauris ultrices rhoncus tempor. Suspendisse in finibus nisl.

Cras fringilla arcu nec sem sollicitudin accumsan. In auctor eu neque vitae interdum. Curabitur suscipit massa vitae urna suscipit, at sodales turpis commodo. Nunc condimentum elementum justo quis laoreet. Duis ex tellus, mollis non congue vel, suscipit et enim. In pharetra dictum odio, ut tristique sem faucibus sed. Duis id nisl et diam efficitur malesuada sit amet sollicitudin diam. Nulla sed volutpat massa, et venenatis sem.
EOL;
            break;
        case "Reverse":
            $inputArray = array_reverse(explode("\n", $input));
            $output = implode("\n", $inputArray);
            break;
        case "Reset":
            $input = "";
            $output = "";
            break;
    }
}

?><!DOCTYPE HTML>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="description" content="Online tools." />
  <title>Online tools</title>
  <link href="favicon.png" rel="icon" type="image/png" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="style.css?v=1.0.4" type="text/css" media="screen" />
  <script type="text/javascript" src="js/beautify.js" async></script>
  <script type="text/javascript" src="js/beautify-css.js" async></script>
  <script type="text/javascript" src="js/beautify-html.js" async></script>
  <script type="text/javascript">
    var beautify_in_progress = false;
    function beautify() {
      if (beautify_in_progress) return;
        beautify_in_progress = true;
        var source = document.getElementById("input").value,
            output,
            opts = {
  indent_size: 2,
  indent_char: " ",
  max_preserve_newlines: 5,
  preserve_newlines: true,
  keep_array_indentation: false,
  break_chained_methods: false,
  indent_scripts: "normal",
  brace_style: "collapse",
  space_before_conditional: true,
  unescape_strings: false,
  jslint_happy: false,
  end_with_newline: false,
  wrap_line_length: "0",
  indent_inner_html: false,
  comma_first: false,
  e4x: false,
  indent_empty_lines: false
};
        if (looks_like_html(source)) {
          output = html_beautify(source, opts);
        } else {
          output = js_beautify(source, opts);
        }
        document.getElementById("output").value = output;
        beautify_in_progress = false;
      }
    function looks_like_html(source) {
      var trimmed = source.replace(/^[ \t\n\r]+/, '');
      var comment_mark = '<' + '!-' + '-';
      return (trimmed && (trimmed.substring(0, 1) === '<' && trimmed.substring(0, 4) !== comment_mark));
    }
  </script>
</head>

<body>
<main>
  <h1>Online tools</h1>
  <form method="post" action="?">
    <label for="input">Input</label>
    <textarea name="input" placeholder="Input..." autofocus="autofocus" id="input"><?php echo $input; ?></textarea>
    <div id="buttons">
      <input type="submit" name="task" value="HTML Entity encode" title="Convert all applicable characters to HTML entities" />
      <input type="submit" name="task" value="HTML Entity decode" title="Convert all HTML entities to their applicable characters" />
      <input type="submit" name="task" value="quoted-printable encode" title="Convert a 8 bit string to a quoted-printable string" />
      <input type="submit" name="task" value="quoted-printable decode" title="Convert a quoted-printable string to an 8 bit string" />
      <input type="submit" name="task" value="URL encode" title="URL-encodes string" />
      <input type="submit" name="task" value="URL decode" title="Decodes URL-encoded string" />
      <input type="submit" name="task" value="base64 encode" title="Encodes data with MIME base64" />
      <input type="submit" name="task" value="base64 decode" title="Decodes data encoded with MIME base64" />
      <input type="submit" name="task" value="UTF-8 encode" title="Encodes an ISO-8859-1 string to UTF-8" />
      <input type="submit" name="task" value="UTF-8 decode" title="Converts a string with ISO-8859-1 characters encoded with UTF-8 to single-byte ISO-8859-1" />
      <input type="submit" name="task" value="JSON encode" title="Returns the JSON representation of a value" />
      <input type="submit" name="task" value="JSON decode" title="Decodes a JSON string" />
      <input type="submit" name="task" value="Serialize" title="Generates a storable representation of a value" />
      <input type="submit" name="task" value="Unserialize" title="Creates a PHP value from a stored representation" />
      <input type="submit" name="task" value="Lowercase" title="Make a string lowercase" />
      <input type="submit" name="task" value="Uppercase" title="Make a string uppercase" />
      <input type="submit" name="task" value="Sort ASC" title="Sort lines in ascending order" />
      <input type="submit" name="task" value="Sort DESC" title="Sort lines in descending order" />
      <input type="submit" name="task" value="Hash" title="Calculate the MD5 and SHA1 hash of a string" />
      <input type="submit" name="task" value="Time to date" title="Unix timestamp to human readable format, H:i:s d.m.Y" />
      <input type="submit" name="task" value="String to time" title="Parse about any English textual datetime description into a Unix timestamp" />
      <input type="submit" name="task" value="User agent" title="Return browser user agent" />
      <input type="submit" name="task" value="Strip tags" title="Strip HTML and PHP tags from a string" />
      <input type="submit" name="task" value="Grab emails" title="Grab all emails from a string and remove duplicates" />
      <input type="submit" name="task" value="Remove duplicates" title="Remove duplicate lines" />
      <input type="button" name="task" value="Beautify JS/HTML" onClick="beautify()" title="Unpack minified JavaScript or HTML code" />
      <input type="submit" name="task" value="Lorem Ipsum" title="5 paragraphs of Lorem Ipsum text" />
      <input type="submit" name="task" value="Reverse" title="Reverse content" />
      <input type="submit" name="task" value="Clean HTML" title="Remove any styling and uncommon HTML code" />
      <input type="submit" name="task" value="Reset" title="Reset form" />
    </div><!-- #buttons -->
  </form>
  <label for="output">Output</label>
  <textarea id="output" placeholder="Output..."><?php if(is_array($output)) { print_r($output); } else { echo $output; } ?></textarea>
  <div id="ip">Your public IP address: <?php echo $_SERVER['REMOTE_ADDR']; ?></div>
</main>
</body>
</html>
