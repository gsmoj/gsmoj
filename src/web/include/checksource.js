function checkIsChinese(str){  
	 //如果值为空，通过校验  
	 if   (str   ==   "")  
	         return   false;  
	 var   pattern   =   /([\u4E00-\u9FA5]|[\uFE30-\uFFA0])+/gi;  
	 if   (pattern.test(str))  
	         return   true;  
	 else  
	         return   false;  
}
function checksource(src){
        if (document.getElementById("language").value>"3")
            return true;
		var keys=new Array();
		var errs=new Array();
		var msg="";
		keys[0]="void main";
		errs[0]="main 함수의 반환형을 void로 쓰면 컴파일 오류가 납니다. int main()을 쓰고 마지막에 return 0;을 넣으세요.\nVC 같은 Windows 컴파일러는 허용하지만 C/C++ 표준은 void main()을 허용하지 않습니다!!!";
   		if (document.getElementById("language").value=="3"){
		     	keys[0]="int main";
	        	errs[0]="Java는 public static void main 함수가 있어야 합니다";
      		}
		keys[1]="Please";
		errs[1]="문제에서 요구하지 않으면 ‘Please input’ 같은 안내 문구를 출력하지 마세요";		
		keys[2]="请";
		errs[2]="문제에서 요구하지 않으면 ‘입력하세요’ 같은 안내 문구를 출력하지 마세요";		
		keys[3]="输入";
		errs[3]="문제에서 요구하지 않으면 ‘입력하세요’ 같은 안내 문구를 출력하지 마세요";		
		keys[3]="input";
		errs[3]="문제에서 요구하지 않으면 ‘Please input’ 같은 안내 문구를 출력하지 마세요";		
		keys[4]="max=%d";
		errs[4]="문제에서 요구하지 않으면 ‘max=’ 같은 안내 문구를 출력하지 마세요";		
		keys[5]="mian";
		errs[5]="main을 mian으로 잘못 쓰지 않았나요?";	
		for(var i=0;i<keys.length;i++){
			if(src.indexOf(keys[i])!=-1){
				msg+=errs[i]+"\n";
			}
		}
		if(checkIsChinese(src))
			msg+="코드에 중국어나 전각 문자가 있습니다! 이 시스템의 문제는 대개 안내 문구 출력을 요구하지 않습니다.\n먼저 예제 입력으로 실행해 예제 출력과 비교하세요. 안내 문구, 쉼표, 등호, 공백 등 불필요한 출력이 있으면 오답 처리됩니다!\n코드에 전각 괄호, 세미콜론, 따옴표, 공백이 있으면 컴파일 오류가 납니다.";
		if(msg.length>0)
			return confirm(msg+"\n 코드에 오류가 있을 수 있습니다. 그래도 제출할까요?\n먼저 문제의 예제 입력으로 실행해 출력이 예제 출력과 완전히 같은지 확인하세요.\n공백이나 문장 부호가 하나만 달라도 오답 처리됩니다.\n컴파일 오류가 나면 컴파일 오류 문구를 눌러 자세한 내용을 확인하고 고치세요.");					
		else
			return true;
}
