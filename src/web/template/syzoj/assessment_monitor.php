<?php
// 수행평가 부정행위 감지 스크립트. 진행 중인 수행평가의 문제 화면(problem)과 편집기 화면(editor)에서 불러온다.
// 화면 이탈·복귀, 붙여넣기, 한 번에 많은 코드가 들어오는 경우(burst), 복사를 assessment_event.php로 보낸다.
// 사용 전 contest-check.php가 $is_assessment, $start_time, $end_time을 정해 두어야 한다.
if (!isset($gs_monitor_place)) $gs_monitor_place = "problem";
if (isset($is_assessment) && $is_assessment && isset($cid) && intval($cid) > 0
    && isset($_SESSION[$OJ_NAME . '_' . 'user_id'])
    && !isset($_SESSION[$OJ_NAME . '_' . 'administrator']) && !isset($_SESSION[$OJ_NAME . '_' . 'm' . intval($cid)])
    && time() >= $start_time && time() < $end_time) {
?>
<script>
(function(){
  var cid = <?php echo intval($cid) ?>;
  var num = <?php echo isset($pid) ? intval($pid) : -1 ?>;
  var place = <?php echo json_encode($gs_monitor_place) ?>;
  var endAt = Date.now() + <?php echo intval(($end_time - time()) * 1000) ?>;
  var url = <?php echo json_encode((isset($path_fix) ? $path_fix : "") . "assessment_event.php") ?>;
  var BURST_CHARS = 100, BURST_LINES = 5;

  function send(type, amount, lines){
    if (Date.now() >= endAt) return;
    var data = new FormData();
    data.append("cid", cid); data.append("num", num); data.append("place", place);
    data.append("type", type); data.append("amount", amount || 0); data.append("lines", lines || 0);
    if (navigator.sendBeacon && navigator.sendBeacon(url, data)) return;
    try { fetch(url, {method: "POST", body: data, credentials: "same-origin", keepalive: true}); } catch (e) {}
  }
  function countLines(text){ return text ? text.split(/\r\n|\r|\n/).length : 0; }

  // 화면 이탈은 가장 바깥 창에서만 잰다. 문제 화면 안의 편집기 iframe은 부모 창이 대신 잰다.
  if (window.top === window) {
    var leftAt = 0;
    function check(){
      var away = document.hidden || !document.hasFocus();
      if (away && !leftAt) { leftAt = Date.now(); send("leave", 0, 0); }
      else if (!away && leftAt) { send("return", Math.round((Date.now() - leftAt) / 1000), 0); leftAt = 0; }
    }
    document.addEventListener("visibilitychange", check);
    window.addEventListener("blur", function(){ setTimeout(check, 200); });
    window.addEventListener("focus", check);
    setInterval(check, 1000);
  }

  var lastPasteAt = 0;
  document.addEventListener("paste", function(e){
    var text = (e.clipboardData || window.clipboardData) ? (e.clipboardData || window.clipboardData).getData("text") : "";
    lastPasteAt = Date.now();
    send("paste", text.length, countLines(text));
  }, true);

  document.addEventListener("copy", function(){
    var text = "";
    if (place === "editor" && window.editor && editor.getCopyText) text = editor.getCopyText();
    if (!text && window.getSelection) text = String(window.getSelection());
    send("copy", text.length, countLines(text));
  }, true);

  // 붙여넣기 이벤트 없이 한 번에 많은 코드가 들어오면(예: 자동 입력 도구) burst로 기록한다.
  if (place === "editor" && window.editor && editor.on) {
    editor.on("change", function(delta){
      if (delta.action !== "insert" || !editor.isFocused() || Date.now() - lastPasteAt < 1000) return;
      var text = delta.lines.join("\n");
      if (text.length >= BURST_CHARS || delta.lines.length >= BURST_LINES) send("burst", text.length, delta.lines.length);
    });
  }
})();
</script>
<?php } ?>
