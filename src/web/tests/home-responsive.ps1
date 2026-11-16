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
Assert-Contains $header 'aria-controls="gs-collapse"' '모바일 메뉴 토글이 메뉴 패널과 연결되어 있지 않습니다.'
Assert-Contains $header '.gs-collapse.open { display: flex; }' '모바일 메뉴가 열릴 때 사용할 레이아웃 규칙이 없습니다.'
Assert-Contains $header "panel.classList.toggle('open')" '모바일 메뉴 토글이 메뉴 패널을 열지 않습니다.'
Assert-NotContains $header 'class="desktop-only' '모바일 메뉴 안의 항목이 desktop-only로 숨겨집니다.'
Assert-Contains $index 'min-width: 0;' '홈 컬럼이 viewport보다 줄어들 수 없습니다.'
Assert-Contains $index 'table-layout: fixed;' '홈 표의 반응형 열 너비 규칙이 없습니다.'
Assert-Contains $index 'class="ui grid home-content-grid"' '홈 콘텐츠 그리드가 Semantic UI 3열 규칙과 충돌합니다.'
Assert-Contains $index 'grid-template-columns: minmax(0, 2fr) minmax(240px, 1fr);' '데스크톱 홈 그리드의 사이드바 폭이 안정적으로 지정되지 않았습니다.'
Assert-NotContains $index '.home-content-grid table { min-width: 460px; }' '홈 표가 모바일에서 고정 폭으로 확장됩니다.'
Assert-Contains $index 'grid-template-columns: minmax(0, 1fr);' '모바일 홈 그리드가 한 열로 바뀌지 않습니다.'

Write-Output 'home responsive regression checks passed'
