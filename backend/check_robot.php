
<?php

/**
 * Kiem tra robots.txt truoc khi crawler thu thap du lieu.
 *
 * Ket qua tra ve:
 * - allowed: co duoc phep truy cap URL hay khong
 * - message: thong bao ket qua
 * - robots_url: dia chi file robots.txt
 */

function checkRobots($url, $userAgent = '*')
{
    // 1. Kiem tra URL dau vao
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return [
            'allowed' => false,
            'message' => 'URL khong hop le.',
            'robots_url' => null
        ];
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    $host = parse_url($url, PHP_URL_HOST);

    if (!in_array($scheme, ['http', 'https'], true) || !$host) {
        return [
            'allowed' => false,
            'message' => 'Chi chap nhan URL HTTP hoac HTTPS.',
            'robots_url' => null
        ];
    }

    // 2. Tao dia chi robots.txt
    $port = parse_url($url, PHP_URL_PORT);

    $robotsUrl = $scheme . '://' . $host;

    if ($port !== null) {
        $robotsUrl .= ':' . $port;
    }

    $robotsUrl .= '/robots.txt';

    // 3. Tai robots.txt bang cURL
    $ch = curl_init();

    $caCertPath = __DIR__ . '/../certs/cacert.pem';

    $curlOptions = [
        CURLOPT_URL => $robotsUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'WebDataCrawler/1.0',
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ];

    if (file_exists($caCertPath)) {
        $curlOptions[CURLOPT_CAINFO] = realpath($caCertPath);
    }

    curl_setopt_array($ch, $curlOptions);

    $content = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);
    $curlErrorMessage = curl_error($ch);

    // Neu gap loi SSL certificate tren moi truong Windows/dev, thu lai voi SSL verify tat
    if ($curlError === 60 || $curlError === 77) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $content = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_errno($ch);
        $curlErrorMessage = curl_error($ch);
    }

    // PHP 8.5 khong can goi curl_close()

    // 4. Xu ly loi ket noi
    if ($content === false || $curlError !== 0) {
        return [
            'allowed' => false,
            'message' => 'Khong the doc robots.txt: ' . $curlErrorMessage,
            'curl_error_code' => $curlError,
            'curl_error' => $curlErrorMessage,
            'robots_url' => $robotsUrl
        ];
    }

    // 5. Neu robots.txt khong ton tai (404 hoac 410)
    if ($httpCode === 404 || $httpCode === 410) {
        return [
            'allowed' => true,
            'message' => 'Khong tim thay robots.txt (HTTP ' . $httpCode . ').',
            'robots_url' => $robotsUrl
        ];
    }

    // Cac loi HTTP khac: khong tu dong cho phep crawler
    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'allowed' => false,
            'message' => 'Khong doc duoc robots.txt. HTTP ' . $httpCode,
            'robots_url' => $robotsUrl
        ];
    }

    // 6. Phan tich cac quy tac robots.txt
    $lines = preg_split('/\r\n|\r|\n/', $content);

    $groups = [];
    $currentAgents = [];
    $currentRules = [];
    $hasRules = false;

    foreach ($lines as $line) {
        // Bo phan ghi chu va khoang trang
        $line = trim(explode('#', $line, 2)[0]);

        if ($line === '' || strpos($line, ':') === false) {
            continue;
        }

        list($directive, $value) = explode(':', $line, 2);

        $directive = strtolower(trim($directive));
        $value = trim($value);

        if ($directive === 'user-agent') {
            // Bat dau nhom moi neu nhom truoc da co quy tac
            if ($hasRules) {
                $groups[] = [
                    'agents' => $currentAgents,
                    'rules' => $currentRules
                ];

                $currentAgents = [];
                $currentRules = [];
                $hasRules = false;
            }

            $currentAgents[] = strtolower($value);
        } elseif (
            in_array($directive, ['allow', 'disallow'], true)
            && !empty($currentAgents)
        ) {
            // Disallow rong khong chan duong dan nao
            if ($value !== '') {
                $currentRules[] = [
                    'type' => $directive,
                    'path' => $value
                ];

                $hasRules = true;
            }
        }
    }

    // Luu nhom cuoi cung
    if (!empty($currentAgents)) {
        $groups[] = [
            'agents' => $currentAgents,
            'rules' => $currentRules
        ];
    }

    // 7. Chon nhom User-agent phu hop
    $agent = strtolower($userAgent);
    $matchedGroups = [];
    $bestAgentLength = -1;

    foreach ($groups as $group) {
        foreach ($group['agents'] as $groupAgent) {
            if ($groupAgent === '*') {
                $agentLength = 0;
            } elseif (
                $agent !== '*'
                && strpos($agent, $groupAgent) !== false
            ) {
                $agentLength = strlen($groupAgent);
            } else {
                continue;
            }

            if ($agentLength > $bestAgentLength) {
                $bestAgentLength = $agentLength;
                $matchedGroups = [$group];
            } elseif ($agentLength === $bestAgentLength) {
                $matchedGroups[] = $group;
            }
        }
    }

    // Khong co nhom quy tac phu hop
    if (empty($matchedGroups)) {
        return [
            'allowed' => true,
            'message' => 'Khong co quy tac User-agent phu hop.',
            'robots_url' => $robotsUrl
        ];
    }

    // 8. Lay duong dan can kiem tra
    $path = parse_url($url, PHP_URL_PATH);

    if ($path === null || $path === '') {
        $path = '/';
    }

    $query = parse_url($url, PHP_URL_QUERY);

    if ($query !== null) {
        $path .= '?' . $query;
    }

    // 9. Tim quy tac khop dai nhat
    $bestRule = null;
    $bestLength = -1;

    foreach ($matchedGroups as $group) {
        foreach ($group['rules'] as $rule) {
            $rulePath = $rule['path'];

            // Ho tro ky tu * va dau $ trong robots.txt
            $pattern = preg_quote($rulePath, '~');
            $pattern = str_replace('\*', '.*', $pattern);

            if (substr($rulePath, -1) === '$') {
                $pattern = substr($pattern, 0, -2) . '$';
            } else {
                $pattern .= '.*';
            }

            if (preg_match('~^' . $pattern . '~u', $path)) {
                $ruleLength = strlen(rtrim($rulePath, '$'));

                // Quy tac dai hon duoc uu tien.
                // Neu bang nhau, Allow duoc uu tien.
                if (
                    $ruleLength > $bestLength
                    || (
                        $ruleLength === $bestLength
                        && $rule['type'] === 'allow'
                        && $bestRule !== null
                        && $bestRule['type'] === 'disallow'
                    )
                ) {
                    $bestLength = $ruleLength;
                    $bestRule = $rule;
                }
            }
        }
    }

    // 10. Tra ket qua
    if ($bestRule !== null && $bestRule['type'] === 'disallow') {
        return [
            'allowed' => false,
            'message' => 'robots.txt khong cho phep truy cap duong dan nay.',
            'robots_url' => $robotsUrl,
            'matched_rule' => 'Disallow: ' . $bestRule['path']
        ];
    }

    return [
        'allowed' => true,
        'message' => 'Khong co quy tac nao chan URL nay.',
        'robots_url' => $robotsUrl,
        'matched_rule' => $bestRule !== null
            ? 'Allow: ' . $bestRule['path']
            : null
    ];
}
