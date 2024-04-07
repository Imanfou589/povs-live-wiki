from flask import Flask, jsonify
from bs4 import BeautifulSoup
import requests

app = Flask(__name__)

def getCharacters():
    with requests.Session() as se:
        se.headers = {
            "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
            "Accept-Encoding": "gzip, deflate, br",
            "Accept-Language": "en-US,en;q=0.9"
        }

        characters = []
        charactersURL = se.get('https://vorp.fandom.com/tr/wiki/Karakterler')
        soup = BeautifulSoup(charactersURL.content, 'html.parser')

        for i in range(22):
            characterPageMain = f"gallery-{i}"
            characterPageContent = soup.find('div', id=characterPageMain)

            if characterPageContent:
                charactersContent = characterPageContent.find_all('a')
                for character in charactersContent:
                    character_name = character.text.strip()
                    if character_name:
                        characters.append(character_name)
        return characters

def getCharacterDetail(name):
    with requests.Session() as se:
        se.headers = {
            "User-Agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36 Edg/121.0.0.0",
            "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7",
            "Accept-Encoding": "gzip, deflate, br",
            "Accept-Language": "en-US,en;q=0.9"
        }

    editedName2 = "FandomPP-" + name.replace(' ', '_').split("_")[0]
    editedName = name.replace(' ', '_').split("_")[0] + name.replace(' ', '_').split("_")[1][0]
    character_page = se.get(f"https://vorp.fandom.com/tr/wiki/{name.replace(' ', '_')}")
    character_soup = BeautifulSoup(character_page.content, 'html.parser')
    features_data = character_soup.find('aside')
    pTag_data = character_soup.find_all('p')

    channel_data = character_soup.find('a', class_='external free')
    
    if channel_data:
        channel_url = channel_data['href']
        if 'twitch.tv' in channel_url and 'team/vorp' not in channel_url:
            channelName = channel_url.replace('https://www.twitch.tv/', '').replace('http://www.twitch.tv/', '').replace('https://twitch.tv/', '').replace('http://twitch.tv/', '')
            channel = channelName.lower()
    bio = ""
    if pTag_data:
        bio = max(pTag_data, key=lambda p: len(p.get_text())).text.strip()

    features = {}
    if features_data:
        for section in features_data.find_all('section'):
            for item in section.find_all('div', class_='pi-item'):
                label = item.find('h3', class_='pi-data-label').text.strip()
                value = item.find('div', class_='pi-data-value').text.strip()
                features[label] = value
                
    return {'features': features, 'bio': bio, 'channel': channel}



@app.route('/api/characters', methods=['GET'])
def list_characters():
    return getCharacters()

@app.route('/api/characters/<name>', methods=['GET'])
def list_details(name):
    return getCharacterDetail(name)

if __name__ == '__main__':
    app.run()
