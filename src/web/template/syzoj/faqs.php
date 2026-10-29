<?php $show_title="$MSG_FAQ - $OJ_NAME"; ?>
<?php include("template/$OJ_TEMPLATE/header.php");?>
<div class="gs-page-head">
    <h1>도움말</h1>
    <p>채점 환경과 자주 묻는 질문을 정리했어요</p>
</div>
<div class="padding">
    <div class="font-content">
        <h2 class="ui header">채점 안내</h2>
        <p>
            <br> C는 <code>gcc 11.4.0</code>을 사용하며, 컴파일 명령은 다음과 같습니다.
            &nbsp;<code>gcc Main.c -o Main -fno-asm -Wall -lm --static -O2 -std=c99 -DONLINE_JUDGE</code>
            <br> C++은 <code>g++ 11.4.0</code>을 사용하며, 컴파일 명령은 다음과 같습니다.
            &nbsp;<code>g++ -fno-asm -Wall -lm --static -O2 -std=c++17 -DONLINE_JUDGE -o Main Main.cc</code>;
            <br> 코드에 <code>#pragma GCC optimize ("O0")</code>를 추가하여 O2 최적화를 수동으로 끌 수 있습니다.
            <br> Pascal은 <code>fpc 3.2.2</code>를 사용하며, 컴파일 명령은 다음과 같습니다.
            &nbsp;<code>fpc Main.pas -oMain -O1 -Co -Cr -Ct -Ci</code>.
            <br> Java는 <code>OpenJDK 17.0.4</code>를 사용하며, 컴파일 명령은 다음과 같습니다.
            <code>   javac -J-Xms64m -J-Xmx128m Main.java</code>. 코드에 <code>public class</code>가 없다면 메인 클래스의 이름을 <code>Main</code>으로 지정해 주세요. Java는 채점 시 추가로 2초의 실행 시간과 64MB의 실행 메모리를 제공합니다.
            <br>
            여기 안내된 컴파일러 버전은 참고용이며, 실제 채점 서버의 컴파일러 버전을 기준으로 합니다.
        </p>
        <p>프로그램 작성 시 <strong>표준 입출력(Standard Input/Output)</strong>을 사용해 주세요.</p>
        
<h2 class="ui header">Q: cin/cout을 쓰면 왜 시간 초과(TLE)가 나나요?</h2>
<p>A: cin/cout은 기본적으로 stdin/stdout과 동기화되어 있어 속도가 느려지고, 더 많은 시스템 호출이 발생하여 성능에 영향을 받습니다. main 함수의 시작 부분에 아래 코드를 추가하면 입출력 속도를 높일 수 있습니다:
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">ios::sync_with_stdio(false);
cin.tie(0);</pre>
        </div>

        * 또한, endl 대신 '\n'을 사용해 주세요. endl은 출력할 때마다 버퍼를 비우는(flush) 동작을 강제하므로 출력 버퍼의 효율을 떨어뜨리고 속도를 느리게 만듭니다.
    </p>

<h2 class="ui header">Q: gets 함수는 더 이상 사용할 수 없나요?</h2>
<p>A: gets 함수는 입력받을 길이를 제한할 수 없어, 역사적으로 수많은 버퍼 오버플로우 보안 취약점의 원인이 되었습니다. 이 때문에 최신 표준에서는 완전히 삭제되었으므로, 대신 fgets 함수를 사용해 주세요. 또는 아래와 같이 매크로를 정의하여 대체할 수도 있습니다:
    <div class="ques-view">   #define gets(S) fgets(S,sizeof(S),stdin)  </div>
</p>
<h2 class="ui header">Q: 제 컴퓨터(로컬)에서는 잘 작동하는데, 제출하면 왜 틀렸다고 나오나요?</h2>
<p>A: 입력 버퍼를 비우기 위해 rewind 함수를 사용하지 마세요. 온라인 저지(OJ)의 입력은 본질적으로 파일(File) 스트림이므로, 키보드로 직접 입력하는 것과 논리 구조가 다릅니다. 다른 사람들은 정답을 맞혔는데 본인만 틀린다면 코드에서 예외 케이스(모든 경우의 수)를 고려하지 못했을 가능성이 큽니다.
    예제 데이터(샘플)를 통과했다고 해서 반드시 올바른 정답인 것은 아닙니다. 만약 모든 사용자가 해당 문제를 맞히지 못하고 있다면 테스트 데이터 자체에 오류가 있을 수 있으니, 관리자(<?php echo $OJ_ADMIN ?>)에게 문의해 주시기 바랍니다.
