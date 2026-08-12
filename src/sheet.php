<?php

if( file_exists('install.php') )
{
	header("Location: install.php");
	exit;
}

$game = $_GET['p'];

// Language logic

include 'lang/TranslateTool.php';
$language = TranslateTool::loadLanguage(isset($_GET['l']) ? $_GET['l'] : null, 'sheet.php');
$languageQuery = ($language != TranslateTool::getDefaultLanguage() ? '?l='. $language : '');

if (file_exists($game.'/data-'. $language .'.xml'))
	$xml = simplexml_load_file($game.'/data-'. $language .'.xml');
else if (file_exists($game.'/data.xml'))
	$xml = simplexml_load_file($game.'/data.xml');

if( !isset($xml) )
{
	if( $game == "credits" )
	{
		echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Thanks!</title>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/uikit/1.2.0/css/uikit.gradient.min.css" rel="stylesheet" type="text/css">
		<link href="style.css" rel="stylesheet" type="text/css">
	</head>

	<body>
		<div class="uk-container uk-container-center">
			<div class="uk-grid">
			</div>
		</div>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".uk-grid").load("credits.php");
			});
		</script>
	</body>
</html>';
		exit;		
	}
	else if( is_dir($game) && $game != "lang" && $game != "images" && $game != "trailers" && $game != "_template" )
	{
		echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>Instructions</title>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/uikit/1.2.0/css/uikit.gradient.min.css" rel="stylesheet" type="text/css">
		<link href="style.css" rel="stylesheet" type="text/css">
	</head>

	<body>
		<div class="uk-container uk-container-center">
			<div class="uk-grid">
			</div>
		</div>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script type="text/javascript">
			$(function() {
				$(".uk-grid").load("create.php?s=installation");

				setInterval(function() {
					$(".uk-grid").load("create.php?s=installation");
				}, 5000);
			});
		</script>
	</body>
</html>';

		// Todo: These steps will fail if safemode is turned on
		if( !is_dir($game.'/images') ) {
			mkdir($game.'/images');
		}
		if( !is_dir($game.'/trailers') ) {
			mkdir($game.'/trailers');
		}
		if( !file_exists($game.'/_data.xml') ) {
			copy('_template/_data.xml',$game.'/_data.xml');
		}

		exit;
	}
	else
	{
		header("Location: index.php");
		exit;
	}
}

// Set default value for monetize
$monetize = 0;

