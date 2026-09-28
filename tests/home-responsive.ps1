param(
    [string]$Root = (Split-Path -Parent $PSScriptRoot)
)

$header = Get-Content -Raw (Join-Path $Root 'template/syzoj/header.php')
$index = Get-Content -Raw (Join-Path $Root 'template/syzoj/index.php')

function Assert-Contains([string]$Text, [string]$Needle, [string]$Message) {
    if ($Text.IndexOf($Needle, [System.StringComparison]::Ordinal) -lt 0) {
        throw $Message
    }
}

function Assert-NotContains([string]$Text, [string]$Needle, [string]$Message) {
    if ($Text.IndexOf($Needle, [System.StringComparison]::Ordinal) -ge 0) {
        throw $Message
    }
}

Assert-Contains $header 'id="mobile-menu-toggle"' '모바일 메뉴 토글이 없습니다.'
Assert-Contains $header '#menu.mobile-menu-open' '모바일 메뉴가 열릴 때 사용할 레이아웃 규칙이 없습니다.'
Assert-Contains $header '#menu.mobile-menu-open .desktop-only' '열린 모바일 메뉴의 항목명이 숨겨져 있습니다.'
Assert-Contains $index 'min-width: 0;' '홈 컬럼이 viewport보다 줄어들 수 없습니다.'
Assert-Contains $index 'table-layout: fixed;' '홈 표의 반응형 열 너비 규칙이 없습니다.'
Assert-NotContains $index '.home-content-grid table { min-width: 460px; }' '홈 표가 모바일에서 고정 폭으로 확장됩니다.'

Write-Output 'home responsive regression checks passed'
