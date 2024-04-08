from flask import Flask, jsonify
from bs4 import BeautifulSoup
from flask_cors import CORS
import requests

app = Flask(__name__)
CORS(app)

def getCharacters():
    with requests.Session() as se:
        se.headers = {"cookie": "CONSENT=YES+cb.20230531-04-p0.en+FX+908"}
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
        se.headers = {"cookie": "CONSENT=YES+cb.20230531-04-p0.en+FX+908"}

    editedName2 = "FandomPP-" + name.replace(' ', '_').split("_")[0]
    editedName = name.replace(' ', '_').split("_")[0] + name.replace(' ', '_').split("_")[1][0]
    character_page = se.get(f"https://vorp.fandom.com/tr/wiki/{name.replace(' ', '_')}")
    character_soup = BeautifulSoup(character_page.content, 'html.parser')
    features_data = character_soup.find('aside')
    pTag_data = character_soup.find_all('p')

    channel = None  # Assigning a default value

    channel_data = character_soup.find('a', class_='external free')
    
    if channel_data:
        channel_url = channel_data['href']
        if 'twitch.tv' in channel_url and 'team/vorp' not in channel_url:
            channelName = channel_url.replace('https://www.twitch.tv/', '').replace('http://www.twitch.tv/', '').replace('https://twitch.tv/', '').replace('http://twitch.tv/', '')
            channel = channelName.lower()

    avatar = character_soup.find('img', attrs={'alt': editedName})
    if avatar is None:
        editedName = '3.0'
        avatar = character_soup.find('img', class_={'alt': editedName})
        if avatar is None:
            avatar = character_soup.find('img', attrs={'alt': editedName2})

    if avatar is not None:
        avatar = avatar['src']
    else:
        avatar = False

    bio = max(pTag_data, key=lambda p: len(p.get_text())).text.strip()
    features = {}
    for section in features_data.find_all('section'):
        for item in section.find_all('div', class_='pi-item'):
            label = item.find('h3', class_='pi-data-label').text.strip()
            value = item.find('div', class_='pi-data-value').text.strip()
            features[label] = value
    return {'avatar': avatar, 'features': features,'bio': bio, 'channel': channel}

@app.route('/api/characters', methods=['GET'])
def list_characters():
    return getCharacters()

@app.route('/api/characters/<name>', methods=['GET'])
def list_details(name):
    return getCharacterDetail(name)

if __name__ == '__main__':
    app.run(debug=True)
