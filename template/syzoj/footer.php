</div>
</div>
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE"?>/css/semantic.min.js"></script>
<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE"?>/css/Chart.min.js"></script>
    <style>
    .footer.gs-footer {
        box-sizing: border-box;
        position: relative;
        width: 100%;
        margin: 48px 0 0 !important;
        padding: 44px 0 56px !important;
        border: 0 !important;
        border-top: 1px solid #dde1e6 !important;
        border-radius: 0;
        background: none transparent;
        box-shadow: none;
        color: #6b7280;
        font-size: 14px;
        line-height: 1.6;
    }
    .gs-footer a { color: #4b5563; text-decoration: none; }
    .gs-footer a:hover { color: #111827; }
    .gs-footer-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px 24px; padding-bottom: 18px; border-bottom: 1px solid #dde1e6; }
    .gs-footer-brand { color: #374151; font-size: 17px; font-weight: 500; }
    .gs-footer-links { display: flex; flex-wrap: wrap; gap: 8px 24px; }
    .gs-footer-links a.strong { color: #111827; font-weight: 600; }
    .gs-footer-bottom { padding-top: 16px; color: #9ca3af; font-size: 13px; }
    .gs-footer-bottom a { color: inherit; }
    </style>
    <?php include(dirname(__FILE__)."/js.php");?>
<?php
    // 바닥글 안내 페이지: 아래 제목과 같은 공지가 등록되어 있으면 그 공지로 연결한다.
    $gs_footer_pages = array("서비스 소개" => 0, "이용약관" => 0, "개인정보처리방침" => 0);
    $gs_footer_rows = mysql_query_cache("select `news_id`,`title` FROM `news` WHERE `defunct`!='Y' AND `title` in ('서비스 소개','이용약관','개인정보처리방침') ORDER BY `time` DESC");
    if ($gs_footer_rows) {
        foreach ($gs_footer_rows as $gs_footer_row) {
            if (isset($gs_footer_pages[$gs_footer_row['title']]) && !$gs_footer_pages[$gs_footer_row['title']])
                $gs_footer_pages[$gs_footer_row['title']] = intval($gs_footer_row['news_id']);
        }
    }
    $gs_footer_name = $domain==$DOMAIN?$OJ_NAME:ucwords($OJ_NAME)."'s OJ";
?>
    <div class="footer gs-footer">
        <div class="ui container">
            <div class="gs-footer-top">
                <span class="gs-footer-brand"><?php echo $gs_footer_name?></span>
                <nav class="gs-footer-links" aria-label="안내">
                    <?php foreach ($gs_footer_pages as $gs_footer_title => $gs_footer_news_id) { if (!$gs_footer_news_id) continue; ?>
                    <a<?php if ($gs_footer_title == "개인정보처리방침") echo ' class="strong"'?> href="<?php echo $path_fix?>viewnews.php?id=<?php echo $gs_footer_news_id?>"><?php echo $gs_footer_title?></a>
                    <?php } ?>
                    <a href="<?php echo $path_fix?>faqs.php">자주 묻는 질문</a>
                    <?php if (!empty($OJ_ADMIN) && $OJ_ADMIN != "root@localhost") { ?>
                    <a href="mailto:<?php echo htmlentities($OJ_ADMIN, ENT_QUOTES, 'UTF-8')?>">문의하기</a>
                    <?php } ?>
                </nav>
            </div>
            <div class="gs-footer-bottom">
                <div>광주소프트웨어마이스터고등학교 &middot; 광주광역시 광산구 상무대로 312</div>
                <div>&copy; <?php echo date('Y')?> <?php echo $gs_footer_name?>. Powered by <a title="GitHub" target="_blank" rel="noreferrer noopener" href="https://github.com/zhblue/hustoj">HUSTOJ</a>.</div>
                <?php if ($OJ_BEIAN) { ?>
                <div>
                <img src="image/icp.png">
                    <a href="https://beian.miit.gov.cn/" target="_blank"><?php echo $OJ_BEIAN; ?></a>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    </div>
<?php if (isset($_SESSION[$OJ_NAME.'_user_id'])){ ?>
        <iframe id="sk" src="session.php" height=0px width=0px ></iframe>
        <script>
        $(document).ready(function(){
                window.setTimeout("$('#sk').attr('src','session.php');",1200000);
        });
        </script>

<?php } ?>


</body>

</html>
