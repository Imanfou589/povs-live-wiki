<?php

include 'simple_html_dom.php';

function getCharacters() {
    $characters = [];
    $charactersURL = 'https://vorp.fandom.com/tr/wiki/Karakterler';
    $html = file_get_html($charactersURL);

    for ($i = 0; $i < 50; $i++) {
        $characterPageMain = "gallery-$i";
        $characterPageContent = $html->find("#$characterPageMain", 0);

        if ($characterPageContent) {
            $charactersContent = $characterPageContent->find('a');
            foreach ($charactersContent as $character) {
                $character_name = trim($character->plaintext);
                if ($character_name) {
                    $characters[] = $character_name;
                }
            }
        }
    }

    return $characters;
}

function getCharacterDetail($name) {
    $encoded_name = urlencode($name);
    $character_page_url = "https://vorp.fandom.com/tr/wiki/$encoded_name";
    $html = str_get_html(get_web_page($character_page_url));

    if (!$html) {
        return null;
    }

    $features_data = $html->find('aside', 0);
    $pTag_data = $html->find('p');
    $channel = null;
    $channel_data = $html->find('a.external.free', 0);

    if ($channel_data) {
        $channel_url = $channel_data->href;
        if (strpos($channel_url, 'twitch.tv') !== false && strpos($channel_url, 'team/vorp') === false) {
            $channelName = str_replace(['https://www.twitch.tv/', 'http://www.twitch.tv/', 'https://twitch.tv/', 'http://twitch.tv/'], '', $channel_url);
            $channel = strtolower($channelName);
        }
    }

    $avatar_urls = [];
    foreach ($html->find('figure.pi-item img.pi-image-thumbnail') as $img) {
        $avatar_url = $img->getAttribute('src');
        if ($avatar_url) {
            $avatar_urls[] = $avatar_url;
        }
    }

    $bio = '';
    foreach ($pTag_data as $p) {
        $text = trim($p->plaintext);
        if (strlen($text) > strlen($bio)) {
            $bio = $text;
        }
    }

    $features = [];
    foreach ($features_data->find('section') as $section) {
        foreach ($section->find('div.pi-item') as $item) {
            $label = trim($item->find('h3.pi-data-label', 0)->plaintext);
            $value = trim($item->find('div.pi-data-value', 0)->plaintext);
            $features[$label] = $value;
        }
    }

    return ['avatar' => $avatar_urls, 'features' => $features, 'bio' => $bio, 'channel' => $channel];
}

function get_web_page($url) {
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

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);

    $content = curl_exec($ch);

    if (curl_errno($ch)) {
        return false;
    }

    curl_close($ch);
    return $content;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['name'])) {
        $name = $_GET['name'];
        $character_details = getCharacterDetail($name);

        if ($character_details) {
            header('Content-Type: application/json');
            echo json_encode($character_details);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(['error' => 'Character not found']);
        }
    } else {
        $characters_list = getCharacters();
        header('Content-Type: application/json');
        echo json_encode($characters_list);
    }
}

?>
