<?php
// ---------------------------------------------------------------------------
// HUSTOJ Settings Manager
// Only administrators can access this page.
// Database credentials, OJ_DATA, SMTP_PASS and other critical settings
// are intentionally excluded from editing for safety.
// ---------------------------------------------------------------------------

// 管理员权限检查
require_once("../include/db_info.inc.php");
require_once("../include/my_func.inc.php");
if (isset($OJ_LANG) && file_exists("../lang/$OJ_LANG.php")) {
    require_once("../lang/$OJ_LANG.php");
}
if (!(isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) || $domain!=$DOMAIN  ) {
    echo "<a href='../loginpage.php'>" . (isset($MSG_Login) ? $MSG_Login : "Please Login First!") . "</a>";
    exit(1);
}

// 加载语言包
if (isset($OJ_LANG) && file_exists("../lang/$OJ_LANG.php")) {
    require_once("../lang/$OJ_LANG.php");
}

$config_file = dirname(dirname(__FILE__)) . "/include/db_info.inc.php";

function get_config_value($file, $var) {
    $pattern = '/\$' . preg_quote($var, '/') . '\s*=\s*([^;]+);/';
    if (preg_match($pattern, file_get_contents($file), $m)) {
        $val = trim($m[1]);
        if ((strpos($val, '"') === 0 && strrpos($val, '"') === strlen($val) - 1) ||
            (strpos($val, "'") === 0 && strrpos($val, "'") === strlen($val) - 1)) {
            $val = substr($val, 1, -1);
        }
        return $val;
    }
    return '';
}

function cfg($var, $default = '') {
    global $config_file;
    $v = get_config_value($config_file, $var);
    return htmlentities($v !== '' ? $v : $default, ENT_QUOTES, 'UTF-8');
}

function cfg_bool($var) {
    global $config_file;
    $v = get_config_value($config_file, $var);
    return in_array($v, ['true', '1', 'on']);
}

function cfg_chk($var) {
    return cfg_bool($var) ? 'checked' : '';
}

