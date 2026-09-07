<?php require_once ("admin-header.php");
if (!(isset($_SESSION[$OJ_NAME.'_'.'administrator']))){
	exit(1);
}
?>
<title>Copy Problem</title>
<style>
  .copy-problem-list { list-style: none; margin: 0; padding: 0; }
  .copy-problem-list li { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 0; border-bottom: 1px solid var(--admin-border); }
  .copy-problem-list li:last-child { border-bottom: 0; }
  .copy-problem-list .site-label { flex: 0 0 220px; font-weight: 600; color: #334155; }
  .copy-problem-list form { display: flex; flex: 1 1 320px; gap: 8px; margin: 0; }
  .copy-problem-list input[type=text] { flex: 1; }
</style>
<hr>
<h3 style="text-align:center;">원격 문제 가져오기</h3>
<div class="padding" style="max-width:900px;">
<ul class="copy-problem-list">
<li>
  <span class="site-label">uoj.ac</span>
  <form method=POST action=problem_add_page_uoj.php>
    <input name=url type=text class="input input-xxlarge" value="http://uoj.ac/problem/1">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">hustoj.com</span>
  <form method=POST action=problem_add_page_hustoj.php>
    <input name=url type=text class="input input-xxlarge" value="http://hustoj.com/oj/problem.php?id=1000">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">luogu.com.cn</span>
  <form method=POST action="problem_add_page_luogu.php">
    <input name=url type=text class="input input-xxlarge" value="https://www.luogu.com.cn/problem/P1000">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">loj.ac</span>
  <form method=POST action=problem_add_page_loj.php>
    <input name=url type=text class="input input-xxlarge" placeholder="https://loj.ac/problem/1">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">acm.student.cs.uwaterloo.ca</span>
  <form method=POST action=problem_add_page_waterloo.php>
    <input name=url type=text class="input input-xxlarge" placeholder="http://acm.student.cs.uwaterloo.ca/~acm00">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">poj.org (pku)</span>
  <form method=POST action=problem_add_page_pku.php>
    <input name=url class='input input-xxlarge' type=text value='http://poj.org/problem?id=1000'>
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">acm.hdu.edu.cn</span>
  <form method=POST action=problem_add_page_hdu.php>
    <input name=url class="input input-xxlarge" value="https://acm.hdu.edu.cn/showproblem.php?pid=1172" type=text>
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
<li>
  <span class="site-label">acm.zju.edu.cn</span>
  <form method=POST action=problem_add_page_zju.php>
    <input name=url type=text class="input input-xxlarge" placeholder="http://acm.zju.edu.cn/onlinejudge/showProblem.do?problemCode=1001">
    <input type=submit class="btn btn-primary btn-sm" value="가져오기">
  </form>
</li>
</ul>
</div>
