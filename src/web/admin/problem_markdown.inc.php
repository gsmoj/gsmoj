<?php
/*
 * 문제 설명·입력·출력·힌트를 저장하기 전에 마크다운 표시용 span으로 감싼다.
 * 문제 화면(template/syzoj/problem.php)은 class="md" 요소만 marked로 그리므로,
 * 감싸지 않으면 **굵게** 같은 마크다운 문법이 그대로 글자로 보인다.
 * 수정 화면은 problem_editor_content()로 이 span을 벗겨서 입력칸에 보여 준다.
 */
function problem_markdown_wrap($content, $auto_select = false)
{
  global $OJ_MARKDOWN;

  $content = str_replace(array("\r\n", "\r"), "\n", trim((string)$content));
  $plain_content = trim(html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0");
  // 글자가 없어도 이미지·PDF(iframe)·영상(video 등)만 있는 본문은 지우지 않는다.
  if ($plain_content === '' && !preg_match('/<(?:img|iframe|video|audio|embed|object)\b/i', $content)) return '';
  // 마크다운을 쓰지 않는 설정이거나 이미 md로 감싼 내용(가져오기 등)은 그대로 둔다.
  // class="md-spoiler"는 md로 감싼 것이 아니므로 class 값에 md가 단어 단위로 있을 때만 감싼 것으로 본다.
  if (empty($OJ_MARKDOWN) || preg_match('/class=["\'](?:[^"\']*\s)?md(?:\s[^"\']*)?["\']/i', $content)) return $content;

  $class_name = $auto_select ? 'md auto_select' : 'md';
  return "<span class='" . $class_name . "'>\n" . $content . "\n</span>";
}