$save_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check (use admin-header.php on other admin pages for this)
    $protected = [
        'DB_HOST','DB_NAME','DB_USER','DB_PASS',
        'OJ_DATA','OJ_ADMIN','OJ_RANK_HIDDEN',
        'OJ_LOGIN_MOD','OJ_UDPSERVER','OJ_MEMPORT','OJ_REDISPORT',
        'OJ_MEMSERVER','OJ_REDISSERVER','OJ_REDISQNAME',
        'OJ_WEIBO_ASEC','OJ_WEIBO_AKEY','OJ_RR_ASEC','OJ_RR_AKEY',
        'OJ_QQ_ASEC','OJ_QQ_AKEY','OJ_OPENID_PWD',
        'OJ_BG','OJ_AI_HTML','OJ_FANCY_MP3','OJ_CDN_URL',
        'OJ_EXAM_CONTEST_ID','OJ_ON_SITE_CONTEST_ID',
        'OJ_JUDGE_HUB_PATH','OJ_BENCHMARK_MODE',
        'OJ_WEIBO_CBURL','OJ_RR_CBURL','OJ_QQ_CBURL','OJ_UDPPORT',
    ];

    $saved = 0;
    $errors = [];
    $content = file_get_contents($config_file);
    $bool_vars = [
        'OJ_REGISTER','OJ_REG_NEED_CONFIRM','OJ_EMAIL_CONFIRM',
        'OJ_ACE_EDITOR','OJ_AUTO_SHOW_OFF','OJ_MATHJAX','OJ_MENU_NEWS','OJ_MENU_DROPDOWN',
        'OJ_MAIL','OJ_VCODE','OJ_LONG_LOGIN',
        'OJ_NEED_LOGIN','OJ_LIMIT_TO_1_IP','OJ_LOG_ENABLED','OJ_LOG_USER_ENABLED',
        'OJ_LOG_URL_ENABLED','OJ_LOG_TRACE_ENABLED',
        'OJ_SIM','OJ_DICT','OJ_AUTO_SHARE','OJ_PRINTER','OJ_ONLINE','OJ_INDENT',
        'OJ_BLOCKLY','OJ_TEST_RUN','OJ_DOWNLOAD','OJ_SHARE_CODE','OJ_DIV_FILTER',
        'OJ_ENCODE_SUBMIT','OJ_FREE_PRACTICE','OJ_PUBLIC_STATUS','OJ_FANCY_RESULT',
        'OJ_NO_CONTEST_WATCHER','OJ_CONTEST_TOTAL_100','OJ_OLD_FASHIONED',
        'OJ_AINO','OJ_SHOW_DIFF','OJ_SHOW_METAL','OJ_HIDE_RIGHT_ANSWER',
        'OJ_CONTEST_RANK_FIX_HEADER','OJ_OI_MODE','OJ_OI_1_SOLUTION_ONLY',
        'OJ_CE_PENALTY','OJ_APPENDCODE','OJ_OFFLINE_ZIP_CCF_DIRNAME',
        'OJ_NOIP_HINT','OJ_DL_1ST_WA_ONLY','OJ_RECENT_CONTEST',
        'OJ_REMOTE_JUDGE','OJ_NICK_IMMUTABLE',
    ];

    // 如果开启了 SaaS 模式，阻止 SMTP_PASS 被保存（表单不显示，但 POST 可能带值）
    $saas_enabled = in_array(get_config_value($config_file, 'OJ_SaaS_ENABLE'), ['true', '1', 'on']);
    if ($saas_enabled && isset($_POST['cfg_SMTP_PASS'])) {
        unset($_POST['cfg_SMTP_PASS']);
    }

    // 也处理未提交的 checkbox（默认设为 false）
    foreach ($bool_vars as $var) {
        $key = 'cfg_' . $var;
        if (!isset($_POST[$key])) {
            $count = 0;
            $pat = '/^(\s*)(static\s+)?' . '\\' . "\$" . $var . '\s*=[^;]+;/m';
            $content = preg_replace($pat, '$1static $' . $var . '=false;', $content, 1, $count);
            $saved += $count;
        }
    }

    foreach ($_POST as $key => $raw_val) {
        if (strpos($key, 'cfg_') !== 0) continue;
        $var = substr($key, 4);
        if (in_array($var, $protected)) {
            $errors[] = "变量 \${$var} 受保护，无法通过此页面修改。";
            continue;
        }
        $orig_val = get_config_value($config_file, $var);
        $val = trim($raw_val);

        // 构建实际写入的新行内容
        if (in_array($var, $bool_vars)) {
            $new_line = "static \${$var}=true;";
        } elseif ($val === '') {
            $new_line = "static \${$var}=false;";
        } elseif (in_array($val, ['true','false'])) {
            $new_line = "static \${$var}={$val};";
        } elseif (is_numeric($val) && !preg_match('/[a-zA-Z]/', $val)) {
            $new_line = "static \${$var}={$val};";
        } else {
            $val = str_replace('\\', '\\\\', $val);
            $new_line = "static \${$var}=\"{$val}\";";
        }

        // 构建用于比较的 new_val（去掉引号，与 get_config_value 格式一致）
        if (in_array($var, $bool_vars)) {
            $cmp_val = 'true';
        } elseif ($val === '') {
            $cmp_val = 'false';
        } elseif (in_array($val, ['true','false'])) {
            $cmp_val = $val;
        } elseif (is_numeric($val) && !preg_match('/[a-zA-Z]/', $val)) {
            $cmp_val = $val;
        } else {
            $cmp_val = trim($val);
        }

        if ($orig_val === $cmp_val) {
            continue;
        }

        $count = 0;
        $pat2 = '/^(\s*)(static\s+)?' . '\\' . "\$" . $var . '\s*=[^;]+;/m';
        $content = preg_replace($pat2, '$1' . $new_line, $content, 1, $count);
        if ($count > 0) {
            $saved++;
        } else {
            // 变量在文件中不存在，追加到末尾
            $content .= "\n" . $new_line;
            $saved++;
        }
    }

    if (count($errors) > 0) {
        $save_msg = '<div class="alert alert-warning">' . implode('<br>', $errors) . '</div>';
    } elseif ($saved > 0) {
        file_put_contents($config_file, $content);
        $save_msg = '<div class="alert alert-success">저장했습니다. ' . $saved . '개 설정을 업데이트했으며, 새로고침 후 적용됩니다.</div>';
    } else {
        $save_msg = '<div class="alert alert-info">저장할 변경 사항이 없습니다.</div>';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>시스템 설정 - <?php echo isset($MSG_SYSTEM) ? $MSG_SYSTEM : 'System Settings'; ?></title>
    <link rel="stylesheet" href="../include/hoj.css">
    <script src="../template/bs3/jquery.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f0f2f5; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #2c5aa0; color: #fff; padding: 12px 20px; font-size: 15px; font-weight: bold; border-radius: 6px 6px 0 0; }
        .card-body { padding: 20px; }
        .alert { padding: 12px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .alert-success { background: #166534; color: #dcfce7; border: 1px solid #166534; }
        .alert-warning { background: #854d0e; color: #fef9c3; border: 1px solid #854d0e; }
        .alert-info { background: #1e3a5f; color: #e0f2fe; border: 1px solid #1e3a5f; }
        .alert-danger { background: #991b1b; color: #fee2e2; border: 1px solid #991b1b; }
        .protected-note { background: #f8f9fa; border-left: 4px solid #ffc107; padding: 10px 15px; font-size: 13px; color: #666; margin-bottom: 20px; border-radius: 0 4px 4px 0; }
        .protected-note code { background: #fff3cd; padding: 1px 5px; border-radius: 3px; color: #856404; font-size: 12px; }
        .info-row { display: flex; gap: 30px; margin-bottom: 20px; padding: 12px 15px; background: #f0f6ff; border-radius: 4px; font-size: 13px; }
        .info-row span { color: #555; }
        .info-row strong { color: #2c5aa0; }
        .tab-nav { margin: 0 0 -2px 0; padding: 0; list-style: none; overflow: hidden; background: #fff; border-bottom: 2px solid #e0e0e0; border-radius: 6px 6px 0 0; }
        .tab-nav li { display: inline-block; }
        .tab-nav li a { display: block; padding: 10px 18px; color: #666; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: color 0.2s; }
        .tab-nav li a:hover { color: #2c5aa0; }
        .tab-nav li a.active { color: #2c5aa0; border-bottom-color: #2c5aa0; font-weight: bold; }
        .tab-content { display: none; padding: 20px; background: #fff; border-radius: 0 0 6px 6px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
        .tab-content.active { display: block; }
        .setting-group { margin-bottom: 25px; }
        .setting-group-title { font-size: 13px; font-weight: bold; color: #333; border-bottom: 2px solid #2c5aa0; padding-bottom: 4px; margin-bottom: 12px; }
        .row { display: flex; align-items: flex-start; padding: 10px 0; border-bottom: 1px solid #f0f0f0; background: #f8fbff; }
        .row:last-child { border-bottom: none; }
        .row:hover { background: #f0f6ff; }
        .label { width: 220px; flex-shrink: 0; padding-top: 3px; background: #e8f0ff; }
        .label code { font-size: 12px; color: #2c5aa0; background: #e8f0ff; padding: 1px 5px; border-radius: 3px; }
        .label-desc { font-size: 12px; color: #555; display: block; margin-top: 2px; }
        .control { flex: 1; }
        input[type="text"], input[type="number"], select {
            width: 100%; max-width: 380px; padding: 6px 10px;
            border: 1px solid #ddd; border-radius: 4px; font-size: 13px;
        }
        input[type="checkbox"] { width: 16px; height: 16px; margin-top: 4px; }
        .bool-hint { font-size: 11px; color: #888; margin-left: 6px; vertical-align: middle; }
        .btn-save { background: #2c5aa0; color: #fff; border: none; padding: 10px 35px; border-radius: 4px; font-size: 14px; cursor: pointer; margin-top: 15px; }
        .btn-save:hover { background: #1e4087; }
        .tab-footer { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .beta-tag { background: #ffc107; color: #333; font-size: 10px; padding: 1px 5px; border-radius: 3px; margin-left: 4px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="card-header">⚙️ 시스템 설정</div>
        <div class="card-body">
            <?php echo $save_msg; ?>
            <div class="protected-note">
                ⚠️ 다음 설정은 보호되어 이 페이지에서 수정할 수 없습니다. SSH로 <code>include/db_info.inc.php</code>를 수정해 주세요.<br>
                <code>$DB_HOST</code> <code>$DB_NAME</code> <code>$DB_USER</code> <code>$DB_PASS</code>
                <code>$OJ_DATA</code> <code>$SMTP_PASS</code> <code>$OJ_ADMIN</code>
                <code>$OJ_RANK_HIDDEN</code> <code>$OJ_LOGIN_MOD</code>
                <code>$OJ_WEIBO_ASEC</code> <code>$OJ_RR_ASEC</code> <code>$OJ_QQ_ASEC</code>
                <code>$OJ_BG</code> <code>$OJ_CDN_URL</code> <code>$OJ_AI_HTML</code>
                <code>$OJ_FANCY_MP3</code> <code>$OJ_UDPSERVER</code> <code>$OJ_JUDGE_HUB_PATH</code> 등
            </div>
            <div class="info-row">
                <span>📄 설정 파일: <strong><?php echo htmlentities($config_file, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                <span>🎨 템플릿: <strong><?php echo cfg('OJ_TEMPLATE'); ?></strong></span>
                <span>📛 시스템 이름: <strong><?php echo cfg('OJ_NAME'); ?></strong></span>
            </div>
        </div>
    </div>

    <form method="post" id="settings-form">
        <ul class="tab-nav">
            <li><a href="#" class="active" data-tab="tab-system">📋 시스템</a></li>
            <li><a href="#" data-tab="tab-ui">🎨 화면</a></li>
            <li><a href="#" data-tab="tab-mail">✉️ 메일</a></li>
            <li><a href="#" data-tab="tab-security">🔒 보안</a></li>
            <li><a href="#" data-tab="tab-features">🔧 기능</a></li>
            <li><a href="#" data-tab="tab-contest">🏆 대회</a></li>
            <li><a href="#" data-tab="tab-other">⚡ 기타</a></li>
        </ul>

        <?php
        // ---------------------------------------------------------------------------
        // TAB: 系统
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content active" id="tab-system">';
        echo '<div class="setting-group">';

        $systems = [
            ['OJ_NAME', 'OJ_NAME', '왼쪽 상단에 표시할 시스템 이름'],
            ['OJ_HOME', 'OJ_HOME', '홈 디렉터리 경로'],
            ['OJ_LANG', 'OJ_LANG', '기본 화면 언어', 'select', ['cn'=>'중국어(간체)','en'=>'English','th'=>'ภาษาไทย','ko'=>'한국어','fa'=>'فارسی','ug'=>'ئۇيغۇرچە','ru'=>'Русский','he'=>'עברית','mn'=>'Монгол','bo'=>'བོད་སྐད']],
            ['OJ_EXPIRY_DAYS', 'OJ_EXPIRY_DAYS', '수동 생성 계정의 기본 만료 일수'],
            ['OJ_FRIENDLY_LEVEL', 'OJ_FRIENDLY_LEVEL', '사용자 친화 수준(0-9, 높을수록 안내를 많이 표시)'],
        ];
        foreach ($systems as $s) {
            $var = $s[0];
            $label = $s[2];
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $var . '</code><span class="label-desc">' . $label . '</span></div>';
            echo '<div class="control">';
            if (isset($s[3]) && $s[3] === 'select') {
                echo '<select name="cfg_' . $var . '">';
                foreach ($s[4] as $vk => $vv) {
                    $sel = (cfg($var) == $vk) ? 'selected' : '';
                    echo '<option value="' . $vk . '" ' . $sel . '>' . $vv . '</option>';
                }
                echo '</select>';
            } else {
                $v = is_numeric(cfg($var)) ? cfg($var) : '"' . cfg($var) . '"';
                $type = is_numeric(cfg($var)) ? 'number' : 'text';
                echo '<input type="' . $type . '" name="cfg_' . $var . '" value="' . cfg($var) . '">';
            }
            echo '</div></div>';
        }

        $bool_systems = [
            ['OJ_REGISTER', '새 사용자 등록 허용'],
            ['OJ_REG_NEED_CONFIRM', '새 사용자 등록 시 관리자 승인 필요'],
            ['OJ_EMAIL_CONFIRM', '메일로 계정 활성화 사용'],
        ];
        foreach ($bool_systems as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 界面
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-ui">';
        echo '<div class="setting-group">';

        $ui_items = [
            ['OJ_TEMPLATE', 'OJ_TEMPLATE', '사용할 템플릿', 'select', ['syzoj'=>'syzoj(권장)','bs3'=>'bs3','sidebar'=>'sidebar','mdui'=>'mdui','sweet'=>'sweet','bshark'=>'bshark']],
            ['OJ_CSS', 'OJ_CSS', '색상 구성 CSS 파일 이름'],
            ['OJ_LANGMASK', 'OJ_LANGMASK', '언어 마스크(10진수)'],
            ['OJ_INDEX_NEWS_TITLE', 'OJ_INDEX_NEWS_TITLE', 'syzoj 첫 화면에 표시할 글 제목'],
            ['OJ_BEIAN', 'OJ_BEIAN', '페이지 하단에 표시할 등록 번호'],
        ];
        foreach ($ui_items as $s) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $s[0] . '</code><span class="label-desc">' . $s[2] . '</span></div>';
            echo '<div class="control">';
            if (isset($s[3]) && $s[3] === 'select') {
                echo '<select name="cfg_' . $s[0] . '">';
                foreach ($s[4] as $vk => $vv) {
                    $sel = (cfg($s[0]) == $vk) ? 'selected' : '';
                    echo '<option value="' . $vk . '" ' . $sel . '>' . $vv . '</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="text" name="cfg_' . $s[0] . '" value="' . cfg($s[0]) . '">';
            }
            echo '</div></div>';
        }

        $bool_ui = [
            ['OJ_ACE_EDITOR', '문법 강조 코드 편집기 사용'],
            ['OJ_AUTO_SHOW_OFF', '문제 열 때 편집기 기본 표시'],
            ['OJ_MATHJAX', 'MathJax 수식 렌더링 사용'],
            ['OJ_MENU_NEWS', '상단 메뉴에 공지 표시'],
            ['OJ_MENU_DROPDOWN', '메뉴에 드롭다운 스타일 사용'],
        ];
        foreach ($bool_ui as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 邮件
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-mail">';
        echo '<div class="setting-group">';

        $mail_items = [
            ['SMTP_SERVER', 'SMTP_SERVER', 'SMTP 서버 주소'],
            ['SMTP_PORT', 'SMTP_PORT', 'SMTP 포트(25/80/465/587)'],
            ['SMTP_USER', 'SMTP_USER', 'SMTP 사용자 이름(발신자 메일)'],
            ['OJ_KEEP_TIME', 'OJ_KEEP_TIME', 'Cookie 유지 일수'],
            ['OJ_LOGIN_FAIL_LIMIT', 'OJ_LOGIN_FAIL_LIMIT', '5분 내 최대 로그인 실패 횟수'],
        ];
        foreach ($mail_items as $s) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $s[0] . '</code><span class="label-desc">' . $s[2] . '</span></div>';
            echo '<div class="control">';
            $type = is_numeric(cfg($s[0])) ? 'number' : 'text';
            echo '<input type="' . $type . '" name="cfg_' . $s[0] . '" value="' . cfg($s[0]) . '">';
            echo '</div></div>';
        }

        // SMTP_PASS: 仅在 SaaS 关闭时可见可改
        $saas_now = in_array(cfg('OJ_SaaS_ENABLE'), ['true', '1', 'on']);
        if (!$saas_now) {
            echo '<div class="row">';
            echo '<div class="label"><code>$SMTP_PASS</code><span class="label-desc">SMTP 인증 비밀번호(기관 메일 사용 권장)</span></div>';
            echo '<div class="control">';
            echo '<input type="password" name="cfg_SMTP_PASS" value="' . cfg('SMTP_PASS') . '" autocomplete="new-password">';
            echo '</div></div>';
        } else {
            echo '<div class="row">';
            echo '<div class="label"><code>$SMTP_PASS</code><span class="label-desc">SaaS 모드가 켜져 있어 보호됩니다</span></div>';
            echo '<div class="control"><span class="label-desc" style="color:#999">(보호됨: 이 페이지에서 수정할 수 없음)</span></div>';
            echo '</div>';
        }

        $bool_mail = [
            ['OJ_MAIL', '사이트 내 메일 사용'],
            ['OJ_VCODE', '등록/로그인 시 보안 문자 표시'],
            ['OJ_LONG_LOGIN', '로그인 정보 장기 유지 사용'],
        ];
        foreach ($bool_mail as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 安全
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-security">';
        echo '<div class="setting-group">';

        $sec_items = [
            ['OJ_REG_SPEED', 'OJ_REG_SPEED', 'IP당 시간별 최대 등록 수(0=제한 없음)'],
        ];
        foreach ($sec_items as $s) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $s[0] . '</code><span class="label-desc">' . $s[2] . '</span></div>';
            echo '<div class="control">';
            echo '<input type="number" name="cfg_' . $s[0] . '" value="' . intval(cfg($s[0])) . '">';
            echo '</div></div>';
        }

        $bool_sec = [
            ['OJ_NEED_LOGIN', '전체 페이지 로그인 필요'],
            ['OJ_LIMIT_TO_1_IP', '사용자는 하나의 IP에서만 동시 로그인 허용'],
            ['OJ_LOG_ENABLED', '접속 로그 사용'],
            ['OJ_LOG_USER_ENABLED', '로그에 사용자 ID 기록'],
            ['OJ_LOG_URL_ENABLED', '로그에 URL 기록'],
            ['OJ_LOG_TRACE_ENABLED', 'TRACE 로그 기록'],
            ['OJ_NICK_IMMUTABLE', '닉네임 직접 수정 제한<br>(관리자는 사용자 목록에서 더블 클릭하여 수정 가능)'],
        ];
        foreach ($bool_sec as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 功能
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-features">';
        echo '<div class="setting-group">';

        $bool_features = [
            ['OJ_SIM', '유사도 표시(judge.conf 설정 필요)'],
            ['OJ_DICT', '온라인 번역 표시'],
            ['OJ_AUTO_SHARE', '정답 처리한 문제의 다른 사용자 코드 열람 허용'],
            ['OJ_PRINTER', '인쇄 서비스 사용'],
            ['OJ_ONLINE', '온라인 상태 기록'],
            ['OJ_INDENT', '들여쓰기 규칙 자동 검사'],
            ['OJ_BLOCKLY', 'Blockly 시각 프로그래밍 사용'],
            ['OJ_TEST_RUN', '제출 화면에서 테스트 실행 허용'],
            ['OJ_DOWNLOAD', '테스트 데이터 다운로드 허용'],
            ['OJ_SHARE_CODE', '코드 공유 기능 사용'],
            ['OJ_DIV_FILTER', '문제 설명의 div 필터링(표시 오류 해결)'],
            ['OJ_ENCODE_SUBMIT', 'Base64 인코딩 제출 사용(WAF 오탐 방지)'],
            ['OJ_FREE_PRACTICE', '자유 연습에서 대회/과제 문제 제한 해제'],
            ['OJ_PUBLIC_STATUS', '모든 사용자의 채점 결과 공개'],
            ['OJ_FANCY_RESULT', '정답 시 애니메이션 표시'],
            ['OJ_NO_CONTEST_WATCHER', '권한 없는 사용자의 비공개 대회 관전 차단'],
            ['OJ_CONTEST_TOTAL_100', '대회를 100점 만점으로 채점'],
            ['OJ_OLD_FASHIONED', '기존 화면 사용 방식 유지'],
            ['OJ_AINO', 'AI 부정행위 방지 안내 사용'],
            ['OJ_SHOW_DIFF', '오답 시 비교 설명 표시'],
            ['OJ_SHOW_METAL', '순위에 비례해 메달 표시'],
            ['OJ_HIDE_RIGHT_ANSWER', '선택/빈칸 문제의 정답 숨김'],
        ];
        foreach ($bool_features as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 比赛
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-contest">';
        echo '<div class="setting-group">';

        $contest_items = [
            ['OJ_RANK_LOCK_PERCENT', 'OJ_RANK_LOCK_PERCENT', '대회 순위 비공개 시간 비율(0-1, 예: 0.2=마지막 20%)'],
            ['OJ_RANK_LOCK_DELAY', 'OJ_RANK_LOCK_DELAY', '대회 종료 후 순위 비공개 지속 시간(초)'],
            ['OJ_SUBMIT_COOLDOWN_TIME', 'OJ_SUBMIT_COOLDOWN_TIME', '제출 대기 시간(초)'],
            ['OJ_CONTEST_LIMIT_KEYWORD', 'OJ_CONTEST_LIMIT_KEYWORD', '대회 개인 제한 시간 키워드'],
            ['OJ_NOIP_KEYWORD', 'OJ_NOIP_KEYWORD', 'NOIP 모드 활성화 키워드(제목에 포함 시 활성화)'],
            ['OJ_RECENT_CONTEST', 'OJ_RECENT_CONTEST', '외부 대회 JSON 주소(비워 두면 사용 안 함)'],
            ['OJ_MARK', 'OJ_MARK', '점수 표시 방식', 'select', ['mark'=>'mark(정답 점수 표시)','percent'=>'percent(오답 비율 표시)']],
            ['OJ_MARKDOWN', 'OJ_MARKDOWN', 'Markdown 렌더링 엔진', 'select', ['marked.js'=>'marked.js','markdown-it'=>'markdown-it']],
        ];
        foreach ($contest_items as $s) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $s[0] . '</code><span class="label-desc">' . $s[2] . '</span></div>';
            echo '<div class="control">';
            if (isset($s[3]) && $s[3] === 'select') {
                echo '<select name="cfg_' . $s[0] . '">';
                foreach ($s[4] as $vk => $vv) {
                    $sel = (cfg($s[0]) == $vk) ? 'selected' : '';
                    echo '<option value="' . $vk . '" ' . $sel . '>' . $vv . '</option>';
                }
                echo '</select>';
            } else {
                $type = is_numeric(cfg($s[0])) ? 'number' : 'text';
                echo '<input type="' . $type . '" name="cfg_' . $s[0] . '" value="' . cfg($s[0]) . '">';
            }
            echo '</div></div>';
        }

        $bool_contest = [
            ['OJ_CONTEST_RANK_FIX_HEADER', '대회 순위에서 명단 헤더 고정'],
            ['OJ_OI_MODE', 'OI 대회 모드 사용(일부 기능 비활성화)'],
            ['OJ_OI_1_SOLUTION_ONLY', 'OI 모드: 마지막 제출만 유지'],
            ['OJ_CE_PENALTY', '컴파일 오류에 시간 벌점 적용'],
            ['OJ_APPENDCODE', '코드 제출 시 템플릿 자동 추가'],
            ['OJ_OFFLINE_ZIP_CCF_DIRNAME', '오프라인 대회 가져오기 시 CCF 규칙으로 디렉터리 이름 확인'],
            ['OJ_NOIP_HINT', 'NOIP 대회에 문제 힌트 표시'],
            ['OJ_DL_1ST_WA_ONLY', '첫 번째 오답의 테스트 데이터만 다운로드 허용'],
        ];
        foreach ($bool_contest as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code><span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';

        // ---------------------------------------------------------------------------
        // TAB: 其他
        // ---------------------------------------------------------------------------
        echo '<div class="tab-content" id="tab-other">';
        echo '<div class="setting-group">';

        $other_items = [
            ['OJ_POISON_BOT_COUNT', 'OJ_POISON_BOT_COUNT', '봇 계정 차단을 시작할 정답 수'],
        ];
        foreach ($other_items as $s) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $s[0] . '</code><span class="label-desc">' . $s[2] . '</span></div>';
            echo '<div class="control">';
            echo '<input type="number" name="cfg_' . $s[0] . '" value="' . intval(cfg($s[0])) . '">';
            echo '</div></div>';
        }

        $bool_other = [
            ['OJ_REMOTE_JUDGE', '원격 채점 사용', 'beta'],
        ];
        foreach ($bool_other as $b) {
            echo '<div class="row">';
            echo '<div class="label"><code>$' . $b[0] . '</code>';
            if (isset($b[2]) && $b[2] === 'beta') echo '<span class="beta-tag">Beta</span>';
            echo '<span class="label-desc">' . $b[1] . '</span></div>';
            echo '<div class="control">';
            $chk = cfg_chk($b[0]) ? 'checked' : '';
            echo '<input type="checkbox" name="cfg_' . $b[0] . '" value="1" ' . $chk . '></div></div>';
        }
        echo '</div></div>';
        ?>

        <div class="card tab-footer" style="border-radius:6px; margin-top:0;">
            <button type="submit" class="btn-save">💾 설정 저장</button>
        </div>
    </form>
</div>

<script>
$(function() {

    $('.tab-nav a').click(function(e) {
        e.preventDefault();
        $('.tab-nav a').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });
});
</script>

</body>
</html>
