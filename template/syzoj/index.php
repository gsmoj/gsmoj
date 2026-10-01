<?php $show_title="$MSG_HOME - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<head>
<link rel="stylesheet" href="<?php echo "template/$OJ_TEMPLATE";?>/css/slide.css">
<style>
    .home-hero {
        position: relative;
        overflow: hidden;
        margin: 10px 0 24px;
        padding: 34px 38px;
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #aeb8c4;
        border-radius: 14px;
        color: #fff;
        background: linear-gradient(135deg, #1f2937 0%, #111827 70%, #1d4ed8 140%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .16), 0 0 0 3px rgba(174, 184, 196, .16);
    }
    .home-hero::after {
        position: absolute;
        right: 38px;
        top: 50%;
        color: rgba(255, 255, 255, .12);
        content: "GSM";
        font-size: 74px;
        font-weight: 800;
        letter-spacing: 8px;
        line-height: 1;
        transform: translateY(-50%);
    }
    .home-hero h1 {
        position: relative;
        z-index: 1;
        margin: 0 0 8px;
        font-size: 30px;
        letter-spacing: -.5px;
    }
    .home-hero p {
        position: relative;
        z-index: 1;
        max-width: 620px;
        margin: 0 0 22px;
        color: #cbd5e1;
        font-size: 15px;
    }
    .home-hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .home-hero-actions .button {
        margin: 0;
        border-radius: 7px;
    }
    .home-hero-actions .button.primary {
        background: #2563eb;
    }
    .home-hero-actions .ui.basic.button {
        color: #fff !important;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .45);
    }
    .home-hero-actions .ui.basic.button:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, .12);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7);
    }
    .home-content-grid {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 360px);
        column-gap: 1rem;
        width: 100%;
        min-width: 0;
        margin: 0 !important;
    }
    .home-content-grid > .column {
        display: block;
        float: none !important;
        width: auto !important;
        min-width: 0;
        padding: 0 !important;
    }
    .home-content-grid .ui.top.attached.block.header {
        margin-top: 0 !important;
        border: 1px solid #aeb8c4 !important;
        border-bottom: 0 !important;
        border-left: 1px solid #aeb8c4 !important;
        border-radius: 10px 10px 0 0 !important;
        background: #fff !important;
        color: #1f2937 !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .06), 0 0 0 3px rgba(174, 184, 196, .12);
    }
    .home-content-grid .ui.top.attached.block.header {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }
    .home-content-grid .ui.top.attached.block.header > .icon {
        margin: 0;
    }
    .home-content-grid .ui.top.attached.block.header > a {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .home-content-grid .ui.bottom.attached.segment {
        margin-bottom: 22px !important;
        min-width: 0;
        border: 1px solid #aeb8c4 !important;
        border-radius: 0 0 10px 10px !important;
        background: #fff !important;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .07), 0 0 0 3px rgba(174, 184, 196, .12);
    }
    .home-content-grid .ui.bottom.attached.segment table tr {
        border-bottom: 0 !important;
    }
    .home-content-grid .ui.bottom.attached.segment table tr:last-child {
        border-bottom: 0 !important;
    }
    .home-content-grid table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
    }
    .home-content-grid th,
    .home-content-grid td {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    #submission {
        max-width: 100%;
    }
    @media (max-width: 767px) {
        .home-hero { margin: 8px 0 18px; padding: 28px 24px; }
        .home-hero h1 { font-size: 25px; }
        .home-hero p { font-size: 14px; }
        .home-hero::after { right: 20px; font-size: 42px; letter-spacing: 4px; }
        .home-hero-actions { flex-direction: column; align-items: stretch; }
        .home-hero-actions .button { width: 100%; }
        .home-content-grid.ui.grid {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr);
            row-gap: 0;
            margin: 0 !important;
        }
        .home-content-grid.ui.grid > .column {
            display: block;
            width: 100% !important;
            min-width: 0;
            padding: 0 0 1rem !important;
        }
        .home-content-grid .ui.bottom.attached.segment {
            overflow-x: hidden;
        }
        .home-content-grid .ui.top.attached.block.header {
            white-space: normal;
        }
        .home-content-grid th,
        .home-content-grid td {
            white-space: normal;
        }
    }
