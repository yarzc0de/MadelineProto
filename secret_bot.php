#!/usr/bin/env php
<?php
/*
Copyright 2016-2018 Daniil Gentili
(https://daniil.it)
This file is part of MadelineProto.
MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Affero General Public License for more details.
You should have received a copy of the GNU General Public License along with MadelineProto.
If not, see <http://www.gnu.org/licenses/>.
*/
set_include_path(get_include_path().':'.realpath(dirname(__FILE__).'/MadelineProto/'));
require_once 'vendor/autoload.php';
if (file_exists('web_data.php')) {
    require_once 'web_data.php';
}

echo 'Deserializing MadelineProto from s.madeline...'.PHP_EOL;
$MadelineProto = false;

try {
    $MadelineProto = new \danog\MadelineProto\API('s.madeline');
} catch (\danog\MadelineProto\Exception $e) {
    \danog\MadelineProto\Logger::log($e->getMessage());
}
if (file_exists('.env')) {
    echo 'Loading .env...'.PHP_EOL;
    $dotenv = new Dotenv\Dotenv(getcwd());
    $dotenv->load();
}

echo 'Loading settings...'.PHP_EOL;
$settings = json_decode(getenv('MTPROTO_SETTINGS'), true) ?: [];

if ($MadelineProto === false) {
    echo 'Loading MadelineProto...'.PHP_EOL;
    $MadelineProto = new \danog\MadelineProto\API($settings);
    if (getenv('TRAVIS_COMMIT') == '') {
        $sentCode = $MadelineProto->phone_login(readline('Enter your phone number: '));
        \danog\MadelineProto\Logger::log($sentCode, \danog\MadelineProto\Logger::NOTICE);
        echo 'Enter the code you received: ';
        $code = fgets(STDIN, (isset($sentCode['type']['length']) ? $sentCode['type']['length'] : 5) + 1);
        $authorization = $MadelineProto->complete_phone_login($code);
        \danog\MadelineProto\Logger::log($authorization, \danog\MadelineProto\Logger::NOTICE);
        if ($authorization['_'] === 'account.noPassword') {
            throw new \danog\MadelineProto\Exception('2FA is enabled but no password is set!');
        }
        if ($authorization['_'] === 'account.password') {
            \danog\MadelineProto\Logger::log('2FA is enabled', \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_2fa_login(readline('Please enter your password (hint '.$authorization['hint'].'): '));
        }
        if ($authorization['_'] === 'account.needSignup') {
            \danog\MadelineProto\Logger::log('Registering new user', \danog\MadelineProto\Logger::NOTICE);
            $authorization = $MadelineProto->complete_signup(readline('Please enter your first name: '), readline('Please enter your last name (can be empty): '));
        }
    } else {
        $MadelineProto->bot_login(getenv('BOT_TOKEN'));
    }
}
$message = (getenv('TRAVIS_COMMIT') == '') ? 'I iz works always (io laborare sembre) (yo lavorar siempre) (mi labori ĉiam) (я всегда работать) (Ik werkuh altijd) (Ngimbonga ngaso sonke isikhathi ukusebenza)' : ('Travis ci tests in progress: commit '.getenv('TRAVIS_COMMIT').', job '.getenv('TRAVIS_JOB_NUMBER').', PHP version: '.getenv('TRAVIS_PHP_VERSION'));

$MadelineProto->session = 's.madeline';

$sent = [-440592694 => true];

$offset = 0;
while (true) {
    try {
        $updates = $MadelineProto->get_updates(['offset' => $offset, 'limit' => 50, 'timeout' => 0]); // Just like in the bot API, you can specify an offset, a limit and a timeout
        //\danog\MadelineProto\Logger::log($updates);
        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1; // Just like in the bot API, the offset must be set to the last update_id
            switch ($update['update']['_']) {
                /*case 'updateNewChannelMessage':
                    if ($update['update']['message']['out'] || $update['update']['message']['message'] === '') continue;
                    $MadelineProto->messages->sendMessage(['peer' => $update['update']['message']['to_id'], 'message' => $update['update']['message']['message']]);
                    break;
                case 'updateNewMessage':
                    if ($update['update']['message']['out'] || $update['update']['message']['message'] === '') {
                        continue;
                    }
                    break;*/
                case 'updateNewEncryptedMessage':
                    \danog\MadelineProto\Logger::log($MadelineProto->download_to_dir($update['update']['message'], '.'));
                    if (isset($sent[$update['update']['message']['chat_id']])) {
                        continue;
                    }
    $secret = $update['update']['message']['chat_id'];
    $secret_media = [];

    // Photo uploaded as document, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/faust.jpg', 'fausticorn.jpg'); // This gets an inputFile object with file name magic
    $secret_media['document_photo'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/faust.jpg'), 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'faust.jpg', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeImageSize', 'w' => 1280, 'h' => 914]]]]];

    // Photo, secret chat
    $secret_media['photo'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaPhoto', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'caption' => 'This file was uploaded using MadelineProto', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'size' => filesize('tests/faust.jpg'), 'w' => 1280, 'h' => 914]]];

    // GIF, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/pony.mp4');
    $secret_media['gif'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/pony.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/pony.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'pony.mp4', 'size' => filesize('tests/faust.jpg'), 'attributes' => [['_' => 'documentAttributeAnimated']]]]];

    // Sticker, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/lel.webp');
    $secret_media['sticker'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/lel.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/lel.webp'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'lel.webp', 'size' => filesize('tests/lel.webp'), 'attributes' => [['_' => 'documentAttributeSticker', 'alt' => 'LEL', 'stickerset' => ['_' => 'inputStickerSetEmpty']]]]]];

    // Document, secrey chat
    $time = time();
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/60', 'magic'); // This gets an inputFile object with file name magic
    \danog\MadelineProto\Logger::log(time() - $time);
    $secret_media['document'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => 'magic/magic', 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'magic.magic', 'size' => filesize('tests/60'), 'attributes' => [['_' => 'documentAttributeFilename', 'file_name' => 'fairy']]]]];

    // Video, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/swing.mp4');
    $secret_media['video'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/swing.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/swing.mp4'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'swing.mp4', 'size' => filesize('tests/swing.mp4'), 'attributes' => [['_' => 'documentAttributeVideo', 'duration' => 5, 'w' => 1280, 'h' => 720]]]]];

    // audio, secret chat
    $inputEncryptedFile = $MadelineProto->upload_encrypted('tests/mosconi.mp3');
    $secret_media['audio'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => false, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

    $secret_media['voice'] = ['peer' => $secret, 'file' => $inputEncryptedFile, 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => '', 'media' => ['_' => 'decryptedMessageMediaDocument', 'thumb' => file_get_contents('tests/faust.preview.jpg'), 'thumb_w' => 90, 'thumb_h' => 90, 'mime_type' => mime_content_type('tests/mosconi.mp3'), 'caption' => 'test', 'key' => $inputEncryptedFile['key'], 'iv' => $inputEncryptedFile['iv'], 'file_name' => 'mosconi.mp3', 'size' => filesize('tests/mosconi.mp3'), 'attributes' => [['_' => 'documentAttributeAudio', 'voice' => true, 'duration' => 1, 'title' => 'AH NON LO SO IO', 'performer' => 'IL DIO GERMANO MOSCONI']]]]];

    foreach ($secret_media as $type => $smessage) {
        $type = $MadelineProto->messages->sendEncryptedFile($smessage);
    }

                    $i = 0;
                    while ($i < $argv[1]) {
                        echo "SENDING MESSAGE $i TO ".$update['update']['message']['chat_id'].PHP_EOL;
                        $MadelineProto->messages->sendEncrypted(['peer' => $update['update']['message']['chat_id'], 'message' => ['_' => 'decryptedMessage', 'ttl' => 0, 'message' => (string) ($i++)]]);
                    }
                    $sent[$update['update']['message']['chat_id']] = true;
           }
        }
    } catch (\danog\MadelineProto\RPCErrorException $e) {
        \danog\MadelineProto\Logger::log($e);
    } catch (\danog\MadelineProto\Exception $e) {
        \danog\MadelineProto\Logger::log($e->getMessage());
    }
    //sleep(1);
}