foreach( $xml->children() as $child )
{
	switch( $child->getName() )
	{
		case("title"):
			define("GAME_TITLE", $child);
			break;	
		case("release-date"):
			define("GAME_DATE", $child);
			break;
		case("website"):
			define("GAME_WEBSITE", $child);
			// LOCAL CHANGE: see the matching note in index.php.
			define("GAME_WEBSITE_LABEL", isset($child->attributes()->label)
				? (string)$child->attributes()->label
				: trim(parseLink($child), "/"));
			break;
		case("platforms"):
			$platforms = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$platforms[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;
		case("prices"):
			$prices = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$prices[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;
		case("description"):
			define("GAME_DESCRIPTION", $child);
			break;
		case("history"):
			define("GAME_HISTORY", $child);
			break;
		case("histories"):
			$histories = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$histories[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;
		case("features"):
			$features = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$features[$i] = $subchild;
				$i++;
			}
			break;	
		case("trailers"):
			$trailers = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$trailers[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
		case("awards"):
			$awards = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$awards[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
		case("quotes"):
			$quotes = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$quotes[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
		case("monetization-permission"):
			if( strtolower($child) == "false" ) $monetize = 1;
			else if( strtolower($child) == "ask") $monetize = 2;
			else if( strtolower($child) == "non-commercial") $monetize = 3;
			else if( strtolower($child) == "monetize") $monetize = 4;
			break;
		case("additionals"):
			$additionals = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$additionals[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
		case("credits"):
			$credits = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$credits[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
	}
}

if (file_exists('data-'. $language .'.xml'))
	$xml = simplexml_load_file('data-'. $language .'.xml');
else
	$xml = simplexml_load_file('data.xml');

foreach( $xml->children() as $child )
{
	switch( $child->getName() )
	{
		case("title"):
			define("COMPANY_TITLE", $child);
			break;	
		case("based-in"):
			define("COMPANY_BASED", $child);
			break;
		case("description"):
			define("COMPANY_DESCRIPTION", $child);
			break;
		case("analytics"):
			define("ANALYTICS", $child);
			break;
		case("contacts"):
			$contacts = array();
			$i = 0;
			foreach( $child->children() as $subchild )
			{
				$contacts[$i][$subchild->getName()] = $subchild;
				$i++;
			}
			break;					
	}
}

// LOCAL CHANGE: body of the Logo & Icon section. Filtered by filename prefix
// because that folder also holds the screenshots.
function renderLogoBody($dir, $zipName, $zipBase, $emptyHtml)
{
	renderAssetBody('logo', $dir, $zipName, $zipBase, $emptyHtml, array('logo', 'icon'));
}

// LOCAL CHANGE: heading plus body for one extra asset section (Capsules, Elements).
// No upstream equivalent.
function renderAssetSection($id, $heading, $dir, $zipName, $zipBase, $emptyHtml, $justified = false)
{
	echo '					<hr>

					<h2 id="'. $id .'">'. $heading .'</h2>';

	renderAssetBody($id, $dir, $zipName, $zipBase, $emptyHtml, null, $justified);
}

/*
 * Renders one folder of artwork as a grid of uniform tiles.
 *
 * Displayable files become thumbnails; anything else (.psd, .ai, .zip) is listed
 * as a plain download link so source files are never silently dropped.
 *
 * $zipBase is the untranslated "download ... (%s)" string, translated in here
 * because the file size is only known at this point. $prefixes, when given,
 * restricts the listing to filenames starting with one of them.
 */
function renderAssetBody($id, $dir, $zipName, $zipBase, $emptyHtml, $prefixes = null, $justified = false)
{

	if( file_exists($dir .'/'. $zipName) )
	{
		$filesize = filesize($dir .'/'. $zipName);
		if( $filesize > 1024 && $filesize < 1048576 ) {
			$filesize = (int)( $filesize / 1024 ).'KB';
		}
		if( $filesize > 1048576 ) {
			$filesize = (int)(( $filesize / 1024 ) / 1024 ).'MB';
		}

		echo '<a href="'. $dir .'/'. rawurlencode($zipName) .'"><div class="uk-alert">'. tl($zipBase, $filesize) .'</div></a>';
	}

	$images = $files = array();

	if( is_dir($dir) && ($handle = opendir($dir)) )
	{
		while( false !== ($entry = readdir($handle)) )
		{
			if( $entry == '.' || $entry == '..' || $entry == 'README.txt' || $entry == $zipName ) continue;

			// When $prefixes is set the folder is shared with other sections, so take
			// only the files whose name starts with one of them.
			if( is_array($prefixes) )
			{
				$match = false;
				foreach( $prefixes as $p ) {
					if( strpos(strtolower($entry), $p) === 0 ) $match = true;
				}
				if( !$match ) continue;
			}

			// Unlike the screenshot loop, .jpg is accepted: Steam exports are
			// routinely JPEG and dropping them silently would be a trap.
			$ext = strtolower( pathinfo($entry, PATHINFO_EXTENSION) );
			if( in_array($ext, array('png', 'gif', 'jpg', 'jpeg', 'svg', 'webp')) ) $images[] = $entry;
			else if( $ext != '' ) $files[] = $entry;
		}
		closedir($handle);
	}

	// Sorted because readdir() order is arbitrary and docs/ is committed to git;
	// unsorted output would reshuffle the HTML on unrelated rebuilds.
	sort($images);
	sort($files);

	if( $justified )
	{
		/*
		 * Justified rows: every image keeps its true aspect ratio, and each row is
		 * scaled to fill the full width, so the block as a whole reads as a rectangle.
		 *
		 * Each item's flex-basis is its aspect ratio times a nominal row height, and
		 * flex-grow is the same ratio. Items on a row therefore grow by an identical
		 * factor, keeping their widths proportional to their real shapes. Nothing is
		 * cropped and nothing is letterboxed.
		 *
		 * Deliberately NOT class "images": presskit() runs Masonry over .images and
		 * would absolutely-position these, collapsing the container height and pulling
		 * the next heading up into the grid.
		 */
		echo '<div class="assets assets-justified assets-'. $id .'">';

		foreach( $images as $entry )
		{
			$url = $dir .'/'. rawurlencode($entry);

			// Real pixel dimensions, read at build time. Square is a safe fallback for
			// anything getimagesize() cannot parse (e.g. SVG).
			$size = @getimagesize($dir .'/'. $entry);
			$ratio = ( $size && $size[1] > 0 ) ? $size[0] / $size[1] : 1;

			echo '<div class="asset" style="flex-grow: '. round($ratio * 100) .'; flex-basis: '. round($ratio * 130) .'px;">'
				.'<a href="'. $url .'"><img src="'. $url .'" alt="'. htmlspecialchars($entry) .'" loading="lazy" /></a></div>';
		}
	}
	else
	{
		// Upstream's own layout: half-width cells, packed by Masonry.
		echo '<div class="uk-grid images">';

		foreach( $images as $entry )
		{
			// Filenames routinely contain spaces ("Main Capsule.png"), so the URL is
			// encoded while the alt text keeps the readable name.
			$url = $dir .'/'. rawurlencode($entry);
			echo '<div class="uk-width-medium-1-2"><a href="'. $url .'"><img src="'. $url .'" alt="'. htmlspecialchars($entry) .'" /></a></div>';
		}
	}

	echo '</div>';

	foreach( $files as $entry )
	{
		$size = filesize($dir .'/'. $entry);
		$size = $size > 1048576 ? (int)($size / 1048576).'MB' : (int)($size / 1024).'KB';
		echo '<p><a href="'. $dir .'/'. rawurlencode($entry) .'">'. htmlspecialchars($entry) .'</a> ('. $size .')</p>';
	}

	if( count($images) + count($files) == 0 ) {
		echo '<p class="images-text">'. $emptyHtml .'</p>';
	}
}

function parseLink($uri)
{
    $parsed = trim($uri);
    if( strpos($parsed, "https://") === 0 )
        $parsed = substr($parsed, 7);
    if (strpos($parsed, "https://") === 0 )
        $parsed = substr($parsed, 8);
    if( strpos($parsed, "www.") === 0 )
        $parsed = substr($parsed, 4);
    if( strrpos($parsed, "/") == strlen($parsed) - 1)
        $parsed = substr($parsed, 0, strlen($parsed) - 1);
    if( substr($parsed,-1,1) == "/" )
    	$parsed = substr($parsed, 0, strlen($parsed) - 1);
    
    return $parsed;
}

echo '<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
		<title>'. COMPANY_TITLE .'</title>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/uikit/1.2.0/css/uikit.gradient.min.css" rel="stylesheet" type="text/css">
		<link href="style.css" rel="stylesheet" type="text/css">
	</head>

	<body>
		<div class="uk-container uk-container-center">
			<div class="uk-grid">
				<div id="navigation" class="uk-width-medium-1-4">
					<h1 class="nav-header">'. COMPANY_TITLE .'</h1>
					<a class="nav-header" href="index.php'. $languageQuery .'" target="_self">'. tl('press kit') .'</a></strong>
					<ul class="uk-nav uk-nav-side">';

if (count(TranslateTool::getLanguages()) > 1) {
	echo '<li class="language-select"><a>'. tl('Language: ') .'<select onchange="document.location = \'sheet.php?p='. htmlspecialchars($game) .'&l=\'+ this.value;">';
	foreach (TranslateTool::getLanguages() as $tag => $name)
	{
		echo '<option value="'. $tag .'" '. ($tag == $language ? 'selected':'') .'>'. htmlspecialchars($name) .'</option>';
	}
	echo '</select></a></li>';
	echo '<li class="uk-nav-divider"></li>';
}
		
echo '					<li><a href="#factsheet">'. tl('Factsheet') .'</a></li>
						<li><a href="#description">'. tl('Description') .'</a></li>
						<li><a href="#history">'. tl('History') .'</a></li>
						<li><a href="#projects">'. tl('Projects') .'</a></li>
						<li><a href="#trailers">'. tl('Videos') .'</a></li>
						<li><a href="#images">'. tl('Images') .'</a></li>
						<li><a href="#capsules">'. tl('Capsules') .'</a></li>
						<li><a href="#elements">'. tl('Elements') .'</a></li>
						<li><a href="#logo">'. tl('Logo & Icon') .'</a></li>';
if( $monetize >= 1) { echo '<li><a href="#monetize">'. tl('Monetization Permission') .'</a></li>'; }
echo '						<li><a href="#links">'. tl('Additional Links') .'</a></li>
						<li><a href="#credits">'. tl('Team') .'</a></li>
						<li><a href="#contact">'. tl('Contact') .'</a></li>
					</ul>
				</div>
				<div id="content" class="uk-width-medium-3-4">';

if( file_exists($game."/images/header.png") ) {
	echo '<img src="'.$game.'/images/header.png" class="header">';
}

echo '					<div class="uk-grid">
						<div class="uk-width-medium-2-6">
							<h2 id="factsheet">'. tl('Factsheet'). '</h2>
							<p>
								<strong>'. tl('Developer:'). '</strong><br/>
								<a href="index.php'. $languageQuery .'">'. COMPANY_TITLE .'</a><br/>
								'. tl('Based in %s', COMPANY_BASED) .'
							</p>
							<p>
								<strong>'. tl('Release date:'). '</strong><br/>
								'. GAME_DATE .'
							</p>

							<p>
								<strong>'. tl('Platforms:'). '</strong><br />';

for( $i = 0; $i < count($platforms); $i++ )
{
	$name = $link = "";
	foreach( $platforms[$i]['platform']->children() as $child )
	{
		if( $child->getName() == "name" ) {
			$name = $child;
		} else if( $child->getName() == "link" ) {
			$link = $child;
		}
	}
	echo '<a href="https://'.parseLink($link).'">'.$name.'</a><br/>';
}

echo '							</p>
							<p>
								<strong>'. tl('Website:'). '</strong><br/>
								<a href="https://'. parseLink(GAME_WEBSITE) .'">'. GAME_WEBSITE_LABEL .'</a>
							</p>
							<p>
								<strong>'. tl('Regular Price:'). '</strong><br/>';

if( count($prices) == 0 )
{
	echo '-';
}
else
{
	echo '<table>';
	for( $i = 0; $i < count($prices); $i++ )
	{
		$currency = $value = "";

		foreach( $prices[$i]['price']->children() as $child )
		{
			if( $child->getName() == "currency" ) {
				$currency = $child;
			} else if( $child->getName() == "value" ) {
				$value = $child;
			}
		}
		echo '<tr><td>'.$currency.'</td><td>'.$value.'</td></tr>';
	}
	echo'</table>';
}

echo'							</p>
						</div>
						<div class="uk-width-medium-4-6">
							<h2 id="description">'. tl('Description'). '</h2>
							<p>'. GAME_DESCRIPTION .'</p>
							<h2 id="history">'. tl('History'). '</h2>';

for( $i = 0; $i < count($histories); $i++ )
{
	$header = $text ="";

	foreach( $histories[$i]['history']->children() as $child )
	{
		if( $child->getName() == "header" ) $header = $child;
		else if( $child->getName() == "text" ) $text = $child;
	}
	echo '<strong>'.$header.'</strong>
<p>'.$text.'</p>';
}

if( defined("GAME_HISTORY") ) {
	echo '<p>'. GAME_HISTORY .'</p>';
}

// LOCAL CHANGE: upstream ran a second, identical loop over $histories here, so
// every <histories> block on a game page rendered twice. Removed. Blocks now
// render once, above the singular <history> field if both are present.

echo '							<h2>'. tl('Features'). '</h2>
							<ul>';

for( $i = 0; $i < count($features); $i++ )
{
	echo '<li>'.$features[$i].'</li>';
}

echo '							</ul>
						</div>
					</div>

					<hr>

					<h2 id="trailers">'. tl('Videos'). '</h2>';

if( count($trailers) == 0 )
{
	echo '<p>'. tlHtml('There are currently no trailers available for %s. Check back later for more or <a href="#contact">contact us</a> for specific requests!', GAME_TITLE) .'</p>';
}
else
{
	for( $i = 0; $i < count($trailers); $i++ )
	{
		$name = $youtube = $vimeo = $mov = $mp4 = "";
		$ytfirst = -1;

		foreach( $trailers[$i]['trailer']->children() as $child )
		{
			if( $child->getName() == "name" ) {
				$name = $child;
			} else if( $child->getName() == "youtube" ) { 
				$youtube = $child; 
			
				if( $ytfirst == -1 ) { 
					$ytfirst = 1; 
				} 
			} else if( $child->getName() == "vimeo" ) {
				$vimeo = $child; if( $ytfirst == -1 ) {
					$ytfirst = 0;
				}
			} else if( $child->getName() == "mov" ) {
				$mov = $child;
			} else if( $child->getName() == "mp4" ) {
				$mp4 = $child;
			}
		}
				
		if( strlen($youtube) + strlen($vimeo) > 0 )				
		{
			echo '<p><strong>'.$name.'</strong>&nbsp;';
			$result = "";

			if( strlen( $youtube ) > 0 ) {
				$result .= '<a href="https://www.youtube.com/watch?v='.$youtube.'">YouTube</a>, ';
			}
			if( strlen( $vimeo ) > 0 ) {
				$result .= '<a href="https://www.vimeo.com/'.$vimeo.'">Vimeo</a>, ';
			}
			if( strlen( $mov ) > 0 ) {
				$result .= '<a href="'.$game.'/trailers/'.$mov.'">.mov</a>, ';
			}
			if( strlen( $mp4 ) > 0 ) {
				$result .= '<a href="'.$game.'/trailers/'.$mp4.'">.mp4</a>, ';
			}

			echo substr($result, 0, -2);

			if( $ytfirst == 1 ) 
			{
				echo '<div class="uk-responsive-width iframe-container">
		<iframe src="https://www.youtube.com/embed/'. $youtube .'" frameborder="0" allowfullscreen></iframe>
</div>';
			} elseif ( $ytfirst == 0 ) {
				echo '<div class="uk-responsive-width iframe-container">
		<iframe src="https://player.vimeo.com/video/'.$vimeo.'" frameborder="0" allowfullscreen></iframe>
</div>';
			}
			echo '</p>';
		}				
	}
}

echo '					<hr>

					<h2 id="images">'. tl('Images') .'</h2>';

if( file_exists($game."/images/images.zip") )
{
	$filesize = filesize($game."/images/images.zip");
	if( $filesize > 1024 && $filesize < 1048576 ) {
		$filesize = (int)( $filesize / 1024 ).'KB';
	}
	if( $filesize > 1048576 ) {
		$filesize = (int)(( $filesize / 1024 ) / 1024 ).'MB';
	}

	echo '<a href="'. $game .'/images/images.zip"><div class="uk-alert">'. tl('download all screenshots & photos as .zip (%s)', $filesize) .'</div></a>';
}

echo '<div class="uk-grid images">';
if ($handle = opendir($game.'/images'))
{
	$found = 0;
	/* This is the correct way to loop over the directory. */
	while (false !== ($entry = readdir($handle)))
	{
		if( substr($entry,-4) == ".png" || substr($entry,-4) == ".gif" )
		{
			if( substr($entry,0,4) != "logo" && substr($entry,0,4) != "icon" && substr($entry,0,6) != "header" )
			{	
				echo '<div class="uk-width-medium-1-2"><a href="'. $game .'/images/'. $entry .'"><img src="'. $game .'/images/'.$entry.'" alt="'.$entry.'" /></a></div>';
				$found++;
			}
		}
	}
}
echo '</div>';

closedir($handle);

if ($found == 0) {
	echo '<p class="images-text">'. tlHtml('There are currently no screenshots available for %s. Check back later for more or <a href="#contact">contact us</a> for specific requests!', GAME_TITLE) .'</p>';
}

// LOCAL CHANGE: extra asset sections with no upstream equivalent (Steam capsules,
// and the art elements the capsules are built from). Neither is a screenshot, so
// each gets its own heading and its own folder under <game>/images/.
renderAssetSection('capsules', tl('Capsules'), $game .'/images/capsules', 'capsules.zip',
	'download capsules as .zip (%s)',
	tlHtml('There are currently no capsules available for %s. Check back later for more or <a href="#contact">contact us</a> for specific requests!', GAME_TITLE));

// Justified: these are many small parts at wildly different shapes, so pack them
// into full-width rows rather than giving each a half-page cell.
renderAssetSection('elements', tl('Elements'), $game .'/images/elements', 'elements.zip',
	'download elements as .zip (%s)',
	tlHtml('There are currently no elements available for %s. Check back later for more or <a href="#contact">contact us</a> for specific requests!', GAME_TITLE),
	true);

echo '					<hr>

					<h2 id="logo">'. tl('Logo & Icon') .'</h2>';

// LOCAL CHANGE: upstream showed only the two exact filenames logo.png and
// icon.png. This renders every logo and icon images in the folder, so variants like
// "logo glow.png" appear too. The screenshot loop already skips anything starting
// with logo/icon, so those files were otherwise invisible on the page.
renderLogoBody($game .'/images', 'logo.zip', 'download logo files as .zip (%s)',
	tlHtml('There are currently no logos or icons available for %s. Check back later for more or <a href="#contact">contact us</a> for specific requests!', GAME_TITLE));

echo '<hr>';

if( count( $promoterawards ) + count( $awards ) > 0 )
{
	echo('<h2 id="awards">'. tl('Awards & Recognition') .'</h2>');
	echo('<ul>');

	if( count($promoterawards) >= 0 )
	{
		for( $i = 0; $i < count($promoterawards); $i++ )
		{
			$description = $info = "";
			foreach( $promoterawards[$i]['award']->children() as $child )
			{
				if( $child->getName() == "title" ) {
					$description = $child;
				} else if( $child->getName() == "location" ) {
					$info = $child;
				} else if( $child->getName() == "url" ) {
					$url = $child;
				} else if( $child->getName() == "year" ) {
					$year = $child;
				}
			}
			echo '<li>"'.$description.'" <cite>'.$info.'</cite></li>';
		}			
	}
	
	if( count($awards) > 0 )
	{
		for( $i = 0; $i < count($awards); $i++ )
		{
			$description = $info = "";
			foreach( $awards[$i]['award']->children() as $child )
			{
				if( $child->getName() == "description" ) {
					$description = $child;
				} else if( $child->getName() == "info" ) {
					$info = $child;
				}
			}
			echo '<li>"'.$description.'" <cite>'.$info.'</cite></li>';
		}
	}
	
	echo '</ul>';
	echo '<hr>';
}

if( count($promoterquotes) + count($quotes) > 0 )
{
	echo '					<hr>
			
						<h2>'. tl('Selected Articles') .'</h2>
						<ul>';

	if( count($promoterquotes) >= 0 )
	{
		for( $i = 0; $i < count($promoterquotes); $i++ )
		{
			$name = $description = $website = $link = "";
			foreach( $promoterquotes[$i]['review']->children() as $child )
			{
				if( $child->getName() == "quote" ) {
					$description = $child;
				} else if( $child->getName() == "reviewer-name" ) {
					$name = $child;
				} else if( $child->getName() == "publication-name" ) {
					$website = $child;
				} else if( $child->getName() == "url" ) {
					$link = $child;
				}
			}
			echo '<li>"'.$description.'" <br/>
	<cite>- '.$name.', <a href="https://'.parseLink($link).'">'.$website.'</a></cite></li>';
		}
	}
	
	if( count($quotes) > 0 )
	{
		for( $i = 0; $i < count($quotes); $i++ )
		{
			$name = $description = $website = $link = "";
			foreach( $quotes[$i]['quote']->children() as $child )
			{
				if( $child->getName() == "description" ) {
					$description = $child;
				} else if( $child->getName() == "name" ) {
					$name = $child;
				} else if( $child->getName() == "website" ) {
					$website = $child;
				} else if( $child->getName() == "link" ) {
					$link = $child;
				}
			}
			echo '<li>"'.$description.'" <br/>
	<cite>- '.$name.', <a href="https://'.parseLink($link).'">'.$website.'</a></cite></li>';
		}
	}
	
	echo '</ul>';
	echo '<hr>';
}


if( $monetize >= 1 )
{
	echo '<h2 id="monetize">'. tl('Monetization Permission') .'</h2>';
	if( $monetize == 1 ) echo('<p>'. tl('%s does currently not allow for the contents of %s to be published through video broadcasting services.', COMPANY_TITLE, GAME_TITLE) .'</p>');
	if( $monetize == 2 ) echo('<p>'. tl('%s does allow the contents of this game to be published through video broadcasting services only with direct written permission from %s. Check at the bottom of this page for contact information.', COMPANY_TITLE, GAME_TITLE) .'</p>');
	if( $monetize == 3 ) echo('<p>'. tl('%s allows for the contents of %s to be published through video broadcasting services for non-commercial purposes only. Monetization of any video created containing assets from %s is not allowed.', COMPANY_TITLE, GAME_TITLE, GAME_TITLE) .'</p>');
	if( $monetize == 4 ) echo('<p>'. tl('%s allows for the contents of %s to be published through video broadcasting services for any commercial or non-commercial purposes. Monetization of videos created containing assets from %s is legally & explicitly allowed by %s.', COMPANY_TITLE, GAME_TITLE, GAME_TITLE, COMPANY_TITLE) .' '. tlHtml('This permission can be found in writing at <a href="%s">%s</a>.', 'https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 'https://'. $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) .'</p>');
	echo '<hr>';
}


echo '					<h2 id="links">'. tl('Additional Links'). '</h2>';
		
for( $i = 0; $i < count($additionals); $i++ )
{
	$title = $description = $link = "";
	foreach( $additionals[$i]['additional']->children() as $child )
	{
		if( $child->getName() == "title" ) {
			$title = $child;
		} else if( $child->getName() == "description" ) {
			$description = $child;
		} else if( $child->getName() == "link" ) {
			$link = $child;
		}
	}

	if( strpos(parseLink($link),'/') !== 0 ) {
		$linkTitle = substr(parseLink($link),0,strpos(parseLink($link),'/'));
	} else { $linkTitle = $link; }
	
	echo '<p>
	<strong>'.$title.'</strong><br/>
	'.$description.' <a href="https://'.parseLink($link).'" alt="'.parseLink($link).'">'.$linkTitle.'</a>.
</p>';
}

// LOCAL CHANGE: the "About <company>" section was removed at Victor's request. It
// held the studio boilerplate and a "More information" link back to the studio
// page; both duplicate what index.html already says, and the sidebar "press kit"
// link plus the Developer row in the factsheet still link there. The upstream
// markup is in git history if it is ever wanted back.
echo '					<hr>

					<div class="uk-grid">
						<div class="uk-width-medium-1-2">
							<h2 id="credits">'. tl('%s Credits', GAME_TITLE) .'</h2>';

for( $i = 0; $i < count($credits); $i++ )
{
	$previous = $website = $person = $role = "";
	foreach( $credits[$i]['credit']->children() as $child )
	{
		if( $child->getName() == "person" ) {
			$person = $child;
		} else if( $child->getName() == "previous" ) {
			$previous = $child;
		} else if( $child->getName() == "website" ) {
			$website = $child;
		} else if( $child->getName() == "role" ) {
			$role = $child;
		}
	}

	echo '<p>';
				
	if( strlen($website) == 0 )
	{
		echo '<strong>'.$person.'</strong><br/>'.$role;
	}
	else
	{
		echo '<strong>'.$person.'</strong><br/><a href="https://'.parseLink($website).'">'.$role.'</a>';
	}

	echo '</p>';
}

echo '						</div>
						<div class="uk-width-medium-1-2">
							<h2 id="contact">'. tl('Contact') .'</h2>';

for( $i = 0; $i < count($contacts); $i++ )
{
	$link = $mail = $name = $linkLabel = "";
	foreach( $contacts[$i]['contact']->children() as $child )
	{
		if( $child->getName() == "name" ) {
			$name = $child;
		} else if( $child->getName() == "link" ) {
			$link = $child;
			// LOCAL CHANGE: optional label="..." attribute, as on <website>.
			if( isset($child->attributes()->label) ) $linkLabel = (string)$child->attributes()->label;
		} else if( $child->getName() == "mail" ) {
			$mail = $child;
		}
	}

	echo '<p>';

	if( strlen($link) == 0 && strlen($mail) > 0 ) {
		echo '<strong>'.$name.'</strong><br/><a href="mailto:'.$mail.'">'.$mail.'</a>';
	}
	if( strlen($link) > 0 && strlen($mail) == 0 ) {
		echo '<strong>'.$name.'</strong><br/><a href="https://'.parseLink($link).'">'.( strlen($linkLabel) > 0 ? $linkLabel : parseLink($link) ).'</a>';
	}

	echo '</p>';
}

echo '						</div>
					</div>

					<hr>

					<p><a href="https://dopresskit.com/">presskit()</a> by Rami Ismail (<a href="https://www.vlambeer.com/">Vlambeer</a>) - also thanks to <a href="sheet.php?p=credits">these fine folks</a></p>
				</div>
			</div>
		</div>

		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/3.0.4/jquery.imagesloaded.js"></script>		
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/masonry/3.1.2/masonry.pkgd.min.js"></script>
		<script type="text/javascript">
			$( document ).ready(function() {
				var container = $(\'.images\');

				container.imagesLoaded( function() {
					container.masonry({
						itemSelector: \'.uk-width-medium-1-2\',
					});
				});
			});
		</script>';
if ( defined("ANALYTICS") && strlen(ANALYTICS) > 10 )
{
	echo '<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push([\'_setAccount\', \'' . ANALYTICS . '\']);
	_gaq.push([\'_trackPageview\']);

	(function() {
		var ga = document.createElement(\'script\'); ga.type = \'text/javascript\'; ga.async = true;
		ga.src = (\'https:\' == document.location.protocol ? \'https://ssl\' : \'https://www\') + \'.google-analytics.com/ga.js\';
		var s = document.getElementsByTagName(\'script\')[0]; s.parentNode.insertBefore(ga, s);
	})();
</script>';
}
echo'	</body>
</html>';

?>
