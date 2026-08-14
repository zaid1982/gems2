<?php

require_once __DIR__ . '/../src/JWT.php';

use Firebase\JWT\JWT;

class FcmClient {

    private static $accessToken = null;
    private static $tokenExpiry = 0;

    /**
     * @return array
     */
    private static function loadConfig (): array {
        $defaults = array(
            'project_id' => 'gems-eace5',
            'project_number' => '899105022758',
            'service_account_path' => '',
            'legacy_server_key' => '',
            'cafile' => '',
        );
        $config = @parse_ini_file(__DIR__ . '/../library/config.ini', true);
        if (!empty($config['fcm']) && is_array($config['fcm'])) {
            return array_merge($defaults, $config['fcm']);
        }
        return $defaults;
    }

    /**
     * Resolve CA bundle for curl on Windows/XAMPP (avoids "unable to get local issuer certificate").
     */
    private static function resolveCaFile (array $config): string {
        $candidates = array();
        if (!empty($config['cafile'])) {
            $candidates[] = trim($config['cafile']);
        }
        $candidates[] = __DIR__ . '/../library/certs/cacert.pem';
        $candidates[] = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';
        $candidates[] = 'C:\\xampp\\apache\\bin\\curl-ca-bundle.crt';

        $iniCainfo = trim((string) ini_get('curl.cainfo'));
        if ($iniCainfo !== '') {
            $candidates[] = $iniCainfo;
        }
        $iniCafile = trim((string) ini_get('openssl.cafile'));
        if ($iniCafile !== '') {
            $candidates[] = $iniCafile;
        }

        foreach ($candidates as $path) {
            if ($path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }
        return '';
    }

    /**
     * @param resource|\CurlHandle $curl
     * @param array $config
     */
    private static function applyCurlSslOptions ($curl, array $config): void {
        $caFile = self::resolveCaFile($config);
        if ($caFile !== '') {
            curl_setopt($curl, CURLOPT_CAINFO, $caFile);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }
    }

    /**
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public static function send (string $token, string $title, string $body, array $data = array()): bool {
        if (empty($token)) {
            throw new Exception('FCM token empty');
        }
        if (empty($title)) {
            throw new Exception('Notification title empty');
        }
        if (empty($body)) {
            throw new Exception('Notification body empty');
        }

        $config = self::loadConfig();
        $serviceAccountPath = trim($config['service_account_path'] ?? '');
        if (!empty($serviceAccountPath) && file_exists($serviceAccountPath)) {
            return self::sendV1($config, $token, $title, $body, $data);
        }
        return self::sendLegacy($config, $token, $title, $body);
    }

    /**
     * @param array $config
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     * @throws Exception
     */
    private static function sendV1 (array $config, string $token, string $title, string $body, array $data): bool {
        $serviceAccount = json_decode(file_get_contents($config['service_account_path']), true);
        if (empty($serviceAccount['private_key']) || empty($serviceAccount['client_email'])) {
            throw new Exception('Invalid Firebase service account JSON');
        }

        $accessToken = self::getAccessToken($serviceAccount, $config);
        $projectId = !empty($config['project_id']) ? $config['project_id'] : $serviceAccount['project_id'];

        $message = array(
            'message' => array(
                'token' => $token,
                'notification' => array(
                    'title' => $title,
                    'body' => $body,
                ),
                'android' => array(
                    'priority' => 'HIGH',
                    'notification' => array(
                        'channel_id' => 'high_importance_channel',
                    ),
                ),
                'apns' => array(
                    'payload' => array(
                        'aps' => array(
                            'sound' => 'default',
                        ),
                    ),
                ),
            ),
        );

        if (!empty($data)) {
            $message['message']['data'] = array();
            foreach ($data as $key => $value) {
                $message['message']['data'][(string)$key] = (string)$value;
            }
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($message),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer '.$accessToken,
                'Content-Type: application/json',
            ),
        ));
        self::applyCurlSslOptions($curl, $config);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if (!empty($err)) {
            throw new Exception('FCM v1 curl error: '.$err);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('FCM v1 HTTP '.$httpCode.': '.$response);
        }
        return true;
    }

    /**
     * @param array $serviceAccount
     * @param array $config
     * @return string
     * @throws Exception
     */
    private static function getAccessToken (array $serviceAccount, array $config = array()): string {
        $now = time();
        if (!empty(self::$accessToken) && self::$tokenExpiry > ($now + 60)) {
            return self::$accessToken;
        }

        $payload = array(
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        );

        $jwt = JWT::encode($payload, $serviceAccount['private_key'], 'RS256');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(array(
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            )),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded',
            ),
        ));
        self::applyCurlSslOptions($curl, $config);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if (!empty($err)) {
            throw new Exception('OAuth token curl error: '.$err);
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($decoded['access_token'])) {
            throw new Exception('OAuth token HTTP '.$httpCode.': '.$response);
        }

        self::$accessToken = $decoded['access_token'];
        self::$tokenExpiry = $now + intval($decoded['expires_in'] ?? 3600);
        return self::$accessToken;
    }

    /**
     * @param array $config
     * @param string $token
     * @param string $title
     * @param string $body
     * @return bool
     * @throws Exception
     */
    private static function sendLegacy (array $config, string $token, string $title, string $body): bool {
        $serverKey = trim($config['legacy_server_key'] ?? '');
        if (empty($serverKey)) {
            $serverKey = 'AAAA0VbV4yY:APA91bEkhqjl72wrey1qcbBlaaGNZTVtRcDQMwBkIOTkzWzytnTHbEVypleaWjHA3SeO0klvh9M2M_MaX-1yf2jupOZnDyn2Zx9lx2CLDgZGOwPfBpr1HvFO14lnZSKlpqi1rKM5BX-i';
        }

        $payload = json_encode(array(
            'to' => $token,
            'collapse_key' => 'type_a',
            'notification' => array(
                'title' => $title,
                'body' => $body,
            ),
        ));

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://fcm.googleapis.com/fcm/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => array(
                'Accept: */*',
                'Authorization: key='.$serverKey,
                'Content-Type: application/json',
            ),
        ));
        self::applyCurlSslOptions($curl, $config);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $err = curl_error($curl);
        curl_close($curl);

        if (!empty($err)) {
            throw new Exception('FCM legacy curl error: '.$err);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('FCM legacy HTTP '.$httpCode.': '.$response);
        }
        return true;
    }
}
