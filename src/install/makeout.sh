#!/bin/bash
if [ "$1" = "" ]; then
   echo "Usage: $0 standard-execute-binary"
   echo "Example: after compiled your standard program by 'gcc -o main main.cc' "
   echo "         TYPE ---> '$0 main' "
   echo "         will generate .out files for each .in files in "`pwd`
   EXEC="./Main"
else
   EXEC="./$1"
fi
if [ "$EUID" -eq 0 ]; then
    echo "Error: This script should not be run as root." >&2
    exit 1
fi
# 채점 컨테이너의 www-data는 상위 폴더(/home/judge)를 지나갈 수 없어 `python3 파일` 실행이 권한 오류로 막힌다.
# python3는 스크립트를 절대 경로로 바꿔 열기 때문이므로, 현재 폴더 기준으로 읽어서 실행한다.
run_py() {
    python3 -c "exec(compile(open('$1').read(), '$1', 'exec'))"
}

if [ -f "Gen.py" ]; then
    echo "Found Gen.py, running it..."
    run_py Gen.py
    echo "Gen.py finished."
fi

# 정답 코드: 내용이 있는 Main.c > Main.cc > Main.py 순서로 하나를 고른다.
# 컴파일에 실패했을 때 이전에 남은 실행 파일로 출력을 만들지 않도록 먼저 지운다.
rm -f "$EXEC"
RUN=""
if [ -s "Main.c" ]; then
    gcc -Wall -static -DONLINE_JUDGE -lm -o  "$EXEC"  Main.c && RUN="$EXEC"
    echo "Compiled Main.c to Main using gcc with -Wall -static -DONLINE_JUDGE -lm"
elif [ -s "Main.cc" ]; then
    g++ -Wall -static -DONLINE_JUDGE -lm -o  "$EXEC"  Main.cc && RUN="$EXEC"
    echo "Compiled Main.cc to Main using g++ with -Wall -static -DONLINE_JUDGE -lm"
elif [ -s "Main.py" ]; then
    RUN="python"
    echo "Using Main.py with python3"
else
    echo "Error: Neither Main.c, Main.cc nor Main.py found" >&2
fi

if [ -f "interactor.cc" ]; then
    g++ --static -o interactor interactor.cc ||  g++ -o interactor interactor.cc
fi
if [ -f "spj.cc" ]; then
    g++ --static -o spj spj.cc ||  g++ -o spj spj.cc
fi
if [ -f "tpj.cc" ]; then
    g++ --static -o tpj tpj.cc ||  g++ -o tpj tpj.cc
fi
if [ -f "upj.cc" ]; then
    g++ --static -o upj upj.cc ||  g++ -o upj upj.cc
fi

for INFILE in `ls *.in`
do
        OUTFILE=`basename -s .in $INFILE`.out
    if [ "$RUN" = "python" ]; then
        if run_py Main.py < $INFILE > $OUTFILE ; then
                echo "make out for $INFILE -> $OUTFILE <br>"
        else
                echo "make out for $INFILE .....failed"
        fi
    elif [ -n "$RUN" ]; then
        if $RUN < $INFILE > $OUTFILE ; then
                echo "make out for $INFILE -> $OUTFILE <br>"
        else
                echo "make out for $INFILE .....failed"
        fi
    fi
done


