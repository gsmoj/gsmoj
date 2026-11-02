<?php
/*
 * 문제 등록·수정 화면 전용 마크다운 입력기.
 * textarea.problem-md-editor 위에 서식 버튼 6개(굵게·기울임·밑줄·취소선·인라인 코드·스포일러)만 붙인다.
 * 저장 형식은 기존과 같은 마크다운 텍스트이며, 화면 표시는 문제 페이지의 marked가 맡는다.
 * 밑줄과 스포일러는 마크다운 문법이 없어 HTML 태그(<u>, <span class="md-spoiler">)로 넣는다.
 */
?>
<style>
  .md-editor { background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; overflow: hidden; transition: border-color .15s, box-shadow .15s; width: 100%; }
  .md-editor:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, .15); }
  .md-editor-toolbar { align-items: center; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: 2px; padding: 6px 8px; }
  .md-editor-toolbar .md-editor-sep { background: #e2e8f0; height: 18px; margin: 0 6px; width: 1px; }
  /* 관리자 공통 버튼 규칙(body:not(.admin-menu-frame) button)보다 우선하도록 .md-editor를 앞에 붙인다. */
  .md-editor .md-editor-toolbar button { align-items: center; background: transparent; border: 1px solid transparent; border-radius: 6px; color: #475569; cursor: pointer; display: inline-flex; font: 600 14px/1 -apple-system, BlinkMacSystemFont, "Segoe UI", "Malgun Gothic", sans-serif; height: 32px; justify-content: center; margin: 0; min-width: 32px; min-height: 32px; padding: 0 8px; transition: background .12s, color .12s; }
  .md-editor .md-editor-toolbar button:hover { background: #e2e8f0; color: #0f172a; }
  .md-editor .md-editor-toolbar button:active { background: #cbd5e1; }
  .md-editor .md-editor-toolbar button:focus-visible { outline: 2px solid #2563eb; outline-offset: 1px; }
  .md-editor .md-editor-toolbar button svg { display: block; height: 16px; width: 16px; }
  .md-editor .md-editor-toolbar .md-b { font-weight: 800; }
  .md-editor .md-editor-toolbar .md-i { font-family: Georgia, "Times New Roman", serif; font-style: italic; font-size: 16px; }
  .md-editor .md-editor-toolbar .md-u { text-decoration: underline; text-underline-offset: 3px; }
  .md-editor .md-editor-toolbar .md-s { text-decoration: line-through; }
  .md-editor-toolbar .md-editor-hint { color: #94a3b8; font-size: 12px; margin-left: auto; padding-right: 4px; }
  .md-editor textarea.problem-md-editor { background: #fff; border: 0 !important; border-radius: 0; box-shadow: none !important; box-sizing: border-box; color: #1e293b; display: block; font: 15px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", "Malgun Gothic", sans-serif; margin: 0; min-height: 140px; outline: none; padding: 12px 14px; resize: vertical; width: 100% !important; }
  .md-editor textarea.problem-md-editor::placeholder { color: #94a3b8; }
  @media (max-width: 560px) { .md-editor-toolbar .md-editor-hint { display: none; } }
  /* 미리보기용 스포일러 표시. 문제 화면은 template/syzoj/css/gs-page.css에 같은 규칙이 있다. */
  .md-spoiler { background: #334155; border-radius: 4px; box-decoration-break: clone; -webkit-box-decoration-break: clone; color: transparent; cursor: pointer; padding: 0 3px; transition: background .15s, color .15s; }
  .md-spoiler:not(:focus) * { background: transparent !important; border-color: transparent !important; color: transparent !important; }
  .md-spoiler:not(:focus) img { visibility: hidden; }
  .md-spoiler:hover { background: #475569; }
  .md-spoiler:focus { background: #e2e8f0; color: inherit; outline: none; }
</style>
<script>
(function ($) {
  var ICON_CODE = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5.5 4 1.5 8l4 4M10.5 4l4 4-4 4"/></svg>';
  var ICON_SPOILER = '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 8s2.2-4.5 6-4.5S14 8 14 8s-2.2 4.5-6 4.5S2 8 2 8Z"/><circle cx="8" cy="8" r="1.8"/><path d="M2.5 13.5 13.5 2.5"/></svg>';

  // label: 버튼 표시, title: 툴팁, before/after: 선택 영역 앞뒤에 넣을 문법, sample: 선택이 없을 때 넣을 예시 글자
  var ACTIONS = {
    bold:      { html: 'B', cls: 'md-b', title: '굵게 (Ctrl+B)', before: '**', after: '**', sample: '굵은 글씨' },
    italic:    { html: 'I', cls: 'md-i', title: '기울임 (Ctrl+I)', before: '*', after: '*', sample: '기울인 글씨' },
    underline: { html: 'U', cls: 'md-u', title: '밑줄 (Ctrl+U)', before: '<u>', after: '</u>', sample: '밑줄 글씨' },
    strike:    { html: 'S', cls: 'md-s', title: '취소선', before: '~~', after: '~~', sample: '취소선 글씨' },
    code:      { html: ICON_CODE, cls: '', title: '인라인 코드', before: '`', after: '`', sample: 'code' },
    spoiler:   { html: ICON_SPOILER, cls: '', title: '스포일러(숨김) — 클릭해야 보입니다', before: '<span class="md-spoiler" tabindex="0">', after: '</span>', sample: '숨길 내용' }
  };
  var LAYOUT = ['bold', 'italic', 'underline', 'strike', '|', 'code', 'spoiler'];
  var SHORTCUTS = { b: 'bold', i: 'italic', u: 'underline' };

  // 실행 취소(Ctrl+Z)가 유지되도록 가능하면 insertText를 쓰고, 안 되면 setRangeText로 대신한다.
  function replaceSelection(ta, text) {
    var done = false;
    try { done = text !== '' && document.execCommand('insertText', false, text); } catch (e) { done = false; }
    if (!done) {
      ta.setRangeText(text, ta.selectionStart, ta.selectionEnd, 'end');
      ta.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }

  function isWrappedOutside(value, start, end, before, after) {
    if (value.slice(start - before.length, start) !== before || value.slice(end, end + after.length) !== after) return false;
    // '*'(기울임) 검사에서 '**'(굵게)를 기울임으로 잘못 보지 않게 한다. '***'는 둘 다 적용된 상태로 본다.
    if (before === '*' && value.slice(start - 2, start) === '**' && value.slice(start - 3, start) !== '***') return false;
    return true;
  }

  // 선택 영역에 서식을 씌운다. 이미 씌워져 있으면 벗긴다.
  function applyAction(ta, name) {
    var a = ACTIONS[name];
    var start = ta.selectionStart, end = ta.selectionEnd, value = ta.value;
    var selected = value.slice(start, end);
    ta.focus();

    if (isWrappedOutside(value, start, end, a.before, a.after)) {
      ta.setSelectionRange(start - a.before.length, end + a.after.length);
      replaceSelection(ta, selected);
      ta.setSelectionRange(start - a.before.length, start - a.before.length + selected.length);
      return;
    }
    if (selected.length >= a.before.length + a.after.length && selected.indexOf(a.before) === 0 &&
        selected.slice(selected.length - a.after.length) === a.after) {
      var inner = selected.slice(a.before.length, selected.length - a.after.length);
      replaceSelection(ta, inner);
      ta.setSelectionRange(start, start + inner.length);
      return;
    }
    var text = selected || a.sample;
    replaceSelection(ta, a.before + text + a.after);
    ta.setSelectionRange(start + a.before.length, start + a.before.length + text.length);
  }

  // 붙여넣거나 끌어다 놓은 이미지·PDF 등을 업로드하고 커서 위치에 넣는다. (기존 KindEditor 입력기에서 쓰던 업로드 주소 그대로)
  function uploadFile(ta, file) {
    var formData = new FormData();
    if (file.name === 'image.png') formData.append('imgFile', file, 'img' + Date.now() + '.png');
    else formData.append('imgFile', file);
    $.ajax({
      url: '/kindeditor/php/upload_json.php?dir=image',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (res) {
        if (!res || !res.url) { alert((res && res.message) || '파일을 올리지 못했습니다.'); return; }
        var src = res.url, tag = '![이미지](' + src + ')';
        if (/\.mp3$/i.test(src)) tag = '<audio controls src="' + src + '"></audio>';
        else if (/\.pdf$/i.test(src)) tag = '<iframe src="' + src + '" width="100%" height="800px"></iframe>';
        else if (/\.mp4$/i.test(src)) tag = '<video controls width="68%"><source src="' + src + '" type="video/mp4"></video>';
        ta.focus();
        replaceSelection(ta, tag);
      },
      error: function () { alert('파일을 올리지 못했습니다.'); }
    });
  }

  function isUploadable(file) {
    return file && (file.type.indexOf('image/') === 0 || /pdf$/i.test(file.type));
  }

  function enhance(ta) {
    if (ta.getAttribute('data-md-editor') === '1') return;
    ta.setAttribute('data-md-editor', '1');

    var $wrap = $('<div class="md-editor"></div>');
    var $bar = $('<div class="md-editor-toolbar" role="toolbar" aria-label="서식 도구"></div>');
    $.each(LAYOUT, function (_, name) {
      if (name === '|') { $bar.append('<span class="md-editor-sep" aria-hidden="true"></span>'); return; }
      var a = ACTIONS[name];
      $('<button type="button" tabindex="-1"></button>')
        .addClass(a.cls).attr({ title: a.title, 'aria-label': a.title, 'data-action': name }).html(a.html)
        .appendTo($bar);
    });
    $bar.append('<span class="md-editor-hint">마크다운 지원</span>');
    $(ta).before($wrap);
    $wrap.append($bar, ta);

    // 버튼을 눌러도 textarea의 선택 영역이 풀리지 않게 mousedown 기본 동작을 막는다.
    $bar.on('mousedown', 'button', function (e) { e.preventDefault(); });
    $bar.on('click', 'button', function () { applyAction(ta, $(this).attr('data-action')); });

    $(ta).on('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) return;
      var name = SHORTCUTS[(e.key || '').toLowerCase()];
      if (!name) return;
      e.preventDefault();
      applyAction(ta, name);
    }).on('paste', function (e) {
      var items = (e.originalEvent.clipboardData || {}).items || [];
      for (var i = 0; i < items.length; i++) {
        if (items[i].kind === 'file' && isUploadable(items[i].getAsFile())) {
          e.preventDefault();
          uploadFile(ta, items[i].getAsFile());
          return;
        }
      }
    }).on('drop', function (e) {
      var files = (e.originalEvent.dataTransfer || {}).files || [];
      if (!files.length) return;
      e.preventDefault();
      for (var i = 0; i < files.length; i++) if (isUploadable(files[i])) uploadFile(ta, files[i]);
    });
  }

  $(function () { $('textarea.problem-md-editor').each(function () { enhance(this); }); });
})(jQuery);
</script>