</style>
</head>
<?php
// syzoj 메인 페이지는 왼쪽 11칸, 오른쪽 5칸의 Semantic UI 그리드로 구성된다.
// 왼쪽은 공지/랭킹/메인 콘텐츠, 오른쪽은 검색/최근 문제/최근 대회를 담당한다.
?>
<div class="padding">
    <section class="home-hero">
        <h1><?php echo htmlspecialchars($OJ_NAME, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo $MSG_HOME; ?>에서 문제를 풀고, 코드를 제출하고, 실력을 쌓아보세요.</p>
        <div class="home-hero-actions">
            <a class="ui primary button" href="problemset.php"><i class="code icon"></i><?php echo $MSG_PROBLEM; ?> 풀기</a>
            <a class="ui basic button" href="contest.php"><i class="calendar icon"></i><?php echo $MSG_CONTEST; ?> 보기</a>
            <a class="ui basic button" href="ranklist.php"><i class="trophy icon"></i><?php echo $MSG_RANKLIST; ?></a>
        </div>
    </section>

    <div class="ui grid home-content-grid">
        <div class="eleven wide column">
            
            <?php // 최근 공지 10개를 메인 영역 최상단에 노출한다. ?>
            <?php
            $sql_news = "select * FROM `news` WHERE `defunct`!='Y' AND `title`!='faqs.cn' ORDER BY `time` DESC LIMIT 5";
            $result_news = mysql_query_cache( $sql_news );
            $latest_news_id = (!empty($result_news) && isset($result_news[0]['news_id'])) ? intval($result_news[0]['news_id']) : 0;
            ?>
            <h4 class="ui top attached block header" style='margin-top: 10px;'><i class="ui info icon"></i><a href="<?php echo $latest_news_id ? 'viewnews.php?id='.$latest_news_id : '#'; ?>"><?php echo $MSG_NEWS;?></a></h4>
            <div class="ui bottom attached segment">
                <table class="ui very basic table">
                    <thead>
                        <tr>
                            <th><?php echo $MSG_TITLE;?></th>
                            <th><?php echo $MSG_TIME;?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ( $result_news ) {
                            foreach ( $result_news as $row ) {
                                echo "<tr>"."<td>"
                                    ."<a href=\"viewnews.php?id=".$row["news_id"]."\">"
                                    .$row["title"]."</a></td>"
                                    ."<td>".$row["time"]."</td>"."</tr>";
                            }
                        }else{
                            echo "check database connection or account ! ";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <?php /* 이미지 슬라이드 영역.
            image/slide1.jpg, slide2.jpg ... 파일이 있으면 캐러셀을 보여주던 코드지만 현재는 비활성화되어 있다.
            <?php if(file_exists("image/slide1.jpg")){ ?>
            <h4 class="ui top attached block header" style='margin-top: 15px;'><i class="th icon"></i><?php echo $OJ_NAME ?></h4>
            <div class="ui bottom attached center aligned segment carousel">
                <div class="carousel-arrow left" onclick="prevSlide()">&lt;</div> <?php for($i=1;file_exists("image/slide$i.jpg");$i++){ ?>
                <div class="carousel-slide <?php if($i==1) echo "active";?>" style="background-image: url('image/slide<?php echo $i ?>.jpg')"></div>
                <?php } ?>
                
                <div class="carousel-arrow right" onclick="nextSlide()">&gt;</div> <div class="carousel-dots">
                    <span class="carousel-dot active" data-index="0"></span>
                    <span class="carousel-dot" data-index="1"></span>
                    <span class="carousel-dot" data-index="2"></span>
                </div>
            </div>
            <?php } ?>
            */ ?>

<?php
// 페이지네이션 영역. 현재 메인 공지 목록이 여러 페이지로 나뉠 때만 표시된다.
if(isset($pages) && $pages>1 ){
  echo "<div style='display:inline;'>";
  echo "<nav class='center'>";
  echo "<ul class='pagination pagination-sm'>";
  echo "<li class='page-item'><a href='index.php?page=".(strval(1))."'>&lt;&lt;</a></li>";
  echo "<li class='page-item'><a href='index.php?page=".($page==1?strval(1):strval($page-1))."'>&lt;</a></li>";
  for($i=$spage; $i<=$epage; $i++){
    echo "<li class='".($page==$i?"active ":"")."page-item'><a title='go to page' href='index.php?page=".$i."'>".$i."</a></li>";
  }
  echo "<li class='page-item'><a href='index.php?page=".($page==pages?strval($page):strval($page+1))."'>&gt;</a></li>";
  echo "<li class='page-item'><a href='index.php?page=".(strval(pages))."'>&gt;&gt;</a></li>";
  echo "</ul>";
  echo "</nav>";
  echo "</div>";
}
?>

<?php
/* 이달의 스타 유저.
 * 이번 달에 처음 정답 처리된 문제 수를 기준으로 TOP 10을 계산한다.
 * 진행 중인 NOIP/시험성 대회 문제는 $not_in_noip_contests 조건으로 제외된다.
 */
$month_id=mysql_query_cache("select solution_id from solution where  in_date<date_add(curdate(),interval -day(curdate())+1 DAY) order by solution_id desc limit 1;");
if(!empty( $month_id) && isset($month_id[0][0]) ) $month_id=$month_id[0][0];else $month_id=0;
$view_month_rank=mysql_query_cache("select user_id,nick,count(distinct(problem_id)) ac from solution where solution_id>$month_id and problem_id>0  $not_in_noip_contests and user_id not in (".$OJ_RANK_HIDDEN.")  and result=4 and first_time=1 group by user_id,nick order by ac desc limit 10");
            if ( !empty($view_month_rank) ) {
        ?>
            <h4 class="ui top attached block header"><i class="ui star icon"></i><?php echo "랭킹"?></h4>
            <div class="ui bottom attached segment">
                <table class="ui very basic center aligned table" style="table-layout: fixed; ">
                    <tbody>
        <?php
                            foreach ( $view_month_rank as $row ) {
                                    echo "<tr>".
                                            "<td><a target='_blank' href='userinfo.php?user=".htmlentities($row[0],ENT_QUOTES,"UTF-8")."'>".htmlentities($row[0],ENT_QUOTES,"UTF-8")."</a></td>".
                                            "<td>".($row[1])."</td>".
                                            "<td>".($row[2])."</td>".
                                            "</tr>";
                            }
        ?>
                    </tbody>
                </table>
            </div>
        <?php
            }
/* 이달의 스타 유저 영역 끝 */
?>

            <?php // $OJ_INDEX_NEWS_TITLE과 같은 제목의 공지를 메인 안내 콘텐츠로 표시한다. ?>
            <h4 class="ui top attached block header"><i class="ui star icon"></i><?php echo $OJ_INDEX_NEWS_TITLE;?></h4>
            <div class="ui bottom attached segment">
                <table class="ui very basic left aligned table" style="table-layout: fixed; ">
                    <tbody>

                        <?php
                        $sql_news = "select * FROM `news` WHERE `defunct`!='Y' AND `title`='$OJ_INDEX_NEWS_TITLE' ORDER BY `importance` ASC,`time` DESC LIMIT 10";
                        $result_news = mysql_query_cache( $sql_news );
                        if ( $result_news ) {
                            foreach ( $result_news as $row ) {
                                echo "<tr>"."<td>"
                                    .bbcode_to_html($row["content"])."</td></tr>";
                            }
                        }
                        ?>
                         <tr><td>
                                 <center> Recent submission :
                                         <?php echo $speed?> .
                                         <div id=submission style="width:80%;height:300px"></div>
                                 </center>

                        </td></tr>

                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="right floated five wide column">
            
            <?php // 문제 번호를 바로 입력해 problem.php?id=... 로 이동하는 빠른 검색 폼. ?>
            <h4 class="ui top attached block header" style="margin-top: 10px;"><i class="ui search icon"></i><?php echo $MSG_SEARCH;?></h4>
            <div class="ui bottom attached segment">
                <form action="problem.php" method="get">
                    <div class="ui search" style="width: 100%; ">
                        <div class="ui left icon input" style="width: 100%; ">
                            <input class="prompt" style="width: 100%; " type="text" placeholder="<?php echo $MSG_PROBLEM_ID ;?> …" name="id">
                            <i class="search icon"></i>
                        </div>
                        <div class="results" style="width: 100%; "></div>
                    </div>
                </form>
            </div>

            <h4 class="ui top attached block header"><i class="ui rss icon"></i> <?php echo $MSG_RECENT_PROBLEM;?> </h4>
            <div class="ui bottom attached segment">
                <table class="ui very basic center aligned table">
                    <thead>
                        <tr>
                            <th width="60%"><?php echo $MSG_TITLE;?></th>
                            <th width="40%"><?php echo $MSG_TIME;?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        // 현재 진행 중인 특수 올림피아드(NOIP) 모드 대회의 문항들은 유출 방지를 위해 추천 목록에서 제외한다.
        $noip_problems=array_merge(...mysql_query_cache("select problem_id from contest c left join contest_problem cp on start_time<'$now' and end_time>'$now' and  (c.title like ? or (c.contest_type & 20) >0) and c.contest_id=cp.contest_id","%$OJ_NOIP_KEYWORD%"));
        $noip_problems=array_unique($noip_problems);
                         if(isset($_SESSION[$OJ_NAME."_user_id"])) $user_id=$_SESSION[$OJ_NAME."_user_id"]; else $user_id='guest';
                        $sql_problems = "select p.problem_id,title,max_in_date from (select problem_id,min(result) best,max(in_date) max_in_date from solution
                                where user_id=? and result>=4 and problem_id>0 group by problem_id ) s inner join problem p on s.problem_id=p.problem_id
                             where s.best>4 order by max_in_date desc  LIMIT 5";
                        $result_problems = mysql_query_cache( $sql_problems ,$user_id);
                        if ( !empty($result_problems) ) {
                            $i = 1;
                foreach ( $result_problems as $row ) {
                if(in_array(strval($row['problem_id']),$noip_problems)) continue;
                                echo "<tr>"."<td>"
                                    ."<a href=\"problem.php?id=".$row["problem_id"]."\">"
                                    .$row["title"]."</a></td>"
                                    ."<td>".substr($row["max_in_date"],5,5)."</td>"."</tr>";
                            }
                        }

                    ?>
                    </tbody>
                </table>
            </div>
            
            <h4 class="ui top attached block header"><i class="ui calendar icon"></i><?php echo $MSG_RECENT_CONTEST ;?></h4>
            <div class="ui bottom attached center aligned segment">
                <table class="ui very basic center aligned table">
                    <thead>
                        <tr>
                            <th><?php echo $MSG_CONTEST_NAME;?></th>
                            <th><?php echo $MSG_START_TIME;?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        // 최근 생성된 공개 대회 5개를 보여준다.
                        $sql_contests = "select * FROM `contest` where defunct='N' ORDER BY `contest_id` DESC LIMIT 5";
                        $result_contests = mysql_query_cache( $sql_contests );
                        if ( $result_contests ) {
                            $i = 1;
                            foreach ( $result_contests as $row ) {
                                echo "<tr>"."<td>"
                                    ."<a href=\"contest.php?cid=".$row["contest_id"]."\">"
                                    .$row["title"]."</a></td>"
                                    ."<td>".substr($row["start_time"],5,5)."</td>"."</tr>";
                            }
                        }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include("template/$OJ_TEMPLATE/footer.php");?>

<?php /*
<?php if(file_exists("image/slide1.jpg")){ ?>
    <script>
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        let currentIndex = 0;
        let autoPlayInterval;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                if (i === index) {
                    slide.classList.add('active');
                } else {
                    slide.classList.remove('active');
                }
            });
            dots.forEach((dot, i) => {
                if (i === index) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        function nextSlide() {
            currentIndex = (currentIndex + 1) % slides.length;
            showSlide(currentIndex);
        }

        function prevSlide() {
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            showSlide(currentIndex);
        }

        autoPlayInterval = setInterval(nextSlide, 5000); 

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const targetIndex = parseInt(dot.dataset.index);
                if (targetIndex!== currentIndex) {
                    currentIndex = targetIndex;
                    showSlide(currentIndex);
                    clearInterval(autoPlayInterval);
                    autoPlayInterval = setInterval(nextSlide, 5000);
                }
            });
        });

        const carousel = document.querySelector('.carousel');
        carousel.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
        carousel.addEventListener('mouseleave', () => autoPlayInterval = setInterval(nextSlide, 5000));
    </script>
<?php } ?>
*/ ?>

<script src="<?php echo $OJ_CDN_URL.$path_fix."template/$OJ_TEMPLATE"?>/js/echarts.min.js"></script>
        <script type="text/javascript">
            $( function () {
            var all=<?php echo json_encode(array_column($chart_data_all,1))?> ;
            var sub_echarts= echarts.init( $( "#submission" )[0]);
            var maxY=Math.max(all);
            var option = {
            tooltip: {
                trigger: 'axis',
                formatter: '{b0}({a0}): {c0}<br />{b1}({a1}): {c1}'
            },
            legend: {
            data: ['<?php echo $MSG_SUBMIT?>','<?php echo $MSG_AC?>' ]
            },
            xAxis: {
            data: <?php echo json_encode(array_column($chart_data_ac,0))?> 
            ,
            inverse:true
            },
            yAxis: [
                {
                    type: 'value',
                    name: '<?php echo $MSG_SUBMIT?>'
                }
            ],
            series: [
            {
                name: '<?php echo $MSG_SUBMIT?>',
                type: 'bar',
                data: all
                }
            ,
            {
                name: '<?php echo $MSG_AC?>',
                type: 'bar',
                data: <?php echo json_encode(array_column($chart_data_ac,1))?> 
            }]
            };
            sub_echarts.setOption(option);
                } );
        </script>
