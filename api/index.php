<?php

include 'simple_html_dom.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

function fetchCharacterDetails($characterName) {
    $encodedName = urlencode($characterName);
    $characterPageUrl = "https://vorp.fandom.com/tr/wiki/$encodedName";
    $htmlContent = getWebPage($characterPageUrl);

    if (!$htmlContent) {
        return null;
    }

    $html = str_get_html($htmlContent);

    $featuresData = $html->find('aside', 0);
    $twitchChannel = null;
    $channelData = $html->find('a.external.free', 0);

    if ($channelData) {
        $channelUrl = $channelData->href;
        if (strpos($channelUrl, 'twitch.tv') !== false && strpos($channelUrl, 'team/vorp') === false) {
            $channelName = str_replace(['https://www.twitch.tv/', 'http://www.twitch.tv/', 'https://twitch.tv/', 'http://twitch.tv/'], '', $channelUrl);
            $twitchChannel = strtolower($channelName);
        }
    }

    $avatarUrls = [];
    foreach ($html->find('figure.pi-item img.pi-image-thumbnail') as $img) {
        $avatarUrl = $img->getAttribute('src');
        if ($avatarUrl) {
            $avatarUrls[] = $avatarUrl;
        }
    }

    $features = [];
    foreach ($featuresData->find('section') as $section) {
        foreach ($section->find('div.pi-item') as $item) {
            $label = trim($item->find('h3.pi-data-label', 0)->plaintext);
            $value = trim($item->find('div.pi-data-value', 0)->plaintext);
            $features[$label] = $value;
        }
    }

    // Yalnızca <p> etiketlerini al
    $paragraphs = [];
    foreach ($html->find('p') as $p) {
        $paragraphs[] = $p->plaintext;
    }

    return ['name' => $_GET['name'], 'avatar' => $avatarUrls, 'features' => $features, 'bio' => implode("\n", $paragraphs), 'channel' => $twitchChannel];
}

function getWebPage($url) {
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_ENCODING       => "",
        CURLOPT_USERAGENT      => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3",
        CURLOPT_AUTOREFERER    => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT        => 120,
    );

    $curl = curl_init($url);
    curl_setopt_array($curl, $options);

    $content = curl_exec($curl);

    if (curl_errno($curl)) {
        return false;
    }

    curl_close($curl);
    return $content;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['name'])) {
        $characterName = $_GET['name'];
        $characterDetails = fetchCharacterDetails($characterName);

        if ($characterDetails) {
            header('Content-Type: application/json');
            echo json_encode($characterDetails);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(['error' => 'Character not found']);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode("For character wiki information: ?name={characterName_LastName}");
    }
}
?>
