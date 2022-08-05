#/bin/bash
cxxtestgen --error-printer --have-eh -o runner01.cpp solution.h 2>&1 && g++ -o runner01 runner01.cpp 2>&1 && echo '====' && output=`timeout 8s ./runner01 2>&1`
echo -ne "$output"
res=`echo -ne "$output" | grep "Success\|.OK!"`
if [[ "$res" == *".OK!"* ]] ; then
        nota="100%"
elif [[ "$res" == *"Success rate"* ]] ; then
        nota=`echo -ne "$output" | grep "Success" | cut -d ' ' -f3`
else
        nota="0%"
fi
echo -ne "\n\n$nota"