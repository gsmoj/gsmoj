function reinfo(){
	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, "&amp;")
			.replace(/</g, "&lt;")
			.replace(/>/g, "&gt;")
			.replace(/"/g, "&quot;")
			.replace(/'/g, "&#39;");
	}
	var pats=new Array();
	var exps=new Array();
	pats[0]=/A Not allowed system call.* /;
	exps[0]="시스템에서 금지한 시스템 호출을 사용했습니다. 파일이나 프로세스 같은 자원에 권한 없이 접근했는지 확인하세요.<br>관리자이고 답이 맞다고 확인했거나 새 언어 지원을 추가하는 중이라면 <a href='https://zhuanlan.zhihu.com/p/24498599'>여기를 누르세요.</a>";
	pats[1]=/Segmentation fault/;
	exps[1]="세그멘테이션 오류입니다. 배열 범위 초과, 잘못된 포인터, 접근하면 안 되는 메모리 접근이 있는지 확인하세요";
	pats[2]=/Floating point exception/;
	exps[2]="부동소수점 오류입니다. 0으로 나누는 경우가 있는지 확인하세요";
	pats[3]=/buffer overflow detected/;
	exps[3]="버퍼 오버플로입니다. 문자열 길이가 배열 크기를 넘는 경우가 있는지 확인하세요";
	pats[4]=/Killed/;
	exps[4]="메모리나 시간 문제로 프로세스가 종료되었습니다. 무한 루프가 있는지 확인하세요";
	pats[5]=/Alarm clock/;
	exps[5]="시간 문제로 프로세스가 종료되었습니다. 무한 루프가 있는지 확인하세요. 시간 초과(TLE)와 같은 오류입니다";
	pats[6]=/CALLID:20/;
	exps[6]="배열 범위를 넘었을 수 있습니다. 문제의 데이터 크기와 선언한 배열 크기를 비교해 보세요";
	pats[7]=/NoSuchElementException/;
	exps[7]="입력 형식을 잘못 이해했을 수 있습니다. 입력 데이터의 형식과 개수가 예상과 다릅니다";
	pats[8]=/ArrayIndexOutOfBoundsException/;
        exps[8]="배열 인덱스가 범위를 넘었습니다. 반복 변수의 범위가 맞는지 확인하세요. 특수한 값은 따로 처리해야 할 수 있습니다";
	pats[9]=/NoClassDefFoundError: Main/;
        exps[9]="Java로 제출할 때는 public class 이름이 Main이어야 합니다";

	//alert("asdf");
	var errmsg=$("#errtxt").text();
	var expmsg="도움말:<br><hr>";
	for(var i=0;i<pats.length;i++){
	var pat=pats[i];
	var exp=exps[i];
	var ret=pat.exec(errmsg);
	if(ret){
	expmsg+=escapeHtml(ret)+":"+exp+"<br><hr />";
	}
	}
	document.getElementById("errexp").innerHTML=expmsg;
	//alert(expmsg);


}