</p>
        
<h2 class="ui header">개인 정보<br></h2>
        <p>본 사이트는 프로필 이미지를 자동으로 불러오지 않습니다. 수동으로 입력해주세요.</p>
        <h2 class="ui header">채점 결과 안내<br></h2>
        <div class="ques-view">
            <p>제출하신 코드는 채점 시스템에 의해 즉시 채점되며, 매 제출마다 결과가 바로 안내됩니다. 시스템의 주요 반환 메시지는 다음과 같습니다:</p>
            <li>Wait: 채점 시스템이 아직 이 제출을 처리하지 못했습니다. 잠시만 기다려주세요.</li>
            <li>Judging: 채점 시스템이 코드를 채점하는 중입니다. 잠시 후 결과가 나옵니다.</li>
            <li>Compile Error: 제출한 코드가 컴파일에 실패했습니다. "Compile Error" 링크를 클릭하면 컴파일러가 출력한 에러 메시지를 볼 수 있습니다.</li>
            <li>Accepted: 축하합니다! 정답입니다.</li>
            <li>Presentation Error: 프로그램이 출력한 정답은 맞으나, 출력 형식이 맞지 않습니다 (예: 공백이나 줄바꿈이 요구사항과 다름).</li>
            <li>Wrong Answer: 프로그램이 채점 시스템의 테스트 데이터에 대해 잘못된 결과를 반환했습니다.</li>
            <li>Time Limit Exceeded: 프로그램이 제한된 시간 내에 실행을 끝내지 못했습니다.</li>
            <li>Memory Limit Exceeded: 프로그램이 제한된 메모리양을 초과하여 사용했습니다.</li>
            <li>Runtime Error: 프로그램 실행 중에 세그멘테이션 폴트(Segmentation Fault), 런타임 에러, 부동 소수점 예외 등으로 인해 프로그램이 크래시(종료)되었습니다.</li>
            <li>Output Limit Exceeded: 프로그램이 너무 많은 내용을 출력했습니다. 주로 무한 루프에 빠져 출력을 반복할 때 발생합니다.</li>
        </div>


        <h2>언어별 코드 샘플</h2>
        <p>아래 샘플 프로그램들은 <strong>"두 정수 A와 B를 입력받아 그 합을 출력하는 문제(A+B)"</strong>를 해결하는 예시입니다.</p>
        <p><strong>gcc (.c)</strong></p>
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">
<code class="lang-c">#include &lt;stdio.h&gt;
int main(){
    int a, b;
    while(scanf("%d %d",&amp;a, &amp;b) != EOF){
        printf("%d\n", a + b);
    }
    return 0;
}</code></pre>
        </div>
        <p><strong>g++ (.cpp)</strong></p>
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">
<code class="lang-c++">#include &lt;iostream&gt;
using namespace std;
int main(){
    // io speed up
    const char endl = '\n';
    std::ios::sync_with_stdio(false);
    cin.tie(nullptr);

    int a, b;
    while (cin &gt;&gt; a &gt;&gt; b){
        cout &lt;&lt; a+b &lt;&lt; endl;
    }
    return 0;
}</code></pre>
        </div>
        <p><strong>fpc (.pas)</strong></p>
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">
<code class="lang-pascal">var
a, b: integer;
begin
    while not eof(input) do begin
        readln(a, b);
        writeln(a + b);
    end;
end.</code></pre>
        </div>
        <p><strong>javac (.java)</strong></p>
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">
<code class="lang-java">import java.util.Scanner;   
public class Main {
    public static void main(String[] args) {
        Scanner in = new Scanner(System.in);
        while (in.hasNextInt()) {
            int a = in.nextInt();
            int b = in.nextInt();
            System.out.println(a + b);
        }
    }
}</code></pre>
        </div>
        <p><strong>python3 (.py)</strong></p>
        <div class="ui existing segment">
            <pre style="margin-top: 0; margin-bottom: 0; ">
<code class="lang-c">import io
import sys
sys.stdout = io.TextIOWrapper(sys.stdout.buffer,encoding='utf8')
for line in sys.stdin:
    a = line.split()
    print(int(a[0]) + int(a[1]))</code></pre>
        </div>
    </div>
</div>

<?php include("template/$OJ_TEMPLATE/footer.php");?>