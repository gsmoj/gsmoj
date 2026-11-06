<?php
/*
 * 문제 등록·수정 화면 전용 마크다운 입력기.
 * textarea.problem-md-editor 위에 서식 버튼 6개(굵게·기울임·밑줄·취소선·인라인 코드·스포일러)만 붙인다.
 * 저장 형식은 기존과 같은 마크다운 텍스트이며, 화면 표시는 문제 페이지의 marked가 맡는다.
 * 밑줄과 스포일러는 마크다운 문법이 없어 HTML 태그(<u>, <span class="md-spoiler">)로 넣는다.
 * 툴바 오른쪽 '서식 보기' 스위치를 켜면 노션처럼 서식이 적용된 모습 그대로 편집하고, 끄면 **굵게** 같은 원문으로 편집한다.
 * 서식 보기에서 고친 내용은 곧바로 마크다운으로 바꿔 textarea에 넣으므로 저장 형식은 그대로다.
 * 렌더링은 같은 화면에서 이미 불러오는 marked(template/bs3/marked.min.js)를 쓴다.
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
  @media (max-width: 560px) { .md-editor-toolbar .md-editor-hint { display: none; } .md-editor .md-editor-toolbar .md-editor-toggle { margin-left: auto; } }
  /* 서식 보기 스위치 */
  .md-editor .md-editor-toolbar .md-editor-toggle { gap: 8px; margin-left: 8px; padding: 0 6px 0 10px; color: #475569; font-size: 13px; font-weight: 700; }
  .md-editor-toggle .md-editor-switch { background: #cbd5e1; border-radius: 999px; display: inline-block; height: 18px; position: relative; transition: background .15s; width: 32px; }
  .md-editor-toggle .md-editor-switch::after { background: #fff; border-radius: 50%; box-shadow: 0 1px 2px rgba(15, 23, 42, .25); content: ''; height: 14px; left: 2px; position: absolute; top: 2px; transition: transform .15s; width: 14px; }
  .md-editor-toggle[aria-checked="true"] { color: #1d4ed8; }
  .md-editor-toggle[aria-checked="true"] .md-editor-switch { background: #2563eb; }
  .md-editor-toggle[aria-checked="true"] .md-editor-switch::after { transform: translateX(14px); }
  .md-editor-toolbar button.is-active { background: #dbeafe !important; color: #1d4ed8 !important; }
  /* 서식 보기(on): 서식이 적용된 모습 그대로 편집하는 영역. 입력칸과 같은 글꼴·여백을 쓰고, 문제 화면과 비슷하게 꾸민다. */
  .md-editor-visual { box-sizing: border-box; color: #1e293b; font: 15px/1.65 -apple-system, BlinkMacSystemFont, "Segoe UI", "Malgun Gothic", sans-serif; min-height: 140px; outline: none; overflow: auto; overflow-wrap: anywhere; padding: 12px 14px; position: relative; resize: vertical; }
  .md-editor-visual.is-empty::before { color: #94a3b8; content: attr(data-placeholder); left: 14px; pointer-events: none; position: absolute; right: 14px; top: 12px; white-space: pre-wrap; }
  .md-editor-visual > :first-child, .md-editor-visual .md-block > :first-child { margin-top: 0; }
  .md-editor-visual p { margin: 0 0 .8em; }
  .md-editor-visual h1, .md-editor-visual h2, .md-editor-visual h3, .md-editor-visual h4 { margin: 1em 0 .5em; line-height: 1.35; }
  .md-editor-visual code { background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 4px; color: #0f172a; font: 13px Consolas, "D2Coding", monospace; padding: 1px 5px; }
  .md-editor-visual pre { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin: 0 0 .8em; overflow: auto; padding: 10px 12px; white-space: pre; }
  .md-editor-visual pre code { background: transparent; border: 0; padding: 0; }
  .md-editor-visual blockquote { border-left: 4px solid #cbd5e1; color: #475569; margin: 0 0 .8em; padding: 2px 12px; }
  .md-editor-visual ul, .md-editor-visual ol { margin: 0 0 .8em; padding-left: 1.6em; }
  .md-editor-visual table { border-collapse: collapse; margin: 0 0 .8em; }
  .md-editor-visual th, .md-editor-visual td { border: 1px solid #cbd5e1; padding: 4px 10px; }
  .md-editor-visual img, .md-editor-visual video, .md-editor-visual iframe { max-width: 100%; }
  /* 표·HTML 블록은 서식 보기에서 고치면 원문이 바뀔 수 있어 잠가 두고, 서식 보기를 끈 뒤 수정하게 한다. */
  .md-editor-visual .md-block[contenteditable="false"] { border-radius: 6px; cursor: not-allowed; margin: 0 0 .8em; outline: 1px dashed #cbd5e1; outline-offset: 3px; }
  /* 편집 중에는 스포일러 내용이 보여야 고칠 수 있으므로, 숨기는 대신 점선 배경으로 표시한다. */
  .md-editor-visual .md-spoiler, .md-editor-visual .md-spoiler:hover { background: #e2e8f0; color: inherit; outline: 1px dashed #94a3b8; }
  .md-editor-visual .md-spoiler:not(:focus) * { color: inherit !important; }
  .md-editor-visual .md-spoiler:not(:focus) img { visibility: visible; }
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

  // ---------------------------------------------------------------------------
  // 서식 보기(on): 노션처럼 서식이 적용된 모습 그대로 편집하고, 바뀐 내용은 즉시 마크다운으로 바꿔 textarea에 넣는다.
  // 손대지 않은 블록은 원래 마크다운 원문을 그대로 쓰고, 고친 블록만 다시 마크다운으로 만든다.
  // ---------------------------------------------------------------------------
  var VISUAL_KEY = 'gsmoj.mdEditor.visual';
  var MD_OPTS = { breaks: true, gfm: true };
  var EDITORS = [];
  var NATIVE = { bold: 'bold', italic: 'italic', underline: 'underline', strike: 'strikeThrough' };
  var BLOCK_TAGS = /^(P|DIV|H[1-6]|UL|OL|LI|BLOCKQUOTE|PRE|TABLE|THEAD|TBODY|TR|HR|SECTION|FIGURE|DETAILS)$/;
  var RAW_HTML_TAGS = /^(IFRAME|VIDEO|AUDIO|SVG|MATH|TABLE|OBJECT|EMBED|PICTURE)$/;
  var FORMAT_KEY = { B: 'b', STRONG: 'b', I: 'i', EM: 'i', S: 's', DEL: 's', STRIKE: 's', U: 'u' };

  function hasMarked() {
    return !!(window.marked && typeof window.marked.lexer === 'function' && typeof window.marked.parser === 'function');
  }

  // 서식 보기에서는 스크립트와 이벤트 속성(onclick 등)을 지워 관리자 화면에서 실행되지 않게 한다. (원문에는 손대지 않는다)
  function sanitizeHtml(html) {
    var doc = new DOMParser().parseFromString('<div>' + html + '</div>', 'text/html');
    var root = doc.body.firstChild;
    $(root).find('script, style, object, embed').remove();
    $(root).find('*').each(function () {
      for (var i = this.attributes.length - 1; i >= 0; i--) {
        var attr = this.attributes[i];
        if (/^on/i.test(attr.name) || (/^(href|src|action|formaction)$/i.test(attr.name) && /^\s*javascript:/i.test(attr.value))) this.removeAttribute(attr.name);
      }
    });
    return root.innerHTML;
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function repeat(ch, n) { return new Array(n + 1).join(ch); }

  // 마크다운 → 편집 화면. 블록(문단·제목·목록 등)마다 원문을 기억해 두었다가, 바뀌지 않았으면 원문을 그대로 쓴다.
  function renderVisual(ed) {
    var root = ed.$visual[0];
    var src = ed.ta.value.replace(/\r\n?/g, '\n');
    root.innerHTML = '';
    if ($.trim(src) !== '') {
      var tokens = window.marked.lexer(src, MD_OPTS);
      $.each(tokens, function (_, tok) {
        if (tok.type === 'space') return;
        var list = [tok];
        list.links = tokens.links;
        var block = document.createElement('div');
        block.className = 'md-block';
        block.innerHTML = sanitizeHtml(window.marked.parser(list, MD_OPTS));
        if (/^(html|table|hr)$/.test(tok.type)) {
          block.setAttribute('contenteditable', 'false');
          block.setAttribute('title', '이 부분은 서식 보기를 끄고 수정하세요.');
        }
        root.appendChild(block);
        block._mdRaw = tok.raw.replace(/^\n+|\n+$/g, '');
        block._mdHtml = block.innerHTML;
      });
    }
    ed.synced = ed.ta.value;
    updateEmpty(ed);
  }

  // 일반 글자 속 마크다운 기호를 이스케이프해 글자 그대로 보이게 한다. 수식($...$) 안은 건드리지 않는다.
  function escapeText(s) {
    s = s.replace(/ /g, ' ').replace(/\n/g, ' ');
    return s.split(/(\$\$[\s\S]*?\$\$|\$[^$\n]+\$)/).map(function (part, i) {
      if (i % 2) return part;
      return part
        .replace(/\\/g, '\\\\')
        .replace(/([*`~])/g, '\\$1')
        .replace(/_/g, function (m, at, str) { return (/\w/.test(str.charAt(at - 1)) && /\w/.test(str.charAt(at + 1))) ? '_' : '\\_'; })
        .replace(/&(?=#?\w+;)/g, '&amp;')
        .replace(/<(?=[A-Za-z\/!?])/g, '&lt;');
    }).join('');
  }

  function codeSpan(text) {
    text = text.replace(/ /g, ' ').replace(/\n/g, ' ');
    if (text === '') return '';
    var longest = 0;
    (text.match(/`+/g) || []).forEach(function (run) { longest = Math.max(longest, run.length); });
    var fence = repeat('`', longest + 1);
    var pad = /^`|`$/.test(text) ? ' ' : '';
    return fence + pad + text + pad + fence;
  }

  // 앞뒤 공백은 기호 밖으로 빼야 마크다운이 서식으로 인식한다.
  function wrapMarker(open, close, inner) {
    var m = inner.match(/^(\s*)([\s\S]*?)(\s*)$/);
    if (m[2] === '') return inner;
    return m[1] + open + m[2] + close + m[3];
  }

  function onlyAttrs(el, allowed) {
    for (var i = 0; i < el.attributes.length; i++) if (allowed.indexOf(el.attributes[i].name) === -1) return false;
    return true;
  }

  function openTag(el) {
    var s = '<' + el.nodeName.toLowerCase();
    for (var i = 0; i < el.attributes.length; i++) s += ' ' + el.attributes[i].name + '="' + escapeHtml(el.attributes[i].value) + '"';
    return s + '>';
  }

  function isBlock(node) { return node.nodeType === 1 && BLOCK_TAGS.test(node.nodeName); }

  // 같은 서식(굵게 등)이 이어진 형제 노드는 하나로 묶어 '**가****나**'처럼 기호가 붙지 않게 한다.
  function inlineToMd(nodes) {
    var out = '', list = Array.prototype.slice.call(nodes), i = 0;
    while (i < list.length) {
      var node = list[i], key = node.nodeType === 1 && !node.attributes.length ? FORMAT_KEY[node.nodeName] : null;
      if (!key) { out += inlineNode(node); i++; continue; }
      var inner = '';
      while (i < list.length && list[i].nodeType === 1 && !list[i].attributes.length && FORMAT_KEY[list[i].nodeName] === key) {
        inner += inlineToMd(list[i].childNodes);
        i++;
      }
      if (key === 'b') out += wrapMarker('**', '**', inner);
      else if (key === 'i') out += wrapMarker('*', '*', inner);
      else if (key === 's') out += wrapMarker('~~', '~~', inner);
      else out += wrapMarker('<u>', '</u>', inner);
    }
    return out;
  }

  function inlineNode(node) {
    if (node.nodeType === 3) return escapeText(node.nodeValue);
    if (node.nodeType !== 1) return '';
    var tag = node.nodeName;
    if (tag === 'BR') return '\n';
    if (FORMAT_KEY[tag] && !node.attributes.length) return inlineToMd([node]);
    if (tag === 'CODE') return codeSpan(node.textContent);
    if (tag === 'A' && node.getAttribute('href') && onlyAttrs(node, ['href', 'title'])) {
      var title = node.getAttribute('title');
      return '[' + inlineToMd(node.childNodes) + '](' + node.getAttribute('href') + (title ? ' "' + title.replace(/"/g, '\\"') + '"' : '') + ')';
    }
    if (tag === 'IMG') {
      if (node.getAttribute('src') && onlyAttrs(node, ['src', 'alt'])) return '![' + (node.getAttribute('alt') || '') + '](' + node.getAttribute('src') + ')';
      return openTag(node);
    }
    if (tag === 'INPUT') return node.type === 'checkbox' ? (node.hasAttribute('checked') ? '[x] ' : '[ ] ') : '';
    if (tag === 'SPAN' && node.classList.contains('md-spoiler')) {
      return wrapMarker('<span class="md-spoiler" tabindex="0">', '</span>', inlineToMd(node.childNodes));
    }
    // 브라우저가 편집 중에 만드는 스타일 span·font는 벗기고 글자만 남긴다.
    if ((tag === 'SPAN' && onlyAttrs(node, ['style'])) || tag === 'FONT') return inlineToMd(node.childNodes);
    if (RAW_HTML_TAGS.test(tag)) return node.outerHTML;
    if (isBlock(node)) return '\n' + blocksToMd(node.childNodes, true) + '\n';
    // 그 밖의 태그(sup, sub, kbd, 속성이 있는 u 등)는 HTML 태그를 그대로 두고 안쪽만 변환한다.
    return openTag(node) + inlineToMd(node.childNodes) + '</' + tag.toLowerCase() + '>';
  }

  // 문단 한 개. 줄 맨 앞의 '#', '-', '1.' 같은 글자가 제목·목록으로 바뀌지 않게 이스케이프한다.
  function inlineBlock(nodes) {
    var text = inlineToMd(nodes).replace(/^[ \n]+|[ \n]+$/g, '');
    if (text === '') return '';
    return text.split('\n').map(function (line) {
      line = line.replace(/^ +/, '');
      if (/^(=+|-+)\s*$/.test(line)) return '\\' + line;
      return line
        .replace(/^(#{1,6})(?=\s|$)/, '\\$1')
        .replace(/^([-+])(?=\s)/, '\\$1')
        .replace(/^>/, '\\>')
        .replace(/^(\d+)([.)])(?=\s)/, '$1\\$2');
    }).join('\n');
  }

  function listToMd(el) {
    var ordered = el.nodeName === 'OL', n = parseInt(el.getAttribute('start'), 10) || 1, lines = [];
    $.each(el.children, function (_, li) {
      if (li.nodeName !== 'LI') return;
      var marker = ordered ? (n++) + '. ' : '- ';
      var body = blocksToMd(li.childNodes, true) || '';
      lines.push(marker + body.split('\n').join('\n' + repeat(' ', marker.length)));
    });
    return lines.join('\n');
  }

  function blockToMd(el) {
    var tag = el.nodeName;
    if (tag === 'DIV' && el.classList.contains('md-block')) {
      if (el._mdRaw !== undefined && el.innerHTML === el._mdHtml) return el._mdRaw;
      if (el.getAttribute('contenteditable') === 'false') return el._mdRaw !== undefined ? el._mdRaw : el.innerHTML;
      return blocksToMd(el.childNodes);
    }
    if (/^H[1-6]$/.test(tag)) {
      var text = inlineToMd(el.childNodes).replace(/\n/g, ' ').trim();
      return text ? repeat('#', +tag.charAt(1)) + ' ' + text : '';
    }
    if (tag === 'P' || tag === 'DIV' || tag === 'SECTION') {
      for (var i = 0; i < el.childNodes.length; i++) if (isBlock(el.childNodes[i])) return blocksToMd(el.childNodes);
      return inlineBlock(el.childNodes);
    }
    if (tag === 'BLOCKQUOTE') {
      var quoted = blocksToMd(el.childNodes);
      return quoted ? quoted.split('\n').map(function (l) { return l ? '> ' + l : '>'; }).join('\n') : '';
    }
    if (tag === 'UL' || tag === 'OL') return listToMd(el);
    if (tag === 'LI') return blocksToMd(el.childNodes, true);
    if (tag === 'PRE') {
      var code = el.querySelector('code');
      var lang = code ? ((code.className.match(/language-(\S+)/) || [])[1] || '') : '';
      var body = (code || el).textContent.replace(/ /g, ' ').replace(/\n$/, '');
      var fence = '```';
      while (body.indexOf(fence) !== -1) fence += '`';
      return fence + (lang === 'plaintext' ? '' : lang) + '\n' + body + '\n' + fence;
    }
    if (tag === 'HR') return '---';
    // 표 등 그 밖의 블록은 HTML 그대로 둔다. (마크다운 안의 HTML로 그대로 표시된다)
    return el.outerHTML;
  }

  // 블록 여러 개. 블록 사이 공백 글자는 무시하고, 블록 밖에 흩어진 글자는 하나의 문단으로 묶는다.
  function blocksToMd(nodes, tight) {
    var out = [], run = [];
    function flush() {
      if (!run.length) return;
      var t = inlineBlock(run);
      if (t !== '') out.push(t);
      run = [];
    }
    $.each(nodes, function (_, node) {
      if (isBlock(node)) {
        flush();
        var t = blockToMd(node);
        if (t !== '') out.push(t);
      } else if (!(node.nodeType === 3 && !/\S/.test(node.nodeValue) && !run.length)) {
        run.push(node);
      }
    });
    flush();
    return out.join(tight ? '\n' : '\n\n');
  }

  function updateEmpty(ed) {
    var root = ed.$visual[0];
    ed.$visual.toggleClass('is-empty', $.trim(root.textContent) === '' && !root.querySelector('img, iframe, video, audio, table, hr'));
  }

  // 편집 화면의 내용을 마크다운으로 바꿔 textarea에 넣는다. 저장·전체 미리보기는 늘 textarea 값을 쓴다.
  function syncFromVisual(ed) {
    if (!ed.dirty) return;
    var md = blocksToMd(ed.$visual[0].childNodes);
    if (md !== ed.ta.value) {
      ed.ta.value = md;
      ed.synced = md;
      ed.ta.dispatchEvent(new Event('input', { bubbles: true }));
    }
    ed.synced = ed.ta.value;
  }

  // 다른 스크립트(AI 문제 채우기 등)가 textarea 값을 직접 바꾸면 편집 화면을 다시 그린다.
  function refreshIfChanged(ed) {
    if (ed.on && ed.ta.value !== ed.synced) {
      ed.dirty = false;
      renderVisual(ed);
    }
  }

  function selectionInside(ed) {
    var sel = window.getSelection();
    return sel.rangeCount && ed.$visual[0].contains(sel.getRangeAt(0).commonAncestorContainer) ? sel : null;
  }

  function findWrapped(ed, name) {
    var sel = selectionInside(ed);
    if (!sel) return null;
    for (var n = sel.getRangeAt(0).commonAncestorContainer; n && n !== ed.$visual[0]; n = n.parentNode) {
      if (n.nodeType !== 1) continue;
      if (name === 'code' && n.nodeName === 'CODE' && n.parentNode.nodeName !== 'PRE') return n;
      if (name === 'spoiler' && n.classList.contains('md-spoiler')) return n;
    }
    return null;
  }

  // 인라인 코드·스포일러는 브라우저 기본 명령이 없어 선택한 글자를 해당 태그로 감싸거나 벗긴다.
  function toggleWrap(ed, name) {
    var sel = selectionInside(ed);
    if (!sel) return;
    var el = findWrapped(ed, name);
    if (el) {
      var range = document.createRange();
      range.selectNode(el);
      sel.removeAllRanges();
      sel.addRange(range);
      document.execCommand('insertText', false, el.textContent);
      return;
    }
    var text = sel.toString() || ACTIONS[name].sample;
    var html = name === 'code'
      ? '<code>' + escapeHtml(text) + '</code>'
      : '<span class="md-spoiler" tabindex="0">' + escapeHtml(text) + '</span>';
    document.execCommand('insertHTML', false, html);
  }

  function applyVisual(ed, name) {
    if (!selectionInside(ed)) ed.$visual[0].focus();
    if (NATIVE[name]) document.execCommand(NATIVE[name], false, null);
    else toggleWrap(ed, name);
    updateActive(ed);
  }

  // 커서가 있는 곳의 서식에 맞춰 툴바 버튼을 눌린 상태로 표시한다.
  function updateActive(ed) {
    var inside = ed.on && selectionInside(ed);
    ed.$bar.find('button[data-action]').each(function () {
      var name = $(this).attr('data-action'), active = false;
      if (inside) {
        try { active = NATIVE[name] ? document.queryCommandState(NATIVE[name]) : !!findWrapped(ed, name); } catch (e) { active = false; }
      }
      $(this).toggleClass('is-active', active);
    });
  }

  function insertUploadedVisual(ed, range, tag) {
    var html = /^!\[/.test(tag) ? '<img src="' + escapeHtml(tag.replace(/^!\[[^\]]*\]\(|\)$/g, '')) + '" alt="이미지">' : tag;
    ed.$visual[0].focus();
    if (range) {
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    }
    document.execCommand('insertHTML', false, html);
  }

  // on이면 서식이 적용된 편집 화면을, off면 마크다운 원문 입력칸을 보여 준다.
  function setVisual(ed, on) {
    if (on && !hasMarked()) on = false;
    if (on === ed.on) return;
    if (on) {
      ed.$visual.css('min-height', Math.max($(ed.ta).outerHeight(), 140) + 'px');
      ed.dirty = false;
      renderVisual(ed);
      $(ed.ta).hide();
      ed.$visual.show();
    } else {
      syncFromVisual(ed);
      ed.$visual.hide();
      ed.$visual[0].innerHTML = '';
      $(ed.ta).show();
    }
    ed.on = on;
    ed.$wrap.toggleClass('is-visual', on);
    ed.$toggle.attr({
      'aria-checked': on ? 'true' : 'false',
      title: on ? '서식 보기 끄기 — **굵게**처럼 마크다운 원문으로 편집합니다' : '서식 보기 켜기 — 서식이 적용된 모습 그대로 편집합니다'
    });
    updateActive(ed);
  }

  // 스위치는 화면의 모든 입력칸에 함께 적용하고, 다음에 열 때도 같은 상태로 시작한다.
  function setAllVisual(on) {
    $.each(EDITORS, function (_, ed) { setVisual(ed, on); });
    try { localStorage.setItem(VISUAL_KEY, on ? '1' : '0'); } catch (e) { /* 저장할 수 없는 환경은 무시한다 */ }
  }

  // 붙여넣거나 끌어다 놓은 이미지·PDF 등을 업로드한다. (기존 KindEditor 입력기에서 쓰던 업로드 주소 그대로)
  function uploadFile(file, done) {
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
        done(tag);
      },
      error: function () { alert('파일을 올리지 못했습니다.'); }
    });
  }

  function isUploadable(file) {
    return file && (file.type.indexOf('image/') === 0 || /pdf$/i.test(file.type));
  }

  // 업로드가 끝나면 올리기 시작한 곳(편집 화면 또는 원문 입력칸)의 커서 위치에 넣는다.
  function uploadTo(ed, file) {
    var sel = selectionInside(ed), range = ed.on && sel ? sel.getRangeAt(0).cloneRange() : null, wasVisual = ed.on;
    uploadFile(file, function (tag) {
      if (wasVisual && ed.on) { insertUploadedVisual(ed, range, tag); return; }
      ed.ta.focus();
      replaceSelection(ed.ta, tag);
    });
  }

  function handleFiles(ed, e, files) {
    var found = false;
    for (var i = 0; i < files.length; i++) {
      if (isUploadable(files[i])) { found = true; uploadTo(ed, files[i]); }
    }
    if (found) e.preventDefault();
    return found;
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
    var $toggle = $('<button type="button" class="md-editor-toggle" role="switch" aria-checked="false"><span class="md-editor-switch" aria-hidden="true"></span>서식 보기</button>')
      .attr({ 'aria-label': '서식 보기', title: '서식 보기 켜기 — 서식이 적용된 모습 그대로 편집합니다' }).appendTo($bar);
    var $visual = $('<div class="md-editor-visual" contenteditable="true" role="textbox" aria-multiline="true" spellcheck="false"></div>')
      .attr({ 'data-placeholder': ta.getAttribute('placeholder') || '', 'aria-label': '서식이 적용된 본문 편집' }).hide();
    $(ta).before($wrap);
    $wrap.append($bar, ta, $visual);

    var ed = { ta: ta, $wrap: $wrap, $bar: $bar, $toggle: $toggle, $visual: $visual, on: false, dirty: false };
    EDITORS.push(ed);
    if (!hasMarked()) $toggle.prop('disabled', true).attr('title', '서식 보기를 불러오지 못했습니다.');

    $toggle.on('click', function () { setAllVisual(!ed.on); });

    // 버튼을 눌러도 입력칸의 선택 영역이 풀리지 않게 mousedown 기본 동작을 막는다.
    $bar.on('mousedown', 'button', function (e) { e.preventDefault(); });
    $bar.on('click', 'button[data-action]', function () {
      var name = $(this).attr('data-action');
      if (ed.on) applyVisual(ed, name); else applyAction(ta, name);
    });

    $(ta).on('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) return;
      var name = SHORTCUTS[(e.key || '').toLowerCase()];
      if (!name) return;
      e.preventDefault();
      applyAction(ta, name);
    }).on('paste', function (e) {
      var items = (e.originalEvent.clipboardData || {}).items || [], files = [];
      for (var i = 0; i < items.length; i++) if (items[i].kind === 'file') files.push(items[i].getAsFile());
      handleFiles(ed, e, files);
    }).on('drop', function (e) {
      handleFiles(ed, e, (e.originalEvent.dataTransfer || {}).files || []);
    });

    $visual.on('focus', function () {
      refreshIfChanged(ed);
      // Enter는 새 문단(<p>), 서식은 <b>·<i> 태그로 만들게 한다.
      try { document.execCommand('defaultParagraphSeparator', false, 'p'); document.execCommand('styleWithCSS', false, false); } catch (e) { /* 지원하지 않는 브라우저는 기본 동작을 쓴다 */ }
    }).on('input', function () {
      ed.dirty = true;
      updateEmpty(ed);
      syncFromVisual(ed);
    }).on('keydown', function (e) {
      if (!(e.ctrlKey || e.metaKey) || e.altKey || e.shiftKey) return;
      var name = SHORTCUTS[(e.key || '').toLowerCase()];
      if (!name) return;
      e.preventDefault();
      applyVisual(ed, name);
    }).on('keyup mouseup', function () { updateActive(ed); }).on('paste', function (e) {
      var data = e.originalEvent.clipboardData || {}, items = data.items || [], files = [];
      for (var i = 0; i < items.length; i++) if (items[i].kind === 'file') files.push(items[i].getAsFile());
      if (handleFiles(ed, e, files)) return;
      // 다른 곳에서 복사한 글꼴·색 등이 섞이지 않게 글자만 붙여 넣는다.
      e.preventDefault();
      document.execCommand('insertText', false, data.getData ? data.getData('text/plain') : '');
    }).on('drop', function (e) {
      handleFiles(ed, e, (e.originalEvent.dataTransfer || {}).files || []);
    });

    // 제출 직전에 한 번 더 맞춰 둔다.
    if (ta.form) $(ta.form).on('submit', function () { if (ed.on) syncFromVisual(ed); });
  }

  $(function () {
    $('textarea.problem-md-editor').each(function () { enhance(this); });
    var saved = null;
    try { saved = localStorage.getItem(VISUAL_KEY); } catch (e) { saved = null; }
    if (saved === '1') setAllVisual(true);
    setInterval(function () {
      $.each(EDITORS, function (_, ed) { if (document.activeElement !== ed.$visual[0]) refreshIfChanged(ed); });
    }, 500);
  });
})(jQuery);
</script>
